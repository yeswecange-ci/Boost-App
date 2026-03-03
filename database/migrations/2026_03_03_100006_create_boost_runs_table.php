<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Ajouter post_master_id (nullable) à boost_requests pour lier au post canonique
        Schema::table('boost_requests', function (Blueprint $table) {
            $table->unsignedBigInteger('post_master_id')->nullable()->after('post_id');
            $table->foreign('post_master_id', 'fk_boost_requests_post_master')
                  ->references('id')->on('posts_master')
                  ->nullOnDelete();
        });

        // Table d'audit des boosts (un boost = une intention marketing)
        Schema::create('boost_runs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('boost_request_id');
            $table->unsignedBigInteger('post_master_id')->nullable();
            // run_id nullable : le sync run qui a identifié le post comme boostable
            $table->unsignedBigInteger('run_id')->nullable();
            $table->string('requested_by', 80)->nullable();
            $table->enum('status', ['DRAFT', 'PAUSED', 'ACTIVE', 'FAILED', 'CANCELLED'])
                  ->default('DRAFT');
            $table->integer('budget_total_cents');
            $table->char('currency', 3)->default('XOF');
            $table->integer('duration_days');
            $table->json('targeting_json')->nullable();
            $table->timestamps();

            $table->foreign('boost_request_id', 'fk_boost_runs_request')
                  ->references('id')->on('boost_requests')
                  ->cascadeOnDelete();

            $table->foreign('post_master_id', 'fk_boost_runs_post')
                  ->references('id')->on('posts_master')
                  ->nullOnDelete();

            $table->foreign('run_id', 'fk_boost_runs_run')
                  ->references('id')->on('sync_runs')
                  ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('boost_runs');

        Schema::table('boost_requests', function (Blueprint $table) {
            $table->dropForeign('fk_boost_requests_post_master');
            $table->dropColumn('post_master_id');
        });
    }
};
