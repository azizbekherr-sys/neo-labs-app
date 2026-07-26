<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Article extends Model
{
    use HasFactory;

    protected $fillable = [
        'title_uz',
        'title_ru',
        'title_en',
        'photo',
        'description_uz',
        'description_ru',
        'description_en',
        'views',
        'seo_title_uz', 'seo_title_ru', 'seo_title_en',
        'meta_description_uz', 'meta_description_ru', 'meta_description_en',
        'canonical_url', 'robots', 'og_image',
        'schema_description_uz', 'schema_description_ru', 'schema_description_en',
        'author_name', 'author_role_uz', 'author_role_ru', 'author_role_en', 'author_slug',
        'reviewer_name', 'reviewer_role_uz', 'reviewer_role_ru', 'reviewer_role_en', 'reviewed_at',
        'references_uz', 'references_ru', 'references_en',
        'faqs_uz', 'faqs_ru', 'faqs_en', 'schema_type',
    ];

    protected $casts = [
        'views' => 'integer',
        'reviewed_at' => 'datetime',
        'references_uz' => 'array',
        'references_ru' => 'array',
        'references_en' => 'array',
        'faqs_uz' => 'array',
        'faqs_ru' => 'array',
        'faqs_en' => 'array',
    ];
}


