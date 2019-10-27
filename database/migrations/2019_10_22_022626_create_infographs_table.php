<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateInfographsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('infographs', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('name');
            $table->string('slug');
            $table->string('description');
            $table->string('type');
            $table->string('cover_path')->nullable();
            $table->unsignedInteger('downloads')->default(0);
            $table->integer('score')->default(0);
            $table->unsignedInteger('creator_id');
            $table->timestamp('published_at')->nullable();
            $table->timestamp('giftable_at')->nullable();
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
        Schema::dropIfExists('infographs');
    }
}
