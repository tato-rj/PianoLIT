<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddDeletedAtToMerchandiseProducts extends Migration
{
    public function up()
    {
        Schema::table('merchandise_products', function (Blueprint $table) {
            $table->softDeletes();
        });
    }

    public function down()
    {
        Schema::table('merchandise_products', function (Blueprint $table) {
            $table->dropSoftDeletes();
        });
    }
}
