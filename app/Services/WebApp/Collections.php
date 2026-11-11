<?php

namespace App\Services\WebApp;

use App\Playlist;
use App\Services\Collections\Catalog;
use Illuminate\Support\Str;

class Collections
{
    public function __construct(private Catalog $catalog)
    {
    }

    public function data()
    {
        $entries = $this->catalog->entries();
        [$featuredId, $inspirationIds] = $this->catalog->weeklySelection($entries);
        $playlists = $entries->shuffle()
            ->map(function (Playlist $playlist) {
                $key = Str::slug(str_replace(["'", '’'], '', $playlist->name));
                $featuredTag = $this->catalog->badge($playlist);

                return [
                    'playlist' => $playlist,
                    'key' => $key,
                    'image' => $this->catalog->artwork($playlist),
                    'illustrated' => ! $playlist->cover_path,
                    // Keep labels editable in admin and matching exact, including
                    // distinct tags that would otherwise share a slug or "all".
                    'category' => $featuredTag === null ? null : 'featured-'.hash('sha256', $featuredTag),
                    'category_label' => $featuredTag ?? '',
                ];
            })->values();

        $cards = $playlists->keyBy('playlist.id');
        $featured = $cards->get($featuredId);
        $inspiration = collect($inspirationIds)->map(function ($id) use ($cards) { return $cards->get($id); });

        $categories = $playlists->filter(function ($card) { return $card['category_label'] !== ''; })
            ->pluck('category_label', 'category')->sort(SORT_NATURAL | SORT_FLAG_CASE);

        return compact('playlists', 'featured', 'inspiration', 'categories') + ['books' => config('collections.books', [])];
    }
}
