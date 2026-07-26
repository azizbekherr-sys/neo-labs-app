<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class NormalizeProductContent extends Migration
{
    public function up()
    {
        Schema::table('products', function (Blueprint $table) {
            $table->text('short_description_uz')->nullable();
            $table->text('short_description_ru')->nullable();
            $table->text('short_description_en')->nullable();
            $table->string('form_uz')->nullable();
            $table->string('form_ru')->nullable();
            $table->string('form_en')->nullable();
            $table->text('disclaimer_uz')->nullable();
            $table->text('disclaimer_ru')->nullable();
            $table->text('disclaimer_en')->nullable();
            $table->string('external_purchase_url', 2048)->nullable();
            $table->string('instruction_file', 2048)->nullable();
            $table->string('content_status', 32)->default('complete');
            $table->boolean('medical_review_required')->default(false);
            $table->boolean('is_featured')->default(false);
        });

        $products = DB::table('products')->orderBy('id')->get();
        $completedIds = [];
        foreach ($products as $product) {
            $short = [];
            foreach (['uz', 'ru', 'en'] as $locale) {
                $description = (string) ($product->{'description_' . $locale} ?? '');
                $plain = html_entity_decode(strip_tags(preg_replace('/\*\*(.+?)\*\*/su', '$1', $description)), ENT_QUOTES | ENT_HTML5, 'UTF-8');
                $plain = preg_replace('/[\x{1F000}-\x{1FAFF}\x{2600}-\x{27BF}]\x{FE0F}?/u', '', (string) $plain);
                $plain = trim((string) preg_replace('/\s+/u', ' ', (string) $plain));
                $short[$locale] = $plain === '' ? null : Str::limit($plain, 200, '…');
            }

            $hasContent = collect($short)->filter()->isNotEmpty();
            $claimText = mb_strtolower(implode(' ', [
                $product->type_uz ?? '', $product->type_ru ?? '', $product->type_en ?? '',
                $product->description_uz ?? '', $product->description_ru ?? '', $product->description_en ?? '',
            ]));
            $requiresReview = preg_match('/davola|oldini|og‘riq|ogʻriq|homilador|emizikli|samarali|xavfsiz|леч|профилакт|боль|беремен|кормящ|эффектив|безопас|treat|prevent|pain|pregnan|breastfeed|effective|safe|recommended|tavsiya|рекоменду/u', $claimText) === 1;

            DB::table('products')->where('id', $product->id)->update([
                'short_description_uz' => $short['uz'],
                'short_description_ru' => $short['ru'],
                'short_description_en' => $short['en'],
                'disclaimer_uz' => 'Biologik faol qo‘shimcha. Dori vositasi emas.',
                'disclaimer_ru' => 'Биологически активная добавка. Не является лекарственным средством.',
                'disclaimer_en' => 'Dietary supplement. Not a medicine.',
                'content_status' => $hasContent ? 'complete' : 'incomplete',
                'medical_review_required' => $requiresReview,
            ]);

            if ($hasContent) {
                $completedIds[] = $product->id;
            }
        }

        foreach (array_slice(array_reverse($completedIds), 0, 6) as $id) {
            DB::table('products')->where('id', $id)->update(['is_featured' => true]);
        }

        DB::table('products')->where('id', 4)->update([
            'name' => 'FERFOLAS — ichish uchun eritma',
            'name_uz' => 'FERFOLAS — ichish uchun eritma',
            'name_ru' => 'ФЕРФОЛАС — раствор для приема внутрь',
            'name_en' => 'FERFOLAS — oral solution',
            'form_uz' => 'Ichish uchun eritma',
            'form_ru' => 'Раствор для приема внутрь',
            'form_en' => 'Oral solution',
        ]);
        DB::table('products')->where('id', 5)->update([
            'name' => 'FERFOLAS — kapsulalar',
            'name_uz' => 'FERFOLAS — kapsulalar',
            'name_ru' => 'ФЕРФОЛАС — капсулы',
            'name_en' => 'FERFOLAS — capsules',
            'form_uz' => 'Kapsulalar',
            'form_ru' => 'Капсулы',
            'form_en' => 'Capsules',
        ]);
    }

    public function down()
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn([
                'short_description_uz', 'short_description_ru', 'short_description_en',
                'form_uz', 'form_ru', 'form_en',
                'disclaimer_uz', 'disclaimer_ru', 'disclaimer_en',
                'external_purchase_url', 'instruction_file', 'content_status',
                'medical_review_required', 'is_featured',
            ]);
        });
    }
}
