<?php

namespace App\Services\WebApp;

use App\Piece;

/** Web-only presentation and adapter for the existing FindYourMatch Quiz. */
class MatchTour
{
    const INTENTS = [
        'quick' => 'Something I can learn quickly',
        'work' => 'Something to really work on',
        'personal' => 'Something beautiful to play for myself',
        'impressive' => 'Something impressive',
        'unfamiliar' => 'Something unfamiliar',
    ];

    public function catalog()
    {
        // The Quiz uses these tagged pieces for similarity, with free picks as fallback.
        return Piece::query()->where(function ($query) {
            $query->where(function ($query) {
                $query->whereNotIn('composer_id', [32, 50])
                    ->whereHas('tags', function ($q) { $q->where('type', 'level'); })
                    ->whereHas('tags', function ($q) { $q->where('type', 'mood'); });
            })->orWhereNotNull('highlighted_at');
        });
    }

    public function data()
    {
        $pool = $this->catalog()->with(['composer', 'tags'])->withCount([])
            ->whereNotNull('audio_path')->where('audio_path', '!=', '')
            ->whereHas('tags', function ($q) { $q->where('type', 'level'); })
            ->orderByDesc('show_on_tour')->orderBy('id')->get();
        $selected = collect();
        // Prefer editorial tour picks, then maximize contrast in period, mood and composer.
        while ($selected->count() < 10 && $pool->isNotEmpty()) {
            $piece = $pool->sortByDesc(function ($piece) use ($selected) {
                if ($selected->isEmpty()) return $piece->show_on_tour ? 1 : 0;
                return $selected->map(function ($other) use ($piece) {
                    $types = ['period', 'mood', 'genre'];
                    $a = $piece->tags->whereIn('type', $types)->pluck('id');
                    $b = $other->tags->whereIn('type', $types)->pluck('id');
                    return ($piece->composer_id !== $other->composer_id ? 3 : 0)
                        + $a->diff($b)->count() + $b->diff($a)->count();
                })->min() + ($piece->show_on_tour ? 0.5 : 0);
            })->first();
            $selected->push($piece);
            $pool = $pool->reject(function ($other) use ($piece) { return $other->id === $piece->id; });
        }
        $cards = $selected->map(function ($piece) {
            return [
                'id' => $piece->id, 'title' => $piece->name,
                'composer' => $piece->composer->short_name, 'image' => $piece->composer->cover_image,
                'audio' => storage($piece->audio_path),
                'traits' => $piece->tags->whereIn('type', ['period', 'mood', 'genre'])->pluck('name')->values()->all(),
            ];
        })->values()->all();
        $scores = [];
        foreach (['easy' => 'elementary', 'middle' => 'intermediate', 'hard' => 'advanced'] as $key => $level) {
            $piece = Piece::with('composer')->withCount([])->byLevel($level)
                ->whereNotNull('score_path')->where('score_path', '!=', '')
                ->where(function ($q) { $q->whereNull('score_url')->orWhere('score_url', ''); })
                ->orderByDesc('show_on_tour')->orderBy('id')->first();
            $scores[$key] = $piece ? [
                'id' => $piece->id, 'title' => $piece->name,
                'composer' => $piece->composer->short_name, 'url' => storage($piece->score_path),
            ] : null;
        }
        return [
            'total' => $this->catalog()->count(), 'pieces' => $cards, 'scores' => $scores,
            'intents' => self::INTENTS, 'previewSeconds' => config('webapp.media_preview_seconds', 10),
            'ready' => count($cards) === 10 && !in_array(null, $scores, true),
        ];
    }

    public function level(array $reading)
    {
        return $reading[0] ? ($reading[1] ? 'advanced' : 'intermediate') : ($reading[1] ? 'beginner' : 'elementary');
    }

    public function keywords(array $answers)
    {
        $intentTags = [
            'quick' => ['short'], 'work' => ['long'], 'personal' => ['dreamy', 'calm', 'elegant'],
            'impressive' => ['flashy', 'fast'], 'unfamiliar' => [],
        ];
        return array_merge([$answers['preferredPiece']], $answers['winners'],
            [$this->level($answers['reading'])], $intentTags[$answers['intent']]);
    }

    public function exclusions(array $answers)
    {
        // Use the engine's existing exclusion input for unfamiliar repertoire.
        return $answers['intent'] === 'unfamiliar' ? Piece::famous()->pluck('id')->all() : [];
    }
}
