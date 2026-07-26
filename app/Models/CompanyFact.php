<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CompanyFact extends Model
{
    protected $fillable = [
        'key', 'label_uz', 'label_ru', 'label_en', 'value_uz', 'value_ru', 'value_en',
        'source_url', 'document_path', 'is_published',
    ];

    protected $casts = ['is_published' => 'boolean'];
}
