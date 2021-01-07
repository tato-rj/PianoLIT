<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateEScoresTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('e_scores', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('slug');
            $table->string('title');
            $table->string('author')->nullable();
            $table->string('subtitle');
            $table->text('description');
            $table->string('pdf_path')->nullable();
            $table->string('audio_path')->nullable();
            $table->string('cover_path')->nullable();
            $table->string('mockup_path')->nullable();
            $table->text('previews')->nullable();
            $table->integer('score')->default(0);
            $table->unsignedInteger('pages_count')->default(0);
            $table->unsignedInteger('price')->default(0);
            $table->unsignedInteger('discount')->nullable();
            $table->timestamp('published_at')->nullable();
            $table->unsignedInteger('creator_id');
            $table->timestamps();
        });

        Schema::create('e_score_topics', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('slug');
            $table->string('name')->unique();
            $table->unsignedInteger('creator_id')->nullable();
            $table->timestamps();
        });

        Schema::create('e_score_e_score_topic', function (Blueprint $table) {
            $table->unsignedInteger('e_score_topic_id');
            $table->unsignedInteger('e_score_id');
            $table->primary(['e_score_topic_id', 'e_score_id']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('e_scores');
        Schema::dropIfExists('e_score_topic');
        Schema::dropIfExists('e_score_e_score_topic');
    }
}
