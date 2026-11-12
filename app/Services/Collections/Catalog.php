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

    public function bookSeries(): array
    {
        return array_map(function (array $series) {
            $books = array_map(function (array $book) use ($series) {
                $number = $book['number'];
                $url = $book['detail_url'] ?? null;
                $detailUrl = $url && filter_var($url, FILTER_VALIDATE_URL) && parse_url($url, PHP_URL_SCHEME) === 'https'
                    ? $url : null;

                return [
                    'id' => $series['id'].'-book-'.$number,
                    'number' => $number,
                    'title' => $series['title'].' · Book '.$number,
                    'level' => $book['level'],
                    'cover_url' => asset('images/webapp/collections/'.$book['image'].'.webp'),
                    'availability' => $detailUrl === null ? 'coming_soon' : 'available',
                    'detail_url' => $detailUrl,
                ];
            }, $series['books']);

            return ['id' => $series['id'], 'title' => $series['title'], 'books' => $books];
        }, config('collections.book_series', []));
    }

    public function categories(Collection $entries): array
    {
        $groups = [];
        foreach ($entries as $playlist) {
            $label = $this->badge($playlist);
            if ($label === null) continue;

            // Match existing filters for older rows whose exact trimmed labels agree.
            $key = 'featured-'.hash('sha256', $label);
            $groups[$key]['label'] = $label;
            $groups[$key]['collection_ids'][] = (string) $playlist->getKey();
            if ($playlist->featured_tag_id !== null) {
                $tagId = (int) $playlist->featured_tag_id;
                $groups[$key]['tag_id'] = min($groups[$key]['tag_id'] ?? $tagId, $tagId);
            }
        }

        $categories = [];
        foreach ($groups as $key => $group) {
            $categories[] = [
                'id' => isset($group['tag_id']) ? 'tag-'.$group['tag_id'] : $key,
                'label' => $group['label'],
                'collection_ids' => $group['collection_ids'],
            ];
        }
        usort($categories, function (array $a, array $b) {
            return strnatcasecmp($a['label'], $b['label']) ?: strcmp($a['label'], $b['label']);
        });

        return $categories;
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
