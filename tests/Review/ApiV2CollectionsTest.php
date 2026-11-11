<?php

namespace Tests\Review;

use App\{Composer, Piece, Playlist, Tag, Tutorial, User};
use App\Billing\Membership;
use App\Billing\Sources\Stripe;
use App\Services\Api\V2\Tokens;
use App\Services\WebApp\Collections;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\{Cache, Http, Redis};

class ApiV2CollectionsTest extends ReviewTestCase
{
    private const PATH = '/api/v2/collections';
    private const SELECTION = 'webapp.collections.weekly-selection';

    public function setUp(): void
    {
        parent::setUp();
        $this->withExceptionHandling();
        Redis::shouldReceive('get')->andReturn(null);
        $this->withoutMiddleware(\App\Http\Middleware\Logs\RecordAppLog::class);
    }

    private function catalog(): array
    {
        return Model::withoutEvents(function () {
            $composer = create(Composer::class, ['name' => 'Clara Schumann', 'cover_path' => 'fixtures/composer.jpg']);
            $featured = create(Playlist::class, [
                'name' => 'Evening pieces', 'subtitle' => 'Short works for piano',
                'description' => 'Six pieces for a quiet program.', 'published_at' => now(),
                'cover_path' => 'fixtures/evening.jpg', 'featured' => '  Theme  ', 'order' => 10,
            ]);
            $regular = create(Playlist::class, [
                'name' => 'Piano studies', 'subtitle' => ' ', 'description' => 'Studies for developing technique.',
                'published_at' => now(), 'featured' => 'Technique', 'order' => 5,
            ]);
            $level = create(Tag::class, ['type' => 'level', 'name' => 'intermediate']);
            $period = create(Tag::class, ['type' => 'period', 'name' => 'romantic']);
            $length = create(Tag::class, ['type' => 'length', 'name' => 'short']);
            $pieces = collect(range(1, 7))->map(function ($number) use ($composer, $level, $period, $length, $featured, $regular) {
                $piece = create(Piece::class, [
                    'composer_id' => $composer->id, 'name' => 'Study '.$number, 'nickname' => null,
                    'catalogue_name' => 'Op.', 'catalogue_number' => '12', 'collection_name' => null,
                    'collection_number' => null, 'cover_path' => 'fixtures/study.jpg',
                    'score_path' => $number === 1 ? 'fixtures/score.pdf' : null,
                    'audio_path' => $number === 1 ? 'fixtures/full.mp3' : null,
                ]);
                if ($number <= 6) {
                    create(Tutorial::class, ['piece_id' => $piece->id,
                        'type' => $number === 1 ? 'Performance' : 'Synthesia',
                        'category' => $number === 1 ? 'performance' : 'synthesia',
                        'video_url' => 'https://media.example.test/study-'.$number.'.mp4']);
                }
                $piece->tags()->attach([$period->id, $length->id]);
                if ($number === 1) $piece->tags()->attach($level);
                $featured->pieces()->attach($piece, ['order' => $number > 2 ? 8 - $number : 1]);
                $regular->pieces()->attach($piece, ['order' => $number]);
                return $piece;
            });
            Cache::put(self::SELECTION, ['featured' => $featured->id, 'inspiration' => [$regular->id, $featured->id]], now()->addWeek());
            return [$featured, $regular, $pieces];
        });
    }

    private function user(array $attributes = []): User
    {
        return Model::withoutEvents(fn () => create(User::class, $attributes));
    }

    private function credential(User $user): array
    {
        return ['Authorization' => 'Bearer '.app(Tokens::class)->issue($user)['access_token']];
    }

    public function test_bearer_is_required_and_empty_catalog_has_an_explicit_null_featured_id()
    {
        $user = $this->user();
        $this->actingAs($user, 'web');
        $this->getJson(self::PATH.'?user_id='.$user->id)->assertUnauthorized()->assertJsonPath('error.code', 'unauthenticated');
        $this->getJson(self::PATH.'/1')->assertUnauthorized()->assertJsonPath('error.code', 'unauthenticated');
        $this->getJson(self::PATH, $this->credential($user))->assertOk()
            ->assertJsonPath('data.featured_collection_id', null)
            ->assertJsonPath('data.collections', [])
            ->assertJsonStructure(['meta' => ['request_id']])
            ->assertHeader('Cache-Control', 'no-store, private')
            ->assertHeaderMissing('Set-Cookie');
    }

    public function test_catalog_matches_the_web_content_and_orders_by_saved_order_then_id()
    {
        [$featured, $regular] = $this->catalog();
        $tied = Model::withoutEvents(function () use ($regular) {
            $playlist = $regular->replicate();
            $playlist->save();
            $playlist->pieces()->attach($regular->pieces()->pluck('pieces.id')->all());
            return $playlist;
        });
        Cache::put(self::SELECTION, [
            'featured' => $featured->id, 'inspiration' => [$regular->id, $featured->id, $tied->id],
        ], now()->addWeek());
        $headers = $this->credential($this->user());
        $web = app(Collections::class)->data();
        $catalog = $this->getJson(self::PATH.'?page=2', $headers)->assertOk()
            ->assertJsonPath('data.featured_collection_id', (string) $web['featured']['playlist']->id)
            ->assertJsonPath('data.collections.0.access', 'membership_required')
            ->assertJsonPath('data.collections.0.subtitle', null)
            ->assertJsonPath('data.collections.0.featured_badge', null)
            ->assertJsonPath('data.collections.2.artwork_url', $web['featured']['image'])
            ->assertJsonPath('data.collections.2.featured_badge', 'Theme')
            ->assertJsonPath('data.collections.2.piece_count', 6)
            ->assertJsonPath('data.collections.2.access', 'available')->json('data.collections');
        $this->assertSame([(string) $regular->id, (string) $tied->id, (string) $featured->id], array_column($catalog, 'id'));
        $this->assertSame($web['playlists']->pluck('playlist.id')->map(fn ($id) => (string) $id)->sort()->values()->all(),
            collect($catalog)->pluck('id')->sort()->values()->all());
        $featured->updateQuietly(['featured' => null]);
        $this->getJson(self::PATH, $headers)->assertOk()->assertJsonPath('data.collections.2.featured_badge', null);
    }

    public function test_nonmember_gets_featured_rows_and_locked_regular_metadata_without_side_effects()
    {
        [$featured, $regular, $pieces] = $this->catalog();
        $member = $this->user(['super_user' => true]);
        $this->actingAs($member, 'web');
        $headers = $this->credential($this->user(['super_user' => false]));
        $detail = $this->getJson(self::PATH.'/'.$featured->id, $headers)->assertOk()
            ->assertJsonPath('data.collection.description', 'Six pieces for a quiet program.')
            ->assertJsonPath('data.pieces.0.kind', 'piece')
            ->assertJsonPath('data.pieces.0.composer.name', 'Clara Schumann')
            ->assertJsonPath('data.pieces.0.difficulty', 'intermediate')
            ->assertJsonPath('data.pieces.0.media.score', true)
            ->assertJsonPath('data.pieces.0.media.audio', true)
            ->assertJsonPath('data.pieces.0.media.performance', true)
            ->assertJsonPath('data.pieces.1.media.performance', false)
            ->assertJsonPath('data.pieces.1.difficulty', null)
            ->assertDontSee('study-1.mp4')->assertDontSee('score.pdf')->assertDontSee('full.mp3');
        $this->assertSame(collect([0, 1, 5, 4, 3, 2])->map(fn ($index) => (string) $pieces[$index]->id)->all(),
            array_column($detail->json('data.pieces'), 'id'));
        $this->getJson(self::PATH.'/'.$regular->id.'?user_id='.$member->id, $headers)->assertOk()
            ->assertJsonPath('data.collection.access', 'membership_required')
            ->assertJsonPath('data.collection.description', 'Studies for developing technique.')
            ->assertJsonPath('data.collection.piece_count', 6)->assertJsonPath('data.pieces', []);
        $this->getJson('/api/v2/pieces/'.$pieces[0]->id, $headers)->assertOk()
            ->assertJsonPath('data.media.performance.mode', 'blocked')
            ->assertJsonPath('data.piece.media.performance', false);
        $this->assertDatabaseCount('recently_viewed_pieces', 0);
        $this->assertDatabaseCount('activity_events', 0);
        $this->assertDatabaseCount('piece_views', 0);
        Http::assertNothingSent();
    }

    public function test_regular_detail_rechecks_local_membership_and_weekly_featured_id()
    {
        [$featured, $regular] = $this->catalog();
        $member = $this->user();
        $source = Model::withoutEvents(fn () => create(Stripe::class, [
            'status' => 'canceled', 'renews_at' => now()->addMonth(), 'membership_ends_at' => now()->addDay(),
        ]));
        Membership::create(['user_id' => $member->id, 'source_id' => $source->id, 'source_type' => Stripe::class]);
        $headers = $this->credential($member);
        $this->getJson(self::PATH.'/'.$regular->id, $headers)->assertOk()
            ->assertJsonPath('data.collection.access', 'available')->assertJsonCount(6, 'data.pieces');

        $this->travel(2)->days();
        $this->getJson(self::PATH.'/'.$regular->id, $headers)->assertOk()->assertJsonPath('data.pieces', []);
        $this->getJson(self::PATH.'/'.$regular->id, $this->credential($this->user(['super_user' => true])))
            ->assertOk()->assertJsonCount(6, 'data.pieces');

        $featured->updateQuietly(['published_at' => null]);
        $this->getJson(self::PATH.'/'.$regular->id, $headers)->assertOk()->assertJsonCount(6, 'data.pieces');
        $this->getJson(self::PATH, $headers)->assertOk()->assertJsonPath('data.featured_collection_id', (string) $regular->id);
    }

    public function test_non_catalog_entries_are_unavailable_without_changing_legacy_endpoints()
    {
        [$featured, $regular, $pieces] = $this->catalog();
        $journey = Model::withoutEvents(function () use ($pieces) {
            $playlist = create(Playlist::class, ['group' => 'journey', 'published_at' => now()]);
            $playlist->pieces()->attach($pieces->pluck('id')->all());
            return $playlist;
        });
        $regular->updateQuietly(['published_at' => now()->addDay()]);
        $featured->pieces()->detach($pieces->take(3)->pluck('id')->all());
        $headers = $this->credential($this->user());
        foreach ([$featured->id, $regular->id, $journey->id, '99999'] as $id) {
            $this->getJson(self::PATH.'/'.$id, $headers)->assertNotFound()->assertJsonPath('error.code', 'not_found');
        }
        $this->getJson(self::PATH.'/journey', $headers)->assertNotFound()->assertJsonPath('error.code', 'request_failed');
        $this->getJson(self::PATH, $headers)->assertOk()->assertJsonPath('data.collections', []);

        $level = Tag::where('type', 'level')->where('name', 'intermediate')->firstOrFail();
        foreach ($pieces->slice(1) as $piece) $piece->tags()->attach($level);
        Cache::put('app.playlists.order', [$featured->id, $regular->id], now()->addDay());
        $this->getJson('/api/playlists')->assertOk()->assertJsonPath('0.id', $regular->id);
        $this->getJson('/api/playlists/journey')->assertOk()->assertJsonPath('0.id', $journey->id);
        $this->getJson('/api/playlists/'.$regular->id.'/pieces')->assertOk()->assertJsonCount(6);
    }
}
