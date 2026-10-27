<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateGoogleAnalyticsConversions extends Migration
{
    public function up()
    {
        Schema::table('campaign_visits', function (Blueprint $table) {
            $table->text('google_context')->nullable(); // Encrypted browser GA IDs, never account identity.
        });
        Schema::table('campaign_subscriptions', function (Blueprint $table) {
            $table->boolean('google_live')->default(false);
        });
        Schema::create('campaign_checkout_contexts', function (Blueprint $table) {
            $table->uuid('operation_id')->primary();
            $table->foreign('operation_id')->references('id')->on('stripe_billing_operations')->onDelete('cascade');
            $table->uuid('visit_id');
            $table->foreign('visit_id')->references('id')->on('campaign_visits')->onDelete('cascade');
            $table->timestamp('linked_at')->nullable();
            $table->timestamp('created_at');
        });
        Schema::create('google_analytics_conversions', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->unsignedBigInteger('campaign_subscription_id');
            $table->foreign('campaign_subscription_id')->references('id')->on('campaign_subscriptions')->onDelete('cascade');
            $table->string('kind', 24);
            $table->unique(['campaign_subscription_id', 'kind'], 'ga_conversion_identity');
            $table->string('state', 24)->default('pending');
            $table->unsignedInteger('attempts')->default(0);
            $table->timestamp('available_at')->index();
            $table->timestamp('claimed_until')->nullable();
            $table->timestamp('sent_at')->nullable();
            $table->string('last_error', 64)->nullable();
            $table->text('payload')->nullable(); // Encrypted immutable delivery snapshot; no API secret.
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('google_analytics_conversions');
        Schema::dropIfExists('campaign_checkout_contexts');
        Schema::table('campaign_visits', function (Blueprint $table) { $table->dropColumn('google_context'); });
        Schema::table('campaign_subscriptions', function (Blueprint $table) { $table->dropColumn('google_live'); });
    }
}
