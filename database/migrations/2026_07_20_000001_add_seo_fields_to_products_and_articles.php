<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddSeoFieldsToProductsAndArticles extends Migration
{
    public function up()
    {
        Schema::table('products', function (Blueprint $table) {
            $table->string('seo_title_uz')->nullable();
            $table->string('seo_title_ru')->nullable();
            $table->text('meta_description_uz')->nullable();
            $table->text('meta_description_ru')->nullable();
            $table->string('canonical_url')->nullable();
            $table->string('robots')->default('index,follow');
            $table->string('og_image')->nullable();
            $table->text('schema_description_uz')->nullable();
            $table->text('schema_description_ru')->nullable();
            $table->string('sku')->nullable()->index();
            $table->string('country_of_origin')->nullable();
            $table->string('packaging_count_uz')->nullable();
            $table->string('packaging_count_ru')->nullable();
            $table->text('application_uz')->nullable();
            $table->text('application_ru')->nullable();
            $table->text('warnings_uz')->nullable();
            $table->text('warnings_ru')->nullable();
            $table->text('storage_conditions_uz')->nullable();
            $table->text('storage_conditions_ru')->nullable();
            $table->string('shelf_life_uz')->nullable();
            $table->string('shelf_life_ru')->nullable();
            $table->text('registration_info_uz')->nullable();
            $table->text('registration_info_ru')->nullable();
            $table->json('faqs_uz')->nullable();
            $table->json('faqs_ru')->nullable();
        });

        Schema::table('articles', function (Blueprint $table) {
            $table->string('seo_title_uz')->nullable();
            $table->string('seo_title_ru')->nullable();
            $table->text('meta_description_uz')->nullable();
            $table->text('meta_description_ru')->nullable();
            $table->string('canonical_url')->nullable();
            $table->string('robots')->default('index,follow');
            $table->string('og_image')->nullable();
            $table->text('schema_description_uz')->nullable();
            $table->text('schema_description_ru')->nullable();
            $table->string('author_name')->nullable();
            $table->string('author_role_uz')->nullable();
            $table->string('author_role_ru')->nullable();
            $table->string('author_slug')->nullable()->index();
            $table->string('reviewer_name')->nullable();
            $table->string('reviewer_role_uz')->nullable();
            $table->string('reviewer_role_ru')->nullable();
            $table->timestamp('reviewed_at')->nullable();
            $table->json('references_uz')->nullable();
            $table->json('references_ru')->nullable();
            $table->json('faqs_uz')->nullable();
            $table->json('faqs_ru')->nullable();
            $table->string('schema_type')->default('BlogPosting');
        });
    }

    public function down()
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn([
                'seo_title_uz', 'seo_title_ru', 'meta_description_uz', 'meta_description_ru',
                'canonical_url', 'robots', 'og_image', 'schema_description_uz', 'schema_description_ru',
                'sku', 'country_of_origin', 'packaging_count_uz', 'packaging_count_ru',
                'application_uz', 'application_ru', 'warnings_uz', 'warnings_ru',
                'storage_conditions_uz', 'storage_conditions_ru', 'shelf_life_uz', 'shelf_life_ru',
                'registration_info_uz', 'registration_info_ru', 'faqs_uz', 'faqs_ru',
            ]);
        });
        Schema::table('articles', function (Blueprint $table) {
            $table->dropColumn([
                'seo_title_uz', 'seo_title_ru', 'meta_description_uz', 'meta_description_ru',
                'canonical_url', 'robots', 'og_image', 'schema_description_uz', 'schema_description_ru',
                'author_name', 'author_role_uz', 'author_role_ru', 'author_slug', 'reviewer_name',
                'reviewer_role_uz', 'reviewer_role_ru', 'reviewed_at', 'references_uz',
                'references_ru', 'faqs_uz', 'faqs_ru', 'schema_type',
            ]);
        });
    }
}
