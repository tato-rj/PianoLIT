<?php

namespace Tests\Review;

use App\Games\Duels\Duel;
use App\Games\Duels\DuelArchive;
use App\Games\Duels\DuelPlayer;
use App\Games\Duels\GameRegistry;
use Illuminate\Database\QueryException;
use Illuminate\Routing\Middleware\ThrottleRequests;
use Illuminate\Support\Facades\Event;


class GameDuelsTest extends GamesTestCase
{
    public function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware(ThrottleRequests::class);
    }

    private function create(string $game = 'intervals-lab', array $settings = []): array
    {
        $this->signInPlayer();
        return $this->postJson(route('games.duels.store'), ['game' => $game, 'settings' => $settings + ['numOfChallenges' => 2]])->assertOk()->json();
    }

    private function sessionSnapshot(): array
    {
        return session()->all();
    }

    private function switchSession(array $data = []): void
    {
        $this->app['auth']->forgetGuards();
        session()->flush();
        session()->save();
        $this->withSession($data);
        session()->save();
    }

    private function mutate(array $room, string $action, array $data = [])
    {
        return $this->postJson(route('games.duels.update', ['duel' => $room['id'], 'action' => $action]), $data);
    }

    private function paired(): array
    {
        $room = $this->create();
        $host = $this->sessionSnapshot();
        $this->switchSession();
        $guest = $this->postJson(route('games.duels.join'), ['code' => $room['code']])->assertOk()->json();

        return [$room, $host, $this->sessionSnapshot(), $guest];
    }

    private function playing(): array
    {
        [$room, $host, $guest] = $this->paired();
        $this->mutate($room, 'ready')->assertOk();
        $this->switchSession($host);
        $this->mutate($room, 'ready')->assertOk();
        $this->travel(5)->seconds();

        return [$room, $host, $guest];
    }



    public function test_browser_departure_notifies_opponent_and_becomes_leave_after_grace(): void
    {
        [$room, $host, $guest] = $this->playing();
        $this->switchSession($guest);
        $id = str_repeat('a', 32);
        $this->mutate($room, 'connect', ['connection_id' => $id])->assertOk();
        $this->mutate($room, 'depart', ['connection_id' => $id])->assertOk()->assertJsonPath('players.0.disconnected', true);
        $this->travel(29)->seconds();
        $this->switchSession($host);
        $this->mutate($room, 'heartbeat')->assertOk()->assertJsonPath('status', 'playing');
        $this->travel(2)->seconds();
        $this->mutate($room, 'heartbeat')->assertOk()->assertJsonPath('status', 'cancelled')->assertJsonPath('left_by', 'guest');
        $this->getJson(route('games.duels.show', $room['id']))->assertOk()->assertJsonPath('status', 'cancelled');
    }

    public function test_refresh_clears_departure_and_late_signals_from_previous_page_are_ignored(): void
    {
        [$room] = $this->playing();
        $old = str_repeat('a', 32);
        $new = str_repeat('b', 32);
        $this->mutate($room, 'connect', ['connection_id' => $old])->assertOk();
        $this->mutate($room, 'depart', ['connection_id' => $old])->assertOk();
        $this->travel(10)->seconds();
        $state = $this->mutate($room, 'connect', ['connection_id' => $new])->assertOk()->json();
        $this->assertFalse(collect($state['players'])->firstWhere('role', 'host')['disconnected']);
        $this->mutate($room, 'depart', ['connection_id' => $old])->assertOk();
        $this->travel(25)->seconds();
        $this->mutate($room, 'heartbeat', ['connection_id' => $old])->assertOk();
        $player = DuelPlayer::where('duel_id', $room['id'])->where('role', 'host')->firstOrFail();
        $this->assertNull($player->departed_at);
        $this->assertSame($new, $player->connection_id);
        $this->assertTrue($player->last_seen_at->lte(now()->subSeconds(24)), 'An old page cannot renew the current page presence.');
        $this->mutate($room, 'heartbeat', ['connection_id' => $new])->assertOk()->assertJsonPath('status', 'playing');
    }

    public function test_a_missing_close_signal_gets_a_longer_reconnection_timeout(): void
    {
        [$room, $host, $guest] = $this->playing();
        $this->switchSession($guest);
        $id = str_repeat('c', 32);
        $this->mutate($room, 'connect', ['connection_id' => $id])->assertOk();
        $this->travel(100)->seconds();
        $this->mutate($room, 'heartbeat', ['connection_id' => $id])->assertOk()->assertJsonPath('status', 'playing');
        $this->travel(121)->seconds();
        $this->switchSession($host);
        $this->mutate($room, 'heartbeat')->assertOk()->assertJsonPath('status', 'cancelled')->assertJsonPath('left_by', 'guest');
    }

    public function test_browser_presence_cannot_be_changed_by_a_random_visitor_or_room_code(): void
    {
        [$room] = $this->playing();
        $this->mutate($room, 'connect', ['connection_id' => 'bad'])->assertUnprocessable();
        $this->switchSession();
        foreach (['connect', 'depart', 'heartbeat'] as $action) {
            $this->mutate($room, $action, ['connection_id' => str_repeat('d', 32), 'code' => $room['code']])->assertForbidden();
        }
    }

    public function test_cleanup_closes_departed_waiting_hosts_and_codes_cannot_be_claimed(): void
    {
        $room = $this->create();
        $id = str_repeat('e', 32);
        $this->mutate($room, 'connect', ['connection_id' => $id])->assertOk();
        $this->mutate($room, 'depart', ['connection_id' => $id])->assertOk();
        $this->travel(31)->seconds();
        $this->switchSession();
        $this->postJson(route('games.duels.join'), ['code' => $room['code']])->assertUnprocessable();
        $this->artisan('games:cleanup-duels')->assertSuccessful();
        $duel = Duel::findOrFail($room['id']);
        $this->assertSame(Duel::CANCELLED, $duel->status);
        $this->assertSame('host', $duel->left_by);
        $this->assertNull($duel->join_code);
    }

    public function test_heartbeats_from_a_closing_page_do_not_extend_the_departure_deadline(): void
    {
        [$room] = $this->playing();
        $id = str_repeat('f', 32);
        $this->mutate($room, 'connect', ['connection_id' => $id])->assertOk();
        $this->mutate($room, 'depart', ['connection_id' => $id])->assertOk();
        $this->travel(20)->seconds();
        $this->mutate($room, 'heartbeat', ['connection_id' => $id])->assertOk();
        $this->mutate($room, 'depart', ['connection_id' => $id])->assertOk();
        $this->travel(11)->seconds();
        $this->getJson(route('games.duels.show', $room['id']))->assertOk()->assertJsonPath('status', 'cancelled');
    }







    public function test_creation_issues_a_hashed_anonymous_credential_and_complete_settings_snapshot(): void
    {
        $state = $this->create('intervals-lab', ['intervals' => ['P4'], 'timer' => true, 'sound' => false]);
        $this->assertMatchesRegularExpression('/^\d{4}$/', $state['code']);
        $this->assertSame('host', $state['role']);
        $this->assertSame(['P4'], $state['settings']['intervals']);
        $this->assertFalse($state['settings']['sound']);
        $duel = Duel::findOrFail($state['id']);
        $this->assertTrue($duel->expires_at->between(now()->addMinutes(14), now()->addMinutes(16)));
        $token = session('games.duels.'.$duel->id.'.token');
        $this->assertSame(64, strlen($token));
        $this->assertSame(hash('sha256', $token), $duel->players()->first()->token_hash);
        $this->assertStringNotContainsString($token, json_encode($state));
        $this->assertSame($state['id'], $this->create()['id'], 'Retrying room creation must not leak another waiting room.');
    }

    public function test_each_current_game_uses_the_same_duel_contract_and_host_options_on_the_game_page(): void
    {
        foreach (array_keys(GameRegistry::GAMES) as $game) {
            $this->switchSession();
            $room = $this->create($game);
            $response = $this->get($room['game_url'].'&numOfChallenges=12')->assertOk()->assertSee('window.__duelState', false)->assertSee('duel-hud', false);
            $document = new \DOMDocument;
            @$document->loadHTML($response->getContent());
            $restored = json_decode($document->getElementById('pagetitle')->getAttribute('data-game-options'), true);
            $this->assertSame(2, $restored['numOfChallenges']);
            $this->assertSame(2, $room['total']);
        }
    }

    public function test_guest_joins_once_and_host_is_notified_without_exposing_credentials(): void
    {
        [$room, $host, $guest, $state] = $this->paired();
        $this->assertSame('ready', $state['status']);
        $this->assertSame('guest', $state['role']);
        $this->assertNull(Duel::find($room['id'])->join_code);
        $this->postJson(route('games.duels.join'), ['code' => $room['code']])->assertOk()->assertJsonPath('role', 'guest');
        $this->assertSame(2, DuelPlayer::count());
        $this->switchSession();
        $this->postJson(route('games.duels.join'), ['code' => $room['code']])->assertUnprocessable();
    }

    public function test_invalid_expired_cancelled_started_and_full_codes_are_rejected(): void
    {
        $this->postJson(route('games.duels.join'), ['code' => '123'])->assertUnprocessable();
        $this->postJson(route('games.duels.join'), ['code' => 'xxxx'])->assertUnprocessable();
        foreach ([Duel::EXPIRED, Duel::CANCELLED, Duel::PLAYING, Duel::FINISHED] as $status) {
            $this->switchSession();
            $room = $this->create();
            Duel::find($room['id'])->update(['status' => $status]);
            $this->switchSession();
            $this->postJson(route('games.duels.join'), ['code' => $room['code']])->assertUnprocessable();
        }
        $this->switchSession();
        $room = $this->create();
        $this->travel(16)->minutes();
        $this->switchSession();
        $this->postJson(route('games.duels.join'), ['code' => $room['code']])->assertUnprocessable();
    }

    public function test_database_enforces_one_guest_even_with_a_stale_competing_claim(): void
    {
        [$room] = $this->paired();
        $this->expectException(QueryException::class);
        // Simulates the losing writer trying to commit after observing the same free slot.
        DuelPlayer::create(['duel_id' => $room['id'], 'role' => 'guest', 'token_hash' => hash('sha256', 'other-guest')]);
    }



    public function test_ready_is_idempotent_and_both_players_get_an_authoritative_future_start(): void
    {
        [$room, $host, $guest] = $this->paired();
        $this->mutate($room, 'ready')->assertOk()->assertJsonPath('status', 'ready')->assertJsonPath('starts_at', null);
        $this->mutate($room, 'ready')->assertOk()->assertJsonPath('status', 'ready');
        $this->mutate($room, 'progress', ['progress' => 1, 'score' => 3, 'sequence' => 1])->assertStatus(409);
        $this->switchSession($host);
        $state = $this->mutate($room, 'ready')->assertOk()->assertJsonPath('status', 'countdown')->json();
        $this->assertTrue(now()->lt($state['starts_at']));
        $this->mutate($room, 'ready')->assertOk()->assertJsonPath('starts_at', $state['starts_at']);
        $this->travel(5)->seconds();
        $this->getJson(route('games.duels.show', $room['id']))->assertOk()->assertJsonPath('status', 'playing');
    }

    public function test_rematch_requires_both_finished_players_and_reuses_room_settings_and_credentials(): void
    {
        [$room, $host, $guest] = $this->playing();
        $this->mutate($room, 'rematch', ['seed' => $room['seed']])->assertStatus(409);
        $credentials = DuelPlayer::orderBy('role')->pluck('token_hash', 'role')->all();
        foreach ([$host, $guest] as $session) {
            $this->switchSession($session);
            $this->mutate($room, 'connect', ['connection_id' => str_repeat($session === $host ? 'a' : 'b', 32)])->assertOk();
            for ($round = 1; $round <= 2; $round++) {
                $this->mutate($room, 'progress', ['sequence' => $round, 'progress' => $round, 'score' => $round * 3,
                    'checkpoint' => ['_stats' => ['checksTotal' => $round]]])->assertOk();
            }
            $this->mutate($room, 'finish', ['score' => 12, 'accuracy' => 100])->assertOk();
        }
        $connections = DuelPlayer::orderBy('role')->pluck('connection_id', 'role')->all();
        $accepted = $this->mutate($room, 'rematch', ['seed' => $room['seed']])->assertOk()->assertJsonPath('status', 'finished')->json();
        $this->assertTrue(collect($accepted['players'])->firstWhere('role', 'guest')['rematch']);
        $this->assertSame(12, collect($accepted['players'])->firstWhere('role', 'guest')['score']);
        $this->mutate($room, 'rematch', ['seed' => $room['seed']])->assertOk()->assertJsonPath('revision', $accepted['revision']);
        $this->switchSession($host);
        $next = $this->mutate($room, 'rematch', ['seed' => $room['seed']])->assertOk()->assertJsonPath('status', 'countdown')->json();
        $archive = DuelArchive::sole();
        $this->assertSame($room['id'], $archive->duel_id);
        $this->assertSame($room['seed'], $archive->seed);
        $this->assertSame('finished', $archive->status);
        $this->assertSame([12, 12], collect($archive->snapshot['players'])->pluck('score')->all());
        $this->assertSame([100, 100], collect($archive->snapshot['players'])->pluck('result.accuracy')->all());
        $this->assertNotNull($archive->snapshot['finished_at']);
        $this->assertStringNotContainsString('token_hash', json_encode($archive->snapshot));
        $this->assertStringNotContainsString('connection_id', json_encode($archive->snapshot));
        $this->assertSame($room['id'], $next['id']);
        $this->assertSame($room['settings'], $next['settings']);
        $this->assertSame($room['game_url'], $next['game_url']);
        $this->assertNotSame($room['seed'], $next['seed']);
        $this->assertSame($credentials, DuelPlayer::orderBy('role')->pluck('token_hash', 'role')->all());
        $this->assertSame($connections, DuelPlayer::orderBy('role')->pluck('connection_id', 'role')->all());
        $this->assertNull(Duel::find($room['id'])->join_code);
        foreach ($next['players'] as $player) {
            $this->assertSame(0, $player['progress']);
            $this->assertSame(0, $player['score']);
            $this->assertNull($player['result']);
            $this->assertNull($player['finished_at']);
            $this->assertFalse($player['rematch']);
        }
        $this->assertSame(0, $next['sequence']);
        $this->assertNull($next['checkpoint']);
        // Lost responses and delayed gameplay from the previous run cannot alter this run.
        $this->mutate($room, 'rematch', ['seed' => $room['seed']])->assertOk()->assertJsonPath('seed', $next['seed']);
        $this->assertSame(1, DuelArchive::count());
        $this->travel(5)->seconds();
        $this->mutate($room, 'progress', ['seed' => $room['seed'], 'sequence' => 1, 'progress' => 1, 'score' => 3])->assertStatus(409);
        $this->mutate($room, 'progress', ['seed' => $next['seed'], 'sequence' => 1, 'progress' => 1, 'score' => 3])->assertOk();
        $this->switchSession($guest);
        $this->getJson(route('games.duels.show', $room['id']))->assertOk()->assertJsonPath('seed', $next['seed'])->assertJsonPath('role', 'guest');
        $this->switchSession();
        $this->mutate($room, 'rematch', ['seed' => $next['seed']])->assertForbidden();
    }

    public function test_progress_is_authorized_bounded_monotonic_and_idempotent(): void
    {
        [$room, $host] = $this->playing();
        $data = ['sequence' => 1, 'progress' => 1, 'score' => 3, 'checkpoint' => ['_stats' => ['checksTotal' => 1, 'checksCorrect' => 1]]];
        $this->mutate($room, 'progress', $data)->assertOk()->assertJsonPath('sequence', 1);
        $this->mutate($room, 'progress', $data)->assertOk();
        $this->mutate($room, 'progress', ['sequence' => 2, 'progress' => 999, 'score' => 3])->assertUnprocessable();
        $this->mutate($room, 'progress', ['sequence' => 2, 'progress' => 1, 'score' => 3])->assertUnprocessable();
        $this->mutate($room, 'progress', ['sequence' => 2, 'progress' => 2, 'score' => 999])->assertUnprocessable();
        $this->mutate($room, 'progress', ['sequence' => 1, 'progress' => 1, 'score' => 4])->assertStatus(409);
        $this->switchSession();
        $this->mutate($room, 'progress', $data)->assertForbidden();
        $this->switchSession($host);
        $this->getJson(route('games.duels.show', $room['id']))->assertOk()->assertJsonPath('checkpoint._stats.checksCorrect', 1)->assertJsonPath('players.1.progress', 1);
    }

    public function test_first_finisher_allows_opponent_to_continue_and_second_finishes_the_duel(): void
    {
        [$room, $host, $guest] = $this->playing();
        $this->mutate($room, 'finish', ['score' => 0, 'accuracy' => 0])->assertStatus(409);
        foreach ([$host, $guest] as $index => $session) {
            $this->switchSession($session);
            for ($round = 1; $round <= 2; $round++) {
                $this->mutate($room, 'progress', ['sequence' => $round, 'progress' => $round, 'score' => $round * 3])->assertOk();
            }
            $this->mutate($room, 'finish', ['score' => 12, 'accuracy' => 100])->assertOk()->assertJsonPath('status', $index === 0 ? 'playing' : 'finished');
            $this->mutate($room, 'finish', ['score' => 12, 'accuracy' => 100])->assertOk();
        }
        $this->assertSame(2, Duel::find($room['id'])->players()->whereNotNull('finished_at')->count());
    }

    public function test_cancellation_and_cleanup_release_codes_and_refresh_restores_identity(): void
    {
        $room = $this->create();
        $this->getJson(route('games.duels.show', $room['id']))->assertOk()->assertJsonPath('role', 'host')->assertJsonPath('seed', $room['seed']);
        $this->mutate($room, 'cancel')->assertOk()->assertJsonPath('status', 'cancelled');
        $this->mutate($room, 'ready')->assertStatus(409);
        $this->assertNull(Duel::find($room['id'])->join_code);
        $this->switchSession();
        $room = $this->create();
        $this->travel(16)->minutes();
        $this->artisan('games:cleanup-duels')->assertExitCode(0);
        $this->assertSame('expired', Duel::find($room['id'])->status);
        $this->assertNull(Duel::find($room['id'])->join_code);
        $this->travel(8)->days();
        $this->artisan('games:cleanup-duels')->assertExitCode(0);
        $this->assertSame(2, Duel::count());
        $this->assertSame(0, Duel::whereNotIn('status', [Duel::CANCELLED, Duel::EXPIRED])->count());
    }

    public function test_either_player_can_leave_and_the_opponent_is_notified(): void
    {
        foreach (['host', 'guest'] as $role) {
            $this->switchSession();
            [$room, $host, $guest] = $this->playing();
            $this->switchSession($role === 'host' ? $host : $guest);
            $left = $this->mutate($room, 'leave')->assertOk()->assertJsonPath('status', 'cancelled')->assertJsonPath('left_by', $role)->json();
            $this->assertNotNull($left['players'][0]['last_seen_at']);
            $this->assertNotNull(Duel::find($room['id'])->finished_at);
            $this->assertNull(Duel::find($room['id'])->join_code);
            $this->mutate($room, 'leave')->assertOk()->assertJsonPath('revision', $left['revision']);
            $this->switchSession($role === 'host' ? $guest : $host);
            $this->getJson(route('games.duels.show', $room['id']))->assertOk()->assertJsonPath('left_by', $role)->assertJsonPath('status', 'cancelled');
            // A near-simultaneous second departure must not overwrite who left first.
            $this->mutate($room, 'leave')->assertOk()->assertJsonPath('left_by', $role)->assertJsonPath('revision', $left['revision']);
            $this->mutate($room, 'ready')->assertStatus(409);
            $this->mutate($room, 'progress', ['sequence' => 1, 'progress' => 1, 'score' => 3])->assertStatus(409);
            $this->mutate($room, 'finish', ['score' => 0, 'accuracy' => 0])->assertStatus(409);
        }
    }

    public function test_leaving_requires_a_participant_and_also_works_before_play_starts(): void
    {
        [$room, $host, $guest] = $this->paired();
        $this->switchSession();
        $this->mutate($room, 'leave')->assertForbidden();
        $this->switchSession();
        $this->create();
        $this->mutate($room, 'leave')->assertForbidden();
        $this->switchSession($guest);
        $this->mutate($room, 'leave')->assertOk()->assertJsonPath('left_by', 'guest');

        $this->switchSession();
        [$room, $host, $guest] = $this->paired();
        $this->mutate($room, 'ready')->assertOk();
        $this->switchSession($host);
        $this->mutate($room, 'ready')->assertOk()->assertJsonPath('status', 'countdown');
        $this->mutate($room, 'leave')->assertOk()->assertJsonPath('status', 'cancelled');
        $this->travel(5)->seconds();
        $this->getJson(route('games.duels.show', $room['id']))->assertOk()->assertJsonPath('status', 'cancelled');
    }

    public function test_duel_games_show_leave_instead_of_leaderboard_and_settings_controls(): void
    {
        foreach (array_keys(GameRegistry::GAMES) as $game) {
            $this->switchSession();
            $room = $this->create($game);
            $response = $this->get($room['game_url'])->assertOk();
            $document = new \DOMDocument;
            @$document->loadHTML($response->getContent());
            $xpath = new \DOMXPath($document);
            $this->assertSame(1, $xpath->query('//*[@id="page-wrapper"]//button[@data-duel-leave]')->length);
            $this->assertSame(0, $xpath->query('//*[@data-duel-settings]')->length);
            $this->assertSame(0, $xpath->query('//*[@id="controls"]//button[starts-with(@data-bs-target, "#leaderboard-")]')->length);
            $single = $this->get(route('games.'.$game.'.play'))->assertOk();
            @$document->loadHTML($single->getContent());
            $xpath = new \DOMXPath($document);
            $this->assertSame(0, $xpath->query('//*[@id="page-wrapper"]//button[@data-duel-leave]')->length);
            $this->assertSame(1, $xpath->query('//*[@data-duel-settings]/button[not(@disabled)]')->length);
            $this->assertSame(1, $xpath->query('//*[@id="controls"]//button[starts-with(@data-bs-target, "#leaderboard-")]')->length);
        }
    }

    public function test_state_refreshes_do_not_consume_the_room_creation_rate_limit_after_leaving(): void
    {
        $this->withMiddleware(ThrottleRequests::class);
        $room = $this->create();
        for ($index = 0; $index < 20; $index++) {
            $this->getJson(route('games.duels.show', $room['id']))->assertOk();
        }
        $this->mutate($room, 'leave')->assertOk();
        $this->create();
        for ($index = 0; $index < 8; $index++) {
            $this->create();
        }
        $this->postJson(route('games.duels.store'), ['game' => 'intervals-lab', 'settings' => ['numOfChallenges' => 2]])->assertStatus(429);
    }

    public function test_invalid_games_settings_and_unbounded_practice_do_not_create_duels(): void
    {
        $this->signInPlayer();
        foreach ([['game' => 'open-staff', 'settings' => ['numOfChallenges' => 2]], ['game' => 'intervals-lab', 'settings' => ['practiceMode' => true]],
            ['game' => 'intervals-lab', 'settings' => ['numOfChallenges' => 999]], ['game' => 'intervals-lab', 'settings' => ['intervals' => ['bad']]]] as $data) {
            $this->postJson(route('games.duels.store'), $data)->assertUnprocessable();
        }
        $this->assertSame(0, Duel::count());
    }

    public function test_answer_feedback_is_participant_only_expires_and_does_not_change_scores()
    {
        [$room, $host, $guest] = $this->playing();
        $this->postJson(route('games.duels.answer', $room['id']), ['correct' => true])->assertOk();
        $this->switchSession($guest);
        $state = $this->getJson(route('games.duels.show', $room['id']))->assertOk()->json();
        $answer = collect($state['players'])->firstWhere('role', 'host');
        $this->assertTrue($answer['answer']['correct']);
        $this->assertSame(0, $answer['score']);
        $this->assertSame(0, $answer['progress']);
        $this->switchSession();
        $this->getJson(route('games.duels.show', $room['id']))->assertForbidden();
        $this->postJson(route('games.duels.answer', $room['id']), ['correct' => false])->assertForbidden();
        $this->switchSession($host);
        $this->travel(4)->seconds();
        $state = $this->getJson(route('games.duels.show', $room['id']))->assertOk()->json();
        $this->assertNull(collect($state['players'])->firstWhere('role', 'host')['answer']);
    }

    public function test_registered_creation_and_anonymous_join_require_csrf()
    {
        $this->signInPlayer();
        $this->app['env'] = 'review-csrf';
        $this->withSession(['_token' => 'game-csrf']);
        $data = ['game' => 'intervals-lab', 'settings' => ['numOfChallenges' => 2]];
        $this->postJson(route('games.duels.store'), $data)->assertStatus(419);
        $this->app['auth']->forgetGuards();
        $this->postJson(route('games.duels.join'), ['code' => '1234'])->assertStatus(419);
        $this->assertSame(0, Duel::count());
        $this->signInPlayer();
        $this->withHeader('X-CSRF-TOKEN', 'game-csrf')->postJson(route('games.duels.store'), $data)->assertOk();
    }
}
