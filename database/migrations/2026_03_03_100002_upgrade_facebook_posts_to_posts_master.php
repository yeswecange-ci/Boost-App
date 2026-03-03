<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Renommer la table facebook_posts → posts_master
        Schema::rename('facebook_posts', 'posts_master');

        // Ajouter les colonnes SCD2 + statuts de boostabilité
        Schema::table('posts_master', function (Blueprint $table) {
            $table->enum('fb_status', ['FB_OK', 'FB_DELETED_OR_UNAVAILABLE', 'FB_ERROR'])
                  ->default('FB_OK')
                  ->after('last_synced_at');

            $table->dateTime('fb_last_checked_at')->nullable()->after('fb_status');
            $table->text('fb_last_error')->nullable()->after('fb_last_checked_at');

            $table->enum('business_status', ['ACTIVE', 'INACTIVE', 'ARCHIVED'])
                  ->default('ACTIVE')
                  ->after('fb_last_error');

            $table->tinyInteger('is_boostable')->default(1)->after('business_status');

            $table->unsignedBigInteger('last_sync_run_id')->nullable()->after('is_boostable');
            $table->foreign('last_sync_run_id', 'fk_posts_master_sync_run')
                  ->references('id')->on('sync_runs')
                  ->nullOnDelete();

            $table->index('fb_status', 'idx_fb_status');
            $table->index('business_status', 'idx_business_status');
            $table->index('is_boostable', 'idx_boostable');
        });
    }

    public function down(): void
    {
        Schema::table('posts_master', function (Blueprint $table) {
            $table->dropForeign('fk_posts_master_sync_run');
            $table->dropIndex('idx_fb_status');
            $table->dropIndex('idx_business_status');
            $table->dropIndex('idx_boostable');
            $table->dropColumn([
                'fb_status', 'fb_last_checked_at', 'fb_last_error',
                'business_status', 'is_boostable', 'last_sync_run_id',
            ]);
        });

        Schema::rename('posts_master', 'facebook_posts');
    }
};
