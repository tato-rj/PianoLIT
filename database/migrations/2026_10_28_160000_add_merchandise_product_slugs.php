<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\{DB, Schema};
use Illuminate\Support\Str;

class AddMerchandiseProductSlugs extends Migration
{
    public function up()
    {
        Schema::table('merchandise_products', function (Blueprint $table) {
            $table->string('slug')->nullable();
            $table->unique(['printful_store_id', 'slug'], 'merchandise_store_slug_unique');
        });
        // Include retired records so their URLs cannot be reassigned to a different product.
        DB::table('merchandise_products')->orderBy('id')->chunkById(100, function ($products) {
            foreach ($products as $product) {
                $base = rtrim(mb_substr(Str::slug(trim($product->name)), 0, 200), '-') ?: 'product';
                $slug = $base;
                $suffix = 2;
                while (DB::table('merchandise_products')->where('printful_store_id', $product->printful_store_id)->where('slug', $slug)->exists()) {
                    $slug = $base.'-'.$suffix++;
                }
                DB::table('merchandise_products')->where('id', $product->id)->update(['slug' => $slug]);
            }
        });
    }

    public function down()
    {
        Schema::table('merchandise_products', function (Blueprint $table) {
            $table->dropUnique('merchandise_store_slug_unique');
            $table->dropColumn('slug');
        });
    }
}
