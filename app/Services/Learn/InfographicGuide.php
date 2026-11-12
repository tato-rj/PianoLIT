<?php

namespace App\Services\Learn;

use App\Infograph\Infograph;

class InfographicGuide
{
    public static function forArtwork(Infograph $infograph): array
    {
        $guide = config('infographic-guides.'.$infograph->slug, []);

        return ($guide['image'] ?? null) === basename((string) $infograph->cover_path) ? $guide : [];
    }

    public static function description(Infograph $infograph): string
    {
        $guide = self::forArtwork($infograph);
        $description = trim((string) $infograph->description);

        return $description === ($guide['previous_description'] ?? null) ? $guide['description'] : $description;
    }
}
