<?php

namespace App\Services\Api\V2;

use App\{Piece, User};
use App\Services\WebApp\{MatchQuiz, MatchTour, PieceCards};
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class MatchTours
{
    const SLOTS = ['level', 'listening', 'reading-1', 'reading-2', 'taste-1', 'taste-2', 'mood'];
    private $tour, $media;

    public function __construct(MatchTour $tour, MatchTourMedia $media)
    {
        $this->tour = $tour; $this->media = $media;
    }

    public function start(User $user, string $key): array
    {
        $this->enabled();
        $row = DB::transaction(function () use ($user, $key) {
            // Serialize concurrent starts for the same account before unique insertion.
            DB::table('users')->where('id', $user->id)->lockForUpdate()->first();
            $row = DB::table('api_v2_match_tours')->where('user_id', $user->id)->where('start_key', $key)->first();
            if ($row) { $this->alive($row); return $row; }
            $data = $this->tour->data();
            if (!$data['ready']) throw new MatchTourFailure('tour_unavailable', 'The match tour is unavailable. Please try again later.', 503);
            $state = $this->snapshot($data);
            $id = (string) Str::uuid();
            DB::table('api_v2_match_tours')->insert(['id' => $id, 'user_id' => $user->id, 'start_key' => $key, 'revision' => 0,
                'state' => json_encode($state), 'expires_at' => now()->addHours(2), 'created_at' => now()]);
            return DB::table('api_v2_match_tours')->where('id', $id)->first();
        }, 3);
        return $this->render($row);
    }

    public function load(User $user, string $id): array
    {
        $this->enabled();
        return $this->render($this->owned($user, $id));
    }

    public function answer(User $user, string $id, string $slot, array $input): array
    {
        $this->enabled();
        $row = DB::transaction(function () use ($user, $id, $slot, $input) {
            $row = $this->owned($user, $id, true);
            $state = json_decode($row->state, true);
            $fingerprint = hash('sha256', json_encode([$slot, $input['revision'], $input['value']]));
            $receipt = $state['requests'][$input['request_id']] ?? null;
            if ($receipt !== null) {
                if (!hash_equals($receipt, $fingerprint)) throw new MatchTourFailure('idempotency_conflict', 'Use a new request_id for a different answer.', 409);
                return $row; // Return current state; never replay a stale mutation.
            }
            if ($input['revision'] !== (int) $row->revision) throw new MatchTourFailure('stale_tour', 'Reload this tour before updating your answer.', 409);
            if (count($state['requests']) >= 256) throw new MatchTourFailure('tour_limit', 'Please start a new tour.', 409);
            $index = array_search($slot, self::SLOTS, true);
            foreach (array_slice(self::SLOTS, 0, $index) as $required) {
                if (!array_key_exists($required, $state['answers'])) throw new MatchTourFailure('incomplete_tour', 'Answer the earlier questions first.', 409);
            }
            if (!$this->validAnswer($state, $slot, $input['value'])) {
                throw new MatchTourFailure('invalid_answer', 'Choose an answer served for this question.', 422);
            }
            if (!array_key_exists($slot, $state['answers']) || $state['answers'][$slot] !== $input['value']) {
                foreach (array_slice(self::SLOTS, $index + 1) as $dependent) unset($state['answers'][$dependent]);
                $state['answers'][$slot] = $input['value'];
                $state['match'] = null;
                $row->revision++;
            }
            $state['requests'][$input['request_id']] = $fingerprint;
            $this->save($row, $state);
            return $row;
        }, 3);
        return $this->render($row);
    }

    public function result(User $user, string $id): array
    {
        $this->enabled();
        $row = DB::transaction(function () use ($user, $id) {
            $row = $this->owned($user, $id, true);
            $state = json_decode($row->state, true);
            if (count($state['answers']) !== count(self::SLOTS)) throw new MatchTourFailure('incomplete_tour', 'Complete all five steps before requesting a match.', 409);
            if (!$state['match']) {
                $answers = $this->engineAnswers($state);
                $quiz = app(MatchQuiz::class);
                $piece = $quiz->getKeywords($this->tour->keywords($answers))->exclude($this->tour->exclusions($answers))->search(true, true);
                if (!$piece) throw new MatchTourFailure('no_match', 'No match is available right now. Please try again later.', 503);
                $state['match'] = ['id' => (string) $piece->id, 'explanation' => $this->tour->explanation($answers, $quiz->matchContext($piece))];
                $row->revision++;
                $this->save($row, $state);
            }
            return $row;
        }, 3);
        return $this->render($row);
    }

    private function enabled(): void
    {
        if (!config('match-tour.enabled')) throw new MatchTourFailure('tour_unavailable', 'The match tour is unavailable. Please try again later.', 503);
    }

    private function alive($row): void
    {
        if (carbon($row->expires_at)->lte(now())) throw new MatchTourFailure('tour_expired', 'This tour has expired. Start a new tour.', 410);
    }

    private function owned(User $user, string $id, bool $lock = false)
    {
        $query = DB::table('api_v2_match_tours')->where('id', $id)->where('user_id', $user->id);
        $row = ($lock ? $query->lockForUpdate() : $query)->first();
        if (!$row) throw new MatchTourFailure('not_found', 'This tour is unavailable.', 404);
        $this->alive($row);
        return $row;
    }

    private function save($row, array $state): void
    {
        $row->state = json_encode($state);
        DB::table('api_v2_match_tours')->where('id', $row->id)->update(['state' => $row->state, 'revision' => $row->revision]);
    }

    private function snapshot(array $data): array
    {
        $ids = array_merge(array_column($data['levelPieces'], 'id'), array_column($data['pieces'], 'id'), collect($data['scores'])->filter()->pluck('id')->all());
        $pieces = Piece::whereIn('id', $ids)->select(['id', 'composer_id', 'audio_path', 'score_path'])
            ->setEagerLoads([])->withCount([])->with(['composer' => function ($query) { $query->setEagerLoads([])->withCount([]); }])->get()->keyBy('id');
        $card = function ($source) use ($pieces) {
            $piece = $pieces->get($source['id']);
            $fallback = MatchTourMedia::https(asset('images/misc/placeholder-image.png'));
            return ['id' => (string) $source['id'], 'title' => $source['title'],
                'composer' => ['id' => (string) $piece->composer->id, 'name' => $piece->composer->name,
                    'short_name' => $piece->composer->short_name, 'portrait_url' => MatchTourMedia::https($source['image']) ?? $fallback],
                'artwork_url' => MatchTourMedia::https($source['artwork']) ?? $fallback,
                'audio_preview' => $this->media->asset($piece->audio_path, 'audio'),
                'level' => $source['level'], 'traits' => $source['traits']];
        };
        $state = ['total' => $data['total'], 'levels' => [], 'pieces' => array_map($card, $data['pieces']),
            'scores' => [], 'answers' => [], 'requests' => [], 'match' => null];
        foreach ($data['levelPieces'] as $source) $state['levels'][MatchTour::baseLevel($source['level'])] = $card($source);
        foreach ($data['scores'] as $key => $score) {
            $state['scores'][$key] = $score ? ['id' => (string) $score['id'], 'title' => $score['title'], 'composer' => $score['composer'],
                'media' => $this->media->asset($pieces->get($score['id'])->score_path, 'score')] : null;
        }
        return $state;
    }

    private function validAnswer(array $state, string $slot, $value): bool
    {
        if ($slot === 'level') return is_string($value) && array_key_exists($value, $state['levels']);
        if ($slot === 'mood') return is_string($value) && array_key_exists($value, MatchTour::MOODS);
        if (strpos($slot, 'reading-') === 0) return in_array($value, ['easy', 'difficult'], true);
        if ($slot === 'listening') return in_array($value, array_column(array_slice($state['pieces'], 0, 4), 'id'), true);
        $offset = $slot === 'taste-1' ? 4 : 6;
        return $value === null || in_array($value, array_column(array_slice($state['pieces'], $offset, 2), 'id'), true);
    }

    private function engineAnswers(array $state): array
    {
        $answers = $state['answers'];
        return ['playingLevel' => $answers['level'], 'preferredPiece' => (int) $answers['listening'],
            'reading' => [$answers['reading-1'] === 'easy', $answers['reading-2'] === 'easy'],
            'winners' => array_map(function ($slot) use ($answers) { return $answers[$slot] === null ? null : (int) $answers[$slot]; }, ['taste-1', 'taste-2']),
            'mood' => $answers['mood']];
    }

    private function remaining(array $state): array
    {
        // Mirror the website's display estimates, not a second candidate-selection query.
        $total = $state['total']; $count = $total; $answers = $state['answers'];
        $after = function ($ratio, $floor) use ($total, &$count) { $count = min($total, max($floor, min($count - 1, (int) round($total * $ratio)))); };
        if (isset($answers['level'])) $after(0.75, 5);
        if (isset($answers['listening'])) {
            $preferred = collect($state['pieces'])->firstWhere('id', $answers['listening']);
            $after(0.50 - min(5, count(array_unique($preferred['traits']))) * 0.03, 4);
        }
        if (isset($answers['reading-2'])) {
            $level = $this->tour->level([$answers['reading-1'] === 'easy', $answers['reading-2'] === 'easy'], 'intermediate', $answers['level']);
            $after(['elementary' => .12, 'beginner' => .15, 'intermediate' => .18, 'advanced' => .14][$level], 3);
        }
        if (array_key_exists('taste-2', $answers)) {
            $traits = collect($state['pieces'])->whereIn('id', [$answers['taste-1'], $answers['taste-2']])->pluck('traits')->flatten()->all();
            $overlap = count($traits) ? 1 - count(array_unique($traits)) / count($traits) : 0;
            $after(.018 - $overlap * .012, 2);
        }
        return ['value' => $count, 'accuracy' => count($answers) ? 'estimated' : 'exact'];
    }

    private function render($row): array
    {
        $state = json_decode($row->state, true); $answers = $state['answers'];
        $current = null;
        foreach (self::SLOTS as $slot) if (!array_key_exists($slot, $answers)) { $current = $slot; break; }
        $piece = null;
        if ($state['match']) {
            $piece = Piece::withVideoAndScore()->freePicks(false)->find($state['match']['id']);
            if (!$piece) throw new MatchTourFailure('no_match', 'This match is no longer available. Change an answer or restart the tour.', 503);
            PieceCards::load(collect([$piece]), false);
        }
        $summary = $piece ? app(DiscoverResponse::class)->piece($piece) : null;
        if ($summary) {
            // Keep the existing DTO shape while making native URLs independent
            // of an internal HTTP proxy hop. Legacy/Discover serialization stays intact.
            $summary['artwork_url'] = MatchTourMedia::https($summary['artwork_url']) ?? MatchTourMedia::https(asset('images/misc/placeholder-image.png'));
            $summary['composer']['portrait_url'] = MatchTourMedia::https($summary['composer']['portrait_url']) ?? MatchTourMedia::https(asset('images/misc/placeholder-image.png'));
            $summary['target']['web_url'] = MatchTourMedia::https($summary['target']['web_url']);
            $summary['composer']['target']['web_url'] = MatchTourMedia::https($summary['composer']['target']['web_url']);
        }
        $card = function ($source) use ($row) {
            $source['audio_preview'] = $this->media->urls($source['audio_preview'], 'audio', $row->expires_at);
            unset($source['level'], $source['traits']);
            return $source;
        };
        $reading = function ($round) use ($state, $answers, $row) {
            if ($round === 2 && !isset($answers['reading-1'])) return null;
            $score = $this->tour->readingScore($state['scores'], [($answers['reading-1'] ?? null) === 'easy'], $round);
            $score['score_sample'] = $this->media->urls($score['media'], 'score', $row->expires_at);
            unset($score['media']);
            return $score;
        };
        $questions = ['level' => ['choices' => collect(MatchTour::LEVELS)->map(function ($definition, $key) use ($state, $card) {
            return ['value' => $key, 'label' => $definition['label'], 'description' => $definition['hint'], 'piece' => $card($state['levels'][$key])];
        })->values()->all()], 'listening' => ['choices' => array_map($card, array_slice($state['pieces'], 0, 4))],
            'reading-1' => $reading(1), 'reading-2' => $reading(2),
            'taste-1' => ['choices' => array_map($card, array_slice($state['pieces'], 4, 2))],
            'taste-2' => ['choices' => array_map($card, array_slice($state['pieces'], 6, 2))],
            'mood' => ['choices' => collect(MatchTour::MOODS)->map(function ($mood, $key) { return ['value' => $key, 'label' => $mood['label'], 'icon' => $mood['icon']]; })->values()->all()]];
        $step = $current ? ['level' => 1, 'listening' => 2, 'reading-1' => 3, 'reading-2' => 3, 'taste-1' => 4, 'taste-2' => 4, 'mood' => 5][$current] : null;
        return ['id' => $row->id, 'expires_at' => carbon($row->expires_at)->utc()->format('Y-m-d\TH:i:s\Z'), 'revision' => (int) $row->revision,
            'status' => $piece ? 'matched' : ($current ? 'in_progress' : 'ready'),
            'progress' => ['current_step' => $step, 'round' => $current && preg_match('/-(\d)$/', $current, $round) ? (int) $round[1] : null,
                'current_question' => $current, 'initial_library_total' => ['value' => $state['total'], 'accuracy' => 'exact'],
                'remaining_pieces' => $piece ? ['value' => 1, 'accuracy' => 'exact'] : $this->remaining($state)],
            'answers' => (object) $answers, 'questions' => $questions,
            'result' => $piece ? ['piece' => $summary, 'explanation' => $state['match']['explanation']] : null];
    }
}
