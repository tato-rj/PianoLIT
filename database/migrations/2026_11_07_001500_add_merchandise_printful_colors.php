<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddMerchandisePrintfulColors extends Migration
{
    public function up()
    {
        Schema::table('merchandise_images', function (Blueprint $table) {
            // Read-only provider labels, separate from uploaded gallery assignments.
            $table->json('printful_colors')->nullable();
        });
    }

    public function down()
    {
        Schema::table('merchandise_images', function (Blueprint $table) {
            $table->dropColumn('printful_colors');
        });
    }
}
