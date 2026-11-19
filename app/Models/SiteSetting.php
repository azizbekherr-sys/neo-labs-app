<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Crypt;
use Throwable;

/**
 * Small key/value store for settings the admin edits in the panel
 * (e.g. Telegram notifications). Secrets are encrypted with APP_KEY.
 */
class SiteSetting extends Model
{
    protected $fillable = ['key', 'value'];

    public static function getValue(string $key, ?string $default = null): ?string
    {
        try {
            $value = static::query()->where('key', $key)->value('value');
        } catch (Throwable $e) {
            return $default;
        }

        return $value === null ? $default : (string) $value;
    }

    public static function putValue(string $key, ?string $value): void
    {
        static::query()->updateOrCreate(['key' => $key], ['value' => $value]);
    }

    public static function getSecret(string $key): ?string
    {
        $value = static::getValue($key);
        if ($value === null || $value === '') {
            return null;
        }

        try {
            return Crypt::decryptString($value);
        } catch (Throwable $e) {
            return null;
        }
    }

    public static function putSecret(string $key, ?string $value): void
    {
        static::putValue($key, $value === null || $value === '' ? null : Crypt::encryptString($value));
    }
}
