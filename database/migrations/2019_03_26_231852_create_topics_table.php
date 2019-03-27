<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateTopicsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('topics', function (Blueprint $table) {
            $table->increments('id');
            $table->string('type')->nullable();
            $table->string('name');
            $table->unsignedInteger('creator_id')->nullable();
            $table->timestamps();
        });

        Schema::create('post_topic', function (Blueprint $table) {
            $table->unsignedInteger('post_id');
            $table->unsignedInteger('topic_id');
            $table->primary(['post_id', 'topic_id']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('topics');
        Schema::dropIfExists('post_topic');
    }
}
