<?php

namespace App\Services\WebApp;

use App\{Composer, Piece, Tag};
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
        $request->validate(['level' => ['nullable', Rule::in(self::LEVELS)]]);
        $levels = Tag::whereIn('type', ['level', 'sublevel'])->whereIn('name', self::LEVELS)->withCount('pieces')->get()
            ->sortBy(function ($tag) { return array_search($tag->name, self::LEVELS, true); })->values();
        $selected = $request->filled('level') ? $levels->firstWhere('name', $request->level) : $levels->first();
        abort_if($request->filled('level') && !$selected, 404);
        $moods = collect(self::MOODS)->map(function ($mood, $key) use ($selected) {
            $params = ['mood' => $key];
            if ($selected) $params['level'] = $selected->name;
            $query = $this->query($params);
            $mood['count'] = (clone $query)->count();
            // Examples open the existing piece player, retaining its access rules.
            $mood['example'] = (clone $query)->select(['pieces.id'])->setEagerLoads([])->withCount([])
                ->whereNotNull('audio_path')->where('audio_path', '!=', '')->orderBy('pieces.id')->first();
            return $mood;
        });
        $tags = Tag::whereIn('type', ['technique', 'period', 'genre'])->has('pieces')->orderBy('name')->get();
        $composers = Composer::select(['id', 'name', 'cover_path', 'country_id'])->withCount([])->has('pieces')->get();
        $countries = $composers->pluck('country')->filter()->unique('id')->sortBy('name')->values();
        $portraits = $composers->shuffle()->take(3);
        return compact('levels', 'selected', 'moods', 'tags', 'countries', 'portraits');
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
