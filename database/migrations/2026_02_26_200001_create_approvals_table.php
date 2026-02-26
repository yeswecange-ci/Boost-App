<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('approvals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('boost_request_id')->constrained('boost_requests')->onDelete('cascade');
            $table->string('level', 5);        // 'N1' ou 'N2'
            $table->string('action', 30);      // 'approved', 'rejected', 'changes_requested'
            $table->text('comment')->nullable();
            $table->foreignId('user_id')->constrained('users');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('approvals');
    }
};
