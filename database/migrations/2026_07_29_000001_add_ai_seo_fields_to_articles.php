<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Extra per-locale SEO fields populated by the "AI orqali SEO yaratish" step:
 * slug, focus keyword, keyword list, Open Graph title/description and the
 * article image ALT text. All nullable — existing rows are unaffected.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('articles', function (Blueprint $table) {
            foreach (['uz', 'ru', 'en'] as $loc) {
                if (!Schema::hasColumn('articles', "slug_{$loc}")) {
                    $table->string("slug_{$loc}")->nullable();
                }
                if (!Schema::hasColumn('articles', "focus_keyword_{$loc}")) {
                    $table->string("focus_keyword_{$loc}")->nullable();
                }
                if (!Schema::hasColumn('articles', "keywords_{$loc}")) {
                    $table->json("keywords_{$loc}")->nullable();
                }
                if (!Schema::hasColumn('articles', "og_title_{$loc}")) {
                    $table->string("og_title_{$loc}")->nullable();
                }
                if (!Schema::hasColumn('articles', "og_description_{$loc}")) {
                    $table->string("og_description_{$loc}", 500)->nullable();
                }
                if (!Schema::hasColumn('articles', "image_alt_{$loc}")) {
                    $table->string("image_alt_{$loc}")->nullable();
                }
            }
        });
    }

    public function down(): void
    {
        Schema::table('articles', function (Blueprint $table) {
            foreach (['uz', 'ru', 'en'] as $loc) {
                $table->dropColumn([
                    "slug_{$loc}",
                    "focus_keyword_{$loc}",
                    "keywords_{$loc}",
                    "og_title_{$loc}",
                    "og_description_{$loc}",
                    "image_alt_{$loc}",
                ]);
            }
        });
    }
};
