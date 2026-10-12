<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateActivityHistoryTables extends Migration
{
    public function up()
    {
        // No users FK: legacy history can belong to accounts that were deleted.
        Schema::create('activity_visits', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->unsignedInteger('user_id')->nullable();
            $table->uuid('visitor_id');
            $table->string('platform', 16);
            $table->dateTime('started_at', 3);
            $table->dateTime('last_activity_at', 3);
            $table->index(['user_id', 'started_at']);
            $table->index(['visitor_id', 'started_at']);
        });
        Schema::create('activity_pages', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('visit_id')->index();
            $table->unsignedInteger('user_id')->nullable();
            $table->uuid('visitor_id');
            $table->string('platform', 16);
            $table->text('url');
            $table->string('route_name', 160)->nullable();
            $table->dateTime('opened_at', 3);
            $table->unsignedBigInteger('elapsed_ms')->default(0);
            $table->unsignedBigInteger('visible_ms')->default(0);
            $table->index(['user_id', 'opened_at']);
        });
        Schema::create('activity_events', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->uuid('event_id')->unique();
            $table->uuid('visit_id')->nullable()->index();
            $table->uuid('page_id')->nullable()->index();
            $table->unsignedInteger('user_id')->nullable();
            $table->uuid('visitor_id')->nullable();
            $table->string('platform', 16);
            $table->string('type', 64);
            $table->string('source', 16);
            $table->dateTime('occurred_at', 3)->nullable();
            $table->dateTime('received_at', 3);
            $table->unsignedBigInteger('elapsed_ms')->nullable();
            $table->unsignedInteger('sequence')->nullable();
            $table->text('url')->nullable();
            $table->string('route_name', 160)->nullable();
            $table->string('method', 10)->nullable();
            $table->unsignedSmallInteger('status')->nullable();
            $table->string('resource_type', 64)->nullable();
            $table->string('resource_id', 80)->nullable();
            $table->json('metadata')->nullable();
            $table->char('legacy_identity', 64)->nullable()->unique();
            $table->text('legacy_key')->nullable();
            $table->text('legacy_field')->nullable();
            $table->longText('legacy_payload')->nullable();
            $table->index(['user_id', 'occurred_at', 'id'], 'activity_user_time');
            $table->index(['visit_id', 'occurred_at', 'id'], 'activity_visit_time');
            $table->index(['platform', 'type', 'occurred_at'], 'activity_platform_type_time');
        });
        Schema::create('activity_imports', function (Blueprint $table) {
            $table->char('id', 64)->primary();
            $table->string('connection', 80);
            $table->text('prefix');
            $table->string('platforms', 40);
            $table->unsignedBigInteger('before');
            $table->string('cursor', 32)->default('0');
            $table->boolean('scan_started')->default(false);
            $table->json('pending_keys')->nullable();
            $table->text('current_key')->nullable();
            $table->string('hash_cursor', 32)->default('0');
            $table->boolean('complete')->default(false);
            $table->dateTime('updated_at');
        });
        Schema::create('activity_legacy_daily', function (Blueprint $table) {
            $table->char('id', 64)->primary();
            $table->date('date');
            $table->string('platform', 16);
            $table->unsignedBigInteger('total');
            $table->text('source_key');
            $table->dateTime('captured_at');
            $table->unique(['date', 'platform']);
        });
    }

    public function down()
    {
        foreach (['activity_legacy_daily', 'activity_imports', 'activity_events', 'activity_pages', 'activity_visits'] as $table) {
            Schema::dropIfExists($table);
        }
    }
}
