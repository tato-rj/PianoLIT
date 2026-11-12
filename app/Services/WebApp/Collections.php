<?php

namespace App\Services\WebApp;

use App\Playlist;
use App\Services\Collections\Catalog;

class Collections
{
    public function __construct(private Catalog $catalog)
    {
    }

    public function data()
    {
        $entries = $this->catalog->entries();
        [$featuredId, $inspirationIds] = $this->catalog->weeklySelection($entries);
        $categoryGroups = collect($this->catalog->categories($entries));
        $categories = $categoryGroups->pluck('label', 'id');
        $categoryIds = $categoryGroups->mapWithKeys(function (array $category) {
            return array_fill_keys($category['collection_ids'], $category['id']);
        });
        $bookSeries = $this->catalog->bookSeries();
        $playlists = $entries->shuffle()
            ->map(function (Playlist $playlist) use ($categories, $categoryIds) {
                $categoryId = $categoryIds->get($playlist->getKey());

                return [
                    'playlist' => $playlist,
                    'image' => $this->catalog->artwork($playlist),
                    'illustrated' => ! $playlist->cover_path,
                    'category' => $categoryId,
                    'category_label' => $categories->get($categoryId, ''),
                ];
            })->values();

        $cards = $playlists->keyBy('playlist.id');
        $featured = $cards->get($featuredId);
        $inspiration = collect($inspirationIds)->map(function ($id) use ($cards) { return $cards->get($id); });

        return compact('playlists', 'featured', 'inspiration', 'categories', 'bookSeries');
    }
}
