<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateTimelineEventsTable extends Migration
{
    public function up()
    {
        // Shared curated library; legacy mobile timelines remain intact.
        Schema::create('timeline_events', function (Blueprint $table) {
            $table->bigIncrements('id');
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
            $table->string('source_id', 100)->unique();
            $table->string('source_identity', 40)->unique();
            $table->string('wikidata_id', 20);
            $table->string('event_kind', 20);
            $table->text('attribution');
            $table->timestamps();
            $table->index(['year', 'event_date']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('timeline_events');
    }
}
