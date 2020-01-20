<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateEmailListsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('email_lists', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('name');
            $table->string('description');
            $table->timestamp('last_send_at')->nullable();
            $table->timestamps();
        });

        Schema::create('email_list_subscription', function (Blueprint $table) {
            $table->unsignedInteger('email_list_id');
            $table->unsignedInteger('subscription_id');
            $table->primary(['email_list_id', 'subscription_id']);
            $table->unique(['email_list_id', 'subscription_id']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('email_lists');
    }
}
