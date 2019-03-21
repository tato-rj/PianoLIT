<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateUsersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('users', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('first_name');
            $table->string('last_name');
            $table->string('occupation')->nullable();
            $table->unsignedInteger('preferred_piece_id')->nullable();
            $table->string('age_range')->nullable();
            $table->string('experience')->nullable();
            $table->string('email')->unique();
            // $table->timestamp('email_verified_at')->nullable();

            
            $table->string('locale')->default('en_US');
            $table->string('password');
            $table->rememberToken();
            $table->timestamp('trial_ends_at')->nullable();
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
        Schema::dropIfExists('users');
    }
}
