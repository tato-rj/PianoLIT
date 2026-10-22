<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\{DB, Schema};

class CreateMetronomeVoiceRecordingsTable extends Migration
{
    public function up()
    {
        Schema::create('metronome_voice_recordings', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->unsignedBigInteger('user_id')->index();
            $table->string('name', 60);
            $table->uuid('revision');
            $table->longText('clips');
            $table->timestamps();
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
        });

        // Keep the existing private recording intact when upgrading to a library.
        DB::table('metronome_voices')->orderBy('user_id')->chunkById(100, function ($rows) {
            foreach ($rows as $row) {
                // Insert one bounded audio set per query to avoid oversized packets.
                DB::table('metronome_voice_recordings')->insert(['id' => $row->revision, 'user_id' => $row->user_id, 'name' => 'My voice',
                    'revision' => $row->revision, 'clips' => $row->clips,
                    'created_at' => $row->created_at, 'updated_at' => $row->updated_at]);
            }
        }, 'user_id');
    }

    public function down()
    {
        Schema::dropIfExists('metronome_voice_recordings');
    }
}
