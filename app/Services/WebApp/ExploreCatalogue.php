<?php

namespace App\Services\WebApp;

use App\{Composer, Country, Piece, Tag};
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/** Guided browsing belongs to the web app, never the shared mobile feed. */
class ExploreCatalogue
{
    const LEVELS = ['elementary', 'early beginner', 'late beginner', 'early intermediate', 'late intermediate', 'advanced'];
    const MOODS = [
        'gentle' => ['label' => 'Gentle & lyrical', 'description' => 'Flowing melodies and a softer touch.', 'icon' => 'feather', 'tags' => ['calm', 'elegant', 'lyrical']],
        'playful' => ['label' => 'Playful & lively', 'description' => 'Light rhythms and bright character.', 'icon' => 'sun', 'tags' => ['happy', 'playful', 'fast']],
        'dramatic' => ['label' => 'Dramatic', 'description' => 'Strong rhythms and bold contrasts.', 'icon' => 'flame', 'tags' => ['agitated', 'agitaded', 'crazy', 'dramatic', 'flashy']],
        'dreamy' => ['label' => 'Dreamy & atmospheric', 'description' => 'Floating textures and quiet moods.', 'icon' => 'moon', 'tags' => ['dreamy', 'mysterious']],
        'reflective' => ['label' => 'Reflective & melancholy', 'description' => 'Thoughtful melodies with a wistful feel.', 'icon' => 'waves', 'tags' => ['melancholic', 'reflective']],
    ];

    public function data(Request $request)
    {
        $params = array_filter($request->validate([
            'level' => ['nullable', Rule::in(self::LEVELS)],
            'mood' => ['nullable', Rule::in(array_keys(self::MOODS))],
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
        $moods = collect(self::MOODS)->map(function ($mood, $key) {
            $piece = $this->query(['mood' => $key])->select(['pieces.id', 'pieces.cover_path'])
                ->setEagerLoads([])->withCount([])->inRandomOrder()->first();
            if ($piece && !$piece->cover_path) $piece->loadMissing('tags');
            return array_merge($mood, ['image' => $piece ? $piece->web_image_background : null]);
        });
        if ($selected && !isset($params['mood'])) $moods = $moods->map(function ($mood, $key) use ($params) {
            return array_merge($mood, $this->choice(array_merge($params, ['mood' => $key])));
        });
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
                $label = self::MOODS[$value]['label'];
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
                $choices = $moods;
                $guide['heading'] = 'By character';
            } else {
                $guide['heading'] = 'Keep exploring';
            }
        }
        $tags = Tag::where(function ($query) {
            $query->where('type', 'technique')->has('pieces', '>=', 8);
        })->orWhere(function ($query) {
            $query->whereIn('type', ['period', 'genre'])->has('pieces', '>=', 10);
        })->orderBy('name')->get();
        $techniques = collect();
        if (!$selectedTag && $guide) {
            $matchingPieces = $this->query($params)->select('pieces.id')->setEagerLoads([])->withCount([]);
            $techniques = Tag::whereIn('id', $tags->where('type', 'technique')->pluck('id'))
                ->whereHas('pieces', function ($query) use ($matchingPieces) {
                    $query->whereIn('pieces.id', $matchingPieces);
                })->orderBy('name')->get();
        }
        $composers = Composer::select(['id', 'name', 'cover_path', 'country_id'])->withCount([])->has('pieces')->get();
        $countries = $composers->pluck('country')->filter()->unique('id')->sortBy('name')->values();
        $portraits = $composers->shuffle()->take(3);
        return compact('levels', 'selected', 'moods', 'tags', 'countries', 'portraits',
            'guide', 'choices', 'breadcrumbs', 'selectionLabel', 'selectedTag', 'activeSection', 'hasSelection', 'techniques');
    }

    private function choice(array $params)
    {
        $query = $this->query($params);
        return [
            'params' => $params,
            'count' => (clone $query)->count(),
            'example' => (clone $query)->select('pieces.*')->setEagerLoads([])->withCount([])
                ->with(['composer' => function ($query) { $query->withCount([]); }])
                ->whereNotNull('audio_path')->where('audio_path', '!=', '')->orderBy('pieces.id')->first(),
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
            $q->where('type', 'mood')->whereIn('name', self::MOODS[$params['mood']]['tags']);
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
        if (!empty($params['past'])) $query->whereNotNull('highlighted_at')->where('is_free', false);
        return $query;
    }

    public function results(Request $request)
    {
        $params = $request->validate([
            'level' => ['nullable', Rule::in(self::LEVELS)],
            'mood' => ['nullable', Rule::in(array_keys(self::MOODS))],
            'composers' => ['nullable', Rule::in(array_keys(ComposerGroups::OPTIONS))], 'country' => 'nullable|integer|min:1',
            'tag' => 'nullable|integer|min:1', 'short' => 'nullable|boolean', 'past' => 'nullable|boolean',
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
