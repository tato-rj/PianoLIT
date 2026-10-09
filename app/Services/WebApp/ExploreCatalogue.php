<?php

namespace App\Services\WebApp;

use App\{Composer, Country, Piece, Tag};
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

/** Guided browsing belongs to the web app, never the shared mobile feed. */
class ExploreCatalogue
{
    const LEVELS = ['elementary', 'early beginner', 'late beginner', 'early intermediate', 'late intermediate', 'advanced'];
    const LENGTHS = ['short', 'medium', 'long'];
    const MOODS = [
        'gentle' => ['label' => 'Gentle & lyrical', 'description' => 'Flowing melodies and a softer touch.', 'icon' => 'feather', 'tags' => ['calm', 'elegant', 'lyrical']],
        'playful' => ['label' => 'Playful & lively', 'description' => 'Light rhythms and bright character.', 'icon' => 'sun', 'tags' => ['happy', 'playful', 'fast']],
        'dramatic' => ['label' => 'Dramatic', 'description' => 'Strong rhythms and bold contrasts.', 'icon' => 'flame', 'tags' => ['agitated', 'agitaded', 'crazy', 'dramatic', 'flashy']],
        'dreamy' => ['label' => 'Dreamy & atmospheric', 'description' => 'Floating textures and quiet moods.', 'icon' => 'moon', 'tags' => ['dreamy', 'mysterious']],
        'reflective' => ['label' => 'Reflective & melancholy', 'description' => 'Thoughtful melodies with a wistful feel.', 'icon' => 'waves', 'tags' => ['melancholic', 'reflective']],
    ];

    public function moodRules(): array
    {
        return ['bail', 'nullable', 'string', 'max:64', function ($attribute, $value, $fail) {
            if (isset(self::MOODS[$value])) return; // Keep existing bookmarked groups working.
            if (!preg_match('/^tag-([1-9][0-9]*)$/', $value, $match)
                || !Tag::where('type', 'mood')->whereKey($match[1])->exists()) {
                $fail('Invalid mood.');
            }
        }];
    }

    public function moodLabel(string $key): string
    {
        if (isset(self::MOODS[$key])) return self::MOODS[$key]['label'];
        return ucfirst(Tag::where('type', 'mood')->findOrFail(substr($key, 4))->name);
    }

    public function data(Request $request)
    {
        $params = array_filter($request->validate([
            'level' => ['nullable', Rule::in(self::LEVELS)],
            'mood' => $this->moodRules(),
            'tag' => 'nullable|integer|min:1',
            'composers' => ['nullable', Rule::in(array_keys(ComposerGroups::OPTIONS))], 'country' => 'nullable|integer|min:1',
        ]), function ($value) { return $value !== null && $value !== ''; });
        // Preserve the order of selections in links so breadcrumbs retrace the actual path.
        $params = array_replace(array_intersect_key($request->query(), $params), $params);
        $hasSelection = count($params) > 0;
        $selectedTag = isset($params['tag']) ? Tag::whereIn('type', ['technique', 'period', 'genre'])->findOrFail($params['tag']) : null;
        $selectedCountry = isset($params['country']) ? Country::findOrFail($params['country']) : null;
        $levels = Tag::whereIn('type', ['level', 'sublevel'])->whereIn('name', self::LEVELS)->withCount('pieces')->get()
            ->sortBy(function ($tag) { return array_search($tag->name, self::LEVELS, true); })->values();
        $selected = isset($params['level']) ? $levels->firstWhere('name', $params['level']) : (!$hasSelection ? $levels->first() : null);
        abort_if(isset($params['level']) && !$selected, 404);
        if ($selected) $params['level'] = $selected->name;
        $activeSection = 'level';
        $matchingPieces = $this->query($params)->select('pieces.id')->setEagerLoads([])->withCount([]);
        $moodTags = Tag::where('type', 'mood')->select(['tags.id', 'tags.name'])
            ->withCount(['pieces' => function ($query) {
                $query->select(DB::raw('count(distinct pieces.id)'));
            }, 'pieces as matching_pieces_count' => function ($query) use ($matchingPieces) {
                $query->whereIn('pieces.id', $matchingPieces)->select(DB::raw('count(distinct pieces.id)'));
            }])->orderByDesc('pieces_count')->orderBy('name')->orderBy('id')->get();
        // Use the catalogue's own mood names. Legacy combined keys remain
        // accepted by query()/moodRules() only for existing saved links.
        $allMoods = $moodTags->filter(function ($tag) { return $tag->pieces_count > 0; })
            ->mapWithKeys(function ($tag) use ($params) {
                $key = 'tag-'.$tag->id;
                return [$key => [
                    'label' => ucfirst($tag->name), 'description' => '', 'icon' => 'music',
                    'total_count' => $tag->pieces_count, 'count' => $tag->matching_pieces_count,
                    'params' => array_merge($params, ['mood' => $key]),
                ]];
            });
        $directoryKeys = $allMoods->take(8)->keys();
        $contextualMoods = $selected && !isset($params['mood'])
            ? $allMoods->filter(function ($mood) { return $mood['count'] > 0; })
                ->sort(function ($a, $b) { return ($b['count'] <=> $a['count']) ?: strcmp($a['label'], $b['label']); })
            : collect();
        // Generate artwork only for displayed options, sharing one unique image
        // per mood across both columns even when the contextual list exceeds eight.
        $visibleKeys = $directoryKeys->merge($contextualMoods->keys())->unique();
        $usedMoodImages = [];
        $moodImages = [];
        // Give moods with fewer cover candidates the first choice of artwork.
        $allMoods->only($visibleKeys->all())->sortBy('total_count')->each(function ($mood, $key) use (&$usedMoodImages, &$moodImages) {
            $image = $this->moodImage($key, $usedMoodImages);
            if ($image) $usedMoodImages[] = $image;
            $moodImages[$key] = $image;
        });
        $visibleMoods = $allMoods->only($visibleKeys->all())->map(function ($mood, $key) use ($moodImages) {
            return array_merge($mood, ['image' => $moodImages[$key]]);
        });
        $moods = $visibleMoods->only($directoryKeys->all());
        $contextualMoods = $contextualMoods->map(function ($mood, $key) use ($visibleMoods) { return $visibleMoods[$key]; });
        $guide = null;
        $choices = collect();
        $breadcrumbs = [];
        $selectionLabels = [];
        $trail = [];
        foreach ($params as $facet => $value) {
            $trail[$facet] = $value;
            if ($facet === 'tag') {
                $label = ucfirst($selectedTag->name);
                $kind = $selectedTag->type === 'technique' ? 'Technique' : 'Periods & Styles';
                $activeSection = $selectedTag->type === 'technique' ? 'technique' : 'style';
            } elseif ($facet === 'mood') {
                $label = $this->moodLabel($value);
                $kind = 'Mood';
                $activeSection = 'mood';
            } elseif ($facet === 'composers' || $facet === 'country') {
                $label = $facet === 'country' ? $selectedCountry->name : ComposerGroups::OPTIONS[$value]['label'];
                $kind = 'Composers';
                $activeSection = 'composers';
            } else {
                $label = ucwords($value);
                $kind = 'Level';
                $activeSection = 'level';
            }
            $selectionLabels[] = $label;
            $breadcrumbs[] = ['label' => $label, 'params' => $trail];
        }
        $selectionLabel = implode(' · ', $selectionLabels);
        if ($params) {
            $title = array_pop($breadcrumbs)['label'];
            $guide = array_merge(['title' => $title, 'kind' => $kind, 'params' => $params], $this->choice($params));
            if (!$selected) {
                $choices = $levels->map(function ($level) use ($params) {
                    return array_merge(['label' => ucwords($level->name), 'description' => '', 'icon' => 'circle', 'level' => $level->name],
                        $this->choice(array_merge($params, ['level' => $level->name])));
                });
                $guide['heading'] = 'By level';
            } elseif (!isset($params['mood'])) {
                $choices = $contextualMoods;
                $guide['heading'] = 'By character';
            } else {
                $guide['heading'] = 'Keep exploring';
            }
        }
        $choices = $choices->filter(function ($choice) { return $choice['count'] > 0; });
        $tags = Tag::where(function ($query) {
            $query->where('type', 'technique')->has('pieces', '>=', 8);
        })->orWhere(function ($query) {
            $query->whereIn('type', ['period', 'genre'])->has('pieces', '>=', 10);
        })->orderBy('name')->get();
        $techniques = collect();
        $lengths = collect();
        $periods = collect();
        if (!$selectedTag && $guide) {
            $matchingPieces = $this->query($params)->select('pieces.id')->setEagerLoads([])->withCount([]);
            $matchingChoicePieces = function ($query) use ($matchingPieces) {
                $query->whereIn('pieces.id', $matchingPieces);
            };
            $techniques = Tag::whereIn('id', $tags->where('type', 'technique')->pluck('id'))
                ->whereHas('pieces', $matchingChoicePieces)
                ->withCount(['pieces as matching_pieces_count' => $matchingChoicePieces])
                ->orderBy('name')->get();
            $lengths = Tag::where('type', 'length')->whereIn('name', self::LENGTHS)
                ->whereHas('pieces', $matchingChoicePieces)
                ->withCount(['pieces as matching_pieces_count' => $matchingChoicePieces])->get()
                ->sortBy(function ($tag) { return array_search($tag->name, self::LENGTHS, true); })->values();
            $periods = Tag::whereIn('id', $tags->where('type', 'period')->pluck('id'))
                ->whereHas('pieces', $matchingChoicePieces)
                ->withCount(['pieces as matching_pieces_count' => $matchingChoicePieces])
                ->orderBy('order')->orderBy('name')->get();
        }
        $composers = Composer::select(['id', 'name', 'cover_path', 'country_id'])->withCount([])->has('pieces')->get();
        $countries = $composers->pluck('country')->filter()->unique('id')->sortBy('name')->values();
        $portraits = $composers->shuffle()->take(3);
        return compact('levels', 'selected', 'moods', 'tags', 'countries', 'portraits',
            'guide', 'choices', 'breadcrumbs', 'selectionLabel', 'selectedTag', 'activeSection', 'hasSelection', 'techniques', 'lengths', 'periods');
    }

    private function moodImage($mood, array $excluded)
    {
        $pieces = $this->query(['mood' => $mood])->select(['pieces.id', 'pieces.cover_path'])
            ->setEagerLoads([])->withCount([])->inRandomOrder()->cursor();
        $checkedPeriods = [];
        foreach ($pieces as $piece) {
            if ($piece->cover_path) {
                $image = $piece->web_image_background;
            } else {
                $piece->loadMissing('tags');
                $period = $piece->period;
                if ($period && in_array($period->id, $checkedPeriods, true)) continue;
                if ($period) $checkedPeriods[] = $period->id;
                $image = $period ? $period->webCoverImageExcept($excluded) : asset('images/webapp/thumbnail.jpg');
            }
            if ($image && !in_array($image, $excluded, true)) return $image;
        }
        return null;
    }

    private function choice(array $params)
    {
        $query = $this->query($params);
        return [
            'params' => $params,
            'count' => (clone $query)->count(),
        ];
    }

    public static function url($params = [], $label = 'Explore repertoire')
    {
        return route('webapp.search.results', array_merge(['catalogue' => 1, 'search' => $label], $params));
    }

    public function query(array $params)
    {
        $query = Piece::query();
        if (!empty($params['level'])) $query->whereHas('tags', function ($q) use ($params) {
            $q->whereIn('type', ['level', 'sublevel'])->where('name', $params['level']);
        });
        if (!empty($params['mood'])) $query->whereHas('tags', function ($q) use ($params) {
            $q->where('type', 'mood');
            if (isset(self::MOODS[$params['mood']])) {
                $q->whereIn('name', self::MOODS[$params['mood']]['tags']);
            } elseif (preg_match('/^tag-([1-9][0-9]*)$/', $params['mood'], $match)) {
                $q->where('tags.id', $match[1]);
            } else {
                $q->whereRaw('1 = 0');
            }
        });
        if (!empty($params['tag'])) $query->whereHas('tags', function ($q) use ($params) {
            $q->whereIn('type', ['technique', 'period', 'genre'])->where('tags.id', $params['tag']);
        });
        if (!empty($params['composers']) && $params['composers'] !== 'all') $query->whereHas('composer', function ($q) use ($params) {
            ComposerGroups::apply($q, $params['composers']);
        });
        if (!empty($params['country'])) $query->whereHas('composer', function ($q) use ($params) {
            $q->where('country_id', $params['country']);
        });
        if (!empty($params['short'])) $query->whereHas('tags', function ($q) {
            $q->where('type', 'length')->where('name', 'short');
        });
        if (!empty($params['length'])) $query->whereHas('tags', function ($q) use ($params) {
            $q->where('type', 'length')->where('name', $params['length']);
        });
        if (!empty($params['past'])) $query->whereNotNull('highlighted_at')->where('is_free', false);
        return $query;
    }

    public function results(Request $request)
    {
        $params = $request->validate([
            'level' => ['nullable', Rule::in(self::LEVELS)],
            'mood' => $this->moodRules(),
            'composers' => ['nullable', Rule::in(array_keys(ComposerGroups::OPTIONS))], 'country' => 'nullable|integer|min:1',
            'tag' => 'nullable|integer|min:1', 'short' => 'nullable|boolean', 'past' => 'nullable|boolean',
            'length' => ['nullable', Rule::in(self::LENGTHS)],
            'filters' => 'nullable|array|max:6', 'filters.*' => ['string', 'max:1024', function ($attribute, $value, $fail) {
                $names = json_decode($value, true);
                if (!is_array($names) || array_values($names) !== $names || count($names) > 20
                    || count(array_filter($names, 'is_string')) !== count($names)) $fail('Invalid filters.');
            }],
        ]);
        $query = $this->query($params)->latest()->orderByDesc('pieces.id');
        foreach ($params['filters'] ?? [] as $names) $query->whereHas('tags', function ($q) use ($names) {
            $q->whereIn('name', json_decode($names, true));
        });
        $pieces = auth('web')->guest() ? $query->limit(3)->get() : $query->simplePaginate(10)->getCollection();
        return PieceCards::load($pieces);
    }
}
