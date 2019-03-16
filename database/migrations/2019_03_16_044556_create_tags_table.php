<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateTagsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('tags', function (Blueprint $table) {
            $table->increments('id');
            $table->string('type')->nullable();
            $table->string('name');
            $table->unsignedInteger('creator_id')->nullable();
            $table->timestamps();
        });

        Schema::create('piece_tag', function (Blueprint $table) {
            $table->unsignedInteger('piece_id');
            $table->unsignedInteger('tag_id');
            $table->primary(['piece_id', 'tag_id']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('tags');
        Schema::dropIfExists('piece_tag');
    }
}
