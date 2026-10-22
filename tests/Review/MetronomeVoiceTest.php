<?php

namespace Tests\Review;

use App\{Admin, User};
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

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

    private function payload($revision = null)
    {
        return ['revision' => $revision, 'clips' => [$this->clip(1000), $this->clip(2000), $this->clip(3000), $this->clip(4000)]];
    }

    public function test_only_web_accounts_can_read_save_or_delete_recordings()
    {
        $url = route('metronome.voice.show');
        $this->getJson($url)->assertUnauthorized();
        $this->putJson($url, $this->payload())->assertUnauthorized();
        $this->deleteJson($url, ['revision' => 'aaaaaaaa-aaaa-4aaa-8aaa-aaaaaaaaaaaa'])->assertUnauthorized();
        $this->actingAs(create(Admin::class), 'admin')->getJson($url)->assertUnauthorized();
        $this->assertDatabaseCount('metronome_voices', 0);
    }

    public function test_account_ownership_private_reads_atomic_replacement_and_stale_tab_protection()
    {
        $url = route('metronome.voice.show');
        $this->actingAs($this->user, 'web');
        $this->getJson($url)->assertExactJson(['revision' => null, 'clips' => []]);
        $saved = $this->putJson($url, $this->payload() + ['user_id' => $this->other->id])->assertOk();
        $revision = $saved->json('revision');
        $this->assertStringContainsString('no-store', $saved->headers->get('Cache-Control'));
        $this->assertDatabaseHas('metronome_voices', ['user_id' => $this->user->id, 'revision' => $revision]);
        $this->assertDatabaseMissing('metronome_voices', ['user_id' => $this->other->id]);
        $this->actingAs($this->other, 'web')->getJson($url.'?user_id='.$this->user->id)
            ->assertExactJson(['revision' => null, 'clips' => []]);
        $this->deleteJson($url, ['revision' => $revision, 'user_id' => $this->user->id])->assertStatus(409);
        $this->actingAs($this->user, 'web');
        $this->putJson($url, $this->payload())->assertStatus(409);
        $next = $this->putJson($url, $this->payload($revision))->assertOk()->json('revision');
        $this->assertNotSame($next, $revision);
        $this->deleteJson($url, ['revision' => $revision])->assertStatus(409);
        $read = $this->getJson($url)->assertOk()->assertJsonPath('revision', $next);
        $this->assertSame($this->payload()['clips'], $read->json('clips'));
        $this->assertStringContainsString('no-store', $read->headers->get('Cache-Control'));
        $this->deleteJson($url, ['revision' => $next])->assertOk()->assertExactJson(['revision' => null, 'clips' => []]);
        $this->assertDatabaseCount('metronome_voices', 0);
        $this->assertArrayNotHasKey('voice', $this->user->fresh()->getAttributes());
    }

    public function test_malformed_audio_and_incomplete_sets_cannot_replace_a_saved_voice()
    {
        $this->actingAs($this->user, 'web');
        $url = route('metronome.voice.store');
        $revision = $this->putJson($url, $this->payload())->assertOk()->json('revision');
        $badRate = base64_decode($this->clip());
        $badRate = substr_replace($badRate, pack('V', 48000), 24, 4);
        foreach (['not audio', base64_encode('RIFF'.str_repeat('a', 4000)), base64_encode($badRate),
            base64_encode(base64_decode($this->clip()).'extra'), str_repeat('A', 128064)] as $badClip) {
            $payload = $this->payload($revision);
            $payload['clips'][2] = $badClip;
            $this->putJson($url, $payload)->assertUnprocessable();
        }
        $this->putJson($url, ['revision' => $revision, 'clips' => [$this->clip()]])->assertUnprocessable();
        $this->putJson($url, ['revision' => $revision, 'clips' => array_fill_keys(['a', 'b', 'c', 'd'], $this->clip())])->assertUnprocessable();
        $this->getJson($url)->assertJsonPath('revision', $revision)->assertJsonPath('clips', $this->payload()['clips']);
        $this->assertDatabaseCount('metronome_voices', 1);
    }

    public function test_writes_require_csrf_and_account_deletion_removes_private_audio()
    {
        $this->actingAs($this->user, 'web');
        $this->app['env'] = 'review-csrf';
        $url = route('metronome.voice.store');
        $this->withSession(['_token' => 'voice-csrf'])->putJson($url, $this->payload())->assertStatus(419);
        $revision = $this->withHeader('X-CSRF-TOKEN', 'voice-csrf')->putJson($url, $this->payload())->assertOk()->json('revision');
        $this->withHeader('X-CSRF-TOKEN', 'wrong')->deleteJson($url, ['revision' => $revision])->assertStatus(419);
        DB::table('users')->where('id', $this->user->id)->delete();
        $this->assertDatabaseCount('metronome_voices', 0);
    }

    public function test_guest_sign_in_returns_to_the_recorder_and_signed_in_markup_uses_same_host_endpoints()
    {
        $start = route('metronome.voice.start');
        $this->get($start)->assertRedirect();
        $this->assertSame($start, session('url.intended'));
        $this->actingAs($this->user, 'web')->get($start)->assertRedirect(route('metronome.home', ['record-voice' => 1]));
        $this->get(route('metronome.home'))->assertOk()->assertSee('data-voice-recorder', false)
            ->assertSee('data-url="'.route('metronome.voice.show').'"', false)->assertSee('data-csrf=', false)
            ->assertSee('My voice')->assertSee('Use my voice')->assertDontSee('Sign in to record my voice');
    }
}
