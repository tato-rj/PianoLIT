<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

class DropPieceTimelineEventsTable extends Migration
{
    public function up()
    {
        // The shared timeline_events library replaces piece-specific curation.
        Schema::dropIfExists('piece_timeline_events');
    }

    public function down()
    {
        // Restore the old schema for a code rollback; deleted content is not recoverable.
        if (!Schema::hasTable('piece_timeline_events')) {
            require_once __DIR__.'/2026_10_01_120000_create_piece_timeline_events_table.php';
            (new CreatePieceTimelineEventsTable)->up();
        }
    }
}
