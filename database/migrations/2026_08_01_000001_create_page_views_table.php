<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('page_views', function (Blueprint $table) {
            $table->id();
            $table->uuid('event_id')->unique();
            $table->char('visitor_id', 64)->index();
            $table->char('session_id', 64)->index();
            $table->string('event_type', 40)->default('page_view')->index();
            $table->string('path', 500);
            $table->char('path_hash', 40)->index();
            $table->string('route_name', 120)->nullable()->index();
            $table->string('page_type', 40)->default('page')->index();
            $table->unsignedBigInteger('content_id')->nullable()->index();
            $table->string('locale', 5)->nullable()->index();
            $table->string('title')->nullable();
            $table->string('landing_path', 500)->nullable();
            $table->text('referrer_url')->nullable();
            $table->string('referrer_host')->nullable()->index();
            $table->string('source', 120)->nullable()->index();
            $table->string('medium', 80)->nullable();
            $table->string('campaign', 180)->nullable();
            $table->string('channel', 40)->default('direct')->index();
            $table->string('target_url', 1000)->nullable();
            $table->string('device_type', 24)->nullable()->index();
            $table->string('browser', 60)->nullable()->index();
            $table->string('operating_system', 60)->nullable();
            $table->string('country_code', 5)->nullable()->index();
            $table->string('city', 120)->nullable();
            $table->unsignedSmallInteger('screen_width')->nullable();
            $table->unsignedSmallInteger('screen_height')->nullable();
            $table->string('client_language', 32)->nullable();
            $table->string('timezone', 80)->nullable();
            $table->char('ip_hash', 64)->nullable();
            $table->text('user_agent')->nullable();
            $table->timestamp('occurred_at')->index();
            $table->timestamp('created_at')->nullable();

            $table->index(['page_type', 'content_id', 'occurred_at'], 'page_views_content_period_idx');
            $table->index(['channel', 'occurred_at'], 'page_views_channel_period_idx');
            $table->index(['event_type', 'occurred_at'], 'page_views_event_period_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('page_views');
    }
};
