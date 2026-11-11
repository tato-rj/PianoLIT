<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateCrashCourseEmailDesigns extends Migration
{
    public function up()
    {
        Schema::create('crash_course_email_designs', function (Blueprint $table) {
            $table->unsignedBigInteger('lesson_id')->primary();
            $table->json('design');
            $table->unsignedInteger('revision')->default(1);
            $table->timestamps();
            $table->foreign('lesson_id')->references('id')->on('crash_course_lessons')->onDelete('cascade');
        });
        Schema::create('crash_course_email_blocks', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('name', 80);
            $table->json('block');
            $table->unsignedInteger('creator_id');
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('crash_course_email_blocks');
        Schema::dropIfExists('crash_course_email_designs');
    }
}
