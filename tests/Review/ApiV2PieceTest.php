<?php

namespace Tests\Review;

use App\{Composer, FavoriteFolder, Piece, Tag, Tutorial, User, VideoMoment};
use App\Billing\Membership;
use App\Billing\Sources\{Apple, Stripe};
use App\Services\Api\V2\Tokens;
use App\Services\Pieces\PageContent;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\{DB, Http, Mail, Redis};

class ApiV2PieceTest extends ReviewTestCase
{
    private $piece, $video, $user, $token;

    public function setUp(): void
    {
        parent::setUp();
        $this->withExceptionHandling();
        $this->travelTo(carbon('2026-11-04 12:00:00'));
        Redis::shouldReceive('get')->andReturn(null);
        $this->withoutMiddleware([\App\Http\Middleware\Logs\RecordWebAppLog::class, \App\Http\Middleware\UpdateLocation::class]);
        Model::withoutEvents(function () {
            $composer = create(Composer::class, ['name' => 'Clara Schumann', 'biography' => 'Shared composer biography.',
                'cover_path' => 'fixtures/composer.jpg', 'date_of_birth' => '1819-09-13', 'date_of_death' => '1896-05-20']);
            $this->piece = create(Piece::class, ['name' => 'Native detail fixture', 'nickname' => null, 'composer_id' => $composer->id,
                'description' => 'A shared piece description.', 'cover_path' => 'fixtures/piece.jpg',
                'score_path' => 'fixtures/score.pdf', 'score_url' => null,
                'audio_path' => 'fixtures/full.mp3', 'audio_path_rh' => 'fixtures/right.mp3', 'audio_path_lh' => 'fixtures/left.mp3',
                'composed_in' => 1841, 'published_in' => 1842]);
            foreach (['level' => 'intermediate', 'period' => 'romantic', 'length' => 'short', 'mood' => 'dreamy'] as $type => $name) {
                $this->piece->tags()->attach(create(Tag::class, compact('type', 'name')));
            }
            $this->video = create(Tutorial::class, ['piece_id' => $this->piece->id, 'type' => 'Performance', 'category' => 'performance', 'video_url' => 'https://media.example.test/performance.mp4']);
            create(Tutorial::class, ['piece_id' => $this->piece->id, 'type' => 'Synthesia', 'category' => 'synthesia', 'video_url' => 'https://media.example.test/synthesia.mp4']);
            create(Tutorial::class, ['piece_id' => $this->piece->id, 'type' => 'Harmonic analysis', 'category' => 'lesson', 'video_url' => 'https://media.example.test/harmony.mp4']);
            $this->user = create(User::class, ['super_user' => false]);
        });
        $this->video->moments()->create(['start_time' => 12.25, 'end_time' => 19.5, 'title' => 'Opening theme', 'comment' => 'Listen to the shared melody.', 'sort_order' => 0]);
        $this->token = app(Tokens::class)->issue($this->user)['access_token'];
    }

    private function detail(array $query = [], $piece = null)
    {
        return $this->getJson('/api/v2/pieces/'.($piece ?? $this->piece->id).'?'.http_build_query($query), ['Authorization' => 'Bearer '.$this->token]);
    }

    public function test_detail_requires_a_v2_credential_and_has_no_cookie_or_history_side_effect()
    {
        $this->actingAs($this->user, 'web');
        $this->getJson('/api/v2/pieces/'.$this->piece->id.'?user_id='.$this->user->id)->assertUnauthorized()->assertJsonPath('error.code', 'unauthenticated');
        $this->detail()->assertOk()->assertHeader('Cache-Control', 'no-store, private')->assertHeaderMissing('Set-Cookie');
        $this->assertDatabaseCount('recently_viewed_pieces', 0);
        $this->assertDatabaseCount('piece_views', 0);
        Http::assertNothingSent();
        Mail::assertNothingSent();
    }

    public function test_nonmember_gets_page_content_with_locked_full_media_and_no_source_urls()
    {
        $response = $this->detail()->assertOk()->assertJsonPath('data.piece.id', (string) $this->piece->id)
            ->assertJsonPath('data.piece.title', $this->piece->medium_name)
            ->assertJsonPath('data.about.description', $this->piece->description)
            ->assertJsonPath('data.about.for_whom', $this->piece->for_who)
            ->assertJsonPath('data.access.basis', 'membership_required')
            ->assertJsonPath('data.access.full_media', false)
            ->assertJsonPath('data.media.performance.mode', 'blocked')
            ->assertJsonPath('data.media.performance.url', null)
            ->assertJsonPath('data.media.score.pdf_url', null)
            ->assertJsonPath('data.media.score.mode', 'blocked')
            ->assertJsonPath('data.media.audio.tracks.0.url', null);
        $this->assertSame(12.25, $response->json('data.media.performance.moments.0.start_seconds'));
        $this->assertSame('Listen to the shared melody.', $response->json('data.media.performance.moments.0.comment'));
        $this->assertNull($response->json('data.media.audio.moments.0.comment'));
        foreach (['performance.mp4', 'synthesia.mp4', 'harmony.mp4', 'score.pdf', 'full.mp3', 'right.mp3', 'left.mp3'] as $source) $response->assertDontSee($source);
        $this->assertCount(3, $response->json('data.media.audio.tracks'));
        $this->assertNotEmpty($response->json('data.timeline.events'));
        if ($fixture = getenv('API_V2_PIECE_BLOCKED_FIXTURE')) file_put_contents($fixture, json_encode($response->json(), JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)."\n");
    }

    public function test_super_user_gets_original_media_and_shared_metadata_without_provider_calls()
    {
        $this->user->updateQuietly(['super_user' => true]);
        $response = $this->detail()->assertOk()->assertJsonPath('data.access.basis', 'super_user')
            ->assertJsonPath('data.media.performance.url', $this->video->video_url)
            ->assertJsonPath('data.media.score.mode', 'full')
            ->assertJsonPath('data.media.audio.mode', 'full')
            ->assertJsonPath('data.piece.composer.biography', 'Shared composer biography.');
        $this->assertStringEndsWith('/storage/fixtures/score.pdf', $response->json('data.media.score.pdf_url'));
        $this->assertStringEndsWith('/storage/fixtures/right.mp3', $response->json('data.media.audio.tracks.1.url'));
        $this->assertSame($this->piece->rankings, $response->json('data.about.rankings'));
        if ($fixture = getenv('API_V2_PIECE_FIXTURE')) file_put_contents($fixture, json_encode($response->json(), JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)."\n");
        Http::assertNothingSent();
        Mail::assertNothingSent();
    }

    public function test_submitted_identity_cannot_supply_favorites_or_membership_from_another_account()
    {
        $other = Model::withoutEvents(function () { return create(User::class, ['super_user' => true]); });
        $folder = create(FavoriteFolder::class, ['user_id' => $other->id]);
        $other->favorites()->attach($this->piece, ['favorite_folder_id' => $folder->id]);
        $this->detail(['user_id' => $other->id])->assertOk()->assertJsonPath('data.account.is_favorited', false)->assertJsonPath('data.access.full_media', false);
        $own = create(FavoriteFolder::class, ['user_id' => $this->user->id]);
        $this->user->favorites()->attach($this->piece, ['favorite_folder_id' => $own->id]);
        $this->detail(['user_id' => $other->id])->assertOk()->assertJsonPath('data.account.is_favorited', true)->assertJsonPath('data.access.full_media', false);
        $this->assertDatabaseCount('recently_viewed_pieces', 0);
    }

    public function test_current_free_pick_grants_native_media_then_rotation_removes_that_grant()
    {
        $schedule = app(\App\Services\FreePickSchedule::class);
        $schedule->schedule($this->piece);
        $this->detail()->assertOk()->assertJsonPath('data.access.basis', 'free_pick')->assertJsonPath('data.media.score.mode', 'full');
        $next = Model::withoutEvents(function () { return create(Piece::class, ['cover_path' => 'next.jpg']); });
        $schedule->schedule($next, now()->addDay());
        $this->travel(1)->days();
        $this->detail()->assertOk()->assertJsonPath('data.piece.is_free_pick', false)->assertJsonPath('data.access.basis', 'membership_required')->assertJsonPath('data.media.performance.url', null);
    }

    public function memberships()
    {
        return [
            'active Stripe' => [Stripe::class, ['status' => 'active'], true],
            'trial Stripe' => [Stripe::class, ['status' => 'trialing'], true],
            'Stripe grace' => [Stripe::class, ['status' => 'canceled', 'membership_ends_at' => '2026-11-06 12:00:00'], true],
            'ended Stripe' => [Stripe::class, ['status' => 'canceled', 'membership_ends_at' => '2026-11-03 12:00:00'], false],
            'active Apple' => [Apple::class, [], true],
            'expired Apple' => [Apple::class, ['renews_at' => '2026-11-03 12:00:00'], false],
        ];
    }

    /** @dataProvider memberships */
    public function test_native_membership_is_resolved_from_local_state_only($type, $attributes, $expected)
    {
        Model::withoutEvents(function () use ($type, $attributes) {
            $source = create($type, array_merge(['renews_at' => now()->addMonth()], $attributes));
            Membership::create(['user_id' => $this->user->id, 'source_id' => $source->id, 'source_type' => $type]);
        });
        $this->detail()->assertOk()->assertJsonPath('data.access.full_media', $expected);
        Http::assertNothingSent();
    }

    public function test_missing_metadata_and_copyrighted_or_missing_media_are_explicit()
    {
        $this->piece->updateQuietly(['description' => null, 'score_url' => 'https://publisher.example.test/buy', 'score_path' => 'do-not-leak.pdf']);
        $this->piece->tags()->detach();
        $this->user->updateQuietly(['super_user' => true]);
        $this->detail()->assertOk()->assertJsonPath('data.about.description', null)->assertJsonPath('data.about.description_fallback', 'composer_biography')
            ->assertJsonPath('data.about.for_whom', null)->assertJsonPath('data.media.score.mode', 'external')
            ->assertJsonPath('data.media.score.purchase_url', 'https://publisher.example.test/buy')->assertJsonPath('data.media.score.pdf_url', null)->assertDontSee('do-not-leak.pdf');
        $this->piece->updateQuietly(['score_url' => null, 'score_path' => null, 'audio_path' => null, 'audio_path_rh' => null, 'audio_path_lh' => null]);
        DB::table('tutorials')->where('piece_id', $this->piece->id)->delete();
        $this->detail()->assertOk()->assertJsonPath('data.media.performance', null)->assertJsonPath('data.media.synthesia', null)
            ->assertJsonPath('data.media.audio.mode', 'unavailable')->assertJsonPath('data.media.audio.tracks', [])->assertJsonPath('data.media.score.mode', 'unavailable');
    }

    public function test_web_and_v2_use_the_same_page_content_and_do_not_change_legacy_serialization()
    {
        $this->withoutMiddleware(\App\Http\Middleware\Logs\RecordAppLog::class);
        $legacyUrl = 'http://localhost/api/pieces/find?search='.$this->piece->id;
        $before = $this->getJson($legacyUrl)->assertOk()->json();
        $content = app(PageContent::class)->data($this->piece->fresh());
        $shared = \Mockery::mock(PageContent::class)->makePartial();
        $shared->shouldReceive('data')->twice()->andReturn($content);
        $this->app->instance(PageContent::class, $shared);
        $response = $this->detail()->assertOk();
        $web = $this->get(route('webapp.pieces.show', $this->piece))->assertOk();
        $this->assertSame($content['recommendationRows'], $web->viewData('recommendationRows'));
        $this->assertSame($content['synthesiaReleases'], $web->viewData('synthesiaReleases'));
        $this->assertSame($content['timeline']->pluck('title')->all(), array_column($response->json('data.timeline.events'), 'title'));
        $this->assertSame($before, $this->getJson($legacyUrl)->assertOk()->json());
        $this->assertArrayNotHasKey('password', $response->json('data'));
        $this->assertArrayNotHasKey('favorites', $response->json('data.piece'));
    }

    public function test_invalid_and_missing_piece_ids_return_v2_json_errors()
    {
        foreach (['0', '-1', 'find', '1.5', '99999999'] as $id) {
            $this->detail([], $id)->assertNotFound()->assertJsonStructure(['error' => ['code', 'message', 'fields'], 'meta' => ['request_id']]);
        }
    }
}
