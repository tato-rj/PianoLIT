<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateVideoMomentsTable extends Migration
{
    public function up()
    {
        Schema::create('video_moments', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('tutorial_id');
            $table->decimal('start_time', 10, 3);
            $table->decimal('end_time', 10, 3)->nullable();
            $table->string('title');
            $table->text('comment');
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
            $table->index(['tutorial_id', 'sort_order']);
            $table->foreign('tutorial_id')->references('id')->on('tutorials')->onDelete('cascade');
        });
    }

    public function down()
    {
        Schema::dropIfExists('video_moments');
    }
}
