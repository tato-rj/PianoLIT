<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\{DB, Schema};

class AddMerchandiseProductDetails extends Migration
{
    public function up()
    {
        Schema::table('merchandise_products', function (Blueprint $table) {
            $table->string('printful_name')->nullable();
            $table->string('name_override')->nullable();
            $table->string('subtitle')->nullable();
            $table->text('description')->nullable();
        });
        DB::table('merchandise_products')->update(['printful_name' => DB::raw('name')]);
    }

    public function down()
    {
        Schema::table('merchandise_products', function (Blueprint $table) {
            $table->dropColumn(['printful_name', 'name_override', 'subtitle', 'description']);
        });
    }
}
