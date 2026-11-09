<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateApiV2FavoriteFolderReceipts extends Migration
{
    public function up()
    {
        Schema::create('api_v2_favorite_folder_receipts', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('user_id');
            $table->uuid('idempotency_key');
            $table->char('request_hash', 64);
            // Keep the receipt when a folder is deleted: retry must never recreate it.
            $table->unsignedBigInteger('folder_id');
            $table->timestamp('expires_at')->index();
            $table->timestamp('created_at');
            $table->unique(['user_id', 'idempotency_key'], 'v2_favorite_folder_receipt_owner_key');
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
        });
    }

    public function down()
    {
        Schema::dropIfExists('api_v2_favorite_folder_receipts');
    }
}
