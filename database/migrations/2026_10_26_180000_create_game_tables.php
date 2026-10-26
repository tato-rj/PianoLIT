<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateGameTables extends Migration
{
    public function up()
    {
        Schema::create('game_scores', function (Blueprint $table) {
            $table->id();
            $table->string('game', 40);
            $table->string('username', 40);
            $table->string('avatar_url');
            $table->unsignedInteger('score');
            $table->unsignedSmallInteger('rounds');
            $table->unsignedInteger('duration');
            $table->decimal('accuracy', 5, 2);
            $table->unsignedInteger('finalScore');
            $table->timestamps();
            $table->index(['game', 'finalScore']);
            $table->index(['game', 'created_at']);
        });
        Schema::create('game_duels', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->char('code', 4);
            $table->char('join_code', 4)->nullable()->unique();
            $table->string('game', 40);
            $table->json('settings');
            $table->string('seed', 32);
            $table->string('status', 24)->index();
            $table->string('left_by', 5)->nullable();
            $table->unsignedInteger('revision')->default(0);
            $table->timestamp('starts_at', 3)->nullable();
            $table->timestamp('expires_at')->index();
            $table->timestamp('finished_at')->nullable();
            $table->timestamps();
        });
        Schema::create('game_duel_players', function (Blueprint $table) {
            $table->id();
            $table->foreignUuid('duel_id')->constrained('game_duels')->cascadeOnDelete();
            $table->string('role', 5);
            $table->char('token_hash', 64);
            $table->char('connection_id', 32)->nullable();
            $table->timestamp('departed_at')->nullable();
            $table->timestamp('ready_at')->nullable();
            $table->timestamp('rematch_at')->nullable();
            $table->unsignedSmallInteger('progress')->default(0);
            $table->unsignedInteger('score')->default(0);
            $table->unsignedInteger('sequence')->default(0);
            $table->json('result')->nullable();
            $table->json('checkpoint')->nullable();
            $table->uuid('answer_id')->nullable();
            $table->boolean('answer_correct')->nullable();
            $table->timestamp('answered_at', 3)->nullable();
            $table->timestamp('finished_at', 3)->nullable();
            $table->timestamp('last_seen_at')->nullable();
            $table->timestamps();
            $table->unique(['duel_id', 'role']);
        });
        Schema::create('game_duel_archives', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('duel_id')->index();
            $table->string('game', 40);
            $table->string('status', 24);
            $table->string('seed', 32);
            $table->timestamp('played_at')->index();
            $table->json('snapshot');
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('game_duel_archives');
        Schema::dropIfExists('game_duel_players');
        Schema::dropIfExists('game_duels');
        Schema::dropIfExists('game_scores');
    }
}
