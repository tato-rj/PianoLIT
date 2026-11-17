<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddScoreSourceToPieces extends Migration
{
    public function up()
    {
        Schema::table('pieces', function (Blueprint $table) {
            $table->string('score_source', 16)->nullable();
            $table->string('score_source_url', 2048)->nullable();
        });
    }

    public function down()
    {
        Schema::table('pieces', function (Blueprint $table) {
            $table->dropColumn(['score_source', 'score_source_url']);
        });
    }
}
