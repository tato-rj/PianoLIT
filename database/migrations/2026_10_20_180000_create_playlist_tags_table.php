<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\{DB, Schema};

class CreatePlaylistTagsTable extends Migration
{
    public function up()
    {
        Schema::create('playlist_tags', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('name');
            // Exact names remain distinct regardless of the database collation.
            $table->char('name_key', 64)->unique();
            $table->timestamps();
        });
        Schema::table('playlists', function (Blueprint $table) {
            $table->unsignedBigInteger('featured_tag_id')->nullable()->index();
            $table->foreign('featured_tag_id')->references('id')->on('playlist_tags')->onDelete('restrict');
        });

        DB::table('playlists')->whereNotNull('featured')->orderBy('id')->chunkById(200, function ($playlists) {
            foreach ($playlists as $playlist) {
                if (trim($playlist->featured) === '') continue;
                $key = hash('sha256', $playlist->featured);
                DB::table('playlist_tags')->insertOrIgnore([
                    'name' => $playlist->featured, 'name_key' => $key, 'created_at' => now(), 'updated_at' => now(),
                ]);
                $tagId = DB::table('playlist_tags')->where('name_key', $key)->value('id');
                // Keep the original featured bytes for unchanged legacy JSON.
                DB::table('playlists')->where('id', $playlist->id)->update(['featured_tag_id' => $tagId]);
            }
        });
    }

    public function down()
    {
        Schema::table('playlists', function (Blueprint $table) {
            // SQLite rebuilds the table when removing this column.
            if (DB::getDriverName() !== 'sqlite') $table->dropForeign(['featured_tag_id']);
            $table->dropColumn('featured_tag_id');
        });
        Schema::dropIfExists('playlist_tags');
    }
}
