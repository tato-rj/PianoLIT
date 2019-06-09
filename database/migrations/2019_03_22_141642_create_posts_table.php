<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreatePostsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('posts', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('slug');
            $table->unsignedInteger('creator_id');
            $table->string('title')->unique();
            $table->string('description');
            $table->text('content');
            $table->string('gift_path')->nullable();
            $table->string('cover_path')->nullable();
            $table->string('cover_credits')->nullable();
            $table->unsignedInteger('views')->default(0);
            $table->unsignedInteger('reading_time');
            $table->timestamp('published_at')->nullable();
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
        Schema::dropIfExists('posts');
    }
}
