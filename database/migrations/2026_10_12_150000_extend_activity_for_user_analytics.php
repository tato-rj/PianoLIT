<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

class ExtendActivityForUserAnalytics extends Migration
{
    public function up()
    {
        // Preserve original identities and times. Only confirmed browser authentication
        // may associate an anonymous visit with an account.
        Schema::create('activity_visit_links', function (Blueprint $table) {
            $table->uuid('visit_id')->primary();
            $table->unsignedBigInteger('user_id')->index();
            $table->dateTime('linked_at_utc', 3);
            $table->string('reason', 24);
            $table->foreign('visit_id')->references('id')->on('activity_visits')->onDelete('cascade');
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
        });
        Schema::table('activity_visits', function (Blueprint $table) {
            $table->dateTime('started_at_utc', 3)->nullable();
            $table->dateTime('last_activity_at_utc', 3)->nullable();
            $table->dateTime('last_interaction_at', 3)->nullable();
            $table->dateTime('last_interaction_at_utc', 3)->nullable();
            $table->index(['started_at', 'platform'], 'activity_visits_period_platform');
        });
        Schema::table('activity_pages', function (Blueprint $table) {
            $table->dateTime('opened_at_utc', 3)->nullable();
            $table->index(['visit_id', 'opened_at', 'id'], 'activity_pages_visit_order');
        });
        Schema::table('activity_events', function (Blueprint $table) {
            $table->dateTime('occurred_at_utc', 3)->nullable();
            $table->dateTime('received_at_utc', 3)->nullable();
            $table->index(['occurred_at', 'type'], 'activity_events_period_type');
            $table->index(['resource_type', 'resource_id', 'type'], 'activity_events_resource_type');
        });
    }

    public function down()
    {
        Schema::dropIfExists('activity_visit_links');
        if (DB::getDriverName() === 'sqlite') {
            // SQLite >= 3.35 supports native DROP COLUMN. Laravel 8 otherwise
            // delegates this to the incompatible legacy DBAL SQLite driver.
            foreach (['activity_events_period_type', 'activity_events_resource_type', 'activity_pages_visit_order', 'activity_visits_period_platform'] as $index) DB::statement('DROP INDEX '.$index);
            foreach (['activity_events' => ['occurred_at_utc', 'received_at_utc'], 'activity_pages' => ['opened_at_utc'], 'activity_visits' => ['started_at_utc', 'last_activity_at_utc', 'last_interaction_at', 'last_interaction_at_utc']] as $table => $columns) {
                foreach ($columns as $column) DB::statement('ALTER TABLE '.$table.' DROP COLUMN '.$column);
            }
            return;
        }
        Schema::table('activity_events', function (Blueprint $table) {
            $table->dropIndex('activity_events_period_type');
            $table->dropIndex('activity_events_resource_type');
            $table->dropColumn(['occurred_at_utc', 'received_at_utc']);
        });
        Schema::table('activity_pages', function (Blueprint $table) {
            $table->dropIndex('activity_pages_visit_order');
            $table->dropColumn('opened_at_utc');
        });
        Schema::table('activity_visits', function (Blueprint $table) {
            $table->dropIndex('activity_visits_period_platform');
            $table->dropColumn(['started_at_utc', 'last_activity_at_utc', 'last_interaction_at', 'last_interaction_at_utc']);
        });
    }
}
