<?php

use App\Services\SupabaseStorageService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Str;

if (!function_exists('media_using_supabase')) {
    function media_using_supabase(): bool
    {
        return config('services.media.disk') === 'supabase'
            && app(SupabaseStorageService::class)->enabled();
    }
}

if (!function_exists('media_url')) {
    /**
     * Resolve a stored media reference to a displayable URL.
     * Full URLs (Supabase) are returned as-is; relative paths fall back to asset().
     */
    function media_url(?string $value): string
    {
        $value = trim((string) $value);
        if ($value === '') {
            return asset('img/placeholder.png');
        }
        if (Str::startsWith($value, ['http://', 'https://'])) {
            return $value;
        }
        // Legacy Laravel "storage/" paths that were served via the public symlink.
        return asset($value);
    }
}

if (!function_exists('media_store')) {
    /**
     * Store an uploaded file and return a reference (Supabase public URL, or a
     * relative public/ path in local mode). Mirrors the previous local behavior.
     */
    function media_store(UploadedFile $file, string $folder): string
    {
        if (media_using_supabase()) {
            return app(SupabaseStorageService::class)->putFile($file, $folder);
        }
        if (!is_dir(public_path($folder))) {
            mkdir(public_path($folder), 0755, true);
        }
        $name = Str::lower(Str::random(16)) . '.' . ($file->getClientOriginalExtension() ?: 'bin');
        $file->move(public_path($folder), $name);
        return $folder . '/' . $name;
    }
}

if (!function_exists('media_store_binary')) {
    /** Store raw bytes (e.g. an AI/downloaded image) and return a reference. */
    function media_store_binary(string $bytes, string $folder, string $ext, ?string $mime = null): string
    {
        if (media_using_supabase()) {
            return app(SupabaseStorageService::class)->putBinary($bytes, $folder, $ext, $mime);
        }
        if (!is_dir(public_path($folder))) {
            mkdir(public_path($folder), 0755, true);
        }
        $rel = $folder . '/' . Str::lower(Str::random(16)) . '.' . strtolower($ext);
        file_put_contents(public_path($rel), $bytes);
        return $rel;
    }
}

if (!function_exists('media_delete')) {
    /** Best-effort delete of a stored reference (Supabase URL or local path). */
    function media_delete(?string $ref, array $allowedPrefixes = []): void
    {
        $ref = trim((string) $ref);
        if ($ref === '') {
            return;
        }
        if (Str::startsWith($ref, ['http://', 'https://'])) {
            app(SupabaseStorageService::class)->deleteUrl($ref);
            return;
        }
        $rel = ltrim($ref, '/');
        if ($allowedPrefixes && !Str::startsWith($rel, $allowedPrefixes)) {
            return;
        }
        $path = public_path($rel);
        if (is_file($path)) {
            @unlink($path);
        }
    }
}
