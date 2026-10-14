<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\{DB, Schema};

class SupportAppleAuthenticationProviders extends Migration
{
    public function up()
    {
        // Alter columns in place; retain the deployed tables, keys and data.
        Schema::table('user_auth_providers', function (Blueprint $table) {
            $table->string('provider_email')->nullable()->change();
        });
        // Added last: Apple stays disabled if the preceding change fails.
        if (!Schema::hasColumn('auth_link_challenges', 'provider')) Schema::table('auth_link_challenges', function (Blueprint $table) {
            $table->string('provider', 20)->nullable();
        });
        // NULL is deliberately retained for pending legacy Google challenges.
    }

    public function down()
    {
        if (!Schema::hasTable('user_auth_providers')) return;
        if (DB::table('user_auth_providers')->where('provider', 'apple')->exists()
            || DB::table('user_auth_providers')->whereNull('provider_email')->exists()
            || (Schema::hasColumn('auth_link_challenges', 'provider') && DB::table('auth_link_challenges')->where('provider', 'apple')->exists())) {
            throw new RuntimeException('Apple authentication records exist. Preserve them and roll back application code only after providing account recovery.');
        }
        if (Schema::hasColumn('auth_link_challenges', 'provider')) Schema::table('auth_link_challenges', function (Blueprint $table) { $table->dropColumn('provider'); });
        Schema::table('user_auth_providers', function (Blueprint $table) { $table->string('provider_email')->nullable(false)->change(); });
    }
}
