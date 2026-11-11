<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreatePieceWebViewCounts extends Migration
{
    public function up()
    {
        Schema::create('piece_web_view_counts', function (Blueprint $table) {
            $table->unsignedBigInteger('piece_id')->primary();
            $table->foreign('piece_id')->references('id')->on('pieces')->onDelete('cascade');
            $table->unsignedBigInteger('views');
            $table->date('reported_through');
        });
    }

    public function down()
    {
        Schema::dropIfExists('piece_web_view_counts');
    }
}
