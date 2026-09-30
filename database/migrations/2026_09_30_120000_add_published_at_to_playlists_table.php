<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class AddPublishedAtToPlaylistsTable extends Migration
{
    public function up()
    {
        // Fresh installs have the column in the create-table migration; existing
        // installations may already have had it added manually.
        if (! Schema::hasColumn('playlists', 'published_at')) {
            Schema::table('playlists', function (Blueprint $table) {
                $table->timestamp('published_at')->nullable();
            });
        }

        // Keep playlists created before this control was introduced visible.
        // New playlists created through the admin remain unpublished by default.
        DB::table('playlists')->whereNull('published_at')->update(['published_at' => now()]);
    }

    public function down()
    {
        // Do not drop a column that may predate this migration or contain
        // editorial publication dates.
    }
}
