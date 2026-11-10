<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateBlogPostPromotions extends Migration
{
    public function up()
    {
        Schema::create('blog_post_promotions', function (Blueprint $table) {
            $table->unsignedBigInteger('post_id')->primary();
            $table->string('hub', 30);
            $table->foreign('post_id')->references('id')->on('posts')->onDelete('cascade');
        });
    }

    public function down()
    {
        Schema::dropIfExists('blog_post_promotions');
    }
}
