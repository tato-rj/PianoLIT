<?php

namespace App\Games\Duels;

use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class DuelService
{
    public function participant(Request $request, Duel $duel): DuelPlayer
    {
        $credential = $request->session()->get('games.duels.'.$duel->id);
        abort_unless(is_array($credential) && isset($credential['role'], $credential['token']), 403, 'This browser is not a participant in this Duel.');
        $player = $duel->players()->where('role', $credential['role'])->first();
        abort_unless($player && hash_equals($player->token_hash, hash('sha256', $credential['token'])), 403, 'Invalid Duel credential.');

        return $player;
    }

    private function issue(Request $request, Duel $duel, string $role): void
    {
        $token = bin2hex(random_bytes(32));
        $duel->players()->create(['role' => $role, 'token_hash' => hash('sha256', $token), 'last_seen_at' => now()]);
        // The secret lives in the server session, behind the encrypted HttpOnly session cookie.
        $request->session()->put('games.duels.'.$duel->id, compact('role', 'token'));
        $request->session()->put('games.current_duel', $duel->id);
    }

    public function create(Request $request, string $game, array $settings): Duel
    {
        $this->releaseExpiredCodes();
        $current = Duel::find($request->session()->get('games.current_duel'));
        if ($current && $current->status === Duel::WAITING && $current->expires_at->isFuture()) {
            $this->participant($request, $current);

            return $current;
        }
        for ($attempt = 0; $attempt < 100; $attempt++) {
            try {
                return DB::transaction(function () use ($request, $game, $settings) {
                    $code = sprintf('%04d', random_int(0, 9999));
                    $duel = Duel::create(['id' => (string) Str::uuid(), 'code' => $code, 'join_code' => $code, 'game' => $game,
                        'settings' => $settings, 'seed' => bin2hex(random_bytes(16)), 'status' => Duel::WAITING, 'expires_at' => now()->addMinutes(15)]);
                    $this->issue($request, $duel, 'host');

                    return $duel;
                }, 3);
            } catch (QueryException $exception) {
                // Retry only a join-code collision; surface every other database failure.
                $duplicate = ($exception->errorInfo[1] ?? null) === 1062
                    || (($exception->errorInfo[0] ?? null) === '23000' && strpos($exception->getMessage(), 'join_code') !== false);
                if (! $duplicate) { throw $exception; }
                if ($attempt === 99) {
                    abort(503, 'All Duel codes are busy. Please try again shortly.');
                }
            }
        }
        abort(503);
    }

    public function join(Request $request, string $code): Duel
    {
        $current = Duel::find($request->session()->get('games.current_duel'));
        if ($current && $current->code === $code && ! in_array($current->status, [Duel::CANCELLED, Duel::EXPIRED, Duel::FINISHED])) {
            $this->participant($request, $current);

            return $current;
        }

        return DB::transaction(function () use ($request, $code) {
            $duel = Duel::where('join_code', $code)->lockForUpdate()->first();
            if (! $duel || $duel->status !== Duel::WAITING || $duel->expires_at->isPast() || $this->absentPlayer($duel)) {
                throw ValidationException::withMessages(['code' => 'That Duel is invalid, expired, cancelled, or already full.']);
            }
            $claimed = Duel::whereKey($duel->id)->where('status', Duel::WAITING)->where('join_code', $code)
                ->where('expires_at', '>', now())->update(['status' => Duel::READY, 'join_code' => null]);
            if ($claimed !== 1) {
                throw ValidationException::withMessages(['code' => 'That Duel is already full.']);
            }
            $duel->refresh();
            $this->issue($request, $duel, 'guest');
            $this->publish($duel, 'opponent_joined');

            return $duel;
        }, 3);
    }

    public function mutate(Request $request, string $id, string $action, array $data = []): Duel
    {
        return DB::transaction(function () use ($request, $id, $action, $data) {
            $duel = Duel::whereKey($id)->lockForUpdate()->firstOrFail();
            $player = $this->participant($request, $duel);
            $this->advance($duel);
            if ($action === 'rematch') {
                // A retry from the previous match must never request another rematch.
                if ($data['seed'] !== $duel->seed) {
                    return $duel;
                }
                abort_unless($duel->status === Duel::FINISHED, 409, 'Both players must finish before playing again.');
                if ($player->rematch_at) {
                    return $duel;
                }
                $player->update(['rematch_at' => now(), 'last_seen_at' => now()]);
                if ($duel->players()->whereNotNull('rematch_at')->count() === 2) {
                    // Snapshot the finished match before resetting the shared room.
                    // Player serialization excludes credentials and connection identifiers.
                    DuelArchive::create([
                        'id' => (string) Str::uuid(), 'duel_id' => $duel->id,
                        'game' => $duel->game, 'status' => $duel->status, 'seed' => $duel->seed,
                        'played_at' => $duel->starts_at ?? $duel->created_at,
                        'snapshot' => $duel->load('players')->toArray(),
                    ]);
                    $duel->update(['seed' => bin2hex(random_bytes(16)), 'status' => Duel::COUNTDOWN,
                        'starts_at' => now()->addSeconds(4), 'finished_at' => null, 'expires_at' => now()->addMinutes(15)]);
                    $duel->players()->update(['ready_at' => now(), 'progress' => 0, 'score' => 0, 'sequence' => 0,
                        'checkpoint' => null, 'result' => null, 'finished_at' => null, 'rematch_at' => null,
                        'departed_at' => null, 'last_seen_at' => now(), 'answer_id' => null, 'answer_correct' => null, 'answered_at' => null]);
                }
                $this->publish($duel, 'rematch');

                return $duel;
            }
            if (isset($data['seed'])) {
                abort_unless($data['seed'] === $duel->seed, 409, 'This update belongs to an earlier match.');
                unset($data['seed']);
            }
            if (in_array($action, ['connect', 'depart', 'heartbeat'])) {
                if (in_array($duel->status, [Duel::CANCELLED, Duel::EXPIRED, Duel::FINISHED])) {
                    return $duel;
                }
                if ($action === 'connect') {
                    $player->update(['connection_id' => $data['connection_id'], 'departed_at' => null, 'last_seen_at' => now(), 'answer_id' => null, 'answer_correct' => null, 'answered_at' => null]);
                } elseif ($action === 'depart') {
                    // A delayed unload from the previous page cannot evict the refreshed page.
                    if ($player->connection_id !== $data['connection_id']) {
                        return $duel;
                    }
                    if (! $player->departed_at) {
                        $player->update(['departed_at' => now()]);
                    }
                } else {
                    if ($player->connection_id && ($data['connection_id'] ?? null) !== $player->connection_id) {
                        return $duel;
                    }
                    // In-flight heartbeats from an unloading page cannot undo its departure.
                    $player->update(['last_seen_at' => now()]);
                }
                $this->publish($duel, 'connection');

                return $duel;
            }
            $player->update(['last_seen_at' => now()]);
            if ($action === 'leave') {
                if (in_array($duel->status, [Duel::CANCELLED, Duel::EXPIRED, Duel::FINISHED])) {
                    return $duel;
                }
                // Either participant can end an unfinished Duel. The first departure wins.
                $duel->update(['status' => Duel::CANCELLED, 'left_by' => $player->role, 'join_code' => null, 'finished_at' => now()]);
            } elseif ($action === 'cancel') {
                abort_unless($player->role === 'host' && $duel->status === Duel::WAITING, 409, 'Only a waiting host can cancel.');
                $duel->update(['status' => Duel::CANCELLED, 'join_code' => null, 'finished_at' => now()]);
            } elseif ($action === 'ready') {
                abort_unless(in_array($duel->status, [Duel::READY, Duel::COUNTDOWN, Duel::PLAYING]), 409, 'This Duel cannot start.');
                if ($player->ready_at) {
                    return $duel;
                }
                $player->update(['ready_at' => now()]);
                if ($duel->players()->whereNotNull('ready_at')->count() === 2) {
                    $duel->update(['status' => Duel::COUNTDOWN, 'starts_at' => now()->addSeconds(4)]);
                }
            } elseif ($action === 'progress') {
                abort_unless($duel->status === Duel::PLAYING && ! $player->finished_at, 409, 'The game is not active.');
                if ($data['sequence'] <= $player->sequence) {
                    abort_unless($data['sequence'] === $player->sequence && $data['progress'] === $player->progress && $data['score'] === $player->score, 409, 'Stale progress update.');

                    return $duel;
                }
                abort_unless($data['sequence'] === $player->sequence + 1 && $data['progress'] === $player->progress + 1 &&
                    $data['progress'] <= $duel->settings['numOfChallenges'] && $data['score'] >= $player->score && $data['score'] <= $player->score + 100, 422, 'Impossible progress transition.');
                abort_if(strlen(json_encode($data['checkpoint'] ?? [])) > 16000, 422, 'Checkpoint too large.');
                $player->update($data);
            } elseif ($action === 'finish') {
                if ($player->finished_at) {
                    return $duel;
                }
                abort_unless($duel->status === Duel::PLAYING && $player->progress === (int) $duel->settings['numOfChallenges'], 409, 'Complete the rounds before finishing.');
                // Final score bonuses may multiply the earned score by at most four.
                abort_unless($data['score'] >= $player->score && $data['score'] <= $player->score * 4, 422, 'Invalid final score.');
                $player->update(['finished_at' => now(), 'result' => $data, 'score' => $data['score']]);
                if ($duel->players()->whereNotNull('finished_at')->count() === 2) {
                    $duel->update(['status' => Duel::FINISHED, 'finished_at' => now()]);
                }
            }
            $this->publish($duel, $action);

            return $duel;
        }, 3);
    }

    public function answer(Request $request, string $id, bool $correct): void
    {
        DB::transaction(function () use ($request, $id, $correct) {
            $duel = Duel::whereKey($id)->lockForUpdate()->firstOrFail();
            $player = $this->participant($request, $duel);
            $this->advance($duel);
            // The final answer notification may arrive just after the finish request.
            $recentFinish = $player->finished_at && $player->finished_at->gte(now()->subSeconds(5));
            abort_unless(($duel->status === Duel::PLAYING && (! $player->finished_at || $recentFinish)) ||
                ($duel->status === Duel::FINISHED && $recentFinish), 409, 'This Duel is not accepting answers.');
            $player->update(['answer_id' => (string) Str::uuid(), 'answer_correct' => $correct, 'answered_at' => now()]);
        }, 3);
    }

    public function advance(Duel $duel): void
    {
        if (! in_array($duel->status, [Duel::CANCELLED, Duel::EXPIRED, Duel::FINISHED]) && ($absent = $this->absentPlayer($duel))) {
            $duel->update(['status' => Duel::CANCELLED, 'left_by' => $absent->role, 'join_code' => null, 'finished_at' => now()]);
            $this->publish($duel, 'leave');

            return;
        }
        if (in_array($duel->status, [Duel::WAITING, Duel::READY]) && $duel->expires_at->isPast()) {
            $duel->update(['status' => Duel::EXPIRED, 'join_code' => null, 'finished_at' => now()]);
        } elseif ($duel->status === Duel::COUNTDOWN && $duel->starts_at->lte(now())) {
            $duel->update(['status' => Duel::PLAYING]);
        }
    }

    private function absentPlayer(Duel $duel): ?DuelPlayer
    {
        return $duel->players()->whereNotNull('connection_id')->where(function ($query) {
            $query->where('departed_at', '<=', now()->subSeconds(config('games-duels.departure_grace_seconds', 30)))
                ->orWhere(function ($query) {
                    $query->whereNull('departed_at')->where('last_seen_at', '<=', now()->subSeconds(config('games-duels.absence_timeout_seconds', 120)));
                });
        })->orderBy('last_seen_at')->first();
    }

    public function publicState(Duel $duel): array
    {
        return ['id' => $duel->id, 'revision' => $duel->revision, 'status' => $duel->status, 'left_by' => $duel->left_by, 'starts_at' => $duel->starts_at?->toISOString(),
            'server_now' => now()->toISOString(), 'players' => $duel->players()->orderBy('role')->get()->map(fn ($p) => [
                'role' => $p->role, 'ready' => (bool) $p->ready_at, 'progress' => $p->progress, 'score' => $p->score,
                'finished_at' => $p->finished_at?->toISOString(), 'rematch' => (bool) $p->rematch_at,
                'answer' => $p->answered_at && $p->answered_at->gte(now()->subSeconds(3)) ? ['id' => $p->answer_id, 'duel_id' => $duel->id, 'role' => $p->role, 'correct' => (bool) $p->answer_correct] : null,
                'last_seen_at' => $p->last_seen_at?->toISOString(), 'disconnected' => (bool) $p->departed_at, 'result' => $p->result,
            ])->all()];
    }

    public function state(Request $request, Duel $duel): array
    {
        return DB::transaction(function () use ($request, $duel) {
            $duel = Duel::whereKey($duel->id)->lockForUpdate()->firstOrFail();
            $player = $this->participant($request, $duel);
            $previous = $duel->status;
            $revision = $duel->revision;
            $this->advance($duel);
            if ($previous !== $duel->status && $revision === $duel->revision) {
                $this->publish($duel, 'lifecycle');
            }

            return $this->publicState($duel) + ['role' => $player->role, 'code' => $player->role === 'host' ? $duel->code : null,
                'sequence' => $player->sequence, 'checkpoint' => $player->checkpoint,
                'game' => $duel->game, 'settings' => $duel->settings, 'options' => GameRegistry::settings($duel->game, $duel->settings)->browserOptions(),
                'seed' => $duel->seed, 'total' => (int) $duel->settings['numOfChallenges'], 'expires_at' => $duel->expires_at->toISOString(),
                'game_url' => route('games.'.$duel->game.'.play', ['duel' => $duel->id])];
        }, 3);
    }

    private function publish(Duel $duel, string $change): void
    {
        $duel->increment('revision');
        // Every reader gets an authenticated authoritative snapshot. Notifications
        // use the same database state, without requiring a new broadcast runtime.
    }

    private function releaseExpiredCodes(): void
    {
        Duel::where('status', Duel::WAITING)->where('expires_at', '<=', now())
            ->update(['status' => Duel::EXPIRED, 'join_code' => null, 'finished_at' => now()]);
    }

    public function cleanup(): int
    {
        $count = 0;
        // The surviving player's heartbeat normally detects departures; scheduling
        // also closes rooms when both browsers disappear.
        Duel::whereNotIn('status', [Duel::CANCELLED, Duel::EXPIRED, Duel::FINISHED])
            ->whereHas('players', fn ($query) => $query->whereNotNull('connection_id')
                ->where(fn ($query) => $query->whereNotNull('departed_at')->orWhere('last_seen_at', '<=', now()->subSeconds(config('games-duels.absence_timeout_seconds', 120)))))
            ->pluck('id')->each(function ($id) use (&$count) {
                DB::transaction(function () use ($id, &$count) {
                    $duel = Duel::whereKey($id)->lockForUpdate()->first();
                    if (! $duel || in_array($duel->status, [Duel::CANCELLED, Duel::EXPIRED, Duel::FINISHED])) {
                        return;
                    }
                    $this->advance($duel);
                    if ($duel->status === Duel::CANCELLED) {
                        $count++;
                    }
                });
            });
        Duel::whereIn('status', [Duel::WAITING, Duel::READY])->where('expires_at', '<=', now())->pluck('id')->each(function ($id) use (&$count) {
            DB::transaction(function () use ($id, &$count) {
                $duel = Duel::whereKey($id)->lockForUpdate()->first();
                if (! $duel || ! in_array($duel->status, [Duel::WAITING, Duel::READY])) {
                    return;
                }
                $duel->update(['status' => Duel::EXPIRED, 'join_code' => null, 'finished_at' => now()]);
                $this->publish($duel, 'expired');
                $count++;
            });
        });
        // Close abandoned games after a day, retaining all records for Admin history.
        Duel::whereIn('status', [Duel::COUNTDOWN, Duel::PLAYING])->where('updated_at', '<', now()->subDay())->update(['status' => Duel::EXPIRED, 'join_code' => null, 'finished_at' => now()]);

        return $count;
    }
}
