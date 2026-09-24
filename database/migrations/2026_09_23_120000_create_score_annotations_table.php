<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateScoreAnnotationsTable extends Migration
{
    public function up()
    {
        Schema::create('score_annotations', function (Blueprint $table) {
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('piece_id');
            $table->char('score_key', 64);
            $table->unsignedInteger('revision')->default(0);
            $table->longText('marks');
            $table->timestamps();
            $table->primary(['user_id', 'piece_id', 'score_key']);
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('piece_id')->references('id')->on('pieces')->onDelete('cascade');
        });
    }

    public function down()
    {
        Schema::dropIfExists('score_annotations');
    }
}
