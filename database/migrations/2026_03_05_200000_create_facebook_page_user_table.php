<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use App\Models\FacebookPage;
use App\Models\User;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('facebook_page_user', function (Blueprint $table) {
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('facebook_page_id')->constrained()->cascadeOnDelete();
            $table->timestamp('created_at')->useCurrent();
            $table->unique(['user_id', 'facebook_page_id']);
        });

        // Migrer les données existantes depuis users.page_ids (page_id externe → id DB)
        User::whereNotNull('page_ids')->get()->each(function (User $user) {
            $pageIds = is_array($user->page_ids) ? $user->page_ids : json_decode($user->page_ids, true);
            if (empty($pageIds)) return;
            $dbIds = FacebookPage::whereIn('page_id', $pageIds)->pluck('id');
            $user->facebookPages()->sync($dbIds);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('facebook_page_user');
    }
};
