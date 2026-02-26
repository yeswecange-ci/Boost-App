<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Changer l'ENUM status en VARCHAR pour plus de flexibilité
        DB::statement("ALTER TABLE boost_requests MODIFY COLUMN status VARCHAR(50) NOT NULL DEFAULT 'draft'");

        // 2. Renommer les anciens statuts vers les nouveaux noms
        DB::statement("UPDATE boost_requests SET status = 'pending_n1'   WHERE status = 'pending_approval'");
        DB::statement("UPDATE boost_requests SET status = 'rejected_n1'  WHERE status = 'rejected'");
        DB::statement("UPDATE boost_requests SET status = 'paused_ready' WHERE status = 'created'");

        // 3. Ajouter les nouvelles colonnes
        Schema::table('boost_requests', function (Blueprint $table) {
            $table->string('sensitivity')->default('faible')->after('currency');
            $table->string('whatsapp_url', 500)->nullable()->after('sensitivity');
        });
    }

    public function down(): void
    {
        // Retirer les nouvelles colonnes
        Schema::table('boost_requests', function (Blueprint $table) {
            $table->dropColumn(['sensitivity', 'whatsapp_url']);
        });

        // Remettre les anciens noms de statuts
        DB::statement("UPDATE boost_requests SET status = 'pending_approval' WHERE status = 'pending_n1'");
        DB::statement("UPDATE boost_requests SET status = 'rejected'         WHERE status IN ('rejected_n1','rejected_n2')");
        DB::statement("UPDATE boost_requests SET status = 'created'          WHERE status = 'paused_ready'");

        // Restaurer l'ENUM d'origine
        DB::statement("ALTER TABLE boost_requests MODIFY COLUMN status ENUM('draft','pending_approval','approved','rejected','creating','created','active','paused','completed','failed') NOT NULL DEFAULT 'draft'");
    }
};
