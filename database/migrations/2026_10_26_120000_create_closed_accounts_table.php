<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateClosedAccountsTable extends Migration
{
    public function up()
    {
        Schema::create('closed_accounts', function (Blueprint $table) {
            // Survives deletion, with no name, email, provider subject or credentials.
            // Existing purchases/activity continue to reference this retired ID.
            $table->unsignedBigInteger('user_id')->primary();
            $table->timestamp('signed_up_at')->nullable();
            $table->string('origin', 16)->nullable();
            $table->timestamp('closed_at')->index();
        });
    }

    public function down()
    {
        Schema::dropIfExists('closed_accounts');
    }
}
