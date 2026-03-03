<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('post_media_history', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('post_master_id');
            $table->integer('position')->default(1);
            $table->enum('media_type', ['image', 'video', 'unknown'])->default('unknown');
            $table->string('source_url', 1200)->nullable();
            $table->string('preview_url', 1200)->nullable();
            $table->string('link_url', 1200)->nullable();
            $table->string('title', 255)->nullable();
            $table->json('payload')->nullable();
            $table->char('row_hash', 64);
            $table->unsignedBigInteger('run_id');
            $table->tinyInteger('is_active')->default(1);
            $table->dateTime('valid_from')->useCurrent();
            $table->dateTime('valid_to')->nullable();
            $table->timestamps();

            $table->index('post_master_id', 'idx_mh_master');
            $table->index(['post_master_id', 'is_active'], 'idx_mh_master_active');
            $table->index(['post_master_id', 'position'], 'idx_mh_pos');
            $table->index('run_id', 'idx_mh_run');

            $table->foreign('post_master_id', 'fk_media_history_master')
                  ->references('id')->on('posts_master')
                  ->cascadeOnDelete()->cascadeOnUpdate();

            $table->foreign('run_id', 'fk_media_history_run')
                  ->references('id')->on('sync_runs')
                  ->restrictOnDelete()->cascadeOnUpdate();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('post_media_history');
    }
};
