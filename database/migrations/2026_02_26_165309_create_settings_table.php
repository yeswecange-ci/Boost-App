<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('settings', function (Blueprint $table) {
            $table->string('key')->primary();
            $table->text('value')->nullable();
            $table->string('group')->default('general'); // 'n8n', 'meta', 'general'
            $table->timestamps();
        });

        // Valeurs par défaut
        $now = now();
        DB::table('settings')->insert([
            // ── N8N ──────────────────────────────────────
            ['key' => 'n8n.mock_mode',        'value' => 'true',  'group' => 'n8n',  'created_at' => $now, 'updated_at' => $now],
            ['key' => 'n8n.webhook_create',   'value' => null,    'group' => 'n8n',  'created_at' => $now, 'updated_at' => $now],
            ['key' => 'n8n.webhook_activate', 'value' => null,    'group' => 'n8n',  'created_at' => $now, 'updated_at' => $now],
            ['key' => 'n8n.webhook_pause',    'value' => null,    'group' => 'n8n',  'created_at' => $now, 'updated_at' => $now],
            ['key' => 'n8n.secret',           'value' => null,    'group' => 'n8n',  'created_at' => $now, 'updated_at' => $now],
            ['key' => 'n8n.timeout',          'value' => '10',    'group' => 'n8n',  'created_at' => $now, 'updated_at' => $now],
            // ── Meta ─────────────────────────────────────
            ['key' => 'meta.mock_mode',       'value' => 'true',  'group' => 'meta', 'created_at' => $now, 'updated_at' => $now],
            ['key' => 'meta.app_id',          'value' => null,    'group' => 'meta', 'created_at' => $now, 'updated_at' => $now],
            ['key' => 'meta.app_secret',      'value' => null,    'group' => 'meta', 'created_at' => $now, 'updated_at' => $now],
            ['key' => 'meta.access_token',    'value' => null,    'group' => 'meta', 'created_at' => $now, 'updated_at' => $now],
            ['key' => 'meta.api_version',     'value' => 'v21.0', 'group' => 'meta', 'created_at' => $now, 'updated_at' => $now],
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('settings');
    }
};
