<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddCatalogueRelationIndexes extends Migration
{
    public function up()
    {
        // Piece cards count these relations, and their media badges test existence.
        // Index the owning piece to avoid scanning an entire activity/media table
        // for each card. These are non-unique: existing relationship rules stay intact.
        foreach (['piece_views', 'favorites', 'tutorials', 'performances'] as $name) {
            Schema::table($name, function (Blueprint $table) {
                $table->index('piece_id');
            });
        }

        Schema::table('pieces', function (Blueprint $table) {
            $table->index('composer_id');
        });

        Schema::table('piece_tag', function (Blueprint $table) {
            // The existing primary key begins with piece_id; tag counts need
            // the opposite direction as well.
            $table->index(['tag_id', 'piece_id']);
        });
    }

    public function down()
    {
        Schema::table('piece_tag', function (Blueprint $table) {
            $table->dropIndex(['tag_id', 'piece_id']);
        });

        Schema::table('pieces', function (Blueprint $table) {
            $table->dropIndex(['composer_id']);
        });

        foreach (['performances', 'tutorials', 'favorites', 'piece_views'] as $name) {
            Schema::table($name, function (Blueprint $table) {
                $table->dropIndex(['piece_id']);
            });
        }
    }
}
