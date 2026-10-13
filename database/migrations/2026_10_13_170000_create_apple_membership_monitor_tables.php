<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateAppleMembershipMonitorTables extends Migration
{
    public function up()
    {
        // Admin reporting only. Do not add fields to the mobile billing models.
        Schema::create('apple_membership_checks', function (Blueprint $table) {
            $table->unsignedBigInteger('apple_membership_id')->primary();
            $table->string('status', 24)->default('pending');
            $table->string('environment', 16)->nullable();
            $table->string('product_id')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->timestamp('grace_expires_at')->nullable();
            $table->boolean('auto_renews')->nullable();
            $table->timestamp('checked_at')->nullable();
            $table->timestamp('verified_at')->nullable();
            $table->timestamp('source_updated_at')->nullable();
            $table->string('input_hash', 64)->nullable();
            $table->string('error_code', 40)->nullable();
            $table->unsignedInteger('failures')->default(0);
            $table->timestamp('next_check_at')->nullable()->index();
        });

        Schema::create('apple_membership_sync_runs', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->timestamp('started_at');
            $table->timestamp('finished_at')->nullable();
            $table->unsignedInteger('checked')->default(0);
            $table->unsignedInteger('verified')->default(0);
            $table->unsignedInteger('failed')->default(0);
        });
    }

    public function down()
    {
        Schema::dropIfExists('apple_membership_sync_runs');
        Schema::dropIfExists('apple_membership_checks');
    }
}
