<?php

namespace App\Services\Discover;

use App\{Piece, User};
use App\Activity\ExcludedAccounts;
use App\Api\Api;
use App\Services\WebApp\{PublicRepertoire, SimilarPieces};
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\{Cache, DB, Redis};

class Recommendations
{
    const VERSION = 'musical-v1';
    private const LIMIT = 6;
    private const FAVORITE_WEIGHT = 4;
    private const MARKED_SCORE_WEIGHT = 5;
    private const PLAYBACK_WEIGHT = 2;
    private const INTEREST_DAYS = 42;

    public static function playingLevel(User $user): ?string
    {
        return DB::table('discover_preferences')->where('user_id', $user->id)->value('playing_level');
    }

    public function forUser(User $user, Collection $recent, ?string $level): array
    {
        $saved = DB::table('favorites')->where('user_id', $user->id)
            ->where(function ($query) use ($user) {
                $query->whereNull('favorite_folder_id')->orWhereIn('favorite_folder_id',
                    DB::table('favorite_folders')->select('id')->where('user_id', $user->id));
            })->distinct()->orderBy('piece_id')->pluck('piece_id')->all();
        $interest = $practice = array_fill_keys($saved, self::FAVORITE_WEIGHT);
        foreach ($recent as $piece) {
            $weight = $this->recentWeight($piece->last_opened_at);
            if ($weight) $interest[$piece->id] = max($interest[$piece->id] ?? 0, $weight);
        }

        $hasOtherSignals = $level !== null;
        if (!ExcludedAccounts::user($user)) {
            $marked = DB::table('score_annotations')->where('user_id', $user->id)->where('marks', '!=', '[]')
                ->where('updated_at', '>=', now()->subDays(90))->groupBy('piece_id')
                ->orderByRaw('MAX(updated_at) DESC')->orderBy('piece_id')->limit(24)->pluck('piece_id');
            foreach ($marked as $id) $interest[$id] = $practice[$id] = self::MARKED_SCORE_WEIGHT;
            $playback = config('activity.enabled') ? $this->playback($user) : [];
            $hasOtherSignals = $hasOtherSignals || $marked->isNotEmpty() || $playback;
            foreach ($playback as $id => $date) {
                $weight = self::PLAYBACK_WEIGHT * $this->recentWeight($date);
                if ($weight) $interest[$id] = max($interest[$id] ?? 0, $weight);
            }
        }

        $cohort = $saved ? 'favorites' : ($recent->isNotEmpty() ? 'recent_views' : 'no_history');
        if (!$saved && $hasOtherSignals) $cohort = 'other_signals';
        $exclude = array_fill_keys(array_merge(array_keys($interest), $recent->pluck('id')->all()), true);
        $sources = $interest ? $this->traits(Piece::query()->whereIntegerInRaw('pieces.id', array_keys($interest))) : [];
        $prefix = Api::freePickCacheKey(Redis::get('app.discover') ?: 'app.discover');
        $day = now()->toDateString();
        $state = hash('sha256', serialize([$prefix, $day, $interest, $practice, $sources, $exclude, $level]));
        $key = 'discover.for-you.'.self::VERSION.'.'.$user->id;
        $result = Cache::get($key);
        $catalogKey = 'discover.traits.'.self::VERSION.'.'.$prefix.'.'.$day;
        $choose = fn ($catalog) => $this->rank(array_diff_key($catalog, $exclude), $sources, $interest, $practice, $level, $prefix.$day.':'.$user->id);
        if (($result['state'] ?? null) !== $state) {
            // Only public catalogue traits enter this shared cache.
            $catalog = Cache::remember($catalogKey, days(1), fn () => $this->traits(app(PublicRepertoire::class)->pieces()));
            $result = ['state' => $state, 'ids' => $choose($catalog)];
            Cache::put($key, $result, days(1));
        }
        $pieces = $this->cards($result['ids']);
        if ($pieces->count() < count($result['ids'])) {
            // A previously selected piece lost its last resource or was removed.
            $catalog = $this->traits(app(PublicRepertoire::class)->pieces());
            Cache::put($catalogKey, $catalog, days(1));
            $ids = $choose($catalog);
            Cache::put($key, ['state' => $state, 'ids' => $ids], days(1));
            $pieces = $this->cards($ids);
        }
        return ['pieces' => $pieces, 'cohort' => $cohort];
    }

    private function cards(array $ids): Collection
    {
        $pieces = app(PublicRepertoire::class)->pieces()->with('tags')->whereIn('pieces.id', $ids)->get()->keyBy('id');
        return collect($ids)->map(fn ($id) => $pieces->get($id))->filter()->values();
    }

    private function recentWeight(string $date): float
    {
        $age = now()->startOfDay()->diffInDays(carbon($date)->startOfDay());
        return $age > self::INTEREST_DAYS ? 0 : pow(0.5, $age / 14);
    }

    private function playback(User $user): array
    {
        // Read cumulative observations once per piece. A preview, seek, or series
        // of progress callbacks cannot build up the required playback time.
        return Cache::remember('discover.playback.v1.'.$user->id, now()->addMinutes(5), function () use ($user) {
            return DB::table('activity_events')->where('user_id', $user->id)->where('platform', 'webapp')
                ->where('source', 'browser')->where('resource_type', 'piece')->whereNotNull('page_id')
                ->whereIn('type', ['media_pause', 'media_end', 'media_progress'])
                ->where('occurred_at', '>=', now()->subDays(self::INTEREST_DAYS))->whereIn('metadata->media_kind', ['video', 'audio'])
                ->where('metadata->played_seconds', '>=', 20)->where('metadata->unique_watched_seconds', '>=', 15)
                ->groupBy('resource_id')->selectRaw('resource_id, MAX(occurred_at) as played_at')
                ->orderByDesc('played_at')->orderBy('resource_id')->limit(24)->pluck('played_at', 'resource_id')->all();
        });
    }

    private function traits(Builder $pieces): array
    {
        $types = array_merge(array_keys(SimilarPieces::MUSICAL_WEIGHTS), ['level', 'sublevel']);
        $rows = $pieces->leftJoin('piece_tag', 'piece_tag.piece_id', '=', 'pieces.id')
            ->leftJoin('tags', function ($join) use ($types) {
                $join->on('tags.id', '=', 'piece_tag.tag_id')->whereIn('tags.type', $types);
            })->select('pieces.id', 'pieces.composer_id', 'pieces.collection_name', 'pieces.highlighted_at',
                'tags.id as tag_id', 'tags.type', 'tags.name as tag_name')
            ->orderBy('pieces.id')->orderBy('piece_tag.tag_id')->toBase()->cursor();
        $profiles = [];
        $now = now()->toDateTimeString();
        foreach ($rows as $row) {
            $id = $row->id;
            $profiles[$id] ??= ['composer' => $row->composer_id, 'collection' => $row->collection_name,
                'highlight' => $row->highlighted_at && $row->highlighted_at <= $now, 'tags' => []];
            if (!$row->type) continue;
            if (isset(SimilarPieces::MUSICAL_WEIGHTS[$row->type])) {
                $profiles[$id]['tags'][$row->tag_id] = $row->type;
            } else {
                $value = SimilarPieces::LEVELS[strtolower(trim($row->tag_name))] ?? null;
                if ($value !== null) $profiles[$id][$row->type] = min($profiles[$id][$row->type] ?? $value, $value);
            }
        }
        return $profiles;
    }

    private function rank(array $catalog, array $sources, array $interest, array $practice, ?string $level, string $seed): array
    {
        $votes = $levels = $composers = $composerCounts = [];
        foreach ($sources as $id => $piece) {
            foreach ($piece['tags'] as $tag => $type) $votes[$tag] = ($votes[$tag] ?? 0) + $interest[$id];
            $composer = $piece['composer'];
            $composers[$composer] = ($composers[$composer] ?? 0) + $interest[$id];
            $composerCounts[$composer] = ($composerCounts[$composer] ?? 0) + 1;
            $difficulty = $piece['sublevel'] ?? $piece['level'] ?? null;
            if ($difficulty !== null && isset($practice[$id])) {
                $levels[(string) $difficulty] = ($levels[(string) $difficulty] ?? 0) + $practice[$id];
            }
        }
        $explicit = SimilarPieces::LEVELS[$level] ?? null;
        if ($explicit !== null) $levels = [(string) $explicit => 1];
        $maxVote = max(1, max($votes ?: [0]));
        $maxComposer = max(1, max($composers ?: [0]));
        $totalLevels = array_sum($levels);
        foreach ($catalog as $id => &$piece) {
            // Use the strongest match in each musical category so verbose tagging
            // does not give one piece several votes for synonymous moods.
            $matches = [];
            foreach ($piece['tags'] as $tag => $type) {
                $matches[$type] = max($matches[$type] ?? 0, ($votes[$tag] ?? 0) * SimilarPieces::MUSICAL_WEIGHTS[$type]);
            }
            $score = 6 * array_sum($matches) / $maxVote;
            $composer = $piece['composer'];
            if (($composerCounts[$composer] ?? 0) >= 2) $score += 3 * $composers[$composer] / $maxComposer;
            $difficulty = $piece['sublevel'] ?? $piece['level'] ?? null;
            // Browsing and playback indicate taste. Only explicit or practice evidence supplies a level.
            if ($difficulty !== null && $totalLevels) {
                foreach ($levels as $range => $weight) {
                    $score += ($explicit !== null ? 42 : 8) * $weight / $totalLevels * max(0, 1 - abs($difficulty - $range));
                }
            }
            $piece['score'] = $score;
            $piece['tie'] = crc32($seed.':'.$id);
        }
        unset($piece);

        $selected = $byComposer = $byCollection = [];
        while ($catalog && count($selected) < self::LIMIT) {
            $best = $bestKey = null;
            foreach ($catalog as $id => $piece) {
                $collection = $piece['composer'].':'.$piece['collection'];
                $repeats = ($byComposer[$piece['composer']] ?? 0) + ($piece['collection'] ? ($byCollection[$collection] ?? 0) : 0);
                $key = [$piece['score'] / (1 + 0.07 * $repeats), -$repeats, $piece['highlight'], $piece['tie'], $id];
                if ($bestKey === null || $key > $bestKey) { $best = $id; $bestKey = $key; }
            }
            $piece = $catalog[$best];
            $collection = $piece['composer'].':'.$piece['collection'];
            $byComposer[$piece['composer']] = ($byComposer[$piece['composer']] ?? 0) + 1;
            $byCollection[$collection] = ($byCollection[$collection] ?? 0) + 1;
            $selected[] = $best;
            unset($catalog[$best]);
        }
        return $selected;
    }
}
