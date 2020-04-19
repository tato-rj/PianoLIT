<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreatePlaylistsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('playlists', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedInteger('creator_id');
            $table->string('group')->nullable();
            $table->string('cover_path')->nullable();
            $table->string('name');
            $table->string('subtitle');
            $table->string('description');
            $table->string('featured')->nullable();
            $table->unsignedInteger('order')->default(0);
            $table->timestamps();
        });

        Schema::create('piece_playlist', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedInteger('piece_id');
            $table->unsignedInteger('playlist_id');
            $table->unsignedInteger('order')->nullable();
            $table->unique(['piece_id', 'playlist_id']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('playlists');
        Schema::dropIfExists('piece_playlist');
    }
}
