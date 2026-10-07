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
        $pool = $this->catalog()->select(['pieces.id', 'composer_id', 'name', 'audio_path', 'show_on_tour'])
            ->with(['composer' => function ($query) {
                $query->select(['id', 'name', 'cover_path'])->withCount([])->setEagerLoads([]);
            }, 'tags' => function ($query) { $query->select(['tags.id', 'name', 'type']); }])->withCount([])
            ->whereNotNull('audio_path')->where('audio_path', '!=', '')
            ->whereHas('tags', function ($q) { $q->where('type', 'level'); })
            ->orderByDesc('show_on_tour')->orderBy('id')->get()->keyBy('id');
        $selected = collect();
        // Calculate each candidate's trait set once. Keep its nearest distance as
        // picks are added instead of sorting/rebuilding every pair on every round.
        $traits = [];
        $distances = [];
        foreach ($pool as $piece) {
            $traits[$piece->id] = array_fill_keys($piece->tags
                ->whereIn('type', ['period', 'mood', 'genre'])->pluck('id')->all(), true);
        }
        $previous = null;
        while ($selected->count() < 10 && $pool->isNotEmpty()) {
            $winner = null;
            $best = -INF;
            foreach ($pool as $piece) {
                if ($previous) {
                    $distance = ($piece->composer_id !== $previous->composer_id ? 3 : 0)
                        + count(array_diff_key($traits[$piece->id], $traits[$previous->id]))
                        + count(array_diff_key($traits[$previous->id], $traits[$piece->id]));
                    $distances[$piece->id] = min($distances[$piece->id] ?? INF, $distance);
                    $score = $distances[$piece->id] + ($piece->show_on_tour ? 0.5 : 0);
                } else {
                    $score = $piece->show_on_tour ? 1 : 0;
                }
                // Preserve editorial/ID ordering when scores tie.
                if ($score > $best) { $winner = $piece; $best = $score; }
            }
            $selected->push($winner);
            $pool->forget($winner->id);
            $previous = $winner;
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
            $piece = Piece::select(['pieces.id', 'composer_id', 'name', 'score_path'])
                ->with(['composer' => function ($query) {
                    $query->select(['id', 'name'])->withCount([])->setEagerLoads([]);
                }])->withCount([])->byLevel($level)
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
