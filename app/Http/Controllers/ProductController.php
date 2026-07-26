<?php

namespace App\Http\Controllers;

use App\Http\Requests\ProductRequest;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Support\MedicalContent;
use App\Support\ProductMedia;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Lang;
use Illuminate\Support\Str;

class ProductController extends Controller
{
    private const SEO_FIELDS = [
        'seo_title_uz', 'seo_title_ru', 'seo_title_en',
        'meta_description_uz', 'meta_description_ru', 'meta_description_en',
        'canonical_url', 'robots', 'og_image',
        'schema_description_uz', 'schema_description_ru', 'schema_description_en',
    ];

    public function index(Request $request)
    {
        return redirect()->route('dashboard.products.index', $request->query());
    }

    public function create()
    {
        return view('products.create', [
            'productCategories' => ProductCategory::query()->orderBy('name_uz')->get(),
            'productOptions' => collect(),
        ]);
    }

    public function store(ProductRequest $request)
    {
        $validated = $this->prepareData($request, $request->validated());
        $this->storeImages($request, $validated);
        $this->storeInstruction($request, $validated);

        $product = Product::create($validated);
        $this->syncRelatedProducts($product, $request->input('related_products', []));

        return redirect()->route('dashboard.products.index')->with('success', 'Mahsulot muvaffaqiyatli qo‘shildi.');
    }

    public function edit(Product $product)
    {
        return view('products.edit', [
            'product' => $product->load('relatedProducts'),
            'productCategories' => ProductCategory::query()->orderBy('name_uz')->get(),
            'productOptions' => Product::query()
                ->where('id', '!=', $product->getKey())
                ->orderBy('name_uz')
                ->get(['id', 'name', 'name_uz']),
        ]);
    }

    public function update(ProductRequest $request, Product $product)
    {
        $validated = $this->prepareData($request, $request->validated(), $product);
        $this->updateImages($request, $product, $validated);

        $oldInstruction = $product->instruction_file;
        $this->storeInstruction($request, $validated);

        $product->update($validated);
        $this->syncRelatedProducts($product, $request->input('related_products', []));

        if (isset($validated['instruction_file']) && $oldInstruction && $oldInstruction !== $validated['instruction_file']) {
            $this->deletePublicAsset((string) $oldInstruction, ['instructions/']);
        }

        return redirect()->route('dashboard.products.index')->with('success', 'Mahsulot yangilandi.');
    }

    public function destroy(Product $product)
    {
        foreach (collect($product->images ?: [$product->image])->filter() as $image) {
            $this->deletePublicAsset((string) $image, ['products/']);
        }
        if ($product->instruction_file) {
            $this->deletePublicAsset((string) $product->instruction_file, ['instructions/']);
        }
        $product->delete();

        return redirect()->route('dashboard.products.index')->with('success', 'Mahsulot o‘chirildi.');
    }

    private function prepareData(ProductRequest $request, array $validated, ?Product $product = null): array
    {
        foreach (['uz', 'ru', 'en'] as $locale) {
            $validated['benefits_' . $locale] = collect($request->input('benefits_' . $locale, []))
                ->map(fn ($benefit) => trim((string) $benefit))
                ->filter()
                ->take(3)
                ->values()
                ->all();
            $validated['faqs_' . $locale] = $this->buildFaqs(
                $request->input('faq_questions_' . $locale, []),
                $request->input('faq_answers_' . $locale, [])
            );
        }

        $validated['name'] = trim((string) $validated['name_uz']);
        $validated['composition'] = $validated['composition_uz'] ?? ($product->composition ?? null);
        $validated['description'] = $validated['description_uz'] ?? ($product->description ?? null);
        $validated['form'] = $validated['form_uz'] ?? ($product->form ?? null);
        $validated['package'] = $validated['packaging_count_uz'] ?? ($product->package ?? null);
        $validated['manufacturer'] = trim((string) ($validated['manufacturer'] ?? '')) ?: (string) config('seo.site_name', 'NEO-LABS');
        $validated['country_of_origin'] = Str::upper((string) ($validated['country_of_origin'] ?? 'UZ'));

        if (!empty($validated['category_id'])) {
            $category = ProductCategory::find($validated['category_id']);
            if ($category) {
                foreach (['uz', 'ru', 'en'] as $locale) {
                    $field = 'type_' . $locale;
                    if (!$product || trim((string) $product->{$field}) === '') {
                        $validated[$field] = $category->{'name_' . $locale} ?: $category->name_uz;
                    }
                }
                if (!$product || trim((string) $product->type) === '') {
                    $validated['type'] = $category->name_uz;
                }
            }
        }

        foreach (['uz', 'ru', 'en'] as $locale) {
            $field = 'disclaimer_' . $locale;
            if (trim((string) ($validated[$field] ?? ($product->{$field} ?? ''))) === '') {
                $validated[$field] = Lang::get('content.product.disclaimer', [], $locale);
            }
        }

        $requiresReview = $request->boolean('medical_review_required') || MedicalContent::requiresReview($request->all());
        $reviewStatus = $validated['medical_review_status'] ?? ($product->medical_review_status ?? 'not_required');
        if ($requiresReview && $reviewStatus === 'not_required') {
            $reviewStatus = 'pending';
        }
        $validated['medical_review_status'] = $reviewStatus;
        $validated['medical_review_required'] = $requiresReview || in_array($reviewStatus, ['pending', 'approved'], true);
        $validated['is_featured'] = $request->boolean('is_featured');
        $validated['prescription'] = $request->boolean('prescription');
        $validated['seo_override'] = $request->boolean('seo_override');

        if (!$validated['seo_override']) {
            foreach (self::SEO_FIELDS as $field) {
                $validated[$field] = $field === 'robots' ? 'index,follow' : null;
            }
        } else {
            $validated['robots'] = $validated['robots'] ?? 'index,follow';
        }

        $completenessValues = [
            $validated['short_description_uz'] ?? ($product->short_description_uz ?? null),
            $validated['composition_uz'] ?? ($product->composition_uz ?? null),
            $validated['application_uz'] ?? ($product->application_uz ?? null),
            $validated['warnings_uz'] ?? ($product->warnings_uz ?? null),
        ];
        $validated['content_status'] = collect($completenessValues)
            ->every(fn ($value) => trim((string) $value) !== '') ? 'complete' : 'incomplete';

        return Arr::except($validated, [
            'faq_questions_uz', 'faq_answers_uz',
            'faq_questions_ru', 'faq_answers_ru',
            'faq_questions_en', 'faq_answers_en',
            'related_products', 'remove_images', 'images', 'instruction_file',
        ]);
    }

    private function storeImages(ProductRequest $request, array &$validated): void
    {
        $images = $this->moveUploadedImages($request, $validated['name_uz']);
        if ($images) {
            $validated['images'] = $images;
            $validated['image'] = $images[0];
        }
    }

    private function updateImages(ProductRequest $request, Product $product, array &$validated): void
    {
        $newImages = $this->moveUploadedImages($request, $validated['name_uz']);
        $existing = collect($product->images ?: [$product->image])->filter()->values();
        $removeImages = collect($request->input('remove_images', []))->map(fn ($item) => (string) $item);
        $keptImages = $existing->reject(fn ($item) => $removeImages->contains((string) $item))->values();

        foreach ($existing->diff($keptImages) as $removedImage) {
            $this->deletePublicAsset((string) $removedImage, ['products/']);
        }

        if ($newImages || $removeImages->isNotEmpty()) {
            $images = $keptImages->merge($newImages)->values()->all();
            $validated['images'] = $images;
            $validated['image'] = $images[0] ?? null;
        }
    }

    private function moveUploadedImages(ProductRequest $request, string $name): array
    {
        if (!$request->hasFile('images')) {
            return [];
        }

        $images = [];
        foreach ($request->file('images', []) as $file) {
            if (!$file || !$file->isValid()) {
                continue;
            }
            $ref = media_store($file, 'products');
            // Responsive/optimized variants only apply to locally-stored files.
            if (!Str::startsWith($ref, ['http://', 'https://'])) {
                ProductMedia::generateVariants($ref);
            }
            $images[] = $ref;
        }

        return $images;
    }

    private function buildFaqs(array $questions, array $answers): array
    {
        $faqs = [];
        foreach ($questions as $index => $question) {
            $question = trim((string) $question);
            $answer = trim((string) ($answers[$index] ?? ''));
            if ($question !== '' && $answer !== '') {
                $faqs[] = ['question' => $question, 'answer' => $answer];
            }
        }

        return $faqs;
    }

    private function storeInstruction(ProductRequest $request, array &$validated): void
    {
        if (!$request->hasFile('instruction_file')) {
            return;
        }

        $validated['instruction_file'] = media_store($request->file('instruction_file'), 'instructions');
    }

    private function syncRelatedProducts(Product $product, array $relatedIds): void
    {
        $ids = collect($relatedIds)
            ->map(fn ($id) => (int) $id)
            ->filter(fn ($id) => $id > 0 && $id !== (int) $product->getKey())
            ->unique()
            ->take(8)
            ->values()
            ->all();

        $product->relatedProducts()->sync($ids);
    }

    private function deletePublicAsset(string $path, array $allowedPrefixes): void
    {
        if (Str::startsWith($path, ['http://', 'https://'])) {
            media_delete($path);
            return;
        }

        $normalized = ltrim(str_replace('\\', '/', $path), '/');
        if (!collect($allowedPrefixes)->contains(fn ($prefix) => Str::startsWith($normalized, $prefix))) {
            return;
        }

        $absolute = public_path($normalized);
        if (is_file($absolute)) {
            @unlink($absolute);
        }

        if (Str::startsWith($normalized, 'products/')) {
            $stem = pathinfo($normalized, PATHINFO_FILENAME);
            foreach (glob(public_path('products/optimized/' . $stem . '-*.*')) ?: [] as $variant) {
                if (is_file($variant)) {
                    @unlink($variant);
                }
            }
        }
    }
}
