<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateBlogPostViewCounts extends Migration
{
    public function up()
    {
        Schema::create('blog_post_view_counts', function (Blueprint $table) {
            $table->unsignedBigInteger('post_id')->primary();
            $table->foreign('post_id')->references('id')->on('posts')->onDelete('cascade');
            $table->string('slug');
            $table->unsignedBigInteger('views');
            $table->date('reported_through');
        });
    }

    public function down()
    {
        Schema::dropIfExists('blog_post_view_counts');
    }
}
