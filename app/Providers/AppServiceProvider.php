<?php

namespace App\Providers;

use App\Support\PublicContentCache;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\ServiceProvider;
use Illuminate\View\Compilers\BladeCompiler;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        require_once app_path('helpers.php');
    }

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        PublicContentCache::registerInvalidation();
        $this->registerAttributeDirectives();
    }

    /**
     * Laravel 8 has no @checked/@selected/@disabled/@readonly/@required (added in Laravel 9),
     * so the views would print them literally and forms would lose their saved state.
     * Register equivalents only when the framework does not provide them natively.
     */
    private function registerAttributeDirectives(): void
    {
        foreach (['checked', 'selected', 'disabled', 'readonly', 'required'] as $attribute) {
            if (method_exists(BladeCompiler::class, 'compile' . ucfirst($attribute))) {
                continue;
            }

            Blade::directive($attribute, function ($condition) use ($attribute) {
                return "<?php if ({$condition}): echo '{$attribute}'; endif; ?>";
            });
        }
    }
}
