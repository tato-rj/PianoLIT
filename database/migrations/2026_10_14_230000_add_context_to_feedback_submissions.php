<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddContextToFeedbackSubmissions extends Migration
{
    public function up()
    {
        Schema::table('feedback_submissions', function (Blueprint $table) {
            $table->json('context')->nullable();
        });
    }

    public function down()
    {
        Schema::table('feedback_submissions', function (Blueprint $table) {
            $table->dropColumn('context');
        });
    }
}
