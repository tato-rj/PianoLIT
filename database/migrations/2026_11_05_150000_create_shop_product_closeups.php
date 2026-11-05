<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateShopProductCloseups extends Migration
{
    public function up()
    {
        Schema::create('shop_product_closeups', function (Blueprint $table) {
            $table->id();
            $table->string('product_type', 120);
            $table->unsignedBigInteger('product_id');
            $table->string('disk', 30)->default('public');
            $table->string('path')->nullable();
            $table->unsignedInteger('width')->nullable();
            $table->unsignedInteger('height')->nullable();
            $table->char('checksum', 64)->nullable();
            // Keep an empty versioned row after removal to reject stale editor submissions.
            $table->unsignedInteger('revision')->default(1);
            $table->timestamps();
            $table->unique(['product_type', 'product_id'], 'shop_product_closeup_owner_unique');
        });
    }

    public function down()
    {
        Schema::dropIfExists('shop_product_closeups');
    }
}
