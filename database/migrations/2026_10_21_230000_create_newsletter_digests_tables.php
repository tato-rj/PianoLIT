<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateNewsletterDigestsTables extends Migration
{
    public function up()
    {
        Schema::create('newsletter_digests', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('month', 7)->unique();
            $table->unsignedBigInteger('email_list_id');
            $table->json('content_keys');
            $table->timestamp('queued_at')->nullable();
            $table->timestamp('fanned_out_at')->nullable();
            $table->timestamps();
        });
        Schema::create('newsletter_deliveries', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('digest_id');
            $table->unsignedBigInteger('subscription_id');
            $table->timestamp('sent_at')->nullable();
            $table->timestamp('skipped_at')->nullable();
            $table->timestamp('created_at');
            $table->unique(['digest_id', 'subscription_id']);
            $table->foreign('digest_id')->references('id')->on('newsletter_digests')->onDelete('cascade');
        });
    }

    public function down()
    {
        Schema::dropIfExists('newsletter_deliveries');
        Schema::dropIfExists('newsletter_digests');
    }
}
