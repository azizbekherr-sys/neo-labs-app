<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class LogSearchReferral
{
    public function handle(Request $request, Closure $next)
    {
        $response = $next($request);
        $referrer = strtolower((string) $request->headers->get('referer') . ' ' . (string) $request->query('utm_source'));
        $source = $this->source($referrer);

        if ($source && $request->isMethod('GET')) {
            Log::channel('seo')->info('search_referral', [
                'source' => $source,
                'landing_page' => $request->url(),
                'referrer_host' => parse_url($referrer, PHP_URL_HOST),
                'occurred_at' => now()->toIso8601String(),
            ]);
        }

        return $response;
    }

    private function source(string $referrer): ?string
    {
        if ($referrer === '') return null;
        if (Str::contains($referrer, ['chatgpt.com', 'openai.com'])) return 'chatgpt';
        if (Str::contains($referrer, 'perplexity.ai')) return 'perplexity';
        if (Str::contains($referrer, 'copilot.microsoft.com')) return 'bing_copilot';
        if (Str::contains($referrer, 'google.')) return 'google_organic';
        if (Str::contains($referrer, 'bing.com')) return 'bing_organic';
        return null;
    }
}
