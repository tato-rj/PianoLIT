<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreatePieceTimelineEventsTable extends Migration
{
    public function up()
    {
        // Global timelines remain unchanged: they are still used by the mobile API.
        Schema::create('piece_timeline_events', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('piece_id');
            $table->unsignedInteger('year');
            $table->date('event_date')->nullable();
            $table->string('title');
            $table->text('description');
            $table->text('image_url')->nullable();
            $table->text('image_source_url')->nullable();
            $table->text('image_credit')->nullable();
            $table->string('image_license')->nullable();
            $table->text('image_license_url')->nullable();
            $table->text('source_url');
            $table->string('source_id', 100);
            $table->string('wikidata_id', 20);
            $table->string('event_kind', 20);
            $table->text('attribution');
            $table->timestamps();
            $table->unique(['piece_id', 'source_id']);
            $table->index(['piece_id', 'year']);
            $table->foreign('piece_id')->references('id')->on('pieces')->onDelete('cascade');
        });
    }

    public function down()
    {
        Schema::dropIfExists('piece_timeline_events');
    }
}
