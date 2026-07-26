<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateCertificatesAndCompanyFactsTables extends Migration
{
    public function up()
    {
        Schema::create('certificates', function (Blueprint $table) {
            $table->id();
            $table->string('name_uz');
            $table->string('name_ru');
            $table->string('number')->nullable();
            $table->string('issuer_uz')->nullable();
            $table->string('issuer_ru')->nullable();
            $table->date('issued_at')->nullable();
            $table->date('expires_at')->nullable();
            $table->text('scope_uz')->nullable();
            $table->text('scope_ru')->nullable();
            $table->string('document_path')->nullable();
            $table->string('verification_url')->nullable();
            $table->boolean('is_published')->default(false)->index();
            $table->timestamps();
        });

        Schema::create('company_facts', function (Blueprint $table) {
            $table->id();
            $table->string('key')->unique();
            $table->string('label_uz');
            $table->string('label_ru');
            $table->text('value_uz');
            $table->text('value_ru');
            $table->string('source_url')->nullable();
            $table->string('document_path')->nullable();
            $table->boolean('is_published')->default(false)->index();
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('company_facts');
        Schema::dropIfExists('certificates');
    }
}
