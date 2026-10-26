<?php

namespace App\Services;

use App\{Piece, User};
use App\Api\Api;
use App\Services\WebApp\AccessPolicy;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\{Cache, Redis};

/** Content decisions for web Discover and iOS v2; renderers only format these rows. */
class DiscoverFeed
{
    public const FOR_YOU_LIMIT = 6;
    public const FOR_YOU_SUBTITLE = 'Picked for your level and taste.';
    public const LEVELS_TITLE = 'Find pieces at your level';
    public const LEVELS_SUBTITLE = 'Similar difficulty to RCM, ABRSM or Suzuki books.';
    public const SIMILAR_TITLE = "Like this week's free pick";

    private $api, $history;

    public function __construct(Api $api, RecentlyViewedPieces $history)
    {
        $this->api = $api;
        $this->history = $history;
    }

    // Callers supply a session/bearer-authenticated principal, never request user_id.
    public function forUser(?User $user): Collection
    {
        $rows = $this->api->for('webapp')->discover();
        $women = $rows->firstWhere('title', 'From women composers');
        $feature = $rows->firstWhere('title', $women['web_composer_feature'] ?? 'From women composers');
        $rows = $rows->map(function ($row) use ($feature) {
            if ($row['title'] === 'From women composers' && $feature) {
                $row = $feature;
            } elseif ($row['title'] === 'From black composers') {
                $row['title'] = 'Past highlights';
                $row['content'] = Piece::freePicks()->free(false)
                    ->where('highlighted_at', '<=', now())->orderByDesc('id')->with('composer')->take(7)->get();
                $this->api->withAttributes($row['content'], ['type' => 'piece', 'source' => route('api.pieces.find')]);
            }
            // Do not hydrate relations or add request-local fields on the
            // Eloquent objects held in the public content cache.
            $row['content'] = collect($row['content'])->map(function ($model) { return clone $model; });
            return $row;
        });
        $generation = [Redis::get('app.discover') ?: 'app.discover', now()->toDateString(), $rows->map(function ($row) {
            return [$row['title'], collect($row['content'])->pluck('id')->all()];
        })->all()];

        // Arthur's content-parity decision includes these personal rows. This
        // content switch does not apply any web media entitlement to native iOS.
        if ($user && app(AccessPolicy::class)->available('personalization', $user)) {
            $recommendations = $this->recommendations($user, $generation);
            if ($recommendations->isNotEmpty()) {
                $this->api->order(3)->withAttributes($recommendations, ['type' => 'piece', 'source' => route('api.pieces.find')]);
                $position = $rows->search(function ($row) { return $row['title'] === 'Latest pieces'; });
                $rows->splice($position === false ? 3 : $position + 1, 0, [[
                    'title' => 'For you', 'row' => 'gallery', 'type' => 'piece', 'content' => $recommendations,
                ]]);
            }

            $recent = $this->history->forUser($user);
            if ($recent->isNotEmpty()) {
                $this->api->order(2)->withAttributes($recent, ['type' => 'piece', 'source' => route('api.pieces.find')]);
                $position = $rows->search(function ($row) { return $row['title'] === 'Latest pieces'; });
                $rows->splice($position === false ? 2 : $position, 0, [[
                    'title' => 'Recently viewed', 'row' => 'gallery', 'type' => 'piece', 'content' => $recent,
                ]]);
            }
        }

        $pieces = $rows->where('type', 'piece')->flatMap(function ($row) { return collect($row['content']); });
        (new \Illuminate\Database\Eloquent\Collection($pieces->all()))->loadMissing('tags');
        $artworkSeed = hash('sha256', serialize($generation));
        return $rows->map(function ($row) use ($artworkSeed) {
            $ids = [
                'Free weekly pick' => 'free_pick', 'Composers' => 'composers',
                'Recently viewed' => 'recently_viewed', 'Latest pieces' => 'latest_pieces',
                'For you' => 'for_you', 'From women composers' => 'composer_feature',
                'From black composers' => 'composer_feature', 'Past highlights' => 'past_highlights',
                'Equivalent to the Suzuki series' => 'levels_suzuki',
                'Equivalent to the RCM levels' => 'levels_rcm',
                'Equivalent to the ABRSM levels' => 'levels_abrsm',
                "Like today's free pick" => 'similar_to_free_pick',
            ];
            $row['id'] = $ids[$row['title']] ?? (Str::startsWith($row['title'], 'Pieces that are ') ? 'mood' : Str::slug($row['title'], '_'));
            $row['kind'] = [
                'free_pick' => 'featured_piece', 'composers' => 'composer_rail',
                'latest_pieces' => 'artwork_piece_rail', 'composer_feature' => 'composer_feature',
                'past_highlights' => 'featured_piece_list',
            ][$row['id']] ?? 'compact_piece_rail';
            $row['display_title'] = [
                'From black composers' => 'From Black composers',
                "Like today's free pick" => self::SIMILAR_TITLE,
            ][$row['title']] ?? $row['title'];
            if ($row['id'] === 'composer_feature') {
                $row['composer_group'] = $row['title'] === 'From black composers' ? 'black' : 'women';
                $row['featured_composer'] = optional(collect($row['content'])->first())->composer;
            }
            if ($row['id'] === 'for_you') $row['subtitle'] = self::FOR_YOU_SUBTITLE;
            $row['content'] = collect($row['content'])->map(function ($model) use ($artworkSeed) {
                if ($model instanceof Piece) {
                    $model->discover_artwork_url = $model->cover_path ? storage($model->cover_path)
                        : (optional($model->period)->webCoverImageExcept([], $artworkSeed.':'.$model->id) ?: asset('images/webapp/thumbnail.jpg'));
                }
                return $model;
            });
            unset($row['web_composer_feature']);
            return $row;
        })->reject(function ($row) {
            return $row['id'] === 'composer_feature' && $row['content']->isEmpty();
        })->values();
    }

    private function recommendations(User $user, array $generation): Collection
    {
        $user->loadMissing('favorites.tags');
        $profile = $user->favorites->sortBy('id')->map(function ($piece) {
            return [$piece->id, $piece->tags->pluck('name')->sort()->values()->all()];
        })->all();
        $version = [self::FOR_YOU_LIMIT, $generation, $profile];
        $key = 'discover.account.'.$user->id.'.'.hash('sha256', serialize($version));

        // Only IDs enter this account-scoped cache. A lock makes simultaneous
        // first web/native loads choose the same six pieces. Favorites/tag edits,
        // refresh, Free Pick/pool changes and the daily boundary invalidate it.
        $ids = Cache::get($key);
        if ($ids === null) {
            $ids = Cache::lock($key.'.lock', 10)->block(5, function () use ($key, $user) {
                return Cache::remember($key, days(1), function () use ($user) {
                    return $user->suggestions(20)->shuffle()->take(self::FOR_YOU_LIMIT)->pluck('id')->values()->all();
                });
            });
        }
        $pieces = Piece::with(['tags', 'composer'])->whereIn('id', $ids)->get()->keyBy('id');
        return collect($ids)->map(function ($id) use ($pieces) { return $pieces->get($id); })->filter()->values();
    }

    // Both renderers group the same legacy grading pools, without requerying.
    public static function levelSystems($rows): Collection
    {
        return collect([
            ['key' => 'rcm', 'label' => 'RCM', 'unit' => 'Level', 'title' => 'Equivalent to the RCM levels'],
            ['key' => 'abrsm', 'label' => 'ABRSM', 'unit' => 'Level', 'title' => 'Equivalent to the ABRSM levels'],
            ['key' => 'suzuki', 'label' => 'Suzuki', 'unit' => 'Book', 'title' => 'Equivalent to the Suzuki series'],
        ])->map(function ($system) use ($rows) {
            $row = collect($rows)->firstWhere('title', $system['title']);
            $system['cards'] = collect($row['content'] ?? [])->sortBy('name', SORT_NATURAL | SORT_FLAG_CASE)->values();
            return $system;
        });
    }

    public static function composerExcerpt(\App\Composer $composer): string
    {
        return Str::limit(trim(strip_tags($composer->biography ?: $composer->curiosity ?: 'Explore this composer’s piano repertoire.')), 180);
    }
}
