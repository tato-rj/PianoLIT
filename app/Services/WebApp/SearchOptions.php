<?php

namespace App\Services\WebApp;

use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/** Browser-only search controls; shared/mobile search keeps its existing contract. */
class SearchOptions
{
    const FACETS = [
        'level' => ['elementary', 'early beginner', 'late beginner', 'early intermediate', 'late intermediate', 'advanced'],
        'period' => ['baroque', 'classical', 'romantic', 'impressionist', 'modern', 'contemporary'],
        'length' => ['short', 'medium', 'long'],
        'type' => ['pedagogical', 'transcription', 'famous'],
        'ensemble' => ['solo', '4 hands', '6 hands', '8 hands', '2 pianos'],
    ];
    const SORTS = ['relevance', 'title_asc', 'title_desc', 'composer', 'level', 'period'];
    private $params;

    public function __construct(Request $request)
    {
        $rules = ['sort' => ['nullable', Rule::in(self::SORTS)], 'facets' => 'nullable|array:level,period,length,type,ensemble',
            'audio_only' => 'nullable|boolean', 'score_only' => 'nullable|boolean'];
        foreach (self::FACETS as $facet => $names) {
            $rules['facets.'.$facet] = 'nullable|array|max:'.count($names);
            $rules['facets.'.$facet.'.*'] = ['string', Rule::in($names)];
        }
        $this->params = $request->validate($rules);
    }

    public function active()
    {
        return ($this->params['sort'] ?? 'relevance') !== 'relevance'
            || array_filter($this->params['facets'] ?? [])
            || !empty($this->params['audio_only']) || !empty($this->params['score_only']);
    }

    public function filterQuery($query)
    {
        foreach ($this->params['facets'] ?? [] as $facet => $names) {
            if (!$names) continue;
            $query->where(function ($q) use ($facet, $names) {
                $q->whereHas('tags', function ($tags) use ($names) { $tags->whereIn('name', $names); });
                // Solo is the catalogue's default: pieces without an ensemble tag.
                if ($facet === 'ensemble' && in_array('solo', $names, true)) {
                    $q->orWhereDoesntHave('tags', function ($tags) {
                        $tags->whereIn('name', ['4 hands', '6 hands', '8 hands', '2 pianos']);
                    });
                }
            });
        }
        if (!empty($this->params['audio_only'])) $query->whereNotNull('audio_path')->where('audio_path', '!=', '');
        if (!empty($this->params['score_only'])) $query->whereNotNull('score_path')->where('score_path', '!=', '')
            ->where(function ($q) { $q->whereNull('score_url')->orWhere('score_url', ''); });
        return $query;
    }

    public function sortQuery($query)
    {
        $sort = $this->params['sort'] ?? 'relevance';
        if ($sort === 'relevance') return $query;
        $query->reorder();
        if (in_array($sort, ['title_asc', 'title_desc'], true)) {
            $query->orderBy('pieces.name', $sort === 'title_desc' ? 'desc' : 'asc');
        } elseif ($sort === 'composer') {
            $query->orderBy(\App\Composer::select('name')->whereColumn('composers.id', 'pieces.composer_id'));
        } else {
            $names = $sort === 'level'
                ? ['elementary', 'early beginner', 'beginner', 'late beginner', 'early intermediate', 'intermediate', 'late intermediate', 'advanced']
                : self::FACETS['period'];
            $cases = collect($names)->map(function ($name, $index) { return "WHEN '".$name."' THEN ".$index; })->implode(' ');
            $rank = 'CASE tags.name '.$cases.' ELSE 99 END';
            $types = $sort === 'level' ? ['level', 'sublevel'] : ['period'];
            $query->orderBy(\App\Tag::selectRaw($rank)->join('piece_tag', 'tags.id', '=', 'piece_tag.tag_id')
                ->whereColumn('piece_tag.piece_id', 'pieces.id')->whereIn('tags.type', $types)
                ->orderByRaw($sort === 'level' ? "CASE WHEN tags.type = 'sublevel' THEN 0 ELSE 1 END" : $rank)->limit(1));
        }
        return $query->orderBy('pieces.name')->orderBy('pieces.id');
    }

    public function results($query, array $legacyFilters = [])
    {
        if ($query instanceof \Laravel\Scout\Builder) {
            // Without sorted Algolia replicas, scan candidate pages before applying
            // browser-only SQL filters/order, so pagination never sorts just one page.
            $ids = collect();
            $page = 1;
            do {
                $batch = $query->paginateRaw(100, 'page', $page++);
                $raw = $batch->getCollection();
                $hits = $raw->get('hits', []);
                $ids = $ids->merge(collect($hits)->pluck('objectID')->filter(function ($id) {
                    return ctype_digit((string) $id);
                })->map(function ($id) { return (int) $id; }));
                // Algolia's accessible page count can be smaller than nbHits.
                // Use its metadata and stop on empty hits, rather than requesting
                // pages beyond the index's pagination limit. No candidate hydration.
            } while ($hits && $page <= (int) $raw->get('nbPages', 1));
            $ids = $ids->unique()->values();
            $query = \App\Piece::whereIn('pieces.id', $ids);
            if ($ids->isNotEmpty()) {
                $cases = $ids->map(function ($id, $index) { return 'WHEN '.(int) $id.' THEN '.$index; })->implode(' ');
                $query->orderByRaw('CASE pieces.id '.$cases.' END');
            }
            foreach ($legacyFilters as $names) $query->whereHas('tags', function ($q) use ($names) {
                $q->whereIn('name', json_decode($names, true));
            });
        }
        $this->sortQuery($this->filterQuery($query));
        $pieces = auth('web')->guest() ? $query->limit(3)->get() : $query->simplePaginate(10)->getCollection();
        return PieceCards::load($pieces);
    }
}
