<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\{DB, Schema};

class AddCampaignLandingUrl extends Migration
{
    public $withinTransaction = false;

    public function up()
    {
        Schema::table('campaign_visits', function (Blueprint $table) {
            $table->string('landing_url', 1024)->default('');
        });
    }

    public function down()
    {
        $drop = function () {
            Schema::table('campaign_visits', function (Blueprint $table) {
                $table->dropColumn('landing_url');
            });
        };
        if (DB::getDriverName() !== 'sqlite') { $drop(); return; }
        Schema::disableForeignKeyConstraints();
        try { DB::transaction($drop); }
        finally { Schema::enableForeignKeyConstraints(); }
    }
}
