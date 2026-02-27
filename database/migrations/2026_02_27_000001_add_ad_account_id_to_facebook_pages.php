<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('facebook_pages', function (Blueprint $table) {
            // Identifiant du compte publicitaire Meta (act_XXXXXXXXX)
            // Requis dans le payload n8n pour créer les campagnes
            $table->string('ad_account_id')->nullable()->after('page_id');
        });
    }

    public function down(): void
    {
        Schema::table('facebook_pages', function (Blueprint $table) {
            $table->dropColumn('ad_account_id');
        });
    }
};
