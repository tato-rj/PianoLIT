<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateShopOrders extends Migration
{
    public function up()
    {
        Schema::create('shop_orders', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('kind', 20)->default('merchandise');
            $table->char('active_key', 64)->nullable()->unique();
            $table->unsignedBigInteger('purchase_id')->nullable()->index();
            $table->char('owner_hash', 64);
            $table->unsignedBigInteger('user_id')->nullable()->index();
            $table->string('mode', 12);
            $table->unsignedBigInteger('printful_store_id');
            $table->string('status', 32)->default('quoted')->index();
            $table->char('bag_hash', 64);
            $table->json('items');
            $table->text('recipient'); // Encrypted email and delivery address.
            $table->char('currency', 3);
            $table->unsignedInteger('subtotal');
            $table->unsignedInteger('shipping');
            $table->unsignedInteger('tax');
            $table->unsignedInteger('total');
            $table->unsignedInteger('fulfillment_cost');
            $table->string('shipping_id', 100);
            $table->string('shipping_name');
            $table->string('stripe_tax_calculation')->nullable();
            $table->string('stripe_tax_transaction')->nullable()->unique();
            $table->string('stripe_payment_intent')->nullable()->unique();
            $table->string('printful_order_id')->nullable()->unique();
            $table->char('printful_external_id', 32)->unique();
            $table->timestamp('expires_at');
            $table->timestamp('paid_at')->nullable();
            $table->timestamp('submitted_at')->nullable();
            $table->unsignedInteger('attempts')->default(0);
            $table->string('last_error', 100)->nullable();
            $table->timestamps();
        });
    }

    public function down() { Schema::dropIfExists('shop_orders'); }
}
