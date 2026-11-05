<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddShopDisplayMockups extends Migration
{
    public function up()
    {
        foreach (['merchandise_products', 'e_books', 'e_scores'] as $table) {
            Schema::table($table, function (Blueprint $table) {
                $table->string('display_mockup_path')->nullable();
            });
        }
    }

    public function down()
    {
        foreach (['merchandise_products', 'e_books', 'e_scores'] as $table) {
            Schema::table($table, fn (Blueprint $table) => $table->dropColumn('display_mockup_path'));
        }
    }
}
