<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('boost_campaigns', function (Blueprint $table) {
            $table->id();

            // Opérateur
            $table->unsignedBigInteger('user_id')->nullable();
            $table->foreign('user_id')->references('id')->on('users')->nullOnDelete();

            // Section Campaign
            $table->string('campaign_name');
            $table->string('campaign_objective', 100)->default('OUTCOME_TRAFFIC');
            $table->string('special_ad_categories', 50)->default('NONE');
            $table->string('campaign_status', 20)->default('PAUSED');
            $table->string('existing_campaign_id', 100)->nullable();

            // Section Ad Set
            $table->string('adset_name');
            $table->string('budget_type', 30)->default('lifetime_budget');
            $table->unsignedInteger('budget_value');         // montant en FCFA
            $table->unsignedInteger('duration_days')->default(7);
            $table->json('countries');                       // ["CI","SN"]
            $table->json('interests')->nullable();           // [{"id":"..."}]
            $table->string('optimization_goal', 50)->default('LINK_CLICKS');
            $table->string('billing_event', 50)->default('IMPRESSIONS');
            $table->string('bid_strategy', 50)->default('LOWEST_COST_WITHOUT_CAP');

            // Section Ad
            $table->string('ad_name');
            $table->string('post_id', 100);                 // PAGE_ID_POST_ID
            $table->string('ad_status', 20)->default('PAUSED');

            // Exécution
            $table->string('execution_status', 20)->default('pending'); // pending/running/done/error
            $table->string('meta_campaign_id', 100)->nullable();
            $table->string('meta_adset_id', 100)->nullable();
            $table->string('meta_ad_id', 100)->nullable();
            $table->text('error_message')->nullable();
            $table->timestamp('launched_at')->nullable();

            $table->timestamps();
        });

        // Ajouter n8n.webhook_campaign dans settings
        \DB::table('settings')->insertOrIgnore([
            'key'        => 'n8n.webhook_campaign',
            'value'      => null,
            'group'      => 'n8n',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('boost_campaigns');
        \DB::table('settings')->where('key', 'n8n.webhook_campaign')->delete();
    }
};
