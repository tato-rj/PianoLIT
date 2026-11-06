<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateShopDigitalMedia extends Migration
{
    public function up()
    {
        Schema::create('shop_digital_media', function (Blueprint $table) {
            $table->id();
            $table->string('product_type', 80);
            $table->unsignedBigInteger('product_id');
            $table->unsignedInteger('revision')->default(0);
            $table->json('images');
            $table->string('main_image_id', 64)->nullable();
            $table->json('preview_pages')->nullable();
            $table->string('preview_source', 64)->nullable();
            $table->timestamps();
            $table->unique(['product_type', 'product_id']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('shop_digital_media');
    }
}
