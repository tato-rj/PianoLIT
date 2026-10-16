<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateAutomaticEmailSettingsTable extends Migration
{
    public function up()
    {
        Schema::create('automatic_email_settings', function (Blueprint $table) {
            $table->string('email_key', 64)->primary();
            $table->boolean('enabled');
            $table->unsignedBigInteger('updated_by_admin_id')->nullable();
            $table->timestamps();
            $table->foreign('updated_by_admin_id')->references('id')->on('admins')->onDelete('set null');
        });
    }

    public function down()
    {
        Schema::dropIfExists('automatic_email_settings');
    }
}
