<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateAppleMembershipsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('apple_memberships', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('plan')->nullable();
            $table->text('latest_receipt');
            $table->string('password');
            $table->json('latest_receipt_info')->nullable();
            $table->timestamp('renews_at')->nullable();
            $table->timestamp('validated_at')->nullable();
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
        Schema::dropIfExists('apple_memberships');
    }
}
