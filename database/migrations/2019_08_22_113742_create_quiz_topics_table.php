<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateQuizTopicsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('quiz_topics', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('slug');
            $table->string('name')->unique();
            $table->unsignedInteger('creator_id')->nullable();
            $table->timestamps();
        });

        Schema::create('quiz_quiz_topic', function (Blueprint $table) {
            $table->unsignedInteger('topic_id');
            $table->unsignedInteger('quiz_id');
            $table->primary(['topic_id', 'quiz_id']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('quiz_topics');
        Schema::dropIfExists('quiz_quiz_topic');
    }
}
