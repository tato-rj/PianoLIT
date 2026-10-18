<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateWebMetronomePreferencesTable extends Migration
{
    public function up()
    {
        Schema::create('web_metronome_preferences', function (Blueprint $table) {
            $table->unsignedBigInteger('user_id')->primary();
            $table->unsignedSmallInteger('tempo')->default(60);
            $table->unsignedTinyInteger('beats')->default(1);
            $table->string('sound', 24)->default('woodblock');
            $table->timestamps();
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
        });
    }

    public function down()
    {
        Schema::dropIfExists('web_metronome_preferences');
    }
}
