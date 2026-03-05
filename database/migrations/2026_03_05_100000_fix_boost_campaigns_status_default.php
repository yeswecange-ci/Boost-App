<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('boost_campaigns', function (Blueprint $table) {
            $table->string('execution_status', 20)->default('draft')->change();
        });
    }

    public function down(): void
    {
        Schema::table('boost_campaigns', function (Blueprint $table) {
            $table->string('execution_status', 20)->default('pending')->change();
        });
    }
};
