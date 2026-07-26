<?php

namespace App\Support;

class ProductMedia
{
    public static function generateVariants(string $relativePath, array $widths = [320, 640, 960, 1280]): void
    {
        if (!extension_loaded('gd')) {
            return;
        }

        $relativePath = ltrim(str_replace('\\', '/', $relativePath), '/');
        $absolutePath = public_path($relativePath);
        if (!is_file($absolutePath)) {
            return;
        }

        $info = @getimagesize($absolutePath);
        if (!$info || empty($info[0]) || empty($info[1])) {
            return;
        }

        $source = self::open($absolutePath, $info['mime'] ?? '');
        if (!$source) {
            return;
        }

        $directory = public_path('products/optimized');
        if (!is_dir($directory)) {
            mkdir($directory, 0755, true);
        }

        $stem = pathinfo($relativePath, PATHINFO_FILENAME);
        foreach (array_unique($widths) as $width) {
            $width = (int) $width;
            if ($width < 1 || $width > $info[0]) {
                continue;
            }

            $height = max(1, (int) round($info[1] * ($width / $info[0])));
            $canvas = imagecreatetruecolor($width, $height);
            imagealphablending($canvas, false);
            imagesavealpha($canvas, true);
            $transparent = imagecolorallocatealpha($canvas, 255, 255, 255, 127);
            imagefilledrectangle($canvas, 0, 0, $width, $height, $transparent);
            imagecopyresampled($canvas, $source, 0, 0, 0, 0, $width, $height, $info[0], $info[1]);

            if (function_exists('imagewebp')) {
                imagewebp($canvas, $directory . '/' . $stem . '-' . $width . '.webp', 82);
            }
            if (function_exists('imageavif')) {
                imageavif($canvas, $directory . '/' . $stem . '-' . $width . '.avif', 58);
            }

            unset($canvas);
        }

        unset($source);
    }

    private static function open(string $path, string $mime)
    {
        if ($mime === 'image/jpeg' && function_exists('imagecreatefromjpeg')) {
            return @imagecreatefromjpeg($path);
        }
        if ($mime === 'image/png' && function_exists('imagecreatefrompng')) {
            return @imagecreatefrompng($path);
        }
        if ($mime === 'image/webp' && function_exists('imagecreatefromwebp')) {
            return @imagecreatefromwebp($path);
        }

        return null;
    }
}
