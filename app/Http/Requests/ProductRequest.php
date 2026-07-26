<?php

namespace App\Http\Requests;

use App\Support\MedicalContent;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ProductRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        $rules = [
            'name_uz' => ['required', 'string', 'max:255'],
            'name_ru' => ['nullable', 'string', 'max:255'],
            'name_en' => ['nullable', 'string', 'max:255'],
            'category_id' => ['nullable', 'integer', 'exists:product_categories,id'],
            'form_uz' => ['nullable', 'string', 'max:255'],
            'form_ru' => ['nullable', 'string', 'max:255'],
            'form_en' => ['nullable', 'string', 'max:255'],
            'packaging_count_uz' => ['nullable', 'string', 'max:255'],
            'packaging_count_ru' => ['nullable', 'string', 'max:255'],
            'packaging_count_en' => ['nullable', 'string', 'max:255'],
            'short_description_uz' => ['nullable', 'string', 'max:220'],
            'short_description_ru' => ['nullable', 'string', 'max:220'],
            'short_description_en' => ['nullable', 'string', 'max:220'],
            'benefits_uz' => ['nullable', 'array', 'max:3'],
            'benefits_ru' => ['nullable', 'array', 'max:3'],
            'benefits_en' => ['nullable', 'array', 'max:3'],
            'benefits_uz.*' => ['nullable', 'string', 'max:160'],
            'benefits_ru.*' => ['nullable', 'string', 'max:160'],
            'benefits_en.*' => ['nullable', 'string', 'max:160'],
            'composition_uz' => ['nullable', 'string'],
            'composition_ru' => ['nullable', 'string'],
            'composition_en' => ['nullable', 'string'],
            'description_uz' => ['nullable', 'string'],
            'description_ru' => ['nullable', 'string'],
            'description_en' => ['nullable', 'string'],
            'application_uz' => ['nullable', 'string'],
            'application_ru' => ['nullable', 'string'],
            'application_en' => ['nullable', 'string'],
            'warnings_uz' => ['nullable', 'string'],
            'warnings_ru' => ['nullable', 'string'],
            'warnings_en' => ['nullable', 'string'],
            'storage_conditions_uz' => ['nullable', 'string'],
            'storage_conditions_ru' => ['nullable', 'string'],
            'storage_conditions_en' => ['nullable', 'string'],
            'shelf_life_uz' => ['nullable', 'string', 'max:255'],
            'shelf_life_ru' => ['nullable', 'string', 'max:255'],
            'shelf_life_en' => ['nullable', 'string', 'max:255'],
            'registration_info_uz' => ['nullable', 'string'],
            'registration_info_ru' => ['nullable', 'string'],
            'registration_info_en' => ['nullable', 'string'],
            'disclaimer_uz' => ['nullable', 'string', 'max:500'],
            'disclaimer_ru' => ['nullable', 'string', 'max:500'],
            'disclaimer_en' => ['nullable', 'string', 'max:500'],
            'images' => ['nullable', 'array'],
            'images.*' => ['image', 'mimes:jpg,jpeg,png,webp', 'max:6144'],
            'barcode' => ['nullable', 'string', 'max:32'],
            'sku' => ['nullable', 'string', 'max:100'],
            'manufacturer' => ['nullable', 'string', 'max:255'],
            'country_of_origin' => ['nullable', 'string', 'size:2'],
            'price' => ['nullable', 'numeric', 'min:0.01', 'required_if:sales_mode,direct'],
            'stock_status' => ['nullable', Rule::in(['in_stock', 'out_of_stock', 'preorder']), 'required_if:sales_mode,direct'],
            'currency' => ['nullable', Rule::in(['UZS', 'USD', 'EUR']), 'required_if:sales_mode,direct'],
            'sales_mode' => ['nullable', Rule::in(['informational', 'external', 'direct'])],
            'external_purchase_url' => ['nullable', 'url', 'max:2048', 'required_if:sales_mode,external'],
            'instruction_file' => ['nullable', 'file', 'mimes:pdf', 'max:10240'],
            'faq_questions_uz' => ['nullable', 'array'],
            'faq_answers_uz' => ['nullable', 'array'],
            'faq_questions_ru' => ['nullable', 'array'],
            'faq_answers_ru' => ['nullable', 'array'],
            'faq_questions_en' => ['nullable', 'array'],
            'faq_answers_en' => ['nullable', 'array'],
            'related_products' => ['nullable', 'array', 'max:8'],
            'related_products.*' => ['integer', 'distinct', 'exists:products,id'],
            'medical_review_status' => ['nullable', Rule::in(['not_required', 'pending', 'approved'])],
            'medical_review_required' => ['nullable', 'boolean'],
            'seo_override' => ['nullable', 'boolean'],
            'seo_title_uz' => ['nullable', 'string', 'max:255'],
            'seo_title_ru' => ['nullable', 'string', 'max:255'],
            'seo_title_en' => ['nullable', 'string', 'max:255'],
            'meta_description_uz' => ['nullable', 'string', 'max:500'],
            'meta_description_ru' => ['nullable', 'string', 'max:500'],
            'meta_description_en' => ['nullable', 'string', 'max:500'],
            'canonical_url' => ['nullable', 'url', 'max:2048'],
            'robots' => ['nullable', Rule::in(['index,follow', 'noindex,follow'])],
            'og_image' => ['nullable', 'string', 'max:2048'],
            'schema_description_uz' => ['nullable', 'string', 'max:1000'],
            'schema_description_ru' => ['nullable', 'string', 'max:1000'],
            'schema_description_en' => ['nullable', 'string', 'max:1000'],
            'is_featured' => ['nullable', 'boolean'],
            'prescription' => ['nullable', 'boolean'],
            'status' => ['required', Rule::in(['active', 'draft', 'paused'])],
            'remove_images' => ['nullable', 'array'],
            'remove_images.*' => ['string', 'max:2048'],
        ];

        return $rules;
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            $requiresReview = $this->boolean('medical_review_required')
                || MedicalContent::requiresReview($this->all());

            if (
                $this->input('status') === 'active'
                && $requiresReview
                && $this->input('medical_review_status') !== 'approved'
            ) {
                $validator->errors()->add(
                    'medical_review_status',
                    'Tibbiy da’volar mavjud. Mahsulotni nashr qilishdan oldin review holatini “Tasdiqlangan” qiling.'
                );
            }
        });
    }
}
