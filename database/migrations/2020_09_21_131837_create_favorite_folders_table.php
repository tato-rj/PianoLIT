<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateFavoriteFoldersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('favorite_folders', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedInteger('user_id');
            $table->string('name');
            $table->string('description')->nullable();
            $table->timestamps();
        });

        Schema::create('favorite_favorite_folder', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedInteger('favorite_folder_id');
            $table->unsignedInteger('favorite_id');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('favorite_folders');
        Schema::dropIfExists('favorite_favorite_folder');
    }
}
