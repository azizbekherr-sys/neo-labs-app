<?php

namespace App\Http\Controllers;

use App\Models\Article;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class ArticleController extends Controller
{
    public function create()
    {
        return view('articles.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'title_uz' => ['required', 'string', 'max:255'],
            'title_ru' => ['required', 'string', 'max:255'],
            'title_en' => ['required', 'string', 'max:255'],
            'description_uz' => ['nullable', 'string'],
            'description_ru' => ['nullable', 'string'],
            'description_en' => ['nullable', 'string'],
            'photo' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:6144'],
            'photo_path' => ['nullable', 'string', 'max:255'],
            'seo_title_uz' => ['nullable', 'string', 'max:255'], 'seo_title_ru' => ['nullable', 'string', 'max:255'], 'seo_title_en' => ['nullable', 'string', 'max:255'],
            'meta_description_uz' => ['nullable', 'string', 'max:500'], 'meta_description_ru' => ['nullable', 'string', 'max:500'], 'meta_description_en' => ['nullable', 'string', 'max:500'],
            'canonical_url' => ['nullable', 'url', 'max:2048'],
            'robots' => ['nullable', Rule::in(['index,follow', 'noindex,follow'])],
            'og_image' => ['nullable', 'string', 'max:2048'],
            'schema_description_uz' => ['nullable', 'string', 'max:1000'], 'schema_description_ru' => ['nullable', 'string', 'max:1000'], 'schema_description_en' => ['nullable', 'string', 'max:1000'],
            'author_name' => ['nullable', 'string', 'max:255'],
            'author_role_uz' => ['nullable', 'string', 'max:255'], 'author_role_ru' => ['nullable', 'string', 'max:255'], 'author_role_en' => ['nullable', 'string', 'max:255'],
            'reviewer_name' => ['nullable', 'string', 'max:255'],
            'reviewer_role_uz' => ['nullable', 'string', 'max:255'], 'reviewer_role_ru' => ['nullable', 'string', 'max:255'], 'reviewer_role_en' => ['nullable', 'string', 'max:255'],
            'reviewed_at' => ['nullable', 'date'],
            'references_uz_text' => ['nullable', 'string'], 'references_ru_text' => ['nullable', 'string'], 'references_en_text' => ['nullable', 'string'],
            'schema_type' => ['nullable', 'in:Article,BlogPosting,MedicalWebPage'],
            'faq_questions_uz' => ['nullable', 'array'], 'faq_answers_uz' => ['nullable', 'array'],
            'faq_questions_ru' => ['nullable', 'array'], 'faq_answers_ru' => ['nullable', 'array'],
            'faq_questions_en' => ['nullable', 'array'], 'faq_answers_en' => ['nullable', 'array'],
        ]);

        // Upload photo (Supabase Storage or local, per MEDIA_DISK)
        if ($request->hasFile('photo') && $request->file('photo')->isValid()) {
            $validated['photo'] = media_store($request->file('photo'), 'articles');
        }

        // AI-selected image reference from the AI-fill endpoint (a full URL, or a local articles/ path)
        if (empty($validated['photo']) && !empty($validated['photo_path'])) {
            $candidate = (string) $validated['photo_path'];
            if (Str::startsWith($candidate, ['http://', 'https://'])) {
                $validated['photo'] = $candidate;
            } elseif (str_starts_with(ltrim($candidate, '/'), 'articles/') && is_file(public_path(ltrim($candidate, '/')))) {
                $validated['photo'] = ltrim($candidate, '/');
            }
        }
        unset($validated['photo_path']);

        $validated['author_slug'] = !empty($validated['author_name']) ? Str::slug($validated['author_name']) : null;
        $validated['references_uz'] = $this->lines($request->input('references_uz_text'));
        $validated['references_ru'] = $this->lines($request->input('references_ru_text'));
        $validated['references_en'] = $this->lines($request->input('references_en_text'));
        $validated['faqs_uz'] = $this->buildFaqs($request->input('faq_questions_uz', []), $request->input('faq_answers_uz', []));
        $validated['faqs_ru'] = $this->buildFaqs($request->input('faq_questions_ru', []), $request->input('faq_answers_ru', []));
        $validated['faqs_en'] = $this->buildFaqs($request->input('faq_questions_en', []), $request->input('faq_answers_en', []));
        $validated['robots'] = $validated['robots'] ?? 'index,follow';
        $validated['schema_type'] = $validated['schema_type'] ?? 'BlogPosting';

        Article::create($validated);

        return redirect()->route('dashboard.articles.index')->with('success', 'Maqola muvaffaqiyatli qo‘shildi.');
    }

    public function edit(Article $article)
    {
        return view('articles.edit', compact('article'));
    }

    public function update(Request $request, Article $article)
    {
        $validated = $request->validate([
            'title_uz' => ['required', 'string', 'max:255'], 'title_ru' => ['required', 'string', 'max:255'], 'title_en' => ['required', 'string', 'max:255'],
            'description_uz' => ['nullable', 'string'], 'description_ru' => ['nullable', 'string'], 'description_en' => ['nullable', 'string'],
            'photo' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:6144'],
            'seo_title_uz' => ['nullable', 'string', 'max:255'], 'seo_title_ru' => ['nullable', 'string', 'max:255'], 'seo_title_en' => ['nullable', 'string', 'max:255'],
            'meta_description_uz' => ['nullable', 'string', 'max:500'], 'meta_description_ru' => ['nullable', 'string', 'max:500'], 'meta_description_en' => ['nullable', 'string', 'max:500'],
            'canonical_url' => ['nullable', 'url', 'max:2048'], 'robots' => ['nullable', Rule::in(['index,follow', 'noindex,follow'])],
            'og_image' => ['nullable', 'string', 'max:2048'],
            'schema_description_uz' => ['nullable', 'string', 'max:1000'], 'schema_description_ru' => ['nullable', 'string', 'max:1000'], 'schema_description_en' => ['nullable', 'string', 'max:1000'],
            'author_name' => ['nullable', 'string', 'max:255'], 'author_role_uz' => ['nullable', 'string', 'max:255'], 'author_role_ru' => ['nullable', 'string', 'max:255'], 'author_role_en' => ['nullable', 'string', 'max:255'],
            'reviewer_name' => ['nullable', 'string', 'max:255'], 'reviewer_role_uz' => ['nullable', 'string', 'max:255'], 'reviewer_role_ru' => ['nullable', 'string', 'max:255'], 'reviewer_role_en' => ['nullable', 'string', 'max:255'],
            'reviewed_at' => ['nullable', 'date'], 'references_uz_text' => ['nullable', 'string'], 'references_ru_text' => ['nullable', 'string'], 'references_en_text' => ['nullable', 'string'],
            'schema_type' => ['nullable', 'in:Article,BlogPosting,MedicalWebPage'],
            'faq_questions_uz' => ['nullable', 'array'], 'faq_answers_uz' => ['nullable', 'array'],
            'faq_questions_ru' => ['nullable', 'array'], 'faq_answers_ru' => ['nullable', 'array'],
            'faq_questions_en' => ['nullable', 'array'], 'faq_answers_en' => ['nullable', 'array'],
        ]);
        $oldPhoto = $article->photo;
        if ($request->hasFile('photo') && $request->file('photo')->isValid()) {
            $validated['photo'] = media_store($request->file('photo'), 'articles');
        }
        $validated['author_slug'] = !empty($validated['author_name']) ? Str::slug($validated['author_name']) : null;
        $validated['references_uz'] = $this->lines($request->input('references_uz_text'));
        $validated['references_ru'] = $this->lines($request->input('references_ru_text'));
        $validated['references_en'] = $this->lines($request->input('references_en_text'));
        $validated['faqs_uz'] = $this->buildFaqs($request->input('faq_questions_uz', []), $request->input('faq_answers_uz', []));
        $validated['faqs_ru'] = $this->buildFaqs($request->input('faq_questions_ru', []), $request->input('faq_answers_ru', []));
        $validated['faqs_en'] = $this->buildFaqs($request->input('faq_questions_en', []), $request->input('faq_answers_en', []));
        $validated['robots'] = $validated['robots'] ?? 'index,follow';
        $validated['schema_type'] = $validated['schema_type'] ?? 'BlogPosting';
        $article->update($validated);
        if (isset($validated['photo']) && $oldPhoto !== $validated['photo']) {
            $this->deletePhoto($oldPhoto);
        }
        return redirect()->route('dashboard.articles.index')->with('success', 'Maqola yangilandi.');
    }

    public function destroy(Article $article)
    {
        $this->deletePhoto($article->photo);
        $article->delete();
        return redirect()->route('dashboard.articles.index')->with('success', 'Maqola o‘chirildi.');
    }

    private function lines(?string $value): array
    {
        return array_values(array_filter(array_map('trim', preg_split('/\R/u', (string) $value) ?: [])));
    }

    private function buildFaqs(array $questions, array $answers): array
    {
        $faqs = [];
        foreach ($questions as $index => $question) {
            $question = trim((string) $question);
            $answer = trim((string) ($answers[$index] ?? ''));
            if ($question !== '' && $answer !== '') $faqs[] = ['question'=>$question, 'answer'=>$answer];
        }
        return $faqs;
    }

    private function deletePhoto(?string $ref): void
    {
        media_delete($ref, ['articles/']);
    }
}
