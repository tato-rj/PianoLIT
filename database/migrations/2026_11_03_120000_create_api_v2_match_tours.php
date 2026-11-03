<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateApiV2MatchTours extends Migration
{
    public function up()
    {
        Schema::create('api_v2_match_tours', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->unsignedBigInteger('user_id');
            $table->uuid('start_key');
            $table->unsignedInteger('revision')->default(0);
            $table->json('state');
            $table->timestamp('expires_at')->index();
            $table->timestamp('created_at');
            $table->unique(['user_id', 'start_key']);
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
        });
    }

    public function down()
    {
        Schema::dropIfExists('api_v2_match_tours');
    }
}
