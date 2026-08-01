<?php

namespace App\Http\Controllers;

use App\Models\PageView;
use App\Support\AnalyticsClassifier;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class AnalyticsController extends Controller
{
    public function collect(Request $request)
    {
        if ($request->user()) return response()->noContent();

        $data = $request->validate([
            'event_id' => ['required', 'uuid'],
            'visitor_id' => ['nullable', 'string', 'max:100'],
            'session_id' => ['nullable', 'string', 'max:100'],
            'event_type' => ['nullable', 'in:page_view,phone_click,email_click,social_click,contact_form_submit,outbound_click'],
            'path' => ['required', 'string', 'max:1000'],
            'title' => ['nullable', 'string', 'max:500'],
            'landing_path' => ['nullable', 'string', 'max:1000'],
            'referrer' => ['nullable', 'string', 'max:2000'],
            'utm_source' => ['nullable', 'string', 'max:120'],
            'utm_medium' => ['nullable', 'string', 'max:80'],
            'utm_campaign' => ['nullable', 'string', 'max:180'],
            'target_url' => ['nullable', 'string', 'max:1000'],
            'screen_width' => ['nullable', 'integer', 'min:0', 'max:65535'],
            'screen_height' => ['nullable', 'integer', 'min:0', 'max:65535'],
            'client_language' => ['nullable', 'string', 'max:32'],
            'timezone' => ['nullable', 'string', 'max:80'],
        ]);

        $userAgent = Str::limit((string) $request->userAgent(), 2000, '');
        if (AnalyticsClassifier::isBot($userAgent)) return response()->noContent();

        $path = $this->path((string) $data['path']);
        if (!preg_match('#^/(uz|ru|en)(?:/|$)#', $path)) return response()->noContent();

        $referrerUrl = $this->safeUrl($data['referrer'] ?? null);
        $referrerHost = $referrerUrl ? strtolower((string) parse_url($referrerUrl, PHP_URL_HOST)) : null;
        $source = $this->cleanToken($data['utm_source'] ?? null, 120);
        $medium = $this->cleanToken($data['utm_medium'] ?? null, 80);
        $campaign = $this->cleanText($data['utm_campaign'] ?? null, 180);
        $page = AnalyticsClassifier::page($path);
        $now = now();

        try {
            PageView::create([
                'event_id' => $data['event_id'],
                'visitor_id' => $this->anonymousId($data['visitor_id'] ?? null, $request->ip() . '|' . $userAgent),
                'session_id' => $this->anonymousId($data['session_id'] ?? null, $request->ip() . '|' . $userAgent . '|' . $now->format('Y-m-d-H')),
                'event_type' => $data['event_type'] ?? 'page_view',
                'path' => $path,
                'path_hash' => sha1($path),
                'route_name' => $page['route_name'],
                'page_type' => $page['page_type'],
                'content_id' => $page['content_id'],
                'locale' => $page['locale'],
                'title' => $this->cleanText($data['title'] ?? null, 255),
                'landing_path' => isset($data['landing_path']) ? $this->path($data['landing_path']) : $path,
                'referrer_url' => $referrerUrl,
                'referrer_host' => $referrerHost ? Str::limit($referrerHost, 255, '') : null,
                'source' => $source,
                'medium' => $medium,
                'campaign' => $campaign,
                'channel' => AnalyticsClassifier::channel($referrerHost, $source, $medium),
                'target_url' => $this->safeTarget($data['target_url'] ?? null),
                'device_type' => AnalyticsClassifier::device($userAgent, $data['screen_width'] ?? null),
                'browser' => AnalyticsClassifier::browser($userAgent),
                'operating_system' => AnalyticsClassifier::operatingSystem($userAgent),
                'country_code' => $this->country($request),
                'city' => $this->city($request),
                'screen_width' => $data['screen_width'] ?? null,
                'screen_height' => $data['screen_height'] ?? null,
                'client_language' => $this->cleanText($data['client_language'] ?? null, 32),
                'timezone' => $this->cleanText($data['timezone'] ?? null, 80),
                'ip_hash' => $request->ip() ? $this->hash((string) $request->ip()) : null,
                'user_agent' => $userAgent,
                'occurred_at' => $now,
                'created_at' => $now,
            ]);
        } catch (QueryException $exception) {
            // Retried keepalive requests have the same event_id and are safely ignored.
            if (!in_array((string) $exception->getCode(), ['23000', '23505'], true)) {
                report($exception);
            }
        }

        return response()->noContent();
    }

    private function path(string $value): string
    {
        $path = parse_url($value, PHP_URL_PATH) ?: '/';
        $path = '/' . ltrim($path, '/');
        return Str::limit($path, 500, '');
    }

    private function safeUrl(?string $value): ?string
    {
        if (!$value || !filter_var($value, FILTER_VALIDATE_URL)) return null;
        $parts = parse_url($value);
        if (!in_array($parts['scheme'] ?? '', ['http', 'https'], true)) return null;
        return Str::limit(($parts['scheme'] . '://' . ($parts['host'] ?? '') . ($parts['path'] ?? '/')), 2000, '');
    }

    private function safeTarget(?string $value): ?string
    {
        if (!$value) return null;
        if (preg_match('#^(?:https?://|tel:|mailto:)#i', $value)) return Str::limit($value, 1000, '');
        return Str::limit($this->path($value), 1000, '');
    }

    private function anonymousId(?string $value, string $fallback): string
    {
        return $this->hash($value && preg_match('/^[a-zA-Z0-9-]{8,100}$/', $value) ? $value : $fallback);
    }

    private function hash(string $value): string
    {
        return hash_hmac('sha256', $value, (string) config('app.key'));
    }

    private function cleanToken(?string $value, int $limit): ?string
    {
        if (!$value) return null;
        $value = Str::lower(trim(strip_tags($value)));
        return Str::limit(preg_replace('/[^a-z0-9._-]+/i', '-', $value), $limit, '');
    }

    private function cleanText(?string $value, int $limit): ?string
    {
        if (!$value) return null;
        return Str::limit(trim(strip_tags($value)), $limit, '');
    }

    private function country(Request $request): ?string
    {
        foreach (['CF-IPCountry', 'X-Country-Code', 'X-Vercel-IP-Country', 'CloudFront-Viewer-Country'] as $header) {
            $value = strtoupper((string) $request->headers->get($header));
            if (preg_match('/^[A-Z]{2}$/', $value)) return $value;
        }
        return null;
    }

    private function city(Request $request): ?string
    {
        foreach (['CF-IPCity', 'X-City', 'X-Vercel-IP-City'] as $header) {
            $value = rawurldecode((string) $request->headers->get($header));
            if ($value !== '') return $this->cleanText($value, 120);
        }
        return null;
    }
}
