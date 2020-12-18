<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateReviewsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('reviews', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('user_id');
            $table->morphs('reviewable');
            $table->unsignedTinyInteger('rating');
            $table->string('title')->nullable();
            $table->string('content')->nullable();
            $table->boolean('anonymous')->default(0);
            $table->timestamp('published_at')->nullable();
            $table->timestamps();

            $table->unique(['user_id', 'reviewable_type', 'reviewable_id']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('reviews');
    }
}
