<?php

namespace App\Services\Collections;

use App\Playlist;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Cache;

class Catalog
{
    private const SELECTION_KEY = 'webapp.collections.weekly-selection';

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
        $ids = $entries->modelKeys();
        if ($ids === []) {
            return [null, []];
        }

        $selection = Cache::get(self::SELECTION_KEY);
        if (! $this->validSelection($selection, $ids)) {
            $selection = $this->saveSelection($ids);
        }

        return [$selection['featured'], $selection['inspiration']];
    }

    public function rotateWeeklySelection(): void
    {
        $ids = $this->entries()->modelKeys();
        if ($ids !== []) {
            $this->saveSelection($ids, true);
        }
    }

    private function validSelection(?array $selection, array $ids): bool
    {
        // Cache only IDs so admin edits and publication eligibility stay current.
        return $selection && in_array($selection['featured'], $ids, true)
            && count($selection['inspiration']) === min(3, count($ids))
            && ! array_diff($selection['inspiration'], $ids);
    }

    private function saveSelection(array $ids, bool $rotate = false): array
    {
        return Cache::lock(self::SELECTION_KEY.':lock', 10)->block(5, function () use ($ids, $rotate) {
            $cached = Cache::get(self::SELECTION_KEY);
            if (! $rotate && $this->validSelection($cached, $ids)) {
                return $cached;
            }

            $previous = $rotate ? $cached : null;
            $featuredIds = array_diff($ids, [$previous['featured'] ?? null]);
            $selection = [
                'featured' => Arr::random($featuredIds ?: $ids),
                'inspiration' => $this->selectSuggestions($ids, $previous['inspiration'] ?? []),
            ];
            if (! Cache::put(self::SELECTION_KEY, $selection, now()->addWeek()) && $rotate) {
                throw new \RuntimeException('The Collections selection could not be refreshed.');
            }

            return $selection;
        });
    }

    private function selectSuggestions(array $ids, array $previous): array
    {
        shuffle($ids);
        $new = array_diff($ids, $previous);
        $repeat = array_intersect($ids, $previous);
        $suggestions = array_slice(array_merge($new, $repeat), 0, 3);

        // When every eligible collection already appears, change the display order.
        if ($suggestions === $previous && count($suggestions) > 1) {
            $suggestions[] = array_shift($suggestions);
        }

        return $suggestions;
    }
}
