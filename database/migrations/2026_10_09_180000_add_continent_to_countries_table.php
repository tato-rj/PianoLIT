<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class AddContinentToCountriesTable extends Migration
{
    public function up()
    {
        Schema::table('countries', function (Blueprint $table) {
            $table->string('continent')->nullable();
        });
    }

    public function down()
    {
        // Native SQL works on MySQL and SQLite >= 3.35 without Laravel 8's
        // optional Doctrine DBAL dependency for SQLite column removal.
        DB::statement('ALTER TABLE countries DROP COLUMN continent');
    }
}
