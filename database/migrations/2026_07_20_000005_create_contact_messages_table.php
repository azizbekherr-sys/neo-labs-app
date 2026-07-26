<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateContactMessagesTable extends Migration
{
    public function up()
    {
        Schema::create('contact_messages', function (Blueprint $table) {
            $table->id();
            $table->string('context', 32)->default('general')->index();
            $table->string('name');
            $table->string('company')->nullable();
            $table->string('phone')->nullable();
            $table->string('contact')->nullable();
            $table->string('product_type')->nullable();
            $table->text('message');
            $table->string('locale', 5)->nullable()->index();
            $table->string('status', 24)->default('new')->index();
            $table->string('delivery_status', 24)->default('pending')->index();
            $table->string('source_url', 2048)->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->timestamp('read_at')->nullable();
            $table->timestamp('telegram_sent_at')->nullable();
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('contact_messages');
    }
}
