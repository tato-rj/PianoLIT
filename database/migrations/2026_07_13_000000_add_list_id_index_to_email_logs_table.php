<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddListIdIndexToEmailLogsTable extends Migration
{
    public function up()
    {
        Schema::table('email_logs', function (Blueprint $table) {
            $table->index('list_id');
        });
    }

    public function down()
    {
        Schema::table('email_logs', function (Blueprint $table) {
            $table->dropIndex(['list_id']);
        });
    }
}
