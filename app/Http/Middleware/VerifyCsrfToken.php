<?php

namespace App\Http\Middleware;

use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken as Middleware;

class VerifyCsrfToken extends Middleware
{
    /**
     * The URIs that should be excluded from CSRF verification.
     *
     * @var array<int, string>
     */
    protected $except = [
        // Public, anonymous endpoints. A visitor who left the page open longer than the
        // session lifetime (or logged in/out of the admin in another tab) would otherwise
        // get "419 Page Expired" and lose the message. Spam is limited by the honeypot field
        // and the per-IP throttle on the routes; SameSite=Lax keeps the session cookie off
        // cross-site posts, so these routes never act on an authenticated session.
        'contact/send',
        'analytics/collect',
    ];
}
