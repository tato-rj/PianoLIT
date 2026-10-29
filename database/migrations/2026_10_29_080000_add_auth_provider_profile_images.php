<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddAuthProviderProfileImages extends Migration
{
    public function up()
    {
        foreach (['user_auth_providers', 'auth_link_challenges'] as $table) {
            Schema::table($table, function (Blueprint $table) {
                $table->text('profile_image_url')->nullable();
            });
        }
    }

    public function down()
    {
        foreach (['auth_link_challenges', 'user_auth_providers'] as $table) {
            if (Schema::hasColumn($table, 'profile_image_url')) {
                Schema::table($table, function (Blueprint $table) {
                    $table->dropColumn('profile_image_url');
                });
            }
        }
    }
}
