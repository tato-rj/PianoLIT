<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateCrashCourseTopicsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('crash_course_topics', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('slug');
            $table->string('name');
            $table->unsignedInteger('creator_id')->nullable();
            $table->timestamps();
        });

        Schema::create('crash_course_crash_course_topic', function (Blueprint $table) {
            $table->unsignedInteger('crash_course_id');
            $table->unsignedInteger('topic_id');
            $table->primary(['crash_course_id', 'topic_id']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('crash_course_topics');
        Schema::dropIfExists('crash_course_crash_course_topic');
    }
}
