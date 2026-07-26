<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Certificate extends Model
{
    protected $fillable = [
        'name_uz', 'name_ru', 'name_en', 'number',
        'issuer_uz', 'issuer_ru', 'issuer_en', 'issued_at',
        'expires_at', 'scope_uz', 'scope_ru', 'scope_en',
        'document_path', 'verification_url', 'is_published',
    ];

    protected $casts = [
        'issued_at' => 'date',
        'expires_at' => 'date',
        'is_published' => 'boolean',
    ];
}
