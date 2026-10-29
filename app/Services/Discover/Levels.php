<?php

namespace App\Services\Discover;

class Levels
{
    public static function groups($rows)
    {
        return collect([
            ['key' => 'rcm', 'label' => 'RCM', 'unit' => 'Level', 'title' => 'Equivalent to the RCM levels'],
            ['key' => 'abrsm', 'label' => 'ABRSM', 'unit' => 'Level', 'title' => 'Equivalent to the ABRSM levels'],
            ['key' => 'suzuki', 'label' => 'Suzuki', 'unit' => 'Book', 'title' => 'Equivalent to the Suzuki series'],
        ])->map(function ($system) use ($rows) {
            $row = collect($rows)->firstWhere('title', $system['title']);
            $system['cards'] = collect($row['content'] ?? [])->sortBy('name', SORT_NATURAL | SORT_FLAG_CASE)->values();
            return $system;
        });
    }
}
