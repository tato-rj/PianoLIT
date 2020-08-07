<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateEBooksTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('e_books', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('slug');
            $table->string('title');
            $table->string('subtitle');
            $table->text('description');
            $table->string('pdf_path')->nullable();
            $table->string('epub_path')->nullable();
            $table->string('cover_path')->nullable();
            $table->text('previews')->nullable();
            $table->integer('score')->default(0);
            $table->unsignedInteger('pages_count')->default(0);
            $table->unsignedInteger('price')->default(0);
            $table->unsignedInteger('discount')->nullable();
            $table->timestamp('published_at')->nullable();
            $table->unsignedInteger('creator_id');
            $table->timestamps();
        });

        Schema::create('e_book_topics', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('slug');
            $table->string('name')->unique();
            $table->unsignedInteger('creator_id')->nullable();
            $table->timestamps();
        });

        Schema::create('e_book_e_book_topic', function (Blueprint $table) {
            $table->unsignedInteger('e_book_topic_id');
            $table->unsignedInteger('e_book_id');
            $table->primary(['e_book_topic_id', 'e_book_id']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('e_books');
        Schema::dropIfExists('e_book_topic');
        Schema::dropIfExists('e_book_e_book_topic');
    }
}
