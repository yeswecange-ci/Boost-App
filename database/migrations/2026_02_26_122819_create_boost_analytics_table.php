<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    // database/migrations/xxxx_create_boost_analytics_table.php
    public function up(): void
    {
        Schema::create('boost_analytics', function (Blueprint $table) {
            $table->id();
            $table->foreignId('boost_request_id')->constrained()->onDelete('cascade');
            $table->date('date_snapshot');
            $table->integer('impressions')->default(0);
            $table->integer('reach')->default(0);
            $table->integer('clicks')->default(0);
            $table->decimal('spend', 10, 2)->default(0);
            $table->decimal('cpm', 8, 2)->default(0);
            $table->decimal('cpc', 8, 2)->default(0);
            $table->decimal('ctr', 5, 2)->default(0);
            $table->enum('fetched_from', ['meta_api', 'n8n'])->default('meta_api');
            $table->timestamps();

            $table->unique(['boost_request_id', 'date_snapshot']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('boost_analytics');
    }
};
