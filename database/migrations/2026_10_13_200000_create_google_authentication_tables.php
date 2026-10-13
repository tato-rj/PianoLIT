<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateGoogleAuthenticationTables extends Migration
{
    public function up()
    {
        Schema::create('user_auth_providers', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('user_id');
            $table->string('provider', 20);
            $subject = $table->string('provider_user_id', 255);
            if (Schema::getConnection()->getDriverName() === 'mysql') $subject->charset('ascii')->collation('ascii_bin');
            $table->string('provider_email');
            // Keep the legacy NOT NULL password and mobile serialization intact.
            // A random undisclosed password is unusable; resets replace its hash.
            $table->string('unusable_password_hash')->nullable();
            $table->timestamps();
            $table->unique(['provider', 'provider_user_id'], 'auth_provider_identity_unique');
            $table->unique(['user_id', 'provider'], 'auth_provider_user_unique');
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
        });
        Schema::create('auth_link_challenges', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->unsignedBigInteger('user_id');
            $table->string('purpose', 20);
            $table->string('host');
            $table->char('browser_hash', 64);
            $table->string('account_email');
            $table->string('provider_user_id')->nullable();
            $table->string('provider_email')->nullable();
            $table->text('destination')->nullable();
            $table->char('email_token_hash', 64)->nullable();
            $table->timestamp('email_sent_at')->nullable();
            $table->timestamp('expires_at')->index();
            $table->timestamp('consumed_at')->nullable();
            $table->timestamps();
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
        });
        Schema::create('auth_browser_handoffs', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->unsignedBigInteger('user_id');
            $table->string('source_host');
            $table->string('target_host');
            $table->char('source_session_hash', 64);
            $table->char('target_session_hash', 64)->nullable();
            $table->char('token_hash', 64)->nullable();
            $table->text('destination');
            $table->timestamp('expires_at')->index();
            $table->timestamp('consumed_at')->nullable();
            $table->timestamps();
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
        });
        Schema::create('auth_provider_audits', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('user_id')->nullable();
            $table->string('action', 40);
            $table->string('provider', 20)->default('google');
            $table->timestamp('created_at')->index();
            $table->foreign('user_id')->references('id')->on('users')->onDelete('set null');
        });
    }

    public function down()
    {
        Schema::dropIfExists('auth_provider_audits');
        Schema::dropIfExists('auth_browser_handoffs');
        Schema::dropIfExists('auth_link_challenges');
        Schema::dropIfExists('user_auth_providers');
    }
}
