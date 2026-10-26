<?php

namespace Tests\Review;

use App\Games\Duels\{Duel, DuelPlayer, GameRegistry};
use App\Games\Player;

class GamesAuthenticationTest extends GamesTestCase
{
    private function score(): array
    {
        return ['game' => 'Note Nest', 'username' => 'Player',
            'avatar_url' => 'http://games.localhost/images/games/avatars/avatar-1.svg',
            'score' => 20, 'accuracy' => 80, 'rounds' => 4, 'duration' => '00:30'];
    }

    public function test_guests_cannot_post_scores_or_create_rooms_even_with_a_submitted_account_id()
    {
        $this->postJson(route('games.leaderboard.store'), $this->score() + ['user_id' => 123])->assertUnauthorized();
        $this->postJson(route('games.duels.store'), ['game' => 'note-nest', 'settings' => ['numOfChallenges' => 2], 'user_id' => 123])->assertUnauthorized();
        $this->post(route('games.leaderboard.store'), $this->score())->assertRedirect('http://games.localhost/login');
        $this->post(route('games.duels.store'), ['game' => 'note-nest', 'settings' => ['numOfChallenges' => 2]])->assertRedirect('http://games.localhost/login');
        $this->assertSame(0, Player::count());
        $this->assertSame(0, Duel::count());
        $this->assertSame(0, DuelPlayer::count());
        $this->assertFalse(session()->has('games.current_duel'));
    }

    public function test_an_admin_session_does_not_authorize_public_game_writes()
    {
        $admin = new \App\Admin;
        $admin->id = 123;
        $this->actingAs($admin, 'admin');
        $this->postJson(route('games.leaderboard.store'), $this->score())->assertUnauthorized();
        $this->postJson(route('games.duels.store'), ['game' => 'note-nest', 'settings' => ['numOfChallenges' => 2]])->assertUnauthorized();
        $this->assertSame(0, Player::count());
        $this->assertSame(0, Duel::count());
    }

    public function test_a_registered_host_can_start_a_duel_and_an_anonymous_player_can_join_and_get_ready()
    {
        $this->signInPlayer();
        $room = $this->postJson(route('games.duels.store'), ['game' => 'note-nest', 'settings' => ['numOfChallenges' => 2]])
            ->assertOk()->assertJsonPath('role', 'host')->json();

        $this->app['auth']->forgetGuards();
        session()->flush();
        session()->save();
        $guest = $this->postJson(route('games.duels.join'), ['code' => $room['code']])
            ->assertOk()->assertJsonPath('role', 'guest')->assertJsonPath('status', 'ready')->json();
        $this->assertFalse(auth('web')->check());
        $this->get($guest['game_url'])->assertOk();
        $this->postJson(route('games.duels.update', ['duel' => $room['id'], 'action' => 'ready']))->assertOk();
        // A room credential grants participation, never account-level posting/hosting.
        $this->postJson(route('games.duels.store'), ['game' => 'note-nest', 'settings' => ['numOfChallenges' => 2]])->assertUnauthorized();
        $this->postJson(route('games.leaderboard.store'), $this->score())->assertUnauthorized();
        $this->assertSame(1, Duel::count());
        $this->assertSame(2, DuelPlayer::count());
        $this->assertSame(0, Player::count());
    }

    public function test_guests_can_read_leaderboards_and_get_sign_in_links_in_place_of_posting_and_hosting()
    {
        $this->getJson(route('games.leaderboard.show', ['game' => 'Note Nest']))->assertOk();
        $this->get(route('games.home'))->assertOk()->assertSee('data-duel-join', false)
            ->assertDontSee('data-games-profile', false)->assertDontSee('data-duel-create=', false);
        foreach (array_keys(GameRegistry::GAMES) as $game) {
            $this->get(route('games.'.$game.'.play'))->assertOk()
                ->assertSee('Sign in to start a duel')->assertSee('Sign in to post results')
                ->assertDontSee('data-duel-create=', false)->assertDontSee('data-leaderboard-profile', false);
        }
    }

    public function test_registered_nonmembers_keep_posting_hosting_and_their_account_menu()
    {
        $this->signInPlayer();
        $this->get(route('games.home'))->assertOk()->assertSee('data-games-profile', false);
        foreach (array_keys(GameRegistry::GAMES) as $game) {
            $this->get(route('games.'.$game.'.play'))->assertOk()
                ->assertSee('data-duel-create="'.$game.'"', false)->assertSee('data-leaderboard-profile', false);
        }
        $this->post(route('games.leaderboard.store'), $this->score())->assertRedirect();
        $this->assertSame(1, Player::count());
    }
}
