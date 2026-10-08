<?php

namespace App\Services\WebApp;

use App\{Composer, Country, Piece, Tag};

class Explore
{
    public function data()
    {
        // Web presentation only: do not change the shared/mobile Explore feed.
        $tags = Tag::withCount('pieces')->orderBy('order')->orderBy('name')->get();
        $styles = $tags->whereIn('type', ['period', 'genre']);
        $featuredPeriods = collect(['baroque', 'classical', 'romantic', 'impressionist'])
            ->map(function ($name) use ($styles) { return $styles->firstWhere('name', $name); })->filter();
        $otherPeriods = collect(['modern', 'jazz', 'contemporary'])->merge(
            $styles->where('type', 'period')->whereNotIn('id', $featuredPeriods->pluck('id'))->pluck('name')
        )->unique()->values();
        $styleNames = $featuredPeriods->pluck('name')->merge($otherPeriods)->merge($styles->pluck('name'))->unique()->values();
        $levels = collect(['elementary', 'early beginner', 'late beginner', 'early intermediate', 'late intermediate', 'advanced'])
            ->map(function ($name) use ($tags) {
                return $tags->whereIn('type', ['level', 'sublevel'])->firstWhere('name', $name);
            })->filter();
        // These are search shortcuts, so they remain useful without a local tag row.
        $moodIcons = ['calm' => 'waves', 'dreamy' => 'moon', 'playful' => 'sparkles', 'dramatic' => 'wind', 'melancholic' => 'cloud'];
        $moods = collect(array_keys($moodIcons));
        $browseTags = $tags->whereNotIn('name', ['beginner', 'intermediate'])->groupBy(function ($tag) {
            return $tag->type === 'sublevel' ? 'level' : $tag->type;
        });
        $composers = Composer::has('pieces')->inRandomOrder()->take(3)->get();
        $countries = Country::has('pieces')->orderBy('name')->get();
        $freePicks = Piece::freePicks()->orderByDesc('id')->with(['tags', 'composer'])->take(3)->get();

        return compact('featuredPeriods', 'otherPeriods', 'styleNames', 'levels', 'moods', 'moodIcons', 'browseTags', 'composers', 'countries', 'freePicks');
    }
}
