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
