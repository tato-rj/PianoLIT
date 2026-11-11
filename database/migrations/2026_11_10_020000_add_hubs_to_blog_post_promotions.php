<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddHubsToBlogPostPromotions extends Migration
{
    public function up()
    {
        Schema::table('blog_post_promotions', function (Blueprint $table) {
            $table->json('hubs')->nullable();
        });
    }

    public function down()
    {
        Schema::table('blog_post_promotions', function (Blueprint $table) {
            $table->dropColumn('hubs');
        });
    }
}
