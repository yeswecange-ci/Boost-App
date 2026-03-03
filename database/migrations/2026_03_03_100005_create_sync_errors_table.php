<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sync_errors', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('run_id');
            $table->string('post_id', 128)->nullable();
            $table->string('step', 80);
            $table->string('error_code', 40)->nullable();
            $table->text('error_message');
            $table->json('payload')->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->index('run_id', 'idx_err_run');
            $table->index('post_id', 'idx_err_post');

            $table->foreign('run_id', 'fk_sync_errors_run')
                  ->references('id')->on('sync_runs')
                  ->cascadeOnDelete()->cascadeOnUpdate();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sync_errors');
    }
};
