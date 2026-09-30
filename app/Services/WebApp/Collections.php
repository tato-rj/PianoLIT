<?php

namespace App\Services\WebApp;

use App\Playlist;
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

        $featured = $playlists->firstWhere('key', config('collections.featured')) ?? $playlists->first();
        $inspiration = collect(config('collections.inspiration', []))
            ->map(function ($key) use ($playlists) { return $playlists->firstWhere('key', $key); })
            ->filter()->unique(function ($card) { return $card['playlist']->id; })->values();

        if ($inspiration->isEmpty()) {
            $inspiration = $playlists->reject(function ($card) use ($featured) {
                return $featured && $card['playlist']->id === $featured['playlist']->id;
            })->take(3)->values();
        }

        $categories = collect(['mood' => 'Mood', 'composer' => 'Composer', 'level' => 'Level'])
            ->filter(function ($label, $key) use ($playlists) { return $playlists->contains('category', $key); });

        return compact('playlists', 'featured', 'inspiration', 'categories') + ['books' => config('collections.books', [])];
    }
}
