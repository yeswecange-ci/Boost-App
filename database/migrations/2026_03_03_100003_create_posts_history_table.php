<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('posts_history', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('post_master_id');
            $table->string('type', 32)->nullable();
            $table->longText('message')->nullable();
            $table->string('permalink_url', 700)->nullable();
            $table->dateTime('created_time')->nullable();
            $table->string('full_picture', 1000)->nullable();
            $table->string('link_url', 1000)->nullable();
            $table->json('payload')->nullable();
            $table->char('row_hash', 64);
            $table->unsignedBigInteger('run_id');
            $table->tinyInteger('is_active')->default(1);
            $table->dateTime('valid_from')->useCurrent();
            $table->dateTime('valid_to')->nullable();
            $table->timestamps();

            $table->index(['post_master_id', 'is_active'], 'idx_ph_master_active');
            $table->index('run_id', 'idx_ph_run');
            $table->index(['valid_from', 'valid_to'], 'idx_ph_valid');

            $table->foreign('post_master_id', 'fk_posts_history_master')
                  ->references('id')->on('posts_master')
                  ->cascadeOnDelete()->cascadeOnUpdate();

            $table->foreign('run_id', 'fk_posts_history_run')
                  ->references('id')->on('sync_runs')
                  ->restrictOnDelete()->cascadeOnUpdate();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('posts_history');
    }
};
