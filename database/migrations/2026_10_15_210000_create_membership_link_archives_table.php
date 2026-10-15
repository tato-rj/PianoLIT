<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\{DB, Schema};

class CreateMembershipLinkArchivesTable extends Migration
{
    public function up()
    {
        Schema::create('membership_link_archives', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('membership_id')->unique();
            $table->unsignedInteger('user_id')->index();
            $table->unsignedBigInteger('kept_membership_id');
            $table->string('source_type');
            $table->unsignedBigInteger('source_id');
            $table->index(['source_type', 'source_id']);
            $table->timestamp('original_created_at')->nullable();
            $table->timestamp('original_updated_at')->nullable();
            $table->string('repair_version', 80);
            $table->char('plan_hash', 64);
            $table->json('evidence');
            $table->timestamp('archived_at');
        });
    }

    public function down()
    {
        if (DB::table('membership_link_archives')->exists()) {
            throw new \RuntimeException('Restore or preserve the membership link archive before rolling back this migration.');
        }
        Schema::dropIfExists('membership_link_archives');
    }
}
