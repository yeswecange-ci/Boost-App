<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sync_runs', function (Blueprint $table) {
            $table->id();
            $table->string('source', 20)->default('facebook');
            $table->string('page_id', 64);
            $table->enum('status', ['RUNNING', 'FINISHED', 'FAILED'])->default('RUNNING');
            $table->dateTime('started_at')->useCurrent();
            $table->dateTime('finished_at')->nullable();
            $table->string('note', 255)->nullable();

            $table->index(['source', 'page_id'], 'idx_source_page');
            $table->index('status', 'idx_status');
            $table->index('started_at', 'idx_started_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sync_runs');
    }
};
