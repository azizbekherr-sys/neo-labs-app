<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddEnglishLocalizationFields extends Migration
{
    public function up()
    {
        Schema::table('products', function (Blueprint $table) {
            $table->string('name_en')->nullable();
            $table->string('type_en')->nullable();
            $table->text('composition_en')->nullable();
            $table->longText('description_en')->nullable();
            $table->string('seo_title_en')->nullable();
            $table->text('meta_description_en')->nullable();
            $table->text('schema_description_en')->nullable();
            $table->string('packaging_count_en')->nullable();
            $table->text('application_en')->nullable();
            $table->text('warnings_en')->nullable();
            $table->text('storage_conditions_en')->nullable();
            $table->string('shelf_life_en')->nullable();
            $table->text('registration_info_en')->nullable();
            $table->json('faqs_en')->nullable();
        });

        Schema::table('articles', function (Blueprint $table) {
            $table->string('title_en')->nullable();
            $table->longText('description_en')->nullable();
            $table->string('seo_title_en')->nullable();
            $table->text('meta_description_en')->nullable();
            $table->text('schema_description_en')->nullable();
            $table->string('author_role_en')->nullable();
            $table->string('reviewer_role_en')->nullable();
            $table->json('references_en')->nullable();
            $table->json('faqs_en')->nullable();
        });

        Schema::table('certificates', function (Blueprint $table) {
            $table->string('name_en')->nullable();
            $table->string('issuer_en')->nullable();
            $table->text('scope_en')->nullable();
        });

        Schema::table('company_facts', function (Blueprint $table) {
            $table->string('label_en')->nullable();
            $table->text('value_en')->nullable();
        });
    }

    public function down()
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn([
                'name_en', 'type_en', 'composition_en', 'description_en', 'seo_title_en',
                'meta_description_en', 'schema_description_en', 'packaging_count_en',
                'application_en', 'warnings_en', 'storage_conditions_en', 'shelf_life_en',
                'registration_info_en', 'faqs_en',
            ]);
        });
        Schema::table('articles', function (Blueprint $table) {
            $table->dropColumn([
                'title_en', 'description_en', 'seo_title_en', 'meta_description_en',
                'schema_description_en', 'author_role_en', 'reviewer_role_en',
                'references_en', 'faqs_en',
            ]);
        });
        Schema::table('certificates', function (Blueprint $table) {
            $table->dropColumn(['name_en', 'issuer_en', 'scope_en']);
        });
        Schema::table('company_facts', function (Blueprint $table) {
            $table->dropColumn(['label_en', 'value_en']);
        });
    }
}
