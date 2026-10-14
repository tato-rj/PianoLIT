<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\{DB, Schema};

class AddWebColorToFavoriteFolders extends Migration
{
    public function up()
    {
        Schema::table('favorite_folders', function (Blueprint $table) {
            $table->string('web_color', 20)->nullable();
        });

        // Freeze the existing alphabetically assigned colors before new folders
        // or renames can change their positions. Leave folder timestamps intact.
        $colors = ['sky', 'cream', 'mint', 'lavender', 'peach', 'rose', 'aqua', 'butter', 'sage', 'lilac', 'periwinkle', 'terracotta'];
        $userId = null;
        $index = 0;
        DB::table('favorite_folders')->orderBy('user_id')->orderBy('name')->orderBy('id')
            ->select(['id', 'user_id'])->chunk(500, function ($folders) use ($colors, &$userId, &$index) {
                $assignments = [];
                foreach ($folders as $folder) {
                    if ($userId !== $folder->user_id) {
                        $userId = $folder->user_id;
                        $index = 0;
                    }
                    $assignments[$colors[$index++ % count($colors)]][] = $folder->id;
                }
                foreach ($assignments as $color => $ids) DB::table('favorite_folders')->whereIn('id', $ids)->update(['web_color' => $color]);
            });
    }

    public function down()
    {
        Schema::table('favorite_folders', function (Blueprint $table) {
            $table->dropColumn('web_color');
        });
    }
}
