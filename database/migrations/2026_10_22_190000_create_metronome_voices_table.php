<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateMetronomeVoicesTable extends Migration
{
    public function up()
    {
        Schema::create('metronome_voices', function (Blueprint $table) {
            $table->unsignedBigInteger('user_id')->primary();
            $table->uuid('revision');
            // Four bounded PCM clips, kept private with the account and deleted
            // by the foreign key. No public files or mobile model attributes.
            $table->longText('clips');
            $table->timestamps();
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
        });
    }

    public function down()
    {
        Schema::dropIfExists('metronome_voices');
    }
}
