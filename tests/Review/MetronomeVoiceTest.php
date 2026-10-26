<?php

namespace Tests\Review;

use App\{Admin, User};
use App\Services\Metronome\VoiceRecording;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class MetronomeVoiceTest extends ReviewTestCase
{
    protected $user, $other;

    public function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware([\App\Http\Middleware\Logs\RecordActivity::class, \App\Http\Middleware\UpdateLocation::class]);
        $this->withExceptionHandling();
        Model::withoutEvents(function () {
            $this->user = create(User::class)->setAppends(['full_name']);
            $this->other = create(User::class)->setAppends(['full_name']);
        });
    }

    protected function prepareUrlForRequest($uri)
    {
        return strpos($uri, 'http') === 0 ? $uri : parent::prepareUrlForRequest($uri);
    }

    private function clip($value = 4000)
    {
        $pcm = str_repeat(pack('v', $value), 3600);
        return base64_encode(pack('a4Va4a4VvvVVvva4V', 'RIFF', 36 + strlen($pcm), 'WAVE', 'fmt ', 16, 1, 1,
            24000, 48000, 2, 16, 'data', strlen($pcm)).$pcm);
    }

    private function payload($name = 'Calm counting')
    {
        return ['name' => $name, 'clips' => [$this->clip(1000), $this->clip(2000), $this->clip(3000), $this->clip(4000)]];
    }

    private function saved($name = 'Calm counting')
    {
        return $this->actingAs($this->user, 'web')->putJson(route('metronome.voice.store'), $this->payload($name))->assertOk()->json();
    }

    private function url($voice)
    {
        return route('metronome.voice.recording', ['voice' => $voice['id']]);
    }

    public function test_only_web_accounts_can_read_create_rename_or_delete_recordings()
    {
        $url = route('metronome.voice.show');
        $detail = $url.'/'.Str::uuid();
        $this->getJson($url)->assertUnauthorized();
        $this->getJson($detail)->assertUnauthorized();
        $this->putJson($url, $this->payload())->assertUnauthorized();
        $this->patchJson($detail, ['name' => 'New name', 'revision' => (string) Str::uuid()])->assertUnauthorized();
        $this->deleteJson($detail, ['revision' => (string) Str::uuid()])->assertUnauthorized();
        $this->actingAs(create(Admin::class), 'admin')->getJson($url)->assertUnauthorized();
        $this->assertDatabaseCount('metronome_voice_recordings', 0);
    }

    public function test_multiple_named_recordings_are_private_and_list_reads_omit_audio()
    {
        $this->actingAs($this->user, 'web')->getJson(route('metronome.voice.show'))
            ->assertExactJson(['recordings' => [], 'limit' => 20]);
        $first = $this->saved();
        $second = $this->saved('Energetic counting');
        $this->assertNotSame($first['id'], $second['id']);
        $this->assertDatabaseCount('metronome_voice_recordings', 2);
        $list = $this->getJson(route('metronome.voice.show'))->assertOk()->assertJsonCount(2, 'recordings');
        $this->assertArrayNotHasKey('clips', $list->json('recordings.0'));
        $this->assertArrayNotHasKey('user_id', $list->json('recordings.0'));
        $this->assertStringContainsString('no-store', $list->headers->get('Cache-Control'));
        $this->getJson($this->url($first))->assertExactJson($first);
        $this->getJson($this->url($second))->assertExactJson($second);

        $this->actingAs($this->other, 'web')->getJson(route('metronome.voice.show').'?user_id='.$this->user->id)
            ->assertExactJson(['recordings' => [], 'limit' => 20]);
        $this->getJson($this->url($first))->assertNotFound();
        $this->patchJson($this->url($first), ['revision' => $first['revision'], 'name' => 'Stolen'])->assertNotFound();
        $this->deleteJson($this->url($first), ['revision' => $first['revision']])->assertNotFound();
        $new = $this->putJson(route('metronome.voice.store'), $this->payload() + ['user_id' => $this->user->id, 'id' => $first['id']])->assertOk()->json();
        $this->assertDatabaseHas('metronome_voice_recordings', ['id' => $new['id'], 'user_id' => $this->other->id]);
        $this->assertDatabaseHas('metronome_voice_recordings', ['id' => $first['id'], 'user_id' => $this->user->id]);
        $this->assertArrayNotHasKey('voice', $this->user->fresh()->getAttributes());
    }

    public function test_renaming_preserves_audio_and_stale_tabs_cannot_rename_or_delete_it()
    {
        $first = $this->saved();
        $second = $this->saved('Second voice');
        $url = $this->url($first);
        $renamed = $this->patchJson($url, ['revision' => $first['revision'], 'name' => '  Gentle voice  '])->assertOk()->json();
        $this->assertSame('Gentle voice', $renamed['name']);
        $this->assertNotSame($first['revision'], $renamed['revision']);
        $this->patchJson($url, ['revision' => $first['revision'], 'name' => 'Stale'])->assertStatus(409);
        $this->deleteJson($url, ['revision' => $first['revision']])->assertStatus(409);
        $this->getJson($url)->assertExactJson($renamed + ['clips' => $first['clips']]);
        $this->deleteJson($url, ['revision' => $renamed['revision']])->assertOk()->assertExactJson(['deleted' => $first['id']]);
        $this->getJson($url)->assertNotFound();
        $this->getJson($this->url($second))->assertExactJson($second);
        $this->assertDatabaseCount('metronome_voice_recordings', 1);
    }

    public function test_malformed_audio_names_and_incomplete_sets_cannot_change_saved_voices()
    {
        $this->withoutMiddleware(\Illuminate\Routing\Middleware\ThrottleRequests::class);
        $first = $this->saved();
        $url = route('metronome.voice.store');
        $badRate = substr_replace(base64_decode($this->clip()), pack('V', 48000), 24, 4);
        foreach (['not audio', base64_encode('RIFF'.str_repeat('a', 4000)), base64_encode($badRate),
            base64_encode(base64_decode($this->clip()).'extra'), str_repeat('A', 128064)] as $badClip) {
            $payload = $this->payload();
            $payload['clips'][2] = $badClip;
            $this->putJson($url, $payload)->assertUnprocessable();
        }
        $this->putJson($url, ['name' => 'Test', 'clips' => [$this->clip()]])->assertUnprocessable();
        $this->putJson($url, ['name' => 'Test', 'clips' => array_fill_keys(['a', 'b', 'c', 'd'], $this->clip())])->assertUnprocessable();
        foreach (['', '   ', str_repeat('a', 61), ['array']] as $name) {
            $this->putJson($url, $this->payload($name))->assertUnprocessable();
            $this->patchJson($this->url($first), ['revision' => $first['revision'], 'name' => $name])->assertUnprocessable();
        }
        $this->getJson($this->url($first))->assertExactJson($first);
        $this->assertDatabaseCount('metronome_voice_recordings', 1);
    }

    public function test_library_limit_is_enforced_per_account_without_affecting_existing_recordings()
    {
        $first = $this->saved();
        for ($i = 1; $i < VoiceRecording::MAX_RECORDINGS; $i++) {
            app(VoiceRecording::class)->save($this->user, 'Voice '.$i, $this->payload()['clips']);
        }
        $this->putJson(route('metronome.voice.store'), $this->payload())->assertUnprocessable()->assertJsonValidationErrors('name');
        $this->getJson($this->url($first))->assertExactJson($first);
        $this->getJson(route('metronome.voice.show'))->assertJsonCount(20, 'recordings');
        $this->actingAs($this->other, 'web')->putJson(route('metronome.voice.store'), $this->payload())->assertOk();
        $this->assertDatabaseCount('metronome_voice_recordings', 21);
    }

    public function test_writes_require_csrf_and_account_deletion_removes_all_private_voices()
    {
        $first = $this->saved();
        $this->saved('Second');
        $this->app['env'] = 'review-csrf';
        $this->withSession(['_token' => 'voice-csrf'])->putJson(route('metronome.voice.store'), $this->payload())->assertStatus(419);
        $this->patchJson($this->url($first), ['revision' => $first['revision'], 'name' => 'No'])->assertStatus(419);
        $this->deleteJson($this->url($first), ['revision' => $first['revision']])->assertStatus(419);
        $this->withHeader('X-CSRF-TOKEN', 'voice-csrf')->patchJson($this->url($first), ['revision' => $first['revision'], 'name' => 'Yes'])->assertOk();
        DB::table('users')->where('id', $this->user->id)->delete();
        $this->assertDatabaseCount('metronome_voice_recordings', 0);
    }

    public function test_upgrade_keeps_the_existing_audio_and_deleting_it_removes_the_legacy_copy()
    {
        $revision = (string) Str::uuid();
        $clips = json_encode($this->payload()['clips']);
        DB::table('metronome_voices')->insert(['user_id' => $this->user->id, 'revision' => $revision, 'clips' => $clips, 'created_at' => now(), 'updated_at' => now()]);
        require_once database_path('migrations/2026_10_22_210000_create_metronome_voice_recordings_table.php');
        $migration = new \CreateMetronomeVoiceRecordingsTable;
        $migration->down();
        $migration->up();
        $this->assertDatabaseHas('metronome_voice_recordings', ['id' => $revision, 'user_id' => $this->user->id, 'name' => 'My voice', 'clips' => $clips]);
        $this->actingAs($this->user, 'web')->getJson(route('metronome.voice.show'))->assertJsonPath('recordings.0.id', $revision);
        $renamed = $this->patchJson(route('metronome.voice.rename', $revision), ['revision' => $revision, 'name' => 'Original voice'])->assertOk()->json();
        $this->deleteJson(route('metronome.voice.destroy', $revision), ['revision' => $renamed['revision']])->assertOk();
        $this->assertDatabaseCount('metronome_voices', 0);
        $this->assertDatabaseCount('metronome_voice_recordings', 0);
    }

    public function test_guest_sign_in_returns_to_the_recorder_and_signed_in_markup_uses_shared_fields()
    {
        $start = route('metronome.voice.start');
        $this->get($start)->assertRedirect();
        $this->assertSame($start, session('url.intended'));
        $this->actingAs($this->user, 'web')->get($start)->assertRedirect(route('metronome.home', ['record-voice' => 1]));
        $this->get(route('metronome.home'))->assertOk()->assertSee('data-voice-recorder', false)
            ->assertSee('data-url="'.route('metronome.voice.show').'"', false)->assertSee('data-csrf=', false)
            ->assertDontSee('metronome_voice_choice')->assertSee('Save and use')->assertSee('metronome_voice_name')
            ->assertSee('form-control pianolit-input', false)->assertDontSee('Sign in to record my voice')
            ->assertDontSee('data-voice-delete-confirm', false)
            ->assertDontSee('Keep it')->assertDontSee('data-voice-confirm-delete', false);
    }

    public function test_settings_reuse_the_favorites_bottom_panel_with_native_dismissal()
    {
        foreach ([false, true] as $signedIn) {
            if ($signedIn) $this->actingAs($this->user, 'web');
            $html = $this->get(route('metronome.home'))->assertOk()->getContent();
            $dom = new \DOMDocument;
            @$dom->loadHTML($html);
            $xpath = new \DOMXPath($dom);
            $this->assertSame(0, $xpath->query('//dialog')->length);
            $this->assertSame(1, $xpath->query('//*[@id="metronome-settings" and contains(@class, "bottom-sheet") and contains(@class, "offcanvas-bottom") and @tabindex="-1" and @aria-labelledby="settings-title"]')->length);
            $this->assertSame(1, $xpath->query('//*[@id="metronome-settings"]//button[contains(@class, "bottom-sheet-handle") and @data-bs-dismiss="offcanvas" and @aria-label="Close settings"]')->length);
            $this->assertSame(1, $xpath->query('//*[@id="metronome-settings"]//button[@data-settings-close and @data-bs-dismiss="offcanvas"]')->length);
            $this->assertSame(1, $xpath->query('//*[@id="metronome-settings"]//*[@data-sound-menu]')->length);
        }
        $favorite = view('webapp.piece.components.saveto.offcanvas')->render();
        $this->assertStringContainsString('offcanvas offcanvas-bottom bottom-sheet', $favorite);
        $this->assertStringContainsString('id="save-to-offcanvas-content"', $favorite);
        $this->assertStringContainsString('aria-labelledby="save-to-title"', $favorite);
    }

    public function test_sound_selector_groups_recorded_names_and_preserves_the_empty_and_legacy_select_states()
    {
        view()->share('errors', new \Illuminate\Support\ViewErrorBag);
        $html = view('metronome.components.sound-menu', ['canRecord' => true, 'recordings' => [
            ['id' => 'one', 'name' => 'Arthur'], ['id' => 'two', 'name' => 'Arthur'],
            ['id' => 'three', 'name' => '<Voice & piano>'],
        ], 'value' => 'voice:two'])->render();
        $dom = new \DOMDocument;
        @$dom->loadHTML($html);
        $xpath = new \DOMXPath($dom);
        $this->assertSame(4, $xpath->query('//select/option')->length);
        $this->assertSame(3, $xpath->query('//optgroup[@label="Recorded voices"]/option')->length);
        $this->assertSame(1, $xpath->query('//optgroup/option[@value="voice:two" and @selected]')->length);
        $this->assertSame(0, $xpath->query('//option[@value="my-voice"]')->length);
        $this->assertSame('<Voice & piano>', $xpath->query('//option[@value="voice:three"]')->item(0)->textContent);
        $this->assertSame(0, $xpath->query('//option/*')->length);
        $this->assertSame(1, $xpath->query('//button[contains(@class, "form-control-select-trigger") and @aria-haspopup="dialog"]')->length);
        $this->assertSame(3, $xpath->query('//*[@data-sound-voices]/*[@data-sound-row]')->length);
        $this->assertSame(3, $xpath->query('//*[@data-sound-voices]//button[@data-sound-play]')->length);
        $this->assertSame(3, $xpath->query('//*[@data-sound-voices]//button[@data-sound-edit]')->length);
        $this->assertSame(3, $xpath->query('//*[@data-sound-voices]//button[@data-sound-delete]')->length);
        $this->assertSame(0, $xpath->query('//button//button')->length);
        $this->assertSame(1, $xpath->query('//button[@data-voice-start and contains(@class, "btn-sm") and contains(@class, "w-100")]')->length);
        $this->assertSame(0, $xpath->query('//*[@data-sound-popup]//*[@data-voice-start]')->length);
        $this->assertSame(1, $xpath->query('//*[@data-sound-popup]/following-sibling::*//button[@data-voice-start]')->length);
        $empty = view('metronome.components.sound-menu', ['canRecord' => true])->render();
        $this->assertStringContainsString('value="my-voice"', $empty);
        $this->assertStringNotContainsString('<optgroup', $empty);
        $guest = view('metronome.components.sound-menu', ['canRecord' => false])->render();
        $this->assertStringNotContainsString('value="my-voice"', $guest);
        $legacy = view('components.forms.select', ['bag' => 'default', 'name' => 'key', 'optGroups' => ['Tonal' => ['C major' => 'C']], 'select' => 'C'])->render();
        $this->assertStringContainsString('<optgroup label="Tonal">', $legacy);
        $this->assertStringContainsString('C major', $legacy);
    }
}
