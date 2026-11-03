<?php

namespace Tests\Review;

use App\{Composer, Piece, Tag, Tutorial, User};
use App\Services\Api\V2\{MatchTourMedia, MatchTours, Tokens};
use App\Services\WebApp\{MatchQuiz, MatchTour, PieceCards};
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\{DB, File, Storage};
use Illuminate\Support\Str;

class ApiV2MatchTourTest extends ReviewTestCase
{
    private $user, $token, $pieces, $root;

    public function setUp(): void
    {
        parent::setUp();
        $this->withExceptionHandling();
        $this->withServerVariables(['HTTPS' => 'on']);
        $this->travelTo(carbon('2026-11-03 12:00:00'));
        Storage::fake('public');
        $this->root = sys_get_temp_dir().'/pianolit-match-'.Str::uuid();
        config(['match-tour.enabled' => true, 'match-tour.media_root' => $this->root]);
        Storage::disk('public')->put('fixture.mp3', 'original audio must not be served');
        Storage::disk('public')->put('fixture.pdf', 'original score must not be served');
        $this->pieces = Model::withoutEvents(function () {
            $this->user = create(User::class, ['email' => 'tour@example.test']);
            $composer = create(Composer::class, ['name' => 'Fixture Composer', 'cover_path' => 'fixture.jpg']);
            $levels = collect(array_keys(MatchTour::LEVELS))->map(function ($name) { return create(Tag::class, ['name' => $name, 'type' => 'level']); });
            $mood = create(Tag::class, ['name' => 'calm', 'type' => 'mood']);
            return collect(range(0, 15))->map(function ($i) use ($composer, $levels, $mood) {
                $piece = create(Piece::class, ['name' => 'Fixture piece '.$i, 'composer_id' => $composer->id, 'cover_path' => 'fixture.jpg',
                    'audio_path' => 'fixture.mp3', 'score_path' => 'fixture.pdf', 'score_url' => null, 'highlighted_at' => now()->subWeek()]);
                $piece->tags()->attach([$levels[$i % 4]->id, $mood->id]);
                create(Tutorial::class, ['piece_id' => $piece->id, 'type' => 'Tutorial', 'video_url' => 'https://example.test/fixture.mp4']);
                return $piece;
            });
        });
        // Prebuilt test assets exercise real file/signature delivery without encoding per test.
        foreach (['audio' => 'fixture.mp3', 'score' => 'fixture.pdf'] as $type => $source) {
            $key = app(MatchTourMedia::class)->descriptor($source, $type)['key'];
            File::ensureDirectoryExists($this->root.'/'.$key);
            file_put_contents($this->root.'/'.$key.'/manifest.json', json_encode(['duration_seconds' => $type === 'audio' ? 60 : null]));
            foreach ($type === 'audio' ? ['audio.mp3'] : ['excerpt.png', 'sample.png'] as $file) file_put_contents($this->root.'/'.$key.'/'.$file, 'bounded '.$file);
        }
        $this->token = app(Tokens::class)->issue($this->user)['access_token'];
    }

    public function tearDown(): void
    {
        File::deleteDirectory($this->root);
        parent::tearDown();
    }

    private function headers(): array { return ['Authorization' => 'Bearer '.$this->token]; }
    private function start(?string $key = null) { return $this->postJson('/api/v2/match-tours', ['request_id' => $key ?? (string) Str::uuid()], $this->headers()); }
    private function load(array $tour) { return $this->getJson('/api/v2/match-tours/'.$tour['id'], $this->headers()); }
    private function result(array $tour) { return $this->getJson('/api/v2/match-tours/'.$tour['id'].'/result', $this->headers()); }
    private function answer(array $tour, string $slot, $value, ?array $input = null) {
        return $this->putJson('/api/v2/match-tours/'.$tour['id'].'/answers/'.$slot,
            $input ?? ['request_id' => (string) Str::uuid(), 'revision' => $tour['revision'], 'value' => $value], $this->headers());
    }
    private function value(array $tour, string $slot) {
        if ($slot === 'level') return 'beginner';
        if ($slot === 'listening') return $tour['questions'][$slot]['choices'][0]['id'];
        if (strpos($slot, 'reading') === 0) return 'easy';
        if ($slot === 'taste-1') return $tour['questions'][$slot]['choices'][0]['id'];
        if ($slot === 'taste-2') return null;
        return 'calm';
    }
    private function complete(): array {
        $tour = $this->start()->assertOk()->json('data');
        foreach (MatchTours::SLOTS as $slot) $tour = $this->answer($tour, $slot, $this->value($tour, $slot))->assertOk()->json('data');
        return $tour;
    }

    public function test_complete_flow_uses_shared_draw_engine_summary_and_never_records_an_open()
    {
        $snapshots = [];
        $startKey = (string) Str::uuid();
        $tour = $this->start($startKey)->assertOk()->assertHeaderMissing('Set-Cookie')->assertHeader('Cache-Control', 'no-store, private')->json('data');
        $snapshots[] = ['request' => ['method' => 'POST', 'path' => '/match-tours', 'body' => ['request_id' => $startKey]], 'response' => ['data' => $tour, 'meta' => ['request_id' => (string) Str::uuid()]]];
        $this->assertSame(['value' => 16, 'accuracy' => 'exact'], $tour['progress']['initial_library_total']);
        $this->assertNull($tour['questions']['reading-2']);
        $this->assertCount(4, $tour['questions']['level']['choices']);
        $this->assertCount(4, $tour['questions']['listening']['choices']);
        $state = json_decode(DB::table('api_v2_match_tours')->first()->state, true);
        $this->assertCount(8, array_unique(array_column($state['pieces'], 'id')));
        $this->assertSame([], array_intersect(array_column($state['levels'], 'id'), array_column(array_slice($state['pieces'], 0, 4), 'id')));
        foreach (MatchTours::SLOTS as $i => $slot) {
            $input = ['request_id' => (string) Str::uuid(), 'revision' => $tour['revision'], 'value' => $this->value($tour, $slot)];
            $tour = $this->answer($tour, $slot, null, $input)->assertOk()->json('data');
            $snapshots[] = ['request' => ['method' => 'PUT', 'path' => '/match-tours/'.$tour['id'].'/answers/'.$slot, 'body' => $input], 'response' => ['data' => $tour, 'meta' => ['request_id' => (string) Str::uuid()]]];
            $this->assertSame($i + 1, $tour['revision']);
            $this->assertNull($tour['result']);
            $this->assertSame('estimated', $tour['progress']['remaining_pieces']['accuracy']);
            $this->assertGreaterThanOrEqual(2, $tour['progress']['remaining_pieces']['value']);
        }
        $this->assertSame('ready', $tour['status']);
        $this->assertSame('7', (string) $tour['revision']);
        $response = $this->result($tour)->assertOk();
        $final = $response->json('data');
        $snapshots[] = ['request' => ['method' => 'GET', 'path' => '/match-tours/'.$tour['id'].'/result'], 'response' => $response->json()];
        $this->assertSame('matched', $final['status']);
        $this->assertSame(['value' => 1, 'accuracy' => 'exact'], $final['progress']['remaining_pieces']);
        $this->assertSame($final, $this->result($tour)->assertOk()->json('data'));
        $state = json_decode(DB::table('api_v2_match_tours')->first()->state, true);
        $answers = ['playingLevel' => 'beginner', 'preferredPiece' => (int) $tour['answers']['listening'], 'reading' => [true, true],
            'winners' => [(int) $tour['answers']['taste-1'], null], 'mood' => 'calm'];
        $quiz = new class extends MatchQuiz { public function rankedIds() { return $this->ranking->pluck('id')->all(); } };
        $quiz->getKeywords((new MatchTour)->keywords($answers))->search(true, true);
        $this->assertContains((int) $final['result']['piece']['id'], $quiz->rankedIds());
        $piece = Piece::find($final['result']['piece']['id']); PieceCards::load(collect([$piece]), false);
        $summary = app(\App\Services\Api\V2\DiscoverResponse::class)->piece($piece);
        $summary['artwork_url'] = MatchTourMedia::https($summary['artwork_url']);
        $summary['composer']['portrait_url'] = MatchTourMedia::https($summary['composer']['portrait_url']);
        $summary['target']['web_url'] = MatchTourMedia::https($summary['target']['web_url']);
        $summary['composer']['target']['web_url'] = MatchTourMedia::https($summary['composer']['target']['web_url']);
        $this->assertSame($summary, $final['result']['piece']);
        $this->assertSame('piece', $final['result']['piece']['target']['kind']);
        $this->assertFalse($final['result']['piece']['media']['performance']);
        $this->assertDatabaseCount('recently_viewed_pieces', 0);
        $this->assertGuest('web');
        $json = json_encode($snapshots);
        foreach (['fixture.mp3', 'fixture.pdf', 'video_url', 'score_path', 'password', 'access_token'] as $secret) $this->assertStringNotContainsString($secret, $json);
        if ($file = getenv('API_V2_MATCH_FIXTURE')) file_put_contents($file, json_encode(['synthetic' => true, 'exchanges' => $snapshots], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)."\n");
    }

    public function test_adaptive_reading_and_changes_clear_every_dependent_answer_and_match()
    {
        $tour = $this->complete();
        $tour = $this->result($tour)->assertOk()->json('data');
        $tour = $this->answer($tour, 'reading-1', 'difficult')->assertOk()->json('data');
        $this->assertSame(['level', 'listening', 'reading-1'], array_keys($tour['answers']));
        $this->assertNull($tour['result']);
        $this->assertSame('reading-2', $tour['progress']['current_question']);
        $state = json_decode(DB::table('api_v2_match_tours')->first()->state, true);
        $this->assertSame($state['scores']['beginner']['id'], $tour['questions']['reading-2']['id']);
        $tour = $this->answer($tour, 'reading-1', 'easy')->assertOk()->json('data');
        $this->assertSame($state['scores']['hard']['id'], $tour['questions']['reading-2']['id']);
        $tour = $this->answer($tour, 'level', 'advanced')->assertOk()->json('data');
        $this->assertSame(['level' => 'advanced'], $tour['answers']);
        $this->assertNull($tour['questions']['reading-2']);
        $this->result($tour)->assertStatus(409)->assertJsonPath('error.code', 'incomplete_tour');
    }

    public function test_starts_retries_stale_updates_and_explicit_skip_are_safe()
    {
        $key = (string) Str::uuid();
        $tour = $this->start($key)->assertOk()->json('data');
        $this->assertSame($tour, $this->start($key)->assertOk()->json('data'));
        $input = ['request_id' => (string) Str::uuid(), 'revision' => 0, 'value' => 'beginner'];
        $next = $this->answer($tour, 'level', null, $input)->assertOk()->json('data');
        $this->assertSame($next, $this->answer($tour, 'level', null, $input)->assertOk()->json('data'));
        $this->answer($tour, 'level', 'advanced')->assertStatus(409)->assertJsonPath('error.code', 'stale_tour');
        $bad = $input; $bad['value'] = 'advanced';
        $this->answer($next, 'level', null, $bad)->assertStatus(409)->assertJsonPath('error.code', 'idempotency_conflict');
        $later = $this->answer($next, 'listening', $this->value($next, 'listening'))->assertOk()->json('data');
        $this->assertSame($later, $this->answer($tour, 'level', null, $input)->assertOk()->json('data'));
        $same = $this->answer($later, 'level', 'beginner')->assertOk()->json('data');
        $this->assertSame($later['revision'], $same['revision']);
        $this->assertArrayHasKey('listening', $same['answers']);
        $this->assertNotSame($tour['id'], $this->start()->assertOk()->json('data.id'));
        $this->assertDatabaseCount('api_v2_match_tours', 2);
    }

    public function test_invalid_shapes_choices_and_out_of_order_answers_never_mutate_state()
    {
        $tour = $this->start()->assertOk()->json('data');
        $this->answer($tour, 'listening', $this->value($tour, 'listening'))->assertStatus(409);
        foreach ([null, 'expert', 1, true, [], ['elementary']] as $value) $this->answer($tour, 'level', $value)->assertStatus(422);
        $tour = $this->answer($tour, 'level', 'beginner')->assertOk()->json('data');
        $this->answer($tour, 'listening', $tour['questions']['taste-1']['choices'][0]['id'])->assertStatus(422)->assertJsonPath('error.code', 'invalid_answer');
        $this->answer($tour, 'listening', null)->assertStatus(422);
        $this->assertSame($tour, $this->load($tour)->assertOk()->json('data'));
        $this->assertDatabaseCount('recently_viewed_pieces', 0);
    }

    public function test_all_changed_answers_invalidate_only_later_answers_and_cannot_use_other_tours_choices()
    {
        $tour = $this->complete();
        $this->answer($tour, 'taste-1', '')->assertStatus(422);
        $this->answer($tour, 'reading-1', null)->assertStatus(422);
        $this->answer($tour, 'reading-1', 'true')->assertStatus(422);
        $this->answer($tour, 'mood', 'not-a-mood')->assertStatus(422);
        $this->answer($tour, 'mood', null, ['request_id' => (string) Str::uuid(), 'revision' => (string) $tour['revision'], 'value' => 'calm'])->assertStatus(422);
        $this->putJson('/api/v2/match-tours/'.$tour['id'].'/answers/taste-2', ['request_id' => (string) Str::uuid(), 'revision' => $tour['revision']], $this->headers())->assertStatus(422);
        $tour = $this->answer($tour, 'taste-1', null)->assertOk()->json('data');
        $this->assertSame(['level', 'listening', 'reading-1', 'reading-2', 'taste-1'], array_keys($tour['answers']));
        $this->assertArrayHasKey('taste-1', $tour['answers']);
        $this->assertNull($tour['answers']['taste-1']);
        $this->answer($tour, 'taste-2', $tour['questions']['taste-1']['choices'][0]['id'])->assertStatus(422);
        $tour = $this->answer($tour, 'taste-2', null)->assertOk()->json('data');
        $tour = $this->answer($tour, 'reading-2', 'difficult')->assertOk()->json('data');
        $this->assertSame(['level', 'listening', 'reading-1', 'reading-2'], array_keys($tour['answers']));
        $tour = $this->answer($tour, 'listening', $tour['questions']['listening']['choices'][1]['id'])->assertOk()->json('data');
        $this->assertSame(['level', 'listening'], array_keys($tour['answers']));
        $this->post('/api/v2/match-tours', ['request_id' => (string) Str::uuid()], $this->headers())->assertStatus(422)->assertJsonPath('error.code', 'invalid_request');
    }

    public function test_authentication_ownership_expiration_and_deleted_account_remain_separate()
    {
        $key = (string) Str::uuid();
        $tour = $this->start($key)->assertOk()->json('data');
        $this->actingAs($this->user, 'web')->get('/api/v2/match-tours/'.$tour['id'])->assertUnauthorized()->assertJsonPath('error.code', 'unauthenticated');
        $original = $this->token;
        $other = Model::withoutEvents(function () { return create(User::class); });
        $this->token = app(Tokens::class)->issue($other)['access_token'];
        $this->load($tour)->assertNotFound(); $this->answer($tour, 'level', 'beginner')->assertNotFound(); $this->result($tour)->assertNotFound();
        $this->token = $original;
        $this->travel(2)->hours();
        foreach ([$this->load($tour), $this->answer($tour, 'level', 'beginner'), $this->result($tour), $this->start($key)] as $response) $response->assertStatus(410)->assertJsonPath('error.code', 'tour_expired');
        $this->assertNotNull(app(Tokens::class)->resolve($this->token));
        $this->start()->assertOk();
        DB::table('users')->where('id', $this->user->id)->delete();
        $this->assertDatabaseCount('api_v2_match_tours', 0);
    }

    public function test_signed_bounded_media_requires_no_bearer_and_refreshes_without_logout()
    {
        $tour = $this->start()->assertOk()->json('data');
        $url = $tour['questions']['listening']['choices'][0]['audio_preview']['audio_url'];
        $this->assertStringStartsWith('https://', $url);
        $this->get($url)->assertOk()->assertHeader('Content-Type', 'audio/mpeg')->assertHeaderMissing('Set-Cookie');
        $this->get($url, ['Range' => 'bytes=0-3'])->assertStatus(206)->assertHeader('Content-Length', '4');
        $sample = $tour['questions']['reading-1']['score_sample']['sample_url'];
        $this->get($sample)->assertOk()->assertHeader('Content-Type', 'image/png');
        $this->get(preg_replace('/signature=[a-f0-9]+/', 'signature=bad', $url))->assertStatus(403)->assertJsonPath('error.code', 'media_url_expired');
        $this->travel(16)->minutes();
        $this->get($url)->assertStatus(403);
        $fresh = $this->load($tour)->assertOk()->json('data.questions.listening.choices.0.audio_preview.audio_url');
        $this->assertNotSame($url, $fresh); $this->get($fresh)->assertOk();
        $asset = app(MatchTourMedia::class)->descriptor('fixture.mp3', 'audio');
        unlink($this->root.'/'.$asset['key'].'/audio.mp3');
        $this->get($fresh)->assertStatus(503)->assertJsonPath('error.code', 'media_unavailable');
    }

    public function test_missing_beginner_sample_uses_the_web_elementary_fallback_and_expired_state_is_pruned()
    {
        Model::withoutEvents(function () {
            foreach ($this->pieces as $i => $piece) if ($i % 4 === 1) $piece->update(['score_path' => null]);
        });
        $tour = $this->complete();
        $tour = $this->answer($tour, 'reading-1', 'difficult')->assertOk()->json('data');
        $state = json_decode(DB::table('api_v2_match_tours')->first()->state, true);
        $this->assertNull($state['scores']['beginner']);
        $this->assertSame($state['scores']['easy']['id'], $tour['questions']['reading-2']['id']);
        $this->travel(25)->hours();
        $this->artisan('api-v2:prune-match-tours')->assertExitCode(0);
        $this->assertDatabaseCount('api_v2_match_tours', 1);
        $this->travel(1)->hours();
        $this->artisan('api-v2:prune-match-tours')->assertExitCode(0);
        $this->assertDatabaseCount('api_v2_match_tours', 0);
        $this->load($tour)->assertNotFound();
    }

    public function test_offline_preparation_produces_only_bounded_audio_and_zoomable_crops()
    {
        $finder = new \Symfony\Component\Process\ExecutableFinder;
        foreach (['ffmpeg', 'ffprobe', 'pdftoppm'] as $tool) {
            $binary = $finder->find($tool);
            if (!$binary) $this->markTestSkipped($tool.' is required for real sample preparation.');
            config(['match-tour.'.$tool => $binary]);
        }
        $audio = Storage::disk('public')->path('long.wav');
        (new \Symfony\Component\Process\Process([config('match-tour.ffmpeg'), '-nostdin', '-v', 'error', '-f', 'lavfi', '-i', 'sine=frequency=440:duration=120', $audio]))->setTimeout(30)->mustRun();
        // One page with a green middle and a red top: only the middle may survive.
        $stream = '0 1 0 rg 0 280 612 232 re f 1 0 0 rg 0 600 612 192 re f';
        $objects = ['<< /Type /Catalog /Pages 2 0 R >>', '<< /Type /Pages /Kids [3 0 R] /Count 1 >>',
            '<< /Type /Page /Parent 2 0 R /MediaBox [0 0 612 792] /Contents 4 0 R >>',
            '<< /Length '.strlen($stream)." >>\nstream\n".$stream."\nendstream"];
        $pdf = "%PDF-1.4\n"; $offsets = [0];
        foreach ($objects as $i => $object) { $offsets[] = strlen($pdf); $pdf .= ($i + 1)." 0 obj\n".$object."\nendobj\n"; }
        $xref = strlen($pdf); $pdf .= "xref\n0 5\n0000000000 65535 f \n";
        foreach (array_slice($offsets, 1) as $offset) $pdf .= sprintf("%010d 00000 n \n", $offset);
        $pdf .= "trailer\n<< /Size 5 /Root 1 0 R >>\nstartxref\n".$xref."\n%%EOF\n";
        Storage::disk('public')->put('real.pdf', $pdf);
        $media = app(MatchTourMedia::class);
        $media->prepare('long.wav', 'audio'); $media->prepare('real.pdf', 'score');
        $clip = $media->asset('long.wav', 'audio');
        $this->assertGreaterThan(59, $clip['duration_seconds']);
        $this->assertLessThanOrEqual(60.15, $clip['duration_seconds']);
        $sample = $media->asset('real.pdf', 'score');
        foreach (['excerpt' => [900, 360], 'sample' => [1800, 720]] as $kind => $size) {
            $path = $media->file($sample['key'], $kind);
            $this->assertSame($size, array_slice(getimagesize($path), 0, 2));
            $image = imagecreatefrompng($path);
            $this->assertSame(0x00ff00, imagecolorat($image, (int) ($size[0] / 2), (int) ($size[1] / 2)));
            imagedestroy($image);
        }
        $this->assertCount(3, File::files($this->root.'/'.$sample['key']));
        $this->assertCount(2, File::files($this->root.'/'.$clip['key']));
        $this->assertSame($pdf, Storage::disk('public')->get('real.pdf'));
        $media->prepare('real.pdf', 'score'); // Repeat preparation preserves the current assets.
        $this->assertCount(3, File::files($this->root.'/'.$sample['key']));
        unlink($media->file($sample['key'], 'sample'));
        $media->prepare('real.pdf', 'score');
        $this->assertSame($sample, $media->asset('real.pdf', 'score'), 'Preparation repairs incomplete derivatives.');
    }

    public function test_preparation_command_uses_the_shared_pools_and_reports_failures_without_sources()
    {
        $media = \Mockery::mock(MatchTourMedia::class);
        $media->shouldReceive('preparationErrors')->once()->andReturn([]);
        $media->shouldReceive('prepare')->with('fixture.mp3', 'audio')->times(16);
        $media->shouldReceive('prepare')->with('fixture.pdf', 'score')->times(4);
        $this->instance(MatchTourMedia::class, $media);
        $this->artisan('api-v2:prepare-match-media')->assertExitCode(0);
        $failed = \Mockery::mock(MatchTourMedia::class);
        $failed->shouldReceive('preparationErrors')->once()->andReturn([]);
        $failed->shouldReceive('prepare')->with('fixture.mp3', 'audio')->times(16)->andThrow(new \RuntimeException('private source diagnostic'));
        $failed->shouldReceive('prepare')->with('fixture.pdf', 'score')->times(4);
        $this->instance(MatchTourMedia::class, $failed);
        $this->artisan('api-v2:prepare-match-media')->expectsOutput('Preparation incomplete; keep native tours disabled.')->assertExitCode(1);
    }

    public function test_preparation_names_missing_tools_and_stops_before_the_catalogue_or_writes()
    {
        foreach (['ffmpeg', 'ffprobe', 'pdftoppm'] as $tool) config(['match-tour.'.$tool => '/missing-match-tools/'.$tool]);
        $this->instance(MatchTour::class, \Mockery::mock(MatchTour::class));
        $before = File::allFiles($this->root);
        $command = $this->artisan('api-v2:prepare-match-media');
        foreach (['ffmpeg', 'ffprobe', 'pdftoppm'] as $tool) {
            $command->expectsOutput($tool.' executable was not found. Install it or set MATCH_TOUR_'.strtoupper($tool).' to its executable path, then rebuild configuration.');
        }
        $command->expectsOutput('Preparation prerequisites failed; keep native tours disabled.')->assertExitCode(1);
        $this->assertSame(count($before), count(File::allFiles($this->root)));
        $this->assertDatabaseCount('api_v2_match_tours', 0);
    }

    public function test_preparation_shows_progress_and_can_retry_one_eligible_audio_without_other_samples()
    {
        $id = $this->pieces->first()->id;
        $media = \Mockery::mock(MatchTourMedia::class);
        $media->shouldReceive('preparationErrors')->once()->andReturn([]);
        $media->shouldReceive('prepare')->once()->with('fixture.mp3', 'audio');
        $this->instance(MatchTourMedia::class, $media);
        $this->artisan('api-v2:prepare-match-media', ['--piece' => (string) $id, '--type' => 'audio'])
            ->expectsOutput('Checking 1 samples. Existing valid samples are reused.')
            ->expectsOutput('[1/1] Preparing audio for piece '.$id.'...')
            ->expectsOutput('Finished: 1 ready, 0 failed.')
            ->expectsOutput('Selected samples prepared. Run the full preparation check before enabling native tours.')
            ->assertExitCode(0);
    }

    public function test_preparation_retries_only_selected_score_and_rejects_non_tour_pieces()
    {
        $id = app(MatchTour::class)->scoreChoices()['middle']['id'];
        $media = \Mockery::mock(MatchTourMedia::class);
        $media->shouldReceive('preparationErrors')->twice()->andReturn([]);
        $media->shouldReceive('prepare')->once()->with('fixture.pdf', 'score');
        $this->instance(MatchTourMedia::class, $media);
        $this->artisan('api-v2:prepare-match-media', ['--piece' => (string) $id, '--type' => 'score'])
            ->expectsOutput('[1/1] Preparing score for piece '.$id.'...')->assertExitCode(0);
        $this->artisan('api-v2:prepare-match-media', ['--piece' => '999999', '--type' => 'audio'])
            ->expectsOutput('No eligible tour media selected. The piece must belong to the shared tour pools.')->assertExitCode(1);
    }

    public function test_preparation_bad_options_do_not_read_catalogue_or_start_encoders()
    {
        $this->instance(MatchTour::class, \Mockery::mock(MatchTour::class));
        $this->instance(MatchTourMedia::class, \Mockery::mock(MatchTourMedia::class));
        $this->artisan('api-v2:prepare-match-media', ['--piece' => '../61'])->assertExitCode(1);
        $this->artisan('api-v2:prepare-match-media', ['--type' => 'unknown'])->assertExitCode(1);
    }

    public function test_preparation_explains_missing_source_without_exposing_a_private_path()
    {
        try {
            app(MatchTourMedia::class)->prepare('missing-tour-file.mp3', 'audio');
            $this->fail('Missing source must fail preparation.');
        } catch (\App\Services\Api\V2\MatchTourPreparationFailure $error) {
            $this->assertStringContainsString('Source file is missing', $error->getMessage());
            $this->assertStringNotContainsString('missing-tour-file', $error->getMessage());
            $this->assertStringNotContainsString(storage_path(), $error->getMessage());
        }
    }

    public function test_legacy_public_prefix_resolves_audio_and_score_without_changing_source_files()
    {
        $media = app(MatchTourMedia::class);
        foreach (['audio' => 'app/audio/historical.mp3', 'score' => 'app/score/historical.pdf'] as $type => $path) {
            Storage::disk('public')->put($path, 'original '.$type);
            $canonical = $media->descriptor($path, $type);
            $legacy = $media->descriptor('public/'.$path, $type);
            $this->assertSame(realpath(Storage::disk('public')->path($path)), $legacy['source']);
            $this->assertSame($canonical['source'], $legacy['source']);
            $this->assertSame($canonical, $media->descriptor($path, $type), 'Existing canonical generation keys stay unchanged.');
            $this->assertSame('original '.$type, Storage::disk('public')->get($path));
            $this->assertFalse(Storage::disk('public')->exists('public/'.$path));
        }
    }

    public function test_legacy_resolution_matches_existing_storage_url_even_if_a_duplicate_nested_file_exists()
    {
        Storage::disk('public')->put('app/audio/historical.mp3', 'correct source');
        Storage::disk('public')->put('public/app/audio/historical.mp3', 'wrong duplicate');
        $media = app(MatchTourMedia::class);
        $this->assertSame(realpath(Storage::disk('public')->path('app/audio/historical.mp3')),
            $media->descriptor('public/app/audio/historical.mp3', 'audio')['source']);
        $this->assertSame('correct source', Storage::disk('public')->get('app/audio/historical.mp3'));
        $this->assertSame('wrong duplicate', Storage::disk('public')->get('public/app/audio/historical.mp3'));
    }

    public function test_legacy_prefix_cannot_escape_public_disk_through_traversal_or_symlinks()
    {
        $root = realpath(Storage::disk('public')->path(''));
        $outside = dirname($root).'/outside-tour-'.Str::uuid().'.mp3';
        $link = $root.'/escaped-tour.mp3';
        file_put_contents($outside, 'private file must not be read');
        symlink($outside, $link);
        try {
            foreach (['public/../'.basename($outside), 'public/escaped-tour.mp3', 'escaped-tour.mp3'] as $path) {
                try {
                    app(MatchTourMedia::class)->descriptor($path, 'audio');
                    $this->fail('The resolved source must stay inside the public disk.');
                } catch (\App\Services\Api\V2\MatchTourFailure $error) {
                    $this->assertSame('media_unavailable', $error->errorCode);
                    $this->assertSame(503, $error->status);
                }
            }
            $this->assertSame('private file must not be read', file_get_contents($outside));
        } finally { unlink($link); unlink($outside); }
    }

    public function test_complete_native_tour_accepts_legacy_paths_and_preserves_database_and_media_bytes()
    {
        $media = app(MatchTourMedia::class);
        foreach (['audio' => 'fixture.mp3', 'score' => 'fixture.pdf'] as $type => $path) {
            $canonical = $media->descriptor($path, $type);
            $legacy = $media->descriptor('public/'.$path, $type);
            File::copyDirectory($this->root.'/'.$canonical['key'], $this->root.'/'.$legacy['key']);
        }
        DB::table('pieces')->update(['audio_path' => 'public/fixture.mp3', 'score_path' => 'public/fixture.pdf']);
        $tour = $this->complete();
        $this->result($tour)->assertOk()->assertJsonPath('data.status', 'matched');
        $this->get($tour['questions']['listening']['choices'][0]['audio_preview']['audio_url'])->assertOk();
        $this->get($tour['questions']['reading-1']['score_sample']['sample_url'])->assertOk();
        $this->assertSame(16, DB::table('pieces')->where('audio_path', 'public/fixture.mp3')->where('score_path', 'public/fixture.pdf')->count());
        $this->assertSame('original audio must not be served', Storage::disk('public')->get('fixture.mp3'));
        $this->assertSame('original score must not be served', Storage::disk('public')->get('fixture.pdf'));
        $this->assertDatabaseCount('recently_viewed_pieces', 0);
    }

    public function test_preparation_reports_corrupt_audio_probe_error_and_redacts_source_location()
    {
        $binary = (new \Symfony\Component\Process\ExecutableFinder)->find('ffprobe');
        if (!$binary) $this->markTestSkipped('ffprobe is required for the real failure diagnostic.');
        config(['match-tour.ffprobe' => $binary]);
        Storage::disk('public')->put('corrupt-tour-audio.mp3', 'not a media file');
        try {
            app(MatchTourMedia::class)->prepare('corrupt-tour-audio.mp3', 'audio');
            $this->fail('Corrupt audio must fail preparation.');
        } catch (\App\Services\Api\V2\MatchTourPreparationFailure $error) {
            $this->assertStringContainsString('Source audio probe failed (exit', $error->getMessage());
            $this->assertStringNotContainsString('corrupt-tour-audio', $error->getMessage());
            $this->assertStringNotContainsString(storage_path(), $error->getMessage());
            $this->assertStringContainsString('[file]', $error->getMessage());
        }
        $this->assertSame('not a media file', Storage::disk('public')->get('corrupt-tour-audio.mp3'));
    }

    public function test_check_only_preparation_accepts_configured_absolute_paths_and_detects_stale_configuration()
    {
        foreach (['ffmpeg', 'ffprobe', 'pdftoppm'] as $tool) config(['match-tour.'.$tool => PHP_BINARY]);
        $this->instance(MatchTour::class, \Mockery::mock(MatchTour::class));
        $this->artisan('api-v2:prepare-match-media --check')->expectsOutput('Preparation prerequisites found. No catalogue or sample files were changed.')->assertExitCode(0);
        config(['match-tour.ffmpeg' => null, 'match-tour.media_root' => null]);
        $this->artisan('api-v2:prepare-match-media --check')
            ->expectsOutput('Missing match-tour.ffmpeg configuration; rebuild Laravel configuration with php artisan config:cache.')
            ->expectsOutput('Missing match-tour.media_root configuration; rebuild Laravel configuration with php artisan config:cache.')
            ->assertExitCode(1);
    }

    public function test_unavailable_media_catalogue_no_match_gate_rate_limit_and_server_errors_are_json()
    {
        config(['match-tour.enabled' => false]);
        $this->start()->assertStatus(503)->assertJsonPath('error.code', 'tour_unavailable');
        config(['match-tour.enabled' => true]);
        $tour = $this->complete();
        Tutorial::query()->delete();
        $this->result($tour)->assertStatus(503)->assertJsonPath('error.code', 'no_match');
        $this->assertNull($this->load($tour)->assertOk()->json('data.result'));
        File::deleteDirectory($this->root);
        $before = DB::table('api_v2_match_tours')->count();
        $this->start()->assertStatus(503)->assertJsonPath('error.code', 'media_unavailable');
        $this->assertSame($before, DB::table('api_v2_match_tours')->count(), 'Failed start rolls back all tour state.');
        $this->instance(MatchTour::class, \Mockery::mock(MatchTour::class)->shouldReceive('data')->andThrow(new \RuntimeException('private diagnostic'))->getMock());
        $this->start()->assertStatus(500)->assertJsonPath('error.code', 'server_error')->assertDontSee('private diagnostic');
        for ($i = 0; $i < 7; $i++) $last = $this->start();
        $last->assertStatus(429)->assertHeader('Retry-After')->assertJsonPath('error.code', 'rate_limited');
    }
}
