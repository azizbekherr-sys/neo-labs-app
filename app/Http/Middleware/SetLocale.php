<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\URL;
use Symfony\Component\HttpFoundation\Response;

class SetLocale
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $supported = ['ru', 'uz', 'en'];

        // 1) Prefer locale from URL prefix: /ru/..., /uz/... or /en/...
        $pathLocale = (string) $request->segment(1);
        if (in_array($pathLocale, $supported, true)) {
            $locale = $pathLocale;
            session(['locale' => $locale]);
        } else {
            // 2) Fallback to session/app default
            $locale = (string) session('locale', config('app.locale', 'ru'));
            if (!in_array($locale, $supported, true)) {
                $locale = 'ru';
            }
        }

        app()->setLocale($locale);

        // Make route() automatically include {locale} parameter where applicable
        URL::defaults(['locale' => $locale]);

        return $next($request);
    }
}


