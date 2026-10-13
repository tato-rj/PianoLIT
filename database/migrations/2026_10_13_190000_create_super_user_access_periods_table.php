<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\{DB, Schema};

class CreateSuperUserAccessPeriodsTable extends Migration
{
    public function up()
    {
        Schema::create('super_user_access_periods', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('user_id')->index();
            // Null for closed periods; a unique key prevents two current grants.
            $table->unsignedBigInteger('current_user_id')->nullable()->unique();
            $table->timestamp('granted_at')->nullable();
            $table->unsignedBigInteger('granted_by')->nullable();
            $table->timestamp('first_observed_at');
            $table->timestamp('revoked_at')->nullable();
            $table->unsignedBigInteger('revoked_by')->nullable();
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('granted_by')->references('id')->on('admins')->onDelete('set null');
            $table->foreign('revoked_by')->references('id')->on('admins')->onDelete('set null');
        });

        // Record existing access as observed now, without inventing historical grant dates.
        DB::table('users')->where('super_user', true)->select('id')->orderBy('id')->chunkById(500, function ($users) {
            $observedAt = now();
            // Preserve a real grant captured by the admin toggle during deployment.
            DB::table('super_user_access_periods')->insertOrIgnore($users->map(function ($user) use ($observedAt) {
                return ['user_id' => $user->id, 'current_user_id' => $user->id, 'first_observed_at' => $observedAt];
            })->all());
        });
    }

    public function down()
    {
        Schema::dropIfExists('super_user_access_periods');
    }
}
