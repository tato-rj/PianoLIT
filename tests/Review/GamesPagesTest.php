<?php
namespace Tests\Review;

use App\Games\Duels\GameRegistry;
use App\Games\Player;
use Illuminate\Support\Facades\DB;

class GamesPagesTest extends GamesTestCase
{
    public function test_hub_uses_pianolit_shell_and_reference_cards_and_settings()
    {
        $response = $this->get(route('games.home'))->assertOk();
        $response->assertSee('PianoLIT Games')->assertSee('site-header', false)->assertSee('site-footer', false)
            ->assertSee('btn-floating', false)->assertSee('data-duel-join', false)->assertSee('aria-current="page"', false);
        foreach (array_keys(GameRegistry::GAMES) as $slug) {
            $response->assertSee('data-game-card="'.$slug.'"', false)->assertSee('leaderboard-'.$slug.'-modal', false);
        }
        $this->assertSame(11, substr_count($response->getContent(), 'data-game-card='));
    }

    public function test_games_are_focused_and_keep_settings_and_result_controls()
    {
        foreach (array_keys(GameRegistry::GAMES) as $slug) {
            $this->get(route('games.'.$slug.'.play'))->assertOk()
                ->assertDontSee('class="site-header', false)->assertDontSee('class="site-footer', false)
                ->assertSee('id="page-wrapper"', false)->assertSee('window.__challengeOptions', false)
                ->assertSee('data-leaderboard-profile', false)->assertSee('window.__duelConfig', false);
        }
    }

    public function test_settings_queries_are_validated_and_replay_booleans_are_preserved()
    {
        $this->getJson(route('games.note-nest.play', ['numOfChallenges' => 999]))->assertStatus(422);
        $this->getJson(route('games.note-nest.play', ['clefs' => ['bad']]))->assertStatus(422);
        $response = $this->get(route('games.note-nest.play', ['numOfChallenges' => 7, 'clefs' => ['alto'], 'sound' => 0]))->assertOk();
        $doc = new \DOMDocument; @$doc->loadHTML($response->getContent());
        $options = json_decode($doc->getElementById('pagetitle')->getAttribute('data-game-options'), true);
        $this->assertSame(['alto'], $options['clefs']); $this->assertFalse($options['sound']);
    }

    public function test_scores_and_points_are_validated_and_leaderboards_filter_by_game_and_period()
    {
        $data = ['game' => 'Note Nest', 'username' => 'PracticePlayer', 'avatar_url' => 'http://games.localhost/images/games/avatars/avatar-1.svg',
            'score' => 30, 'accuracy' => 95, 'rounds' => 4, 'duration' => '01:05'];
        $url = route('games.leaderboard.store');
        $this->post($url, $data)->assertRedirect(route('games.note-nest.play'));
        $player = Player::firstOrFail();
        $this->assertSame(65, $player->duration);
        $this->getJson(route('games.leaderboard.final-points', ['score' => 30, 'accuracy' => 95, 'rounds' => 4, 'duration' => 65]))
            ->assertOk()->assertExactJson([$player->finalScore]);
        $this->postJson($url, array_replace($data, ['avatar_url' => 'https://invalid.test/avatar.svg']))->assertStatus(422);
        $this->postJson($url, array_replace($data, ['game' => 'not-a-game']))->assertStatus(422);
        $this->postJson($url, array_replace($data, ['score' => -1]))->assertStatus(422);
        $this->postJson($url, array_replace($data, ['accuracy' => 101]))->assertStatus(422);
        $this->get(route('games.leaderboard.show', ['game' => 'Note Nest', 'range' => 'week']))->assertOk()->assertSee('PracticePlayer');
        DB::table('game_scores')->update(['created_at' => now()->subDays(8)]);
        $this->get(route('games.leaderboard.show', ['game' => 'Note Nest', 'range' => 'week']))->assertOk()->assertDontSee('PracticePlayer');
        $this->get(route('games.leaderboard.show', ['game' => 'Note Nest', 'range' => 'all']))->assertOk()->assertSee('PracticePlayer');
        $this->get(route('games.leaderboard.show', ['game' => 'Beat Hero']))->assertOk()->assertDontSee('PracticePlayer');
    }

    public function test_games_use_only_the_plural_host_and_unknown_games_are_404()
    {
        $this->assertNull(app('router')->getRoutes()->getByName('games.singular'));
        $this->get('http://game.localhost/note-nest')->assertNotFound();
        $this->get('http://games.localhost/not-a-game')->assertNotFound();
        $routes = app('router')->getRoutes();
        $this->assertContains(\App\Http\Middleware\Logs\RecordWebLog::class, $routes->getByName('games.duels.show')->excludedMiddleware());
    }

    public function test_score_submission_restores_that_runs_settings_without_trusting_a_return_url()
    {
        $options = ['numOfChallenges' => 7, 'sound' => false, 'clefs' => ['alto']];
        $data = ['game' => 'Note Nest', 'username' => 'Player', 'avatar_url' => 'http://games.localhost/images/games/avatars/avatar-2.svg',
            'score' => 20, 'accuracy' => 80, 'rounds' => 7, 'duration' => '00:59', 'return_options' => json_encode($options)];
        $response = $this->withHeader('Referer', 'https://unrelated.test/')->post(route('games.leaderboard.store'), $data)->assertRedirect();
        $destination = $response->headers->get('Location');
        $this->assertSame('games.localhost', parse_url($destination, PHP_URL_HOST));
        $this->assertSame('/note-nest', parse_url($destination, PHP_URL_PATH));
        parse_str(parse_url($destination, PHP_URL_QUERY), $query);
        $this->assertEquals(['numOfChallenges' => '7', 'sound' => '0', 'clefs' => ['alto']], $query);
        $this->postJson(route('games.leaderboard.store'), array_replace($data, ['return_options' => '{"numOfChallenges":999}']))->assertStatus(422);
        $this->assertSame(1, Player::count());
    }

    public function test_each_games_saved_replay_url_accepts_empty_array_sentinels()
    {
        foreach (GameRegistry::GAMES as $slug => $class) {
            $options = (new $class)->replayOptions();
            $query = array_map(fn ($value) => $value === [] ? [''] : $value, $options);
            $this->get(route('games.'.$slug.'.play', $query))->assertOk();
        }
        $this->getJson(route('games.home', ['category' => ['reading']]))->assertStatus(422);
        $this->get(route('games.home', ['numOfChallenges' => ['ignored']]))->assertOk();
    }

    public function test_game_profile_lives_in_the_header_account_menu_for_guests_and_accounts()
    {
        $user = \Illuminate\Database\Eloquent\Model::withoutEvents(function () {
            return create(\App\User::class, ['first_name' => 'Header', 'email' => 'games-header@example.test']);
        });
        foreach ([false, true] as $signedIn) {
            if ($signedIn) $this->actingAs($user, 'web');
            $html = $this->get(route('games.home'))->assertOk()->getContent();
            $doc = new \DOMDocument; @$doc->loadHTML($html); $xpath = new \DOMXPath($doc);
            $this->assertSame(1, $xpath->query('//header//button[contains(@class, "account-circle") and @data-bs-toggle="dropdown"]')->length);
            $this->assertSame(1, $xpath->query('//header//div[contains(@class, "dropdown-menu")]//*[@data-games-profile]')->length);
            $this->assertSame(1, $xpath->query('//header//div[contains(@class, "games-account-menu") and contains(@class, "dropdown-menu-end") and not(contains(@class, "animated"))]')->length);
            $this->assertSame(0, $xpath->query('//main//*[@data-games-profile] | //*[@id="user-avatar"] | //*[@id="user-avatar-menu"]')->length);
            $this->assertSame(1, $xpath->query('//header//*[@data-games-profile]//button[@data-profile-delete]')->length);
            $this->assertSame(11, $xpath->query('//header//*[@data-games-profile]//*[@data-profile-game]')->length);
            $this->assertSame($signedIn ? 0 : 1, $xpath->query('//header//a[@href="http://games.localhost/login"]')->length);
            $this->assertSame($signedIn ? 1 : 0, $xpath->query('//header//a[@href="http://localhost/profile"]')->length);
            $this->assertSame($signedIn ? 1 : 0, $xpath->query('//header//form[@action="http://games.localhost/logout"]//input[@name="_token"]')->length);
            $this->assertSame($signedIn ? 1 : 0, $xpath->query('//header//span[contains(@class, "account-circle__initial") and text()="H"]')->length);
        }
        $this->get(route('games.note-match.play'))->assertOk()->assertDontSee('data-games-profile', false);
    }
}