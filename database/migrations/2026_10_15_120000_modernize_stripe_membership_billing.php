<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class ModernizeStripeMembershipBilling extends Migration
{
    public function up()
    {
        Schema::table('stripe_memberships', function (Blueprint $table) {
            $table->string('subscription_id')->nullable()->unique();
            $table->string('billing_account', 8)->nullable();
            $table->string('payment_method_id')->nullable()->index();
            $table->string('pending_plan')->nullable();
            $table->timestamp('plan_changes_at')->nullable();
            $table->string('schedule_id')->nullable();
            $table->string('latest_invoice_id')->nullable();
            $table->text('payment_url')->nullable();
            $table->string('payment_status', 32)->nullable();
            $table->timestamp('synced_at')->nullable()->index();
            $table->index('stripe_id');
        });
        Schema::table('payments', function (Blueprint $table) {
            $table->string('currency', 3)->nullable();
            // Historical duplicates require review; do not delete financial history.
            $table->index('charge_id');
        });
        Schema::create('stripe_billing_profiles', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->unsignedBigInteger('user_id')->unique();
            $table->string('billing_account', 8);
            $table->string('customer_id')->nullable()->unique();
            $table->timestamps();
        });
        Schema::create('stripe_billing_operations', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->unsignedBigInteger('user_id')->index();
            $table->string('kind', 24);
            $table->string('active_key')->nullable()->unique();
            $table->string('billing_account', 8);
            $table->string('customer_id')->nullable();
            $table->string('subscription_id')->nullable()->index();
            $table->string('setup_intent_id')->nullable();
            $table->string('plan')->nullable();
            $table->string('coupon')->nullable();
            $table->string('state', 24)->default('pending');
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();
        });
        Schema::create('stripe_webhook_events', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('billing_account', 8);
            $table->string('event_id');
            $table->string('type');
            $table->text('payload');
            $table->unsignedInteger('attempts')->default(0);
            $table->string('last_error')->nullable();
            $table->timestamp('processed_at')->nullable()->index();
            $table->timestamps();
            $table->unique(['billing_account', 'event_id'], 'stripe_webhook_identity');
        });
    }

    public function down()
    {
        Schema::dropIfExists('stripe_webhook_events');
        Schema::dropIfExists('stripe_billing_operations');
        Schema::dropIfExists('stripe_billing_profiles');
        Schema::table('payments', function (Blueprint $table) {
            $table->dropIndex(['charge_id']);
            $table->dropColumn('currency');
        });
        Schema::table('stripe_memberships', function (Blueprint $table) {
            $table->dropUnique(['subscription_id']);
            $table->dropIndex(['stripe_id']);
            $table->dropIndex(['payment_method_id']);
            $table->dropIndex(['synced_at']);
            $table->dropColumn(['subscription_id', 'billing_account', 'payment_method_id', 'pending_plan', 'plan_changes_at', 'schedule_id', 'latest_invoice_id', 'payment_url', 'payment_status', 'synced_at']);
        });
    }
}
