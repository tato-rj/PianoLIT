<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateRecentlyViewedPiecesTable extends Migration
{
    public function up()
    {
        Schema::create('recently_viewed_pieces', function (Blueprint $table) {
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('piece_id');
            $table->timestamp('viewed_at', 6);
            $table->primary(['user_id', 'piece_id']);
            $table->index(['user_id', 'viewed_at']);
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('piece_id')->references('id')->on('pieces')->onDelete('cascade');
        });
    }

    public function down()
    {
        Schema::dropIfExists('recently_viewed_pieces');
    }
}
