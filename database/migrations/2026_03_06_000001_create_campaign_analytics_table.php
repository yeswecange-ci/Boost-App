<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('campaign_analytics', function (Blueprint $table) {
            $table->id();
            $table->foreignId('boost_campaign_id')->constrained()->cascadeOnDelete();
            $table->date('date_snapshot');
            $table->unsignedBigInteger('impressions')->default(0);
            $table->unsignedBigInteger('reach')->default(0);
            $table->unsignedBigInteger('clicks')->default(0);
            $table->decimal('spend', 12, 2)->default(0);
            $table->decimal('cpm',    8, 2)->default(0); // coût pour 1 000 impressions
            $table->decimal('cpc',    8, 2)->default(0); // coût par clic
            $table->decimal('ctr',    6, 4)->default(0); // taux de clic (%)
            $table->timestamps();

            $table->unique(['boost_campaign_id', 'date_snapshot']);
            $table->index('date_snapshot');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('campaign_analytics');
    }
};
