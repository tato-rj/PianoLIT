<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddMerchandiseGalleryColors extends Migration
{
    public function up()
    {
        Schema::table('merchandise_images', function (Blueprint $table) {
            // Imports remain unassigned until a manager chooses their color.
            $table->string('gallery_color')->nullable();
        });
    }

    public function down()
    {
        Schema::table('merchandise_images', function (Blueprint $table) {
            $table->dropColumn('gallery_color');
        });
    }
}
