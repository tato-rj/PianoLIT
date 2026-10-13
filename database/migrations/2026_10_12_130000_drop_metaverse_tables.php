<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

class DropMetaverseTables extends Migration
{
    public function up()
    {
        Schema::dropIfExists('metaverse_events');
        Schema::dropIfExists('metaverse_locations');
    }

    public function down()
    {
        // Restore empty schemas for a code rollback; deleted records are not recoverable.
        if (!Schema::hasTable('metaverse_locations')) {
            require_once __DIR__.'/2022_06_20_231005_create_metaverse_locations_table.php';
            (new CreateMetaverseLocationsTable)->up();
        }

        if (!Schema::hasTable('metaverse_events')) {
            require_once __DIR__.'/2022_06_20_141032_create_metaverse_events_table.php';
            (new CreateMetaverseEventsTable)->up();
        }
    }
}
