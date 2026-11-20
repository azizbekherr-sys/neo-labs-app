<?php

namespace App\Exceptions;

use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Illuminate\Http\Exceptions\ThrottleRequestsException;
use Illuminate\Support\Facades\URL;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Throwable;

class Handler extends ExceptionHandler
{
    /**
     * A list of the exception types that are not reported.
     *
     * @var array<int, class-string<Throwable>>
     */
    protected $dontReport = [
        //
    ];

    /**
     * A list of the inputs that are never flashed for validation exceptions.
     *
     * @var array<int, string>
     */
    protected $dontFlash = [
        'current_password',
        'password',
        'password_confirmation',
    ];

    /**
     * Register the exception handling callbacks for the application.
     *
     * @return void
     */
    public function register()
    {
        $this->reportable(function (Throwable $e) {
            //
        });
    }

    public function render($request, Throwable $e)
    {
        // Too many contact form submits: keep the visitor on the form with their text instead of a bare 429 page.
        if ($e instanceof ThrottleRequestsException && $request->routeIs('contact.send') && !$request->expectsJson()) {
            return redirect()->back()
                ->withInput($request->except(['_token', 'website']))
                ->with('contact_error', __('site.contacts.too_many'));
        }

        if ($e instanceof NotFoundHttpException) {
            $supported = config('seo.locales', ['uz', 'ru', 'en']);
            $pathLocale = (string) $request->segment(1);
            $locale = in_array($pathLocale, $supported, true)
                ? $pathLocale
                : (string) config('seo.default_locale', 'uz');

            app()->setLocale($locale);
            URL::defaults(['locale' => $locale]);
        }

        return parent::render($request, $e);
    }
}
