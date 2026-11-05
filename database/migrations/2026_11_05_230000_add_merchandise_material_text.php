<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddMerchandiseMaterialText extends Migration
{
    public function up()
    {
        Schema::table('merchandise_materials', function (Blueprint $table) {
            $table->text('description_text')->nullable();
        });
        Schema::table('merchandise_products', function (Blueprint $table) {
            $table->text('materials_override')->nullable();
        });
    }

    public function down()
    {
        Schema::table('merchandise_products', function (Blueprint $table) {
            $table->dropColumn('materials_override');
        });
        Schema::table('merchandise_materials', function (Blueprint $table) {
            $table->dropColumn('description_text');
        });
    }
}
