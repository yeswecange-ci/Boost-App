<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    // database/migrations/xxxx_create_boost_requests_table.php
public function up(): void
    {
        Schema::create('boost_requests', function (Blueprint $table) {
            $table->id();

            // Infos du post
            $table->string('post_id');
            $table->string('page_id');
            $table->string('page_name');
            $table->text('post_url')->nullable();
            $table->text('post_thumbnail')->nullable();
            $table->text('post_message')->nullable();

            // Paramètres du boost
            $table->date('start_date');
            $table->date('end_date');
            $table->decimal('budget', 10, 2);
            $table->string('currency')->default('XOF');
            $table->json('target'); // âge, genre, pays, intérêts

            // Statut
            $table->enum('status', [
                'draft',
                'pending_approval',
                'approved',
                'rejected',
                'creating',   // N8N en cours
                'created',    // N8N a créé, en pause
                'active',
                'paused',
                'completed',
                'failed'
            ])->default('draft');

            // Utilisateurs
            $table->foreignId('operator_id')->constrained('users');
            $table->foreignId('validator_id')->nullable()->constrained('users');

            // Rejet
            $table->text('rejection_reason')->nullable();

            // Meta IDs (renvoyés par N8N après création)
            $table->string('meta_campaign_id')->nullable();
            $table->string('meta_adset_id')->nullable();
            $table->string('meta_ad_id')->nullable();

            // Traces N8N
            $table->json('n8n_payload')->nullable();
            $table->json('n8n_response')->nullable();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('boost_requests');
    }
};
