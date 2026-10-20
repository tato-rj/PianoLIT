<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateMailgunEventReceiptsTable extends Migration
{
    public function up()
    {
        Schema::create('mailgun_event_receipts', function (Blueprint $table) {
            $table->char('event_key', 64)->primary();
            $table->char('token_hash', 64)->nullable()->unique();
            $table->unsignedBigInteger('email_log_id')->index();
            $table->string('event', 16);
            $table->dateTime('occurred_at');
            $table->timestamp('created_at');
        });
    }

    public function down()
    {
        Schema::dropIfExists('mailgun_event_receipts');
    }
}
