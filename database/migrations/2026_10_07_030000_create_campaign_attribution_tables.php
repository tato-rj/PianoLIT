<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateCampaignAttributionTables extends Migration
{
    public function up()
    {
        Schema::create('campaign_visits', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('campaign', 64)->index();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->timestamp('consented_at');
            $table->timestamp('expires_at');
            $table->timestamp('created_at')->index();
        });
        Schema::create('campaign_subscriptions', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->uuid('visit_id');
            $table->foreign('visit_id')->references('id')->on('campaign_visits')->onDelete('cascade');
            $table->string('stripe_subscription_id')->unique();
            $table->string('stripe_customer_id');
            $table->string('billing_account', 8);
            $table->string('plan', 64);
            $table->timestamp('trial_started_at')->nullable();
            $table->timestamp('subscribed_at');
        });
        // Minimal billing receipts, independent of campaign consent. No raw webhook
        // bodies or contact data. Invoice receipts also reconcile arrival order.
        Schema::create('stripe_payment_receipts', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('kind', 8);
            $table->string('stripe_object_id');
            $table->unique(['kind', 'stripe_object_id'], 'stripe_receipt_identity');
            $table->string('stripe_customer_id')->nullable();
            $table->string('stripe_subscription_id')->nullable()->index();
            $table->unsignedBigInteger('amount')->nullable();
            $table->string('currency', 3)->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->timestamp('created_at')->index();
        });
    }

    public function down()
    {
        Schema::dropIfExists('stripe_payment_receipts');
        Schema::dropIfExists('campaign_subscriptions');
        Schema::dropIfExists('campaign_visits');
    }
}
