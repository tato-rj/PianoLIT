<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateMerchandiseMaterials extends Migration
{
    public function up()
    {
        Schema::create('merchandise_materials', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained('merchandise_products')->cascadeOnDelete();
            $table->unsignedBigInteger('sync_variant_id');
            $table->unsignedBigInteger('catalog_variant_id');
            $table->unsignedBigInteger('catalog_product_id');
            $table->string('color')->nullable();
            $table->string('size')->nullable();
            $table->json('composition');
            $table->timestamp('updated_at');
            $table->unique(['product_id', 'sync_variant_id'], 'merchandise_material_variant_unique');
        });
    }

    public function down()
    {
        Schema::dropIfExists('merchandise_materials');
    }
}
