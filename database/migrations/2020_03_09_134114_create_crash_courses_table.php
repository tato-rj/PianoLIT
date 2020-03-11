<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateCrashCoursesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('crash_courses', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('slug');
            $table->unsignedInteger('creator_id');
            $table->string('title')->unique();
            $table->string('description');
            $table->string('cover_path')->nullable();
            $table->timestamp('published_at')->nullable();
            $table->timestamps();
        });

        Schema::create('crash_course_subscriptions', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('first_name');
            $table->unsignedInteger('subscriber_id');
            $table->unsignedInteger('crash_course_id');
            $table->unsignedInteger('last_sent_lesson_id')->nullable();
            $table->timestamp('last_sent_at')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamp('cancelled_at')->nullable();
            $table->timestamps();
        });

        Schema::create('crash_course_lessons', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedInteger('crash_course_id');
            $table->string('subject');
            $table->text('body');
            $table->unsignedInteger('views')->default(0);
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
        Schema::dropIfExists('crash_courses');
        Schema::dropIfExists('crash_course_subscription');
    }
}
