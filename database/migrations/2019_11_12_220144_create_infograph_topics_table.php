<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateInfographTopicsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('infograph_topics', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('slug');
            $table->string('name')->unique();
            $table->unsignedInteger('creator_id')->nullable();
            $table->timestamps();
        });

        Schema::create('infograph_infograph_topic', function (Blueprint $table) {
            $table->unsignedInteger('topic_id');
            $table->unsignedInteger('infograph_id');
            $table->primary(['topic_id', 'infograph_id']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('infograph_topics');
        Schema::dropIfExists('infograph_infograph_topic');
    }
}
