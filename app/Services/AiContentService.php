<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use RuntimeException;

/**
 * AI-powered product content assistant.
 *
 * Provider is selected by config('services.ai.provider'):
 *   - gemini    -> Google Gemini (free tier) via the Generative Language API
 *   - anthropic -> Claude / Anthropic Messages API
 *
 * Called via raw HTTP (Laravel's Guzzle-backed Http client) because Composer
 * is not available in this environment to install a vendor SDK.
 *
 * Modes:
 *   - full:      from a free-text UZ brief, produce ALL fields in UZ + RU + EN + SEO
 *   - translate: from the entered UZ fields, produce RU + EN (echoes UZ back)
 *   - seo:       from the entered content, produce SEO title/meta per locale
 */
class AiContentService
{
    /** Locales the admin form exposes. */
    private const LOCALES = ['uz', 'ru', 'en'];

    public function generate(string $mode, ?string $brief, array $fields): array
    {
        $system = $this->systemPrompt();
        $user = $this->userPrompt($mode, $brief, $fields) . "\n\n" . $this->outputKeysHint();

        $provider = (string) config('services.ai.provider', 'gemini');
        $data = $provider === 'anthropic'
            ? $this->callAnthropic($system, $user)
            : $this->callGemini($system, $user);

        return $this->normalize($data);
    }

    /**
     * Generate article content in UZ + RU + EN from a source article's text
     * (or from already-entered UZ fields when translating).
     * Returns title/description/seo per locale plus an English `image_query`.
     */
    public function generateArticle(string $mode, ?string $sourceText, array $fields): array
    {
        $system = $this->articleSystemPrompt();
        $user = $this->articleUserPrompt($mode, $sourceText, $fields) . "\n\n" . $this->articleKeysHint();

        $provider = (string) config('services.ai.provider', 'gemini');
        // Articles need a large output budget: a full, comprehensive body in
        // three languages. Give it room and time so nothing is truncated.
        $data = $provider === 'anthropic'
            ? $this->callAnthropic($system, $user, $this->articleSchema(), 40000, 170)
            : $this->callGemini($system, $user, 40000, 170);

        return $this->normalizeArticle($data);
    }

    // ---------------------------------------------------------------- Gemini

    private function callGemini(string $system, string $user, int $maxTokens = 8000, int $timeout = 90): array
    {
        $key = (string) config('services.gemini.key');
        if ($key === '') {
            throw new RuntimeException('GEMINI_API_KEY .env faylida sozlanmagan.');
        }
        $model = (string) config('services.gemini.model', 'gemini-2.5-flash');
        $base = rtrim((string) config('services.gemini.base_url', 'https://generativelanguage.googleapis.com'), '/');

        $response = Http::withHeaders(['x-goog-api-key' => $key, 'content-type' => 'application/json'])
            ->timeout($timeout)
            ->post("{$base}/v1beta/models/{$model}:generateContent", [
                'system_instruction' => ['parts' => [['text' => $system]]],
                'contents' => [['role' => 'user', 'parts' => [['text' => $user]]]],
                'generationConfig' => [
                    'responseMimeType' => 'application/json',
                    'temperature' => 0.4,
                    'maxOutputTokens' => $maxTokens,
                ],
            ]);

        if (!$response->successful()) {
            $err = $response->json('error.message') ?? ('HTTP ' . $response->status());
            throw new RuntimeException('AI xizmati xatosi: ' . $err);
        }

        $text = $response->json('candidates.0.content.parts.0.text');
        return $this->decodeJson($text);
    }

    // ------------------------------------------------------------- Anthropic

    private function callAnthropic(string $system, string $user, ?array $schema = null, int $maxTokens = 8000, int $timeout = 90): array
    {
        $key = (string) config('services.anthropic.key');
        if ($key === '') {
            throw new RuntimeException('ANTHROPIC_API_KEY .env faylida sozlanmagan.');
        }
        $model = (string) config('services.anthropic.model', 'claude-opus-4-8');
        $base = rtrim((string) config('services.anthropic.base_url', 'https://api.anthropic.com'), '/');

        $response = Http::withHeaders([
            'x-api-key' => $key,
            'anthropic-version' => '2023-06-01',
            'content-type' => 'application/json',
        ])->timeout($timeout)->post($base . '/v1/messages', [
            'model' => $model,
            'max_tokens' => $maxTokens,
            'system' => $system,
            'output_config' => ['format' => ['type' => 'json_schema', 'schema' => $schema ?? $this->schema()]],
            'messages' => [['role' => 'user', 'content' => $user]],
        ]);

        if (!$response->successful()) {
            $err = $response->json('error.message') ?? ('HTTP ' . $response->status());
            throw new RuntimeException('AI xizmati xatosi: ' . $err);
        }

        $text = collect($response->json('content', []))->firstWhere('type', 'text')['text'] ?? null;
        return $this->decodeJson($text);
    }

    private function decodeJson(?string $text): array
    {
        if (!is_string($text)) {
            throw new RuntimeException('AI javobi bo‘sh keldi.');
        }
        // Strip accidental ```json fences.
        $text = trim(preg_replace('/^```(?:json)?|```$/m', '', trim($text)));
        $data = json_decode($text, true);
        if (!is_array($data)) {
            throw new RuntimeException('AI javobini o‘qib bo‘lmadi.');
        }
        return $data;
    }

    // --------------------------------------------------------------- Prompts

    private function systemPrompt(): string
    {
        return <<<'TXT'
You are a content assistant for NEO-LABS, a health-supplements (BAA/dietary supplement) e-commerce brand in Uzbekistan. You produce structured product-catalog content in three languages: Uzbek (uz, Latin script — the primary language), Russian (ru), and English (en).

RULES:
- Return ONLY a single JSON object. Every field must be present; use an empty string (or empty array for benefits) when you have nothing reliable to put there.
- Translations must be natural and idiomatic for each language, not word-for-word. Keep the same meaning and marketing tone across all three languages.
- Uzbek uses the Latin alphabet (o‘, g‘, sh, ch), not Cyrillic.
- DO NOT invent facts you were not given. Never fabricate composition, dosage, ingredient amounts, certificate numbers, or specific medical claims. If composition/application/warnings are not provided or derivable, leave them empty.
- Health-claim safety: do NOT promise that the product "cures", "treats", or "prevents" diseases. Describe supportive benefits responsibly (e.g. "supports immunity", "helps replenish magnesium").
- "short" is a concise hero description, ~160–220 characters, one or two sentences.
- "benefits" is up to 3 short benefit phrases per language (max ~60 chars each).
- "composition", "application", "warnings" may use simple HTML (<p>, <ul>, <li>, <strong>) or plain text; keep them concise.
- "description" (Uzbek only) is a fuller product description; simple HTML is allowed.
- "seo_title" ≤ 60 characters; "meta_description" ≤ 160 characters; write real, keyword-aware SEO text per language, not a copy of the name.
TXT;
    }

    private function outputKeysHint(): string
    {
        return 'Return a JSON object with EXACTLY these keys and no others: '
            . '"description_uz"; and for each locale of uz, ru, en: '
            . '"name_{loc}", "form_{loc}", "packaging_{loc}", "short_{loc}", "composition_{loc}", '
            . '"application_{loc}", "warnings_{loc}", "seo_title_{loc}", "meta_description_{loc}" (all strings), '
            . 'and "benefits_{loc}" (an array of up to 3 strings). Replace {loc} with uz, ru and en.';
    }

    private function userPrompt(string $mode, ?string $brief, array $fields): string
    {
        $context = json_encode($this->pickContext($fields), JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);

        return match ($mode) {
            'translate' => "TASK: The Uzbek fields below are already filled. Keep the uz_* values as given (echo them back), and produce natural ru_* and en_* translations of every field. Also produce SEO for all three languages.\n\nENTERED UZBEK CONTENT:\n{$context}",
            'seo' => "TASK: Using the product content below, write strong SEO seo_title and meta_description for uz, ru and en. Echo the other fields back from what is provided (translate to ru/en if only uz is present).\n\nPRODUCT CONTENT:\n{$context}",
            default => "TASK: From the free-text product brief below (written in Uzbek), extract and compose complete catalog content. Fill all Uzbek fields, then produce natural Russian and English versions, plus SEO for each language. Do not invent composition or dosage that is not stated in the brief.\n\nPRODUCT BRIEF:\n" . trim((string) $brief),
        };
    }

    /** Collect the content the client already entered. */
    private function pickContext(array $fields): array
    {
        $keys = ['name', 'form', 'packaging', 'short', 'composition', 'application', 'warnings', 'description'];
        $out = [];
        foreach (self::LOCALES as $loc) {
            foreach ($keys as $k) {
                $field = "{$k}_{$loc}";
                if (!empty($fields[$field])) {
                    $out[$field] = (string) $fields[$field];
                }
            }
            if (!empty($fields["benefits_{$loc}"]) && is_array($fields["benefits_{$loc}"])) {
                $b = array_values(array_filter(array_map('strval', $fields["benefits_{$loc}"]), fn ($v) => trim($v) !== ''));
                if ($b) {
                    $out["benefits_{$loc}"] = $b;
                }
            }
        }
        return $out;
    }

    /** JSON schema used for Anthropic structured outputs. */
    private function schema(): array
    {
        $props = ['description_uz' => ['type' => 'string']];
        $required = ['description_uz'];
        foreach (self::LOCALES as $loc) {
            foreach (['name', 'form', 'packaging', 'short', 'composition', 'application', 'warnings', 'seo_title', 'meta_description'] as $k) {
                $props["{$k}_{$loc}"] = ['type' => 'string'];
                $required[] = "{$k}_{$loc}";
            }
            $props["benefits_{$loc}"] = ['type' => 'array', 'items' => ['type' => 'string']];
            $required[] = "benefits_{$loc}";
        }

        return [
            'type' => 'object',
            'additionalProperties' => false,
            'properties' => $props,
            'required' => $required,
        ];
    }

    // --------------------------------------------------------- Article prompts

    private function articleSystemPrompt(): string
    {
        return <<<'TXT'
You are an editorial assistant for NEO-LABS, a health & wellness brand in Uzbekistan. You turn a source article into original blog content for three languages: Uzbek (uz, Latin script — primary), Russian (ru), and English (en).

RULES:
- Return ONLY a single JSON object; every field must be present (empty string if unknown).
- COMPLETENESS IS THE #1 PRIORITY: the article MUST include EVERY piece of information from the source — every section, heading, fact, number, statistic, name, quote, list item, step, example, recommendation, warning and detail. Do NOT summarize, shorten, condense, skip or omit anything. The finished body must be AT LEAST as long and as complete as the source; when in doubt, include more, never less.
- REWRITE in your own words — do NOT copy the source text verbatim. Rephrase and reorganize it into original, well-structured prose, but PRESERVE all of the source's information and meaning while doing so. Never plagiarize, but never drop content in the name of brevity.
- Preserve the source's structure: keep the same sections/headings (rephrased) and cover them in the same order and depth as the original.
- Produce the article body as clean semantic HTML using <h2>, <h3>, <p>, <ul>, <li>, <strong> (no <html>/<body>, no inline styles, no images).
- Keep the same meaning across all three languages; translations must be natural and idiomatic. Uzbek uses the Latin alphabet (o‘, g‘, sh, ch).
- Health-claim safety: do NOT claim the content "cures", "treats" or "prevents" disease; keep an informational, responsible tone. Do NOT invent statistics, studies, citations, dates, or author names.
- "title" ≤ 90 characters, engaging. "seo_title" ≤ 60 chars. "meta_description" ≤ 160 chars. "schema_description" is a 1–2 sentence plain-text summary.
- "image_query": 2–4 English keywords describing a suitable, non-branded stock photo for the article topic (e.g. "healthy breakfast vegetables", "woman doing yoga"). No text, no logos, no specific people.
TXT;
    }

    private function articleKeysHint(): string
    {
        return 'Return a JSON object with EXACTLY these keys: for each locale of uz, ru, en: '
            . '"title_{loc}", "description_{loc}" (HTML body), "seo_title_{loc}", "meta_description_{loc}", '
            . '"schema_description_{loc}" (all strings); plus "image_query" (one English string). '
            . 'Replace {loc} with uz, ru and en.';
    }

    private function articleUserPrompt(string $mode, ?string $sourceText, array $fields): string
    {
        if ($mode === 'translate') {
            $context = json_encode($this->pickArticleContext($fields), JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
            return "TASK: The Uzbek article fields below are filled. Keep the uz_* values (echo them back), produce natural ru_* and en_* translations of the title, body and SEO fields, and add an image_query.\n\nENTERED UZBEK ARTICLE:\n{$context}";
        }

        return "TASK: Rewrite the ENTIRE source article below into an original article (do not copy it verbatim) for uz, ru and en, plus an image_query for a matching stock photo.\n"
            . "You MUST carry over ALL of the information from the source: every section, heading, paragraph, fact, number, statistic, name, quote, list, step, example and recommendation. Do not summarize, shorten or omit anything — the result must be at least as complete and detailed as the source. Reword and restructure, but keep 100% of the content.\n\n"
            . "SOURCE ARTICLE:\n" . trim((string) $sourceText);
    }

    private function pickArticleContext(array $fields): array
    {
        $out = [];
        foreach (self::LOCALES as $loc) {
            foreach (['title', 'description', 'seo_title', 'meta_description', 'schema_description'] as $k) {
                if (!empty($fields["{$k}_{$loc}"])) {
                    $out["{$k}_{$loc}"] = (string) $fields["{$k}_{$loc}"];
                }
            }
        }
        return $out;
    }

    private function articleSchema(): array
    {
        $props = ['image_query' => ['type' => 'string']];
        $required = ['image_query'];
        foreach (self::LOCALES as $loc) {
            foreach (['title', 'description', 'seo_title', 'meta_description', 'schema_description'] as $k) {
                $props["{$k}_{$loc}"] = ['type' => 'string'];
                $required[] = "{$k}_{$loc}";
            }
        }
        return ['type' => 'object', 'additionalProperties' => false, 'properties' => $props, 'required' => $required];
    }

    private function normalizeArticle(array $data): array
    {
        $out = ['image_query' => (string) ($data['image_query'] ?? '')];
        foreach (self::LOCALES as $loc) {
            foreach (['title', 'description', 'seo_title', 'meta_description', 'schema_description'] as $k) {
                $out["{$k}_{$loc}"] = (string) ($data["{$k}_{$loc}"] ?? '');
            }
        }
        return $out;
    }

    /** Guarantee a predictable shape for the frontend. */
    private function normalize(array $data): array
    {
        $out = ['description_uz' => (string) ($data['description_uz'] ?? '')];
        foreach (self::LOCALES as $loc) {
            foreach (['name', 'form', 'packaging', 'short', 'composition', 'application', 'warnings', 'seo_title', 'meta_description'] as $k) {
                $out["{$k}_{$loc}"] = (string) ($data["{$k}_{$loc}"] ?? '');
            }
            $b = $data["benefits_{$loc}"] ?? [];
            $out["benefits_{$loc}"] = is_array($b)
                ? array_values(array_slice(array_map('strval', $b), 0, 3))
                : [];
        }
        return $out;
    }
}
