<?php

namespace App\Services\Discover;

use App\{Piece, User};
use App\Api\Api;
use App\Services\RecentlyViewedPieces;

/** Content selection shared by browser Discover and native v2; never legacy iOS. */
class Feed
{
    public function rows(?User $user, bool $personalize = true)
    {
        $api = (new Api)->for('webapp');
        $rows = $api->discover(false);
        $women = $rows->firstWhere('title', 'From women composers');
        $feature = $rows->firstWhere('title', $women['web_composer_feature'] ?? 'From women composers');
        // Additional candidate pools are displayed only through the original slot.
        $additionalTitles = ComposerFeatures::additionalTitles();
        $rows = $rows->reject(function ($row) use ($additionalTitles) {
            return in_array($row['title'], $additionalTitles, true);
        })->values()->map(function ($row) use ($api, $feature) {
            if ($row['title'] === 'From women composers' && $feature) return $feature;
            if ($row['title'] === 'From black composers') {
                $row['title'] = 'Past highlights';
                $row['content'] = Piece::freePicks()->free(false)->where('highlighted_at', '<=', now())
                    ->orderByDesc('id')->with('composer')->take(7)->get();
                $api->withAttributes($row['content'], ['type' => 'piece', 'source' => route('api.pieces.find')]);
            }
            return $row;
        });

        if ($user && $personalize) {
            $pieces = app(RecentlyViewedPieces::class)->forUser($user);
            $level = Recommendations::playingLevel($user);
            $selection = app(Recommendations::class)->forUser($user, $pieces, $level);
            $suggestions = $selection['pieces'];
            $api->order(3)->withAttributes($suggestions, ['type' => 'piece', 'source' => route('api.pieces.find')]);
            $rows->splice(3, 0, [['title' => 'For you', 'row' => 'gallery', 'type' => 'piece', 'content' => $suggestions,
                'playing_level' => $level, 'cohort' => $selection['cohort']]]);
            if ($pieces->isNotEmpty()) {
                $api->order(2)->withAttributes($pieces, ['type' => 'piece', 'source' => route('api.pieces.find')]);
                $position = $rows->search(function ($row) { return $row['title'] === 'Latest pieces'; });
                $rows->splice($position === false ? 2 : $position, 0, [[
                    'title' => 'Recently viewed', 'row' => 'gallery', 'type' => 'piece', 'content' => $pieces,
                ]]);
            }
        }

        // In-memory cache stores may return the same objects. Neither renderer
        // may add presentation fields to the shared cached catalogue models.
        return $rows->map(function ($row) {
            $row['content'] = collect($row['content'])->map(function ($item) {
                $item = clone $item;
                if ($item instanceof Piece) $item->discover_artwork_url = Artwork::forPiece($item);
                return $item;
            });
            return $row;
        });
    }
}
