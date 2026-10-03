<?php

namespace App\Services\WebApp;

use App\Playlist;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

class Collections
{
    public function data()
    {
        // Keep mobile eligibility/count semantics, without invoking the API's
        // shared ordering cache, which can write playlist order on a page visit.
        $playlists = Playlist::whereNull('group')->published()->has('pieces', '>', 5)
            ->select('playlists.*')
            ->withCount(['pieces' => function ($query) { $query->has('tutorials'); }])
            ->sorted()->orderBy('id')->get()
            ->filter(function ($playlist) { return $playlist->pieces_count >= 5; })
            ->map(function ($playlist) {
                $key = Str::slug(str_replace(["'", '’'], '', $playlist->name));

                return [
                    'playlist' => $playlist,
                    'key' => $key,
                    'image' => $playlist->cover_path ? $playlist->cover_image : asset('images/webapp/collections/featured.webp'),
                    'illustrated' => ! $playlist->cover_path,
                    'category' => config('collections.categories.'.$key, 'other'),
                ];
            })->values();

        [$featured, $inspiration] = $this->weeklySelection($playlists);

        $categories = collect(['mood' => 'Mood', 'composer' => 'Composer', 'level' => 'Level'])
            ->filter(function ($label, $key) use ($playlists) { return $playlists->contains('category', $key); });

        return compact('playlists', 'featured', 'inspiration', 'categories') + ['books' => config('collections.books', [])];
    }

    private function weeklySelection($playlists)
    {
        if ($playlists->isEmpty()) {
            return [null, collect()];
        }

        $cards = $playlists->keyBy(function ($card) { return $card['playlist']->id; });
        $ids = $cards->keys()->all();
        $count = min(3, count($ids));
        $key = 'webapp.collections.weekly-selection';
        $selection = Cache::get($key);

        // Cache only IDs so admin edits and publication eligibility stay current.
        // Replace withdrawn selections, or fill slots when a small catalog grows.
        if (! $selection || ! in_array($selection['featured'], $ids, true)
            || count($selection['inspiration']) !== $count
            || array_diff($selection['inspiration'], $ids)) {
            $selection = [
                'featured' => $playlists->random()['playlist']->id,
                'inspiration' => $playlists->random($count)->map(function ($card) {
                    return $card['playlist']->id;
                })->all(),
            ];
            Cache::put($key, $selection, now()->addWeek());
        }

        return [
            $cards->get($selection['featured']),
            collect($selection['inspiration'])->map(function ($id) use ($cards) {
                return $cards->get($id);
            }),
        ];
    }
}
