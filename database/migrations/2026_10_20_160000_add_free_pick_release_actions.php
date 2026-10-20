<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\{DB, Schema};

class AddFreePickReleaseActions extends Migration
{
    public function up()
    {
        Schema::table('free_picks', function (Blueprint $table) {
            // Existing bookings never acquire an automatic email choice implicitly.
            $table->boolean('refresh_discover')->default(false);
            $table->boolean('send_email')->default(false);
            $table->dateTime('discover_refreshed_at')->nullable();
            $table->dateTime('email_queued_at')->nullable();
            $table->dateTime('release_completed_at')->nullable();
        });
        DB::table('free_picks')->whereNull('retired_at')->where('scheduled_at', '>', now('UTC'))
            ->update(['refresh_discover' => true]);
        Schema::create('free_pick_email_deliveries', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('free_pick_id');
            $table->unsignedBigInteger('subscription_id');
            $table->dateTime('sent_at')->nullable();
            $table->unique(['free_pick_id', 'subscription_id']);
            $table->foreign('free_pick_id')->references('id')->on('free_picks')->onDelete('cascade');
            $table->foreign('subscription_id')->references('id')->on('subscriptions')->onDelete('cascade');
        });
    }

    public function down()
    {
        Schema::dropIfExists('free_pick_email_deliveries');
        Schema::table('free_picks', function (Blueprint $table) {
            $table->dropColumn(['refresh_discover', 'send_email', 'discover_refreshed_at', 'email_queued_at', 'release_completed_at']);
        });
    }
}
