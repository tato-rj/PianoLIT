<?php

namespace App\Services\Collections;

use App\Playlist;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Cache;

class Catalog
{
    public function entries(): Collection
    {
        return Playlist::whereNull('group')->published()->has('pieces', '>', 5)
            ->select('playlists.*')->withCount('playablePieces as pieces_count')
            ->sorted()->orderBy('id')->get()
            ->filter(function (Playlist $playlist) { return $playlist->pieces_count >= 5; })->values();
    }

    public function artwork(Playlist $playlist): string
    {
        return $playlist->cover_image ?: asset('images/webapp/collections/featured.webp');
    }

    public function badge(Playlist $playlist): ?string
    {
        $tag = trim((string) $playlist->featured);
        return $tag === '' ? null : $tag;
    }

    public function weeklySelection(Collection $entries): array
    {
        if ($entries->isEmpty()) {
            return [null, []];
        }

        $ids = $entries->modelKeys();
        $count = min(3, count($ids));
        $key = 'webapp.collections.weekly-selection';
        $selection = Cache::get($key);

        // Cache only IDs so admin edits and publication eligibility stay current.
        if (! $selection || ! in_array($selection['featured'], $ids, true)
            || count($selection['inspiration']) !== $count
            || array_diff($selection['inspiration'], $ids)) {
            $selection = [
                'featured' => $entries->random()->getKey(),
                'inspiration' => $entries->random($count)->modelKeys(),
            ];
            Cache::put($key, $selection, now()->addWeek());
        }

        return [$selection['featured'], $selection['inspiration']];
    }
}
