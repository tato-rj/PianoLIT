<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateFeedbackSubmissionsTable extends Migration
{
    public function up()
    {
        Schema::create('feedback_submissions', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('user_id')->nullable();
            $table->string('rating', 16);
            $table->text('message')->nullable();
            $table->timestamps();
            $table->index(['rating', 'created_at']);
            $table->index('created_at');
            $table->foreign('user_id')->references('id')->on('users')->onDelete('set null');
        });
    }

    public function down()
    {
        Schema::dropIfExists('feedback_submissions');
    }
}
