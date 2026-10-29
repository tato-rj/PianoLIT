<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddShopOrderTracking extends Migration
{
    public function up()
    {
        Schema::table('shop_orders', function (Blueprint $table) {
            $table->string('printful_status', 32)->nullable();
            $table->timestamp('tracking_checked_at')->nullable()->index();
            $table->string('tracking_error', 100)->nullable();
        });
        Schema::create('shop_shipments', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->uuid('order_id');
            $table->foreign('order_id')->references('id')->on('shop_orders');
            $table->string('printful_id', 32);
            $table->string('status', 24)->default('shipped');
            $table->string('carrier', 100)->nullable();
            $table->string('service', 150)->nullable();
            $table->string('tracking_number', 200)->nullable();
            $table->text('tracking_url')->nullable();
            $table->json('items');
            $table->timestamp('shipped_at')->nullable();
            $table->timestamp('delivered_at')->nullable();
            $table->date('estimated_from')->nullable();
            $table->date('estimated_to')->nullable();
            $table->json('events')->nullable();
            $table->timestamps();
            $table->unique(['order_id', 'printful_id']);
        });
        Schema::create('shop_order_emails', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->uuid('order_id');
            $table->foreign('order_id')->references('id')->on('shop_orders');
            $table->unsignedBigInteger('shipment_id')->nullable();
            $table->foreign('shipment_id')->references('id')->on('shop_shipments');
            $table->string('kind', 24);
            $table->string('delivery_key', 100)->unique();
            $table->string('status', 24)->default('pending')->index();
            $table->timestamp('claimed_at')->nullable();
            $table->timestamp('sent_at')->nullable();
            $table->string('last_error', 100)->nullable();
            $table->timestamps();
        });
        Schema::create('printful_webhook_receipts', function (Blueprint $table) {
            $table->char('id', 64)->primary();
            $table->uuid('order_id');
            $table->foreign('order_id')->references('id')->on('shop_orders');
            $table->string('type', 40);
            $table->timestamp('created_at');
        });
    }

    public function down()
    {
        Schema::dropIfExists('printful_webhook_receipts');
        Schema::dropIfExists('shop_order_emails');
        Schema::dropIfExists('shop_shipments');
        Schema::table('shop_orders', fn (Blueprint $table) => $table->dropColumn(['printful_status', 'tracking_checked_at', 'tracking_error']));
    }
}
