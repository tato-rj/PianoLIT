<?php

namespace App\Services\Discover;

use App\Piece;
use Illuminate\Support\Facades\Cache;

class Artwork
{
    public static function forPiece(Piece $piece): string
    {
        // Existing period fallbacks choose randomly. Pin that choice for a day
        // so web and native show the same artwork, including on repeated cards.
        $base = rtrim(config('app.url'), '/');
        if (!preg_match('#^https?://#i', $base)) $base = request()->getScheme().'://'.$base;
        $state = [$base, $piece->cover_path, $piece->updated_at, optional($piece->period)->name];
        $key = 'discover.artwork.v1:'.$piece->id.':'.hash('sha256', json_encode($state));
        return Cache::remember($key, days(1), function () use ($piece, $base) {
            $url = $piece->web_image_background;
            // Canonicalize same-application assets across the main/my hosts.
            $parts = parse_url($url);
            if (($parts['host'] ?? null) === request()->getHost()) {
                return $base.($parts['path'] ?? '').(isset($parts['query']) ? '?'.$parts['query'] : '');
            }
            return $url;
        });
    }
}
