<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddAdminUserDirectoryIndexes extends Migration
{
    public function up()
    {
        Schema::table('users', function (Blueprint $table) {
            $table->index(['created_at', 'id'], 'users_admin_created_at_id_index');
        });
        Schema::table('memberships', function (Blueprint $table) {
            $table->index('user_id', 'memberships_user_id_index');
        });
    }

    public function down()
    {
        Schema::table('memberships', function (Blueprint $table) {
            $table->dropIndex('memberships_user_id_index');
        });
        Schema::table('users', function (Blueprint $table) {
            $table->dropIndex('users_admin_created_at_id_index');
        });
    }
}
