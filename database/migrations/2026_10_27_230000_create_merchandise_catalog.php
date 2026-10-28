<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateMerchandiseCatalog extends Migration
{
    public function up()
    {
        Schema::create('merchandise_products', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('printful_store_id');
            $table->unsignedBigInteger('printful_product_id');
            $table->string('name');
            $table->json('variants');
            // Ownership is enforced under the product lock when selecting images.
            $table->unsignedBigInteger('main_image_id')->nullable();
            $table->unsignedInteger('revision')->default(1);
            $table->timestamp('synced_at')->nullable();
            $table->timestamps();
            $table->unique(['printful_store_id', 'printful_product_id'], 'merchandise_printful_product_unique');
        });
        Schema::create('merchandise_images', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained('merchandise_products')->cascadeOnDelete();
            $table->string('disk', 30)->default('public');
            $table->string('path');
            $table->string('source', 20);
            $table->char('source_key', 64)->nullable();
            $table->char('checksum', 64);
            $table->string('alt');
            $table->unsignedSmallInteger('position');
            $table->unsignedInteger('width');
            $table->unsignedInteger('height');
            $table->timestamps();
            $table->softDeletes();
            $table->unique(['product_id', 'source_key']);
            $table->unique(['product_id', 'checksum']);
            $table->index(['product_id', 'deleted_at', 'position']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('merchandise_images');
        Schema::dropIfExists('merchandise_products');
    }
}
