<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreatePiecesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('pieces', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('name');
            $table->string('nickname')->nullable();
            $table->string('catalogue_name')->nullable();
            $table->string('catalogue_number')->nullable();
            $table->string('collection_name')->nullable();
            $table->string('collection_number')->nullable();
            $table->string('movement_number')->nullable();
            $table->string('key');
            $table->text('curiosity')->nullable();
            $table->string('audio_path')->nullable();
            $table->string('audio_path_rh')->nullable();
            $table->string('audio_path_lh')->nullable();
            $table->text('itunes')->nullable();
            $table->text('youtube')->nullable();
            $table->string('score_url')->nullable();
            $table->string('score_path')->nullable();
            $table->string('score_editor')->nullable();
            $table->string('score_publisher')->nullable();
            $table->string('score_copyright')->nullable();
            $table->unsignedInteger('composer_id');
            $table->unsignedInteger('composed_in')->nullable();
            $table->unsignedInteger('creator_id')->nullable();
            $table->unsignedInteger('performer_id')->nullable();
            $table->unsignedInteger('views')->default(0);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('pieces');
    }
}
