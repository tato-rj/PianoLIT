<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateEmailCampaignReportsTable extends Migration
{
    public function up()
    {
        Schema::create('email_campaign_reports', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('list_id')->unique();
            $table->string('name')->index();
            $table->timestamp('sent_at')->index();
            $table->unsignedBigInteger('emails_count')->default(0);
            $table->unsignedBigInteger('delivered_count')->default(0);
            $table->unsignedBigInteger('failed_count')->default(0);
            $table->unsignedBigInteger('opens_count')->default(0);
            $table->unsignedBigInteger('clicks_count')->default(0);
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('email_campaign_reports');
    }
}
