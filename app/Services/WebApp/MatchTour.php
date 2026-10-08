<?php

namespace App\Services\WebApp;

use App\Piece;
use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Support\Facades\Crypt;

/** Web-only presentation and adapter for the existing FindYourMatch Quiz. */
class MatchTour
{
    const LEVELS = [
        'elementary' => ['label' => 'Elementary', 'hint' => 'Simple melodies and rhythms'],
        'beginner' => ['label' => 'Beginner', 'hint' => 'Hands together and basic chords'],
        'intermediate' => ['label' => 'Intermediate', 'hint' => 'Independent hands, shaping and pedal'],
        'advanced' => ['label' => 'Advanced', 'hint' => 'Demanding technique and control'],
    ];

    public static function baseLevel($name)
    {
        return preg_replace('/^(early|late) /', '', (string) $name);
    }

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
        return $piece->web_image_background;
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
        $draw = $this->drawChoices($token);
        return $draw ? $draw['ids'] : null;
    }

    public function drawChoices($token)
    {
        try { $draw = json_decode(Crypt::decryptString($token), true); }
        catch (DecryptException $exception) { return null; }
        $ids = $draw['ids'] ?? null;
        if (!in_array($draw['version'] ?? null, [1, 2], true) || !is_int($draw['expires'] ?? null) || $draw['expires'] < now()->timestamp
            || !is_array($ids) || count($ids) !== 10 || count(array_filter($ids, 'is_int')) !== 10
            || count(array_unique($ids)) !== 10) return null;
        if ($this->listeningPool()->whereIn('pieces.id', $ids)->count() !== 10) return null;
        // Version-one tours already open at deployment can still finish for two hours.
        if ($draw['version'] === 1) { $draw['levels'] = []; return $draw; }
        $levels = $draw['levels'] ?? null;
        if (!is_array($levels) || array_keys($levels) !== array_keys(self::LEVELS)
            || count(array_filter($levels, 'is_int')) !== 4 || count(array_unique($levels)) !== 4) return null;
        $pieces = $this->listeningPool()->whereIn('pieces.id', $levels)->select('pieces.id')->withCount([])->setEagerLoads([])->with('tags')->get()->keyBy('id');
        foreach ($levels as $level => $id) {
            $piece = $pieces->get($id);
            if (!$piece || self::baseLevel(optional($piece->tags->firstWhere('type', 'level'))->name) !== $level) return null;
        }
        return $draw;
    }

    private function questionCard(Piece $piece)
    {
        return [
            'id' => $piece->id, 'title' => $piece->name,
            'composer' => $piece->composer->short_name, 'image' => $piece->composer->cover_image,
            'audio' => storage($piece->audio_path), 'artwork' => self::artwork($piece),
            'level' => optional($piece->tags->firstWhere('type', 'level'))->name,
            'traits' => $piece->tags->whereIn('type', ['period', 'mood', 'genre'])->pluck('name')->values()->all(),
        ];
    }

    public function data()
    {
        // Reuse the historical free-pick list for every questionnaire example.
        $pool = $this->listeningPool()->select(['pieces.id', 'composer_id', 'name', 'audio_path', 'cover_path', 'show_on_tour'])
            ->with(['composer' => function ($query) {
                $query->select(['id', 'name', 'cover_path'])->withCount([])->setEagerLoads([]);
            }, 'tags' => function ($query) { $query->select(['tags.id', 'name', 'type']); }])->withCount([])
            ->get()->shuffle()->keyBy('id');
        // Sample one real example per playing range from the same loaded, shuffled pool.
        $levelPieces = collect(self::LEVELS)->map(function ($definition, $level) use ($pool) {
            return $pool->first(function ($piece) use ($level) {
                return self::baseLevel(optional($piece->tags->firstWhere('type', 'level'))->name) === $level;
            });
        })->filter()->map(function ($piece) { return $this->questionCard($piece); });
        // Listening measures taste, so omit examples below late beginner. Reuse
        // the existing extended-level accessor (sublevel first) and loaded tags.
        $openingPool = $pool->filter(function ($piece) {
            return in_array(optional($piece->extended_level)->name, [
                'late beginner', 'intermediate', 'early intermediate', 'late intermediate',
                'advanced', 'early advanced', 'late advanced',
            ], true);
        });
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
                // Randomize the eligible listening choices, then favor contrasting duels.
                if ($selected->count() < 4) {
                    if (!$openingPool->has($piece->id)) continue;
                    $score = 0;
                }
                // Random pool order also breaks contrast ties differently on each tour.
                if ($score > $best) { $winner = $piece; $best = $score; }
            }
            if (!$winner) break; // Never fill a missing listening choice with a simpler piece.
            $selected->push($winner);
            $pool->forget($winner->id);
            $previous = $winner;
        }
        $cards = $selected->map(function ($piece) { return $this->questionCard($piece); })->values()->all();
        $scores = [];
        foreach (['easy' => 'elementary', 'beginner' => 'beginner', 'middle' => 'intermediate', 'hard' => 'advanced'] as $key => $level) {
            $piece = Piece::freePicks(false)->select(['pieces.id', 'composer_id', 'name', 'score_path'])
                ->with(['composer' => function ($query) {
                    $query->select(['id', 'name'])->withCount([])->setEagerLoads([]);
                }])->withCount([])->whereHas('tags', function ($query) use ($level) {
                    $query->where('type', 'level')->whereIn('name', [$level, 'early '.$level, 'late '.$level]);
                })
                ->whereNotNull('score_path')->where('score_path', '!=', '')
                ->where(function ($q) { $q->whereNull('score_url')->orWhere('score_url', ''); })
                ->orderByDesc('show_on_tour')->orderBy('id')->first();
            $scores[$key] = $piece ? [
                'id' => $piece->id, 'title' => $piece->name,
                'composer' => $piece->composer->short_name, 'url' => storage($piece->score_path),
            ] : null;
        }
        $ready = $levelPieces->count() === 4 && count($cards) === 10 && !in_array(null, \Illuminate\Support\Arr::except($scores, 'beginner'), true);
        return [
            'draw' => $ready ? Crypt::encryptString(json_encode(['version' => 2, 'levels' => $levelPieces->map(function ($piece) { return $piece['id']; })->all(), 'ids' => array_column($cards, 'id'), 'expires' => now()->addHours(2)->timestamp])) : null,
            'levelPieces' => $levelPieces->values()->all(), 'levels' => self::LEVELS,
            'total' => $this->catalog()->count(), 'pieces' => $cards, 'scores' => $scores,
            'intents' => self::INTENTS, 'moods' => collect(self::MOODS)->map(function ($mood) { return \Illuminate\Support\Arr::except($mood, 'tags'); })->all(), 'previewSeconds' => config('webapp.match_tour_audio_seconds', 60),
            'ready' => $ready,
        ];
    }

    public function level(array $reading, $fallback = 'intermediate', $playingLevel = null)
    {
        if ($reading[0] === null) return $playingLevel ?? $fallback;
        $readingLevel = $reading[1] === null ? ($reading[0] ? 'intermediate' : 'beginner')
            : ($reading[0] ? ($reading[1] ? 'advanced' : 'intermediate') : ($reading[1] ? 'beginner' : 'elementary'));
        if ($playingLevel === null) return $readingLevel;
        // Playing familiar repertoire and reading unfamiliar music are different skills.
        // Weight the chosen playing range twice; reading can refine it by at most one range.
        $levels = array_keys(self::LEVELS);
        return $levels[(int) round((2 * array_search($playingLevel, $levels, true) + array_search($readingLevel, $levels, true)) / 3)];
    }

    public function keywords(array $answers)
    {
        $intentTags = [
            'quick' => ['short'], 'work' => ['long'], 'personal' => ['dreamy', 'calm', 'elegant'],
            'impressive' => ['flashy', 'fast'], 'unfamiliar' => [],
        ];
        $fallback = 'intermediate';
        if ($answers['reading'][0] === null && !isset($answers['playingLevel'])) {
            $preferred = Piece::with('tags')->withCount([])->find($answers['preferredPiece']);
            $fallback = optional($preferred ? $preferred->tags->firstWhere('type', 'level') : null)->name ?? $fallback;
        }
        $tags = isset($answers['mood']) ? self::MOODS[$answers['mood']]['tags'] : $intentTags[$answers['intent']];
        return array_merge([$answers['preferredPiece']], array_values(array_filter($answers['winners'], function ($id) { return $id !== null; })),
            [$this->level($answers['reading'], $fallback, $answers['playingLevel'] ?? null)], $tags);
    }

    public function exclusions(array $answers)
    {
        // Use the engine's existing exclusion input for unfamiliar repertoire.
        return ($answers['intent'] ?? null) === 'unfamiliar' ? Piece::famous()->pluck('id')->all() : [];
    }

    public function explanation(array $answers, array $context)
    {
        $describe = function ($tags) {
            return collect($tags)->map(function ($tag) { return $tag === 'agitaded' ? 'agitated' : $tag; })->unique()->take(2)->implode(' and ');
        };
        $shared = $describe($context['sharedMoods']);
        $reasons = $shared ? ["It shares the {$shared} character of the pieces you enjoyed."] : [];
        $mood = $answers['mood'] ?? null;
        if ($mood && $mood !== 'open' && array_intersect(self::MOODS[$mood]['tags'], $context['matchedTags'])) {
            $reasons[] = 'Its character fits your mood: '.lcfirst(self::MOODS[$mood]['label']).'.';
        } elseif (!$mood) {
            $intent = $answers['intent'] ?? null;
            if ($intent === 'quick' && in_array('short', $context['matchedTags'])) $reasons[] = 'Its shorter length suits your wish for a quick piece.';
            if ($intent === 'work' && in_array('long', $context['matchedTags'])) $reasons[] = 'Its longer format gives you a piece to spend time with.';
            if ($intent === 'impressive' && array_intersect(['flashy', 'fast'], $context['matchedTags'])) $reasons[] = 'Its showy character fits your wish for something impressive.';
        }
        if ($answers['reading'][0] !== null && $context['level'] && ($context['levelMatched'] ?? !$context['fallback'])) {
            $reasons[] = (isset($answers['playingLevel']) ? 'Your playing level and sight-reading answers guided us toward ' : 'Your sight-reading answers guided us toward ').$context['level'].' repertoire.';
        } elseif ($answers['reading'][0] === null && ($context['levelMatched'] ?? false)) {
            $reasons[] = isset($answers['playingLevel']) ? 'Its difficulty fits the playing level you chose.' : 'Its difficulty is in the same range as the piece you enjoyed.';
        } elseif (($context['nearestLevel'] ?? false) && $context['pieceLevel']) {
            $reasons[] = 'Its '.$context['pieceLevel'].' difficulty is the closest available to '.(isset($answers['playingLevel']) ? 'your playing level and sight-reading answers.' : ($answers['reading'][0] !== null ? 'the level indicated by your sight-reading answers.' : 'the piece you enjoyed.'));
        }
        if ($context['fallback']) array_unshift($reasons, 'Here is the best match we could find, with a video and score ready for you.');
        if (!$reasons) $reasons[] = 'Explore this piece with its video and score ready for you.';
        return implode(' ', $reasons);
    }
}
