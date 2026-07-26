<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'name_uz',
        'name_ru',
        'name_en',
        'type_uz',
        'type_ru',
        'type_en',
        'composition_uz',
        'composition_ru',
        'composition_en',
        'description_uz',
        'description_ru',
        'description_en',
        'short_description_uz', 'short_description_ru', 'short_description_en',
        'form_uz', 'form_ru', 'form_en',
        'disclaimer_uz', 'disclaimer_ru', 'disclaimer_en',
        'type',
        'composition',
        'barcode',
        'manufacturer',
        'form',
        'dosage',
        'package',
        'price',
        'vat',
        'stock',
        'expires_at',
        'prescription',
        'status',
        'image',
        'images',
        'description',
        'seo_title_uz', 'seo_title_ru', 'seo_title_en',
        'meta_description_uz', 'meta_description_ru', 'meta_description_en',
        'canonical_url', 'robots', 'og_image',
        'schema_description_uz', 'schema_description_ru', 'schema_description_en',
        'sku', 'country_of_origin', 'packaging_count_uz', 'packaging_count_ru', 'packaging_count_en',
        'application_uz', 'application_ru', 'application_en',
        'warnings_uz', 'warnings_ru', 'warnings_en',
        'storage_conditions_uz', 'storage_conditions_ru', 'storage_conditions_en',
        'shelf_life_uz', 'shelf_life_ru', 'shelf_life_en',
        'registration_info_uz', 'registration_info_ru', 'registration_info_en',
        'faqs_uz', 'faqs_ru', 'faqs_en',
        'external_purchase_url', 'instruction_file', 'content_status',
        'medical_review_required', 'is_featured',
        'category_id', 'sales_mode', 'stock_status', 'currency',
        'benefits_uz', 'benefits_ru', 'benefits_en',
        'medical_review_status', 'seo_override',
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'vat' => 'integer',
        'stock' => 'integer',
        'prescription' => 'boolean',
        'expires_at' => 'date',
        'images' => 'array',
        'faqs_uz' => 'array',
        'faqs_ru' => 'array',
        'faqs_en' => 'array',
        'medical_review_required' => 'boolean',
        'is_featured' => 'boolean',
        'benefits_uz' => 'array',
        'benefits_ru' => 'array',
        'benefits_en' => 'array',
        'seo_override' => 'boolean',
    ];

    public function category()
    {
        return $this->belongsTo(ProductCategory::class, 'category_id');
    }

    public function relatedProducts()
    {
        return $this->belongsToMany(
            self::class,
            'product_related',
            'product_id',
            'related_product_id'
        )->withTimestamps();
    }

    public function getEffectiveSalesModeAttribute(): string
    {
        if (in_array($this->sales_mode, ['informational', 'external', 'direct'], true)) {
            return $this->sales_mode;
        }

        return $this->external_purchase_url ? 'external' : 'informational';
    }

    public function localizedBenefits(?string $locale = null): array
    {
        $locale = in_array($locale ?: app()->getLocale(), ['uz', 'ru', 'en'], true)
            ? ($locale ?: app()->getLocale())
            : 'uz';

        foreach ([$locale, 'uz', 'ru', 'en'] as $candidate) {
            $benefits = $this->{'benefits_' . $candidate};
            if (is_array($benefits) && collect($benefits)->filter()->isNotEmpty()) {
                return collect($benefits)->map(fn ($item) => trim((string) $item))->filter()->take(3)->values()->all();
            }
        }

        return [];
    }
}


