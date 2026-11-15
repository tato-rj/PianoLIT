<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddAudioDurationToPieces extends Migration
{
    public function up()
    {
        Schema::table('pieces', function (Blueprint $table) {
            $table->unsignedInteger('audio_duration_seconds')->nullable();
        });
    }

    public function down()
    {
        Schema::table('pieces', function (Blueprint $table) {
            $table->dropColumn('audio_duration_seconds');
        });
    }
}
