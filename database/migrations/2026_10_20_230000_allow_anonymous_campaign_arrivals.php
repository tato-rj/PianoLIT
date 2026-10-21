<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\{DB, Schema};

class AllowAnonymousCampaignArrivals extends Migration
{
    public $withinTransaction = false;

    public function up()
    {
        $this->nullable(true);
    }

    public function down()
    {
        // Older code works with a nullable column. Retain it if narrowing the
        // schema would discard arrivals or require inventing consent.
        if (DB::table('campaign_visits')->whereNull('consented_at')->exists()) return;
        $this->nullable(false);
    }

    private function nullable($nullable)
    {
        Schema::registerCustomDoctrineType(\Illuminate\Database\DBAL\TimestampType::class, 'timestamp', 'timestamp');
        $alter = function () use ($nullable) {
            Schema::table('campaign_visits', function (Blueprint $table) use ($nullable) {
                $table->timestamp('consented_at')->nullable($nullable)->change();
            });
        };
        if (DB::getDriverName() !== 'sqlite') { $alter(); return; }
        // SQLite rebuilds the parent table: keep subscription children intact,
        // and restore FK enforcement even if the transactional rebuild fails.
        Schema::disableForeignKeyConstraints();
        try { DB::transaction($alter); }
        finally { Schema::enableForeignKeyConstraints(); }
    }
}
