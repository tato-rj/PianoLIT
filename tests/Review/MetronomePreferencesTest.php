<?php

namespace Tests\Review;

use App\{Admin, User};
use App\Services\WebApp\MetronomePreferences;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class MetronomePreferencesTest extends ReviewTestCase
{
    protected $user, $other;

    public function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware([\App\Http\Middleware\Logs\RecordWebAppLog::class, \App\Http\Middleware\UpdateLocation::class]);
        $this->withExceptionHandling();
        Model::withoutEvents(function () {
            $this->user = create(User::class)->setAppends(['full_name']);
            $this->other = create(User::class)->setAppends(['full_name']);
        });
    }

    public function test_guests_can_get_defaults_without_queries_but_cannot_read_or_save_account_preferences()
    {
        DB::enableQueryLog();
        $this->assertSame(MetronomePreferences::DEFAULTS, app(MetronomePreferences::class)->forUser(null));
        $this->assertCount(0, DB::getQueryLog());
        DB::disableQueryLog();
        $this->getJson(route('webapp.metronome.preferences.show', ['user_id' => $this->user->id]))->assertUnauthorized();
        $this->patchJson(route('webapp.metronome.preferences.update'), [
            'user_id' => $this->user->id, 'preferences' => ['tempo' => 120],
        ])->assertUnauthorized();
        $this->actingAs(create(Admin::class), 'admin');
        $this->patchJson(route('webapp.metronome.preferences.update'), ['preferences' => ['tempo' => 120]])->assertUnauthorized();
        $this->assertDatabaseCount('web_metronome_preferences', 0);
    }

    public function test_web_accounts_restore_their_own_settings_and_partial_saves_preserve_other_fields()
    {
        $url = route('webapp.metronome.preferences.update');
        $this->actingAs($this->user, 'web');
        $this->getJson(route('webapp.metronome.preferences.show'))->assertOk()->assertExactJson(['preferences' => MetronomePreferences::DEFAULTS]);
        $this->assertDatabaseCount('web_metronome_preferences', 0);
        $this->patchJson($url, ['user_id' => $this->other->id, 'preferences' => ['tempo' => 174, 'beats' => 4, 'sound' => 'female-voice']])
            ->assertOk()->assertExactJson(['preferences' => ['tempo' => 174, 'beats' => 4, 'sound' => 'female-voice']]);
        $this->patchJson($url, ['preferences' => ['tempo' => 20]])->assertOk()
            ->assertExactJson(['preferences' => ['tempo' => 20, 'beats' => 4, 'sound' => 'female-voice']]);
        $this->patchJson($url, ['preferences' => ['sound' => 'voice']])->assertOk();
        $this->patchJson($url, ['preferences' => ['beats' => 2]])->assertOk();
        $response = $this->getJson(route('webapp.metronome.preferences.show'))->assertOk()
            ->assertExactJson(['preferences' => ['tempo' => 20, 'beats' => 2, 'sound' => 'voice']]);
        $this->assertStringContainsString('no-store', $response->headers->get('Cache-Control'));
        $this->assertDatabaseCount('web_metronome_preferences', 1);
        $this->assertDatabaseMissing('web_metronome_preferences', ['user_id' => $this->other->id]);

        $this->actingAs($this->other, 'web')->getJson(route('webapp.metronome.preferences.show', ['user_id' => $this->user->id]))
            ->assertOk()->assertExactJson(['preferences' => MetronomePreferences::DEFAULTS]);
        $this->patchJson($url, ['preferences' => ['tempo' => 200]])->assertOk()
            ->assertExactJson(['preferences' => ['tempo' => 200, 'beats' => 1, 'sound' => 'woodblock']]);
        $this->assertDatabaseCount('web_metronome_preferences', 2);
        $this->assertDatabaseHas('web_metronome_preferences', ['user_id' => $this->user->id, 'tempo' => 20, 'beats' => 2, 'sound' => 'voice']);
        $this->assertArrayNotHasKey('metronome_preferences', $this->user->fresh()->getAttributes());
        DB::table('users')->where('id', $this->other->id)->delete();
        $this->assertDatabaseMissing('web_metronome_preferences', ['user_id' => $this->other->id]);
    }

    public function test_all_sounds_and_tempo_endpoints_are_accepted_and_invalid_shapes_do_not_change_settings()
    {
        $this->actingAs($this->user, 'web');
        $url = route('webapp.metronome.preferences.update');
        foreach (MetronomePreferences::SOUNDS as $sound) {
            $this->patchJson($url, ['preferences' => ['sound' => $sound]])->assertOk()->assertJsonPath('preferences.sound', $sound);
        }
        foreach ([20, 200] as $tempo) {
            $this->patchJson($url, ['preferences' => ['tempo' => $tempo]])->assertOk()->assertJsonPath('preferences.tempo', $tempo);
        }
        $invalid = [null, [], 'tempo', ['tempo' => 19], ['tempo' => 201], ['tempo' => 60.5], ['tempo' => null],
            ['tempo' => 'fast'], ['tempo' => []], ['beats' => 0], ['beats' => 5], ['beats' => 1.5],
            ['sound' => 'unknown'], ['sound' => null], ['sound' => []], ['tempo' => 80, 'user_id' => $this->other->id]];
        foreach ($invalid as $preferences) {
            $this->patchJson($url, compact('preferences'))->assertUnprocessable();
        }
        $this->assertDatabaseCount('web_metronome_preferences', 1);
        $this->assertDatabaseHas('web_metronome_preferences', ['user_id' => $this->user->id, 'tempo' => 200, 'beats' => 1, 'sound' => 'female-voice']);
    }

    public function test_preferences_require_the_web_session_csrf_token()
    {
        $this->actingAs($this->user, 'web');
        $this->app['env'] = 'review-csrf';
        $this->withSession(['_token' => 'metronome-csrf'])->patchJson(route('webapp.metronome.preferences.update'), ['preferences' => ['tempo' => 90]])->assertStatus(419);
        $this->assertDatabaseCount('web_metronome_preferences', 0);
        $this->withHeader('X-CSRF-TOKEN', 'metronome-csrf')->patchJson(route('webapp.metronome.preferences.update'), ['preferences' => ['tempo' => 90]])->assertOk();
        $this->assertDatabaseHas('web_metronome_preferences', ['user_id' => $this->user->id, 'tempo' => 90]);
    }

    public function test_recording_credit_is_available_on_terms_without_adding_text_below_sound()
    {
        $this->get(route('webapp.terms'))->assertOk()->assertSee('Audio credits')->assertSee('TyLean Tuijl')
            ->assertSee('https://freesound.org/people/TyLean/sounds/390514/', false)
            ->assertSee('https://creativecommons.org/licenses/by/3.0/', false);
    }
}
