<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class UpgradeProductExperience extends Migration
{
    public function up()
    {
        Schema::create('product_categories', function (Blueprint $table) {
            $table->id();
            $table->string('slug')->unique();
            $table->string('name_uz');
            $table->string('name_ru')->nullable();
            $table->string('name_en')->nullable();
            $table->timestamps();
        });

        $now = now();
        DB::table('product_categories')->insert([
            ['slug' => 'tablets', 'name_uz' => 'Tabletkalar', 'name_ru' => 'Таблетки', 'name_en' => 'Tablets', 'created_at' => $now, 'updated_at' => $now],
            ['slug' => 'capsules', 'name_uz' => 'Kapsulalar', 'name_ru' => 'Капсулы', 'name_en' => 'Capsules', 'created_at' => $now, 'updated_at' => $now],
            ['slug' => 'liquids', 'name_uz' => 'Suyuq shakllar', 'name_ru' => 'Жидкие формы', 'name_en' => 'Liquid forms', 'created_at' => $now, 'updated_at' => $now],
            ['slug' => 'powders', 'name_uz' => 'Kukun va sache', 'name_ru' => 'Порошки и саше', 'name_en' => 'Powders and sachets', 'created_at' => $now, 'updated_at' => $now],
            ['slug' => 'other', 'name_uz' => 'Boshqa mahsulotlar', 'name_ru' => 'Другие продукты', 'name_en' => 'Other products', 'created_at' => $now, 'updated_at' => $now],
        ]);

        Schema::table('products', function (Blueprint $table) {
            $table->unsignedBigInteger('category_id')->nullable()->index();
            $table->string('sales_mode', 24)->nullable()->index();
            $table->string('stock_status', 24)->nullable();
            $table->char('currency', 3)->nullable();
            $table->json('benefits_uz')->nullable();
            $table->json('benefits_ru')->nullable();
            $table->json('benefits_en')->nullable();
            $table->string('medical_review_status', 24)->default('not_required')->index();
            $table->boolean('seo_override')->default(false);
        });

        Schema::create('product_related', function (Blueprint $table) {
            $table->unsignedBigInteger('product_id');
            $table->unsignedBigInteger('related_product_id');
            $table->timestamps();
            $table->primary(['product_id', 'related_product_id']);
            $table->foreign('product_id')->references('id')->on('products')->cascadeOnDelete();
            $table->foreign('related_product_id')->references('id')->on('products')->cascadeOnDelete();
        });

        $categories = DB::table('product_categories')->pluck('id', 'slug');
        DB::table('products')->orderBy('id')->get()->each(function ($product) use ($categories) {
            $source = Str::lower(implode(' ', [
                $product->type ?? '',
                $product->type_uz ?? '',
                $product->type_ru ?? '',
                $product->type_en ?? '',
                $product->form ?? '',
                $product->form_uz ?? '',
                $product->form_ru ?? '',
                $product->form_en ?? '',
            ]));

            $category = 'other';
            if (Str::contains($source, ['tablet', 'таблет'])) {
                $category = 'tablets';
            } elseif (Str::contains($source, ['kapsul', 'capsul', 'капсул'])) {
                $category = 'capsules';
            } elseif (Str::contains($source, ['sirop', 'eritma', 'solution', 'syrup', 'раствор', 'сироп', 'flakon', 'bottle'])) {
                $category = 'liquids';
            } elseif (Str::contains($source, ['sache', 'саше', 'kukun', 'powder', 'порош'])) {
                $category = 'powders';
            }

            $hasSeoOverride = collect([
                $product->seo_title_uz ?? null,
                $product->seo_title_ru ?? null,
                $product->seo_title_en ?? null,
                $product->meta_description_uz ?? null,
                $product->meta_description_ru ?? null,
                $product->meta_description_en ?? null,
                $product->canonical_url ?? null,
                $product->og_image ?? null,
            ])->filter(fn ($value) => trim((string) $value) !== '')->isNotEmpty();

            DB::table('products')->where('id', $product->id)->update([
                'category_id' => $categories[$category] ?? null,
                'medical_review_status' => !empty($product->medical_review_required) ? 'pending' : 'not_required',
                'seo_override' => $hasSeoOverride,
            ]);
        });
    }

    public function down()
    {
        Schema::dropIfExists('product_related');

        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn([
                'category_id',
                'sales_mode',
                'stock_status',
                'currency',
                'benefits_uz',
                'benefits_ru',
                'benefits_en',
                'medical_review_status',
                'seo_override',
            ]);
        });

        Schema::dropIfExists('product_categories');
    }
}
