<?php

namespace App\Http\Controllers;

use App\Services\AiContentService;
use App\Services\WebArticleService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AiContentController extends Controller
{
    public function fillArticle(Request $request, AiContentService $ai, WebArticleService $web): JsonResponse
    {
        $validated = $request->validate([
            'mode' => 'required|in:full,translate',
            'url' => 'nullable|url|max:2048',
            'fields' => 'nullable|array',
        ]);

        if ($validated['mode'] === 'full' && trim((string) ($validated['url'] ?? '')) === '') {
            return response()->json(['ok' => false, 'error' => 'Maqola havolasini kiriting.'], 422);
        }

        // Full article generation (fetch + AI in three languages) can take a
        // while; give the request room so it isn't killed mid-generation.
        @set_time_limit(210);

        try {
            $sourceText = null;
            $ogImage = null;

            if ($validated['mode'] === 'full') {
                $source = $web->fetch($validated['url']);
                $sourceText = trim(($source['title'] ? $source['title'] . "\n\n" : '') . $source['text']);
                $ogImage = $source['og_image'];
                if ($sourceText === '') {
                    return response()->json(['ok' => false, 'error' => 'Havoladan matn topilmadi.'], 422);
                }
            }

            $data = $ai->generateArticle($validated['mode'], $sourceText, $validated['fields'] ?? []);

            $image = null;
            if ($validated['mode'] === 'full') {
                $path = $web->resolveImage($data['image_query'] ?? null, $ogImage);
                if ($path) {
                    $image = ['path' => $path, 'url' => asset($path)];
                }
            }

            return response()->json(['ok' => true, 'data' => $data, 'image' => $image]);
        } catch (\Throwable $e) {
            report($e);
            return response()->json(['ok' => false, 'error' => $e->getMessage()], 422);
        }
    }

    /** STEP 1 — read a link and professionally edit it in its own language. */
    public function importArticle(Request $request, AiContentService $ai, WebArticleService $web): JsonResponse
    {
        $validated = $request->validate(['url' => 'required|url|max:2048']);
        @set_time_limit(210);
        try {
            $source = $web->fetch($validated['url']);
            $sourceText = trim(($source['title'] ? $source['title'] . "\n\n" : '') . $source['text']);
            if ($sourceText === '') {
                return response()->json(['ok' => false, 'error' => 'Havoladan matn topilmadi. Boshqa havolani sinab ko‘ring.'], 422);
            }
            $data = $ai->importArticle($sourceText);
            if ($data['title'] === '' && $data['body'] === '') {
                return response()->json(['ok' => false, 'error' => 'AI maqolani o‘qiy olmadi. Qayta urinib ko‘ring.'], 422);
            }
            return response()->json(['ok' => true, 'lang' => $data['lang'], 'title' => $data['title'], 'body' => $data['body']]);
        } catch (\Throwable $e) {
            report($e);
            return response()->json(['ok' => false, 'error' => $this->friendly($e)], 422);
        }
    }

    /** STEP 2 — translate one filled language into the other two. */
    public function translateArticle(Request $request, AiContentService $ai): JsonResponse
    {
        $validated = $request->validate([
            'source_lang' => 'required|in:uz,ru,en',
            'title' => 'required|string|max:500',
            'body' => 'nullable|string',
        ]);
        @set_time_limit(210);
        try {
            $translations = $ai->translateArticle(
                $validated['source_lang'],
                $validated['title'],
                (string) ($validated['body'] ?? '')
            );
            return response()->json(['ok' => true, 'translations' => $translations]);
        } catch (\Throwable $e) {
            report($e);
            return response()->json(['ok' => false, 'error' => $this->friendly($e)], 422);
        }
    }

    /** STEP 3 — generate a full SEO package for every filled locale. */
    public function generateSeo(Request $request, AiContentService $ai): JsonResponse
    {
        $validated = $request->validate([
            'fields' => 'required|array',
            'fields.*.title' => 'nullable|string|max:500',
            'fields.*.body' => 'nullable|string',
        ]);
        // Keep only locales that actually have a title or body.
        $filled = [];
        foreach ($validated['fields'] as $loc => $row) {
            if (!in_array($loc, ['uz', 'ru', 'en'], true)) {
                continue;
            }
            $title = trim((string) ($row['title'] ?? ''));
            $body = trim((string) ($row['body'] ?? ''));
            if ($title !== '' || $body !== '') {
                $filled[$loc] = ['title' => $title, 'body' => $body];
            }
        }
        if (!$filled) {
            return response()->json(['ok' => false, 'error' => 'Avval kamida bitta tilda maqola matnini to‘ldiring.'], 422);
        }
        @set_time_limit(150);
        try {
            $seo = $ai->generateArticleSeo($filled);
            return response()->json(['ok' => true, 'seo' => $seo]);
        } catch (\Throwable $e) {
            report($e);
            return response()->json(['ok' => false, 'error' => $this->friendly($e)], 422);
        }
    }

    /** STEP 4 — pick a topic image (free Pexels); `variant` gives a fresh one. */
    public function articleImage(Request $request, AiContentService $ai, WebArticleService $web): JsonResponse
    {
        $validated = $request->validate([
            'title' => 'nullable|string|max:500',
            'body' => 'nullable|string',
            'query' => 'nullable|string|max:200',
            'variant' => 'nullable|integer|min:0|max:60',
        ]);
        @set_time_limit(120);
        try {
            $query = trim((string) ($validated['query'] ?? ''));
            if ($query === '') {
                $title = trim((string) ($validated['title'] ?? ''));
                $body = (string) ($validated['body'] ?? '');
                if ($title === '' && trim(strip_tags($body)) === '') {
                    return response()->json(['ok' => false, 'error' => 'Avval maqola sarlavhasi yoki matnini to‘ldiring.'], 422);
                }
                $query = $ai->imageQuery($title, $body);
                if ($query === '') {
                    // Fallback: build a query from the title's own words.
                    $words = preg_split('/\s+/', trim((string) \Illuminate\Support\Str::ascii($title))) ?: [];
                    $words = array_filter($words, fn ($w) => mb_strlen($w) > 2);
                    $query = trim(implode(' ', array_slice(array_values($words), 0, 5)));
                }
            }
            if ($query === '') {
                return response()->json(['ok' => false, 'error' => 'Mavzuni aniqlab bo‘lmadi. Sarlavhani to‘ldirib qayta urinib ko‘ring.'], 422);
            }
            $image = $web->imageForQuery($query, (int) ($validated['variant'] ?? 0));
            if (!$image) {
                return response()->json(['ok' => false, 'error' => 'Mos rasm topilmadi. “Boshqa rasm” tugmasini bosing yoki qo‘lda yuklang.'], 422);
            }
            return response()->json(['ok' => true, 'image' => $image, 'query' => $query]);
        } catch (\Throwable $e) {
            report($e);
            return response()->json(['ok' => false, 'error' => $this->friendly($e)], 422);
        }
    }

    /** Turn raw exceptions into an admin-friendly message. */
    private function friendly(\Throwable $e): string
    {
        $msg = $e->getMessage();
        if ($e instanceof \Illuminate\Http\Client\ConnectionException || stripos($msg, 'timeout') !== false || stripos($msg, 'cURL') !== false) {
            return 'AI xizmatiga ulanib bo‘lmadi yoki javob juda uzoq davom etdi. Bir oz kutib, qayta urinib ko‘ring.';
        }
        return $msg !== '' ? $msg : 'Kutilmagan xatolik yuz berdi. Qayta urinib ko‘ring.';
    }

    public function fillProduct(Request $request, AiContentService $ai): JsonResponse
    {
        $validated = $request->validate([
            'mode' => 'required|in:full,translate,seo',
            'brief' => 'nullable|string|max:8000',
            'fields' => 'nullable|array',
        ]);

        if ($validated['mode'] === 'full' && trim((string) ($validated['brief'] ?? '')) === '') {
            return response()->json(['ok' => false, 'error' => 'Avval umumiy ma’lumotni yozing.'], 422);
        }

        try {
            $data = $ai->generate(
                $validated['mode'],
                $validated['brief'] ?? null,
                $validated['fields'] ?? []
            );

            return response()->json(['ok' => true, 'data' => $data]);
        } catch (\Throwable $e) {
            report($e);
            return response()->json(['ok' => false, 'error' => $e->getMessage()], 422);
        }
    }
}
