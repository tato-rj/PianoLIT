<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateDiscoverPreferences extends Migration
{
    public function up()
    {
        Schema::create('discover_preferences', function (Blueprint $table) {
            $table->unsignedBigInteger('user_id')->primary();
            $table->string('playing_level', 24);
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
        });
    }

    public function down()
    {
        Schema::dropIfExists('discover_preferences');
    }
}
