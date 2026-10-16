<?php

namespace App\Support;

use Illuminate\Support\Facades\DB;

class FolderPalette
{
    // Names mirror $pastel-palette in resources/sass/shared/_tokens.scss.
    const COLORS = ['sky', 'cream', 'mint', 'lavender', 'peach', 'rose', 'aqua', 'butter', 'sage', 'lilac', 'periwinkle', 'terracotta'];

    public static function colorsFor($folders)
    {
        return $folders->values()->mapWithKeys(function ($folder, $index) {
            return [$folder->id => in_array($folder->web_color, self::COLORS, true)
                ? $folder->web_color : self::COLORS[$index % count(self::COLORS)]];
        });
    }

    public static function forUser($userId)
    {
        // The same database ordering as My Pieces, including folders made on mobile.
        return self::colorsFor(self::foldersForUser($userId));
    }

    public static function preserveExisting($userId)
    {
        $folders = self::foldersForUser($userId);
        $colors = self::colorsFor($folders);
        // Mobile can still create folders without a web color after deployment.
        // Freeze their displayed tones before a browser create/rename reorders them.
        foreach ($folders->whereNull('web_color')->groupBy(function ($folder) use ($colors) { return $colors->get($folder->id); }) as $color => $group) {
            DB::table('favorite_folders')->where('user_id', $userId)->whereNull('web_color')
                ->whereIn('id', $group->pluck('id'))->update(['web_color' => $color]);
        }

        return $colors;
    }

    private static function foldersForUser($userId)
    {
        return DB::table('favorite_folders')->where('user_id', $userId)
            ->orderBy('name')->orderBy('id')->get(['id', 'web_color']);
    }

    public static function suggest($colors)
    {
        $counts = array_fill_keys(self::COLORS, 0);
        foreach ($colors as $color) {
            if (isset($counts[$color])) $counts[$color]++;
        }
        // Use every available tone before repeating, then balance repetitions.
        $choices = array_keys($counts, min($counts), true);

        return $choices[array_rand($choices)];
    }
}
