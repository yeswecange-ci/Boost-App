<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Table pour stocker les entités Meta Ads créées (Campaign/AdSet/Ad)
        Schema::create('ads_entities', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('boost_run_id');
            $table->string('campaign_id', 64)->nullable();
            $table->string('adset_id', 64)->nullable();
            $table->string('ad_id', 64)->nullable();
            $table->string('campaign_status', 20)->nullable();
            $table->string('adset_status', 20)->nullable();
            $table->string('ad_status', 20)->nullable();
            $table->json('payload')->nullable();
            $table->timestamps();

            $table->foreign('boost_run_id', 'fk_ads_entities_boost')
                  ->references('id')->on('boost_runs')
                  ->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ads_entities');
    }
};
