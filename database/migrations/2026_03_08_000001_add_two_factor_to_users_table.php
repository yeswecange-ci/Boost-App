<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // Secret TOTP chiffré avec APP_KEY (AES-256-CBC via Laravel encrypt())
            $table->text('two_factor_secret')->nullable()->after('password');
            // Flag d'activation (la 2FA n'est active qu'après confirmation du premier code)
            $table->boolean('two_factor_enabled')->default(false)->after('two_factor_secret');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['two_factor_secret', 'two_factor_enabled']);
        });
    }
};
