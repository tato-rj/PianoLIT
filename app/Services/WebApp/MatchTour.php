<?php

namespace App\Services\WebApp;

use App\Piece;
use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Support\Facades\Crypt;

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

    const MOODS = [
        'calm' => ['label' => 'Calm & peaceful', 'icon' => 'moon', 'tags' => ['calm', 'dreamy']],
        'energetic' => ['label' => 'Energetic', 'icon' => 'sun', 'tags' => ['fast', 'flashy']],
        'romantic' => ['label' => 'Romantic', 'icon' => 'heart', 'tags' => ['dreamy', 'elegant']],
        'joyful' => ['label' => 'Joyful', 'icon' => 'sprout', 'tags' => ['happy']],
        'dramatic' => ['label' => 'Dramatic', 'icon' => 'cloud', 'tags' => ['agitated', 'agitaded', 'crazy']],
        'mysterious' => ['label' => 'Mysterious', 'icon' => 'mountain', 'tags' => ['mysterious', 'melancholic']],
        'playful' => ['label' => 'Playful', 'icon' => 'sparkles', 'tags' => ['happy', 'elegant']],
        'reflective' => ['label' => 'Reflective', 'icon' => 'waves', 'tags' => ['calm', 'melancholic']],
        'open' => ['label' => 'Open to anything', 'icon' => 'sparkles', 'tags' => []],
    ];

    public static function artwork(Piece $piece)
    {
        return $piece->cover_path ? storage($piece->cover_path)
            : (optional($piece->tags->firstWhere('type', 'period'))->cover_image ?: asset('images/webapp/thumbnail.jpg'));
    }

    public static function video(Piece $piece)
    {
        $videos = $piece->tutorials->filter(function ($tutorial) {
            return trim((string) $tutorial->video_url) !== '';
        });
        return $videos->first(function ($tutorial) {
            return strtolower($tutorial->type) === 'performance';
        }) ?? $videos->first();
    }

    public static function card(Piece $piece, $withVideo = false)
    {
        $video = $withVideo ? self::video($piece) : null;
        return [
            'id' => $piece->id, 'title' => $piece->medium_name, 'composer' => $piece->composer->short_name,
            'image' => $piece->composer->cover_image, 'artwork' => self::artwork($piece),
            'audio' => $piece->audio_path ? storage($piece->audio_path) : null,
            'video' => $video ? $video->video_url : null, 'url' => route('webapp.pieces.show', $piece),
        ];
    }

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

    private function listeningPool()
    {
        return Piece::freePicks(false)->whereNotNull('audio_path')->where('audio_path', '!=', '')
            ->whereHas('tags', function ($q) { $q->where('type', 'level'); });
    }

    public function drawIds($token)
    {
        try { $draw = json_decode(Crypt::decryptString($token), true); }
        catch (DecryptException $exception) { return null; }
        $ids = $draw['ids'] ?? null;
        if (($draw['version'] ?? null) !== 1 || !is_int($draw['expires'] ?? null) || $draw['expires'] < now()->timestamp
            || !is_array($ids) || count($ids) !== 10 || count(array_filter($ids, 'is_int')) !== 10
            || count(array_unique($ids)) !== 10) return null;
        return $this->listeningPool()->whereIn('pieces.id', $ids)->count() === 10 ? $ids : null;
    }

    public function data()
    {
        // Reuse the historical free-pick list for every questionnaire example.
        $pool = $this->listeningPool()->select(['pieces.id', 'composer_id', 'name', 'audio_path', 'cover_path', 'show_on_tour'])
            ->with(['composer' => function ($query) {
                $query->select(['id', 'name', 'cover_path'])->withCount([])->setEagerLoads([]);
            }, 'tags' => function ($query) { $query->select(['tags.id', 'name', 'type']); }])->withCount([])
            ->get()->shuffle()->keyBy('id');
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
                // Sample the opening four from the whole pool, then favor contrasting duels.
                if ($selected->count() < 4) $score = 0;
                // Random pool order also breaks contrast ties differently on each tour.
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
                'audio' => storage($piece->audio_path), 'artwork' => self::artwork($piece),
                'level' => optional($piece->tags->firstWhere('type', 'level'))->name,
                'traits' => $piece->tags->whereIn('type', ['period', 'mood', 'genre'])->pluck('name')->values()->all(),
            ];
        })->values()->all();
        $scores = [];
        foreach (['easy' => 'elementary', 'beginner' => 'beginner', 'middle' => 'intermediate', 'hard' => 'advanced'] as $key => $level) {
            $piece = Piece::freePicks(false)->select(['pieces.id', 'composer_id', 'name', 'score_path'])
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
        $ready = count($cards) === 10 && !in_array(null, \Illuminate\Support\Arr::except($scores, 'beginner'), true);
        return [
            'draw' => $ready ? Crypt::encryptString(json_encode(['version' => 1, 'ids' => array_column($cards, 'id'), 'expires' => now()->addHours(2)->timestamp])) : null,
            'total' => $this->catalog()->count(), 'pieces' => $cards, 'scores' => $scores,
            'intents' => self::INTENTS, 'moods' => collect(self::MOODS)->map(function ($mood) { return \Illuminate\Support\Arr::except($mood, 'tags'); })->all(), 'previewSeconds' => config('webapp.match_tour_audio_seconds', 60),
            'ready' => $ready,
        ];
    }

    public function level(array $reading, $fallback = 'intermediate')
    {
        if ($reading[0] === null) return $fallback;
        if ($reading[1] === null) return $reading[0] ? 'intermediate' : 'beginner';
        return $reading[0] ? ($reading[1] ? 'advanced' : 'intermediate') : ($reading[1] ? 'beginner' : 'elementary');
    }

    public function keywords(array $answers)
    {
        $intentTags = [
            'quick' => ['short'], 'work' => ['long'], 'personal' => ['dreamy', 'calm', 'elegant'],
            'impressive' => ['flashy', 'fast'], 'unfamiliar' => [],
        ];
        $fallback = 'intermediate';
        if ($answers['reading'][0] === null) {
            $preferred = Piece::with('tags')->withCount([])->find($answers['preferredPiece']);
            $fallback = optional($preferred ? $preferred->tags->firstWhere('type', 'level') : null)->name ?? $fallback;
        }
        $tags = isset($answers['mood']) ? self::MOODS[$answers['mood']]['tags'] : $intentTags[$answers['intent']];
        return array_merge([$answers['preferredPiece']], array_values(array_filter($answers['winners'], function ($id) { return $id !== null; })),
            [$this->level($answers['reading'], $fallback)], $tags);
    }

    public function exclusions(array $answers)
    {
        // Use the engine's existing exclusion input for unfamiliar repertoire.
        return ($answers['intent'] ?? null) === 'unfamiliar' ? Piece::famous()->pluck('id')->all() : [];
    }
}
