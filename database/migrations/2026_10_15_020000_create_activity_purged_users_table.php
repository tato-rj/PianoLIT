<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateActivityPurgedUsersTable extends Migration
{
    public function up()
    {
        Schema::create('activity_purged_users', function (Blueprint $table) {
            // Deliberately survives account deletion; no email or activity payload.
            $table->unsignedInteger('user_id')->primary();
            $table->timestamp('purged_at');
        });
    }

    public function down()
    {
        Schema::dropIfExists('activity_purged_users');
    }
}
