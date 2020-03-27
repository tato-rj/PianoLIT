<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateComposersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('composers', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('name');
            $table->text('biography');
            $table->string('cover_path');
            $table->string('gender');
            $table->string('ethnicity')->nullable();
            $table->text('curiosity')->nullable();
            $table->string('period');
            $table->unsignedInteger('country_id')->nullable();
            $table->boolean('is_famous')->default(false);
            $table->boolean('is_pedagogical')->default(false);
            $table->date('date_of_birth')->nullable();
            $table->date('date_of_death')->nullable();
            $table->unsignedInteger('creator_id')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('composers');
    }
}
