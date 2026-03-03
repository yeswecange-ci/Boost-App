<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('facebook_posts', function (Blueprint $table) {
            $table->id();
            $table->string('post_id')->unique();
            $table->foreignId('facebook_page_id')
                  ->constrained('facebook_pages')
                  ->onDelete('cascade');
            $table->text('message');
            $table->text('thumbnail_url')->nullable();
            $table->text('permalink_url');
            $table->enum('type', ['photo', 'video', 'link', 'status'])->default('status');
            $table->unsignedBigInteger('impressions')->default(0);
            $table->timestamp('posted_at')->nullable();
            $table->timestamp('last_synced_at')->nullable();
            $table->timestamps();

            $table->index('facebook_page_id');
            $table->index('posted_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('facebook_posts');
    }
};
