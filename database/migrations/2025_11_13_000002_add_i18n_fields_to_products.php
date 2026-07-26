<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->string('name_uz')->nullable()->after('name');
            $table->string('name_ru')->nullable()->after('name_uz');
            $table->string('type_uz')->nullable()->after('type');
            $table->string('type_ru')->nullable()->after('type_uz');
            $table->text('composition_uz')->nullable()->after('composition');
            $table->text('composition_ru')->nullable()->after('composition_uz');
            $table->text('description_uz')->nullable()->after('description');
            $table->text('description_ru')->nullable()->after('description_uz');
        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn([
                'name_uz', 'name_ru',
                'type_uz', 'type_ru',
                'composition_uz', 'composition_ru',
                'description_uz', 'description_ru',
            ]);
        });
    }
};


