<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('barcode')->nullable()->index();
            $table->string('manufacturer')->nullable();
            $table->string('form')->nullable();
            $table->string('dosage')->nullable();
            $table->string('package')->nullable();
            $table->decimal('price', 12, 2)->nullable();
            $table->unsignedTinyInteger('vat')->default(0);
            $table->unsignedInteger('stock')->default(0);
            $table->date('expires_at')->nullable();
            $table->boolean('prescription')->default(false);
            $table->enum('status', ['active', 'draft', 'paused'])->default('active');
            $table->string('image')->nullable();
            $table->text('description')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};


