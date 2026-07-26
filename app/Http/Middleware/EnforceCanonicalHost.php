<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class EnforceCanonicalHost
{
    public function handle(Request $request, Closure $next)
    {
        $isLocalDevelopmentServer = PHP_SAPI === 'cli-server'
            && in_array($request->getHost(), ['127.0.0.1', 'localhost', '::1'], true);

        if (!$isLocalDevelopmentServer && app()->environment('production') && config('seo.force_canonical_host')) {
            $host = (string) config('seo.canonical_host');
            if (!$request->isSecure() || $request->getHost() !== $host || ($request->getPathInfo() !== '/' && Str::endsWith($request->getPathInfo(), '/'))) {
                $path = $request->getPathInfo() === '/' ? '/' : rtrim($request->getPathInfo(), '/');
                $target = 'https://' . $host . $path;
                if ($request->getQueryString()) {
                    $target .= '?' . $request->getQueryString();
                }
                return redirect()->away($target, 301);
            }
        }

        return $next($request);
    }
}
