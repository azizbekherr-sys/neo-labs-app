<?php

/**
 * Laravel - A PHP Framework For Web Artisans
 *
 * @package  Laravel
 * @author   Taylor Otwell <taylor@laravel.com>
 */

// Dev-only shim: this app targets Laravel 8 but runs here on PHP 8.5, which emits
// deprecation notices (e.g. stock config/database.php uses PDO::MYSQL_ATTR_SSL_CA)
// during bootstrap, before Laravel's error handler is active — so they print as
// raw HTML above the page. Silence deprecations for `php artisan serve` only;
// production goes through public/index.php and is unaffected.
error_reporting(E_ALL & ~E_DEPRECATED & ~E_USER_DEPRECATED);
ini_set('display_errors', '0');

$uri = urldecode(
    parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH) ?? ''
);

// This file allows us to emulate Apache's "mod_rewrite" functionality from the
// built-in PHP web server. This provides a convenient way to test a Laravel
// application without having installed a "real" web server software here.
if ($uri !== '/' && file_exists(__DIR__.'/public'.$uri)) {
    return false;
}

require_once __DIR__.'/public/index.php';
