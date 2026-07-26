<?php

namespace App\Support;

use Illuminate\Support\Arr;
use Illuminate\Support\Str;

class MedicalContent
{
    private const REVIEW_TERMS = [
        'davolaydi', 'davolash', 'oldini oladi', 'kafolatlangan', 'xavfsiz', 'samarali',
        'лечит', 'лечение', 'предотвращает', 'гарантирован', 'безопасен', 'эффективен',
        'treats', 'treatment', 'prevents', 'guaranteed', 'safe', 'effective',
    ];

    public static function requiresReview(array $input): bool
    {
        $values = Arr::only($input, [
            'short_description_uz', 'short_description_ru', 'short_description_en',
            'description_uz', 'description_ru', 'description_en',
            'benefits_uz', 'benefits_ru', 'benefits_en',
            'application_uz', 'application_ru', 'application_en',
        ]);

        $text = Str::lower(implode(' ', Arr::flatten($values)));

        return Str::contains($text, self::REVIEW_TERMS);
    }
}
