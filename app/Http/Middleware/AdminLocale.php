<?php

namespace App\Http\Middleware;

use Closure;

class AdminLocale
{
    public function handle($request, Closure $next)
    {
        app()->setLocale('uz');

        return $next($request);
    }
}
