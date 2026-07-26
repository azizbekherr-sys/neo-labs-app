<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use RuntimeException;

/**
 * Uploads media to Supabase Storage (S3-like object storage) via its REST API.
 * Returns a public URL. Used when config('services.media.disk') === 'supabase'
 * so uploads persist on ephemeral hosts (e.g. Render free tier).
 */
class SupabaseStorageService
{
    public function enabled(): bool
    {
        return (bool) config('services.supabase.url') && (bool) config('services.supabase.key');
    }

    public function putFile(UploadedFile $file, string $folder): string
    {
        $ext = strtolower($file->getClientOriginalExtension() ?: $file->guessExtension() ?: 'bin');
        return $this->put(file_get_contents($file->getRealPath()), $folder, $ext, $file->getMimeType());
    }

    public function putBinary(string $bytes, string $folder, string $ext, ?string $mime = null): string
    {
        return $this->put($bytes, $folder, strtolower($ext), $mime);
    }

    private function put(string $bytes, string $folder, string $ext, ?string $mime): string
    {
        $base = rtrim((string) config('services.supabase.url'), '/');
        $key = (string) config('services.supabase.key');
        $bucket = (string) config('services.supabase.bucket', 'media');
        $mime = $mime ?: 'application/octet-stream';
        $path = trim($folder, '/') . '/' . Str::lower(Str::random(24)) . '.' . $ext;

        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . $key,
            'apikey' => $key,
            'x-upsert' => 'true',
        ])->withBody($bytes, $mime)->timeout(60)
            ->post("{$base}/storage/v1/object/{$bucket}/{$path}");

        if (!$response->successful()) {
            throw new RuntimeException('Supabase Storage yuklash xatosi: ' . $response->body());
        }

        return "{$base}/storage/v1/object/public/{$bucket}/{$path}";
    }

    /** Delete an object given its public URL (no-op if not a Supabase URL). */
    public function deleteUrl(string $publicUrl): void
    {
        $base = rtrim((string) config('services.supabase.url'), '/');
        $bucket = (string) config('services.supabase.bucket', 'media');
        $needle = "{$base}/storage/v1/object/public/{$bucket}/";
        if (!Str::startsWith($publicUrl, $needle)) {
            return;
        }
        $path = Str::after($publicUrl, $needle);
        $key = (string) config('services.supabase.key');

        try {
            Http::withHeaders(['Authorization' => 'Bearer ' . $key, 'apikey' => $key])
                ->timeout(30)
                ->delete("{$base}/storage/v1/object/{$bucket}/{$path}");
        } catch (\Throwable $e) {
            // best-effort cleanup
        }
    }
}
