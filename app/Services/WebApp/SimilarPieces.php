<?php

namespace App\Services\WebApp;

use App\Piece;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

/** Ranked web piece recommendations; legacy mobile and tour matching stay separate. */
class SimilarPieces
{
    // IDs describe storage, not musical difficulty. Accept both split level tags
    // and the older base-level + sublevel representation.
    private const LEVELS = [
        'elementary' => 0, 'early beginner' => 0.75, 'beginner' => 1,
        'late beginner' => 1.25, 'early intermediate' => 1.75,
        'intermediate' => 2, 'late intermediate' => 2.25, 'advanced' => 3,
    ];
    private const WEIGHTS = [
        'mood' => 4, 'genre' => 3, 'technique' => 2, 'ranking' => 1,
        'season' => 1, 'period' => 1, 'length' => 1,
    ];

    public function forPiece(Piece $piece, ?int $limit = null): Collection
    {
        $piece->loadMissing('tags');
        $musicalIds = $piece->tags->whereIn('type', ['mood', 'genre', 'technique'])->pluck('id')->all();
        if (!$musicalIds || ($limit !== null && $limit <= 0)) return new Collection;

        $sourceLevel = $this->level($piece);
        $rankingIds = $piece->tags->where('type', 'ranking')->pluck('id')->all();
        // A missing/unknown difficulty needs shared grading evidence, rather
        // than silently treating untagged repertoire as elementary.
        if ($sourceLevel === null && !$rankingIds) return new Collection;

        $sourceIds = $piece->tags->whereIn('type', array_keys(self::WEIGHTS))->pluck('id')->all();
        $sourceWeight = $piece->tags->sum(function ($tag) { return self::WEIGHTS[$tag->type] ?? 0; });
        $weightSql = $this->caseSql('tags.type', self::WEIGHTS);
        $weightBindings = $this->caseBindings(self::WEIGHTS);
        $levelSql = $this->caseSql('LOWER(TRIM(tags.name))', self::LEVELS);
        $levelBindings = $this->caseBindings(self::LEVELS);

        // Start from the reverse tag index, then aggregate only candidate tags.
        // No candidate Piece/composer/media models are hydrated before LIMIT.
        $candidates = DB::table('piece_tag')->select('piece_id')->distinct()
            ->whereIn('tag_id', $musicalIds)->where('piece_id', '!=', $piece->id);
        $profiles = DB::table('piece_tag as profile_tags')
            ->joinSub($candidates, 'candidates', function ($join) {
                $join->on('candidates.piece_id', '=', 'profile_tags.piece_id');
            })
            ->join('tags', 'tags.id', '=', 'profile_tags.tag_id')
            ->select('profile_tags.piece_id')
            ->selectRaw("SUM($weightSql) AS total_weight", $weightBindings)
            ->selectRaw('SUM(CASE WHEN '.$this->inSql('profile_tags.tag_id', $sourceIds)." THEN $weightSql ELSE 0 END) AS shared_weight", array_merge($sourceIds, $weightBindings))
            ->selectRaw('MAX(CASE WHEN '.$this->inSql('profile_tags.tag_id', $rankingIds).' THEN 1 ELSE 0 END) AS shared_ranking', $rankingIds)
            ->selectRaw("COALESCE(MIN(CASE WHEN tags.type = 'sublevel' THEN $levelSql END), MIN(CASE WHEN tags.type = 'level' THEN $levelSql END)) AS difficulty", array_merge($levelBindings, $levelBindings))
            ->groupBy('profile_tags.piece_id');

        $query = Piece::query()->joinSub($profiles, 'similarity', function ($join) {
            $join->on('similarity.piece_id', '=', 'pieces.id');
        })->select('pieces.*')->setEagerLoads([]);

        $distanceSql = '1';
        $distanceBindings = [];
        if ($sourceLevel !== null) {
            $distanceSql = 'COALESCE(ABS(similarity.difficulty - ?), 1)';
            $distanceBindings = [$sourceLevel];
            $query->where(function ($query) use ($sourceLevel) {
                $query->whereRaw('ABS(similarity.difficulty - ?) <= 1', [$sourceLevel])
                    ->orWhere(function ($query) {
                        $query->whereNull('similarity.difficulty')->where('similarity.shared_ranking', 1);
                    });
            });
        } else {
            $query->where('similarity.shared_ranking', 1);
        }

        // Weighted Jaccard overlap is symmetric and does not reward entries just
        // for having more tags. Period/length/grades support musical overlap;
        // none can create a candidate alone. Nearby difficulty adds up to 15.
        $query->orderByRaw("(100.0 * similarity.shared_weight / (? + similarity.total_weight - similarity.shared_weight) + 15 * (1 - $distanceSql)) DESC", array_merge([$sourceWeight], $distanceBindings))
            ->orderByRaw("$distanceSql ASC", $distanceBindings)
            ->orderBy('pieces.id');
        if ($limit !== null) $query->limit($limit);

        // Only counts used by list sorting/video badges; avoid default counts
        // and the composer's country/piece-count work on every candidate.
        return $query->withCount(['views', 'tutorials'])->with([
            'tags', 'composer' => function ($query) {
                $query->select('composers.*')->setEagerLoads([]);
            },
        ])->get();
    }

    private function level(Piece $piece): ?float
    {
        foreach (['sublevel', 'level'] as $type) {
            $levels = $piece->tags->where('type', $type)->map(function ($tag) {
                return self::LEVELS[strtolower(trim($tag->name))] ?? null;
            })->filter(function ($level) { return $level !== null; });
            if ($levels->isNotEmpty()) return $levels->min();
        }
        return null;
    }

    private function caseSql(string $column, array $values): string
    {
        return 'CASE '.$column.str_repeat(' WHEN ? THEN ?', count($values)).' ELSE NULL END';
    }

    private function caseBindings(array $values): array
    {
        $bindings = [];
        foreach ($values as $name => $value) { $bindings[] = $name; $bindings[] = $value; }
        return $bindings;
    }

    private function inSql(string $column, array $ids): string
    {
        return $ids ? $column.' IN ('.implode(', ', array_fill(0, count($ids), '?')).')' : '0 = 1';
    }
}
