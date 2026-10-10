<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class AddOrderingToTagsTable extends Migration
{
    public function up()
    {
        Schema::table('tags', function (Blueprint $table) {
            $table->unsignedSmallInteger('ordering')->nullable();
        });
    }

    public function down()
    {
        // Native DDL works on MySQL and the review suite's SQLite without DBAL.
        DB::statement('ALTER TABLE tags DROP COLUMN ordering');
    }
}
