<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateEmailLogsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('email_logs', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('message_id')->index();
            $table->string('list_id')->nullable();
            $table->morphs('sender');
            $table->string('recipient');
            $table->dateTime('delivered_at')->nullable();
            $table->tinyInteger('unique_delivered')->default(0);
            $table->dateTime('failed_at')->nullable();
            $table->tinyInteger('unique_failed')->default(0);
            $table->integer('opened')->default(0);
            $table->tinyInteger('unique_opened')->default(0);
            $table->integer('clicked')->default(0);
            $table->tinyInteger('unique_clicked')->default(0);
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
        Schema::dropIfExists('email_logs');
    }
}
