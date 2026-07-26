<?php

namespace App\Support;

use Illuminate\Support\Str;

class Media
{
    public static function responsive(?string $path, string $kind, array $widths): array
    {
        $fallback = asset('img/placeholder.png');
        if (!$path) {
            return ['src' => $fallback, 'srcset' => null, 'avif_srcset' => null, 'fallback' => $fallback, 'width' => 800, 'height' => 800];
        }

        $normalized = ltrim(str_replace('\\', '/', trim($path)), '/');
        if (Str::startsWith($normalized, ['http://', 'https://'])) {
            return ['src' => $normalized, 'srcset' => null, 'avif_srcset' => null, 'fallback' => $normalized, 'width' => 800, 'height' => 800];
        }

        if (!Str::startsWith($normalized, [$kind . '/', 'storage/'])) {
            $normalized = $kind . '/' . $normalized;
        }

        $absolutePath = public_path($normalized);
        $dimensions = is_file($absolutePath) ? @getimagesize($absolutePath) : false;
        $intrinsicWidth = $dimensions[0] ?? 800;
        $intrinsicHeight = $dimensions[1] ?? 800;
        $stem = pathinfo(basename($normalized), PATHINFO_FILENAME);
        $variants = [];
        $avifVariants = [];

        foreach ($widths as $width) {
            if ($width > $intrinsicWidth) {
                continue;
            }
            $variant = $kind . '/optimized/' . $stem . '-' . $width . '.webp';
            if (is_file(public_path($variant))) {
                $variants[] = asset($variant) . ' ' . $width . 'w';
            }
            $avifVariant = $kind . '/optimized/' . $stem . '-' . $width . '.avif';
            if (is_file(public_path($avifVariant))) {
                $avifVariants[] = asset($avifVariant) . ' ' . $width . 'w';
            }
        }

        $fallbackSrc = is_file($absolutePath) ? asset($normalized) : $fallback;
        $src = $variants
            ? preg_replace('/\s+\d+w$/', '', end($variants))
            : $fallbackSrc;

        return [
            'src' => $src,
            'srcset' => $variants ? implode(', ', $variants) : null,
            'avif_srcset' => $avifVariants ? implode(', ', $avifVariants) : null,
            'fallback' => $fallbackSrc,
            'width' => $intrinsicWidth,
            'height' => $intrinsicHeight,
        ];
    }
}
