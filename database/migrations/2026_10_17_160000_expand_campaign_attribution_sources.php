<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\{DB, Schema};

class ExpandCampaignAttributionSources extends Migration
{
    public function up()
    {
        Schema::table('campaign_visits', function (Blueprint $table) {
            $table->string('utm_source', 100)->default('');
            $table->string('utm_medium', 100)->default('');
            $table->string('utm_campaign', 100)->default('');
            $table->string('utm_content', 100)->default('');
            $table->index(['utm_source', 'created_at'], 'campaign_source_arrival');
        });
        // The original implementation accepted exactly this one campaign.
        // Preserve its visits and subscription links without relying on config.
        DB::table('campaign_visits')->where('campaign', 'respighi_notturno')->update([
            'utm_source' => 'youtube.com', 'utm_medium' => 'organic_video',
            'utm_campaign' => 'respighi_notturno', 'utm_content' => 'description',
        ]);
    }

    public function down()
    {
        Schema::table('campaign_visits', function (Blueprint $table) {
            $table->dropIndex('campaign_source_arrival');
            $table->dropColumn(['utm_source', 'utm_medium', 'utm_campaign', 'utm_content']);
        });
    }
}
