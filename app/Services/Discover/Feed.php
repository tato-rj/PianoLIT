<?php

namespace App\Services\Discover;

use App\{Piece, User};
use App\Api\Api;
use App\Services\RecentlyViewedPieces;
use Illuminate\Support\Facades\{Cache, Redis};

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
            $suggestions = $this->suggestions($user);
            $api->order(3)->withAttributes($suggestions, ['type' => 'piece', 'source' => route('api.pieces.find')]);
            $rows->splice(3, 0, [['title' => 'For you', 'row' => 'gallery', 'type' => 'piece', 'content' => $suggestions]]);
            $pieces = app(RecentlyViewedPieces::class)->forUser($user);
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

    private function suggestions(User $user)
    {
        $user->loadMissing('favorites.tags');
        // Cache only IDs, separately for each account and favorites/tag state.
        // The shared admin refresh, Free Pick rotation and daily TTL invalidate it.
        $state = $user->favorites->map(function ($piece) {
            return [$piece->id, $piece->tags->pluck('id')->sort()->values()->all()];
        })->sortBy(0)->values()->all();
        $prefix = Redis::get('app.discover') ?: 'app.discover';
        $key = Api::freePickCacheKey('discover.personal.v2:'.$prefix.':'.$user->id.':'.hash('sha256', json_encode($state)));
        $ids = Cache::remember($key, days(1), function () use ($user) {
            return $user->suggestions(20)->shuffle()->take(6)->pluck('id')->values()->all();
        });
        $pieces = Piece::with(['tags', 'composer'])->whereIn('id', $ids)->get()->keyBy('id');
        return collect($ids)->map(function ($id) use ($pieces) { return $pieces->get($id); })->filter()->values();
    }
}
