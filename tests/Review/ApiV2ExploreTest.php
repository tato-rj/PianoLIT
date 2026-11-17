<?php

namespace Tests\Review;

use App\Blog\Post;
use App\{Composer, Piece, Tag, Tutorial, User};
use App\Services\Api\V2\Tokens;
use App\Services\FreePickSchedule;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\{Cache, Http, Redis};

class ApiV2ExploreTest extends ReviewTestCase
{
    private const PATH = '/api/v2/explore';
    private User $user;
    private array $headers;

    public function setUp(): void
    {
        parent::setUp();
        $this->withExceptionHandling();
        $this->travelTo(now()->startOfSecond());
        Redis::shouldReceive('get')->andReturn(null);
        $this->user = Model::withoutEvents(fn () => create(User::class, ['super_user' => false]));
        $this->headers = ['Authorization' => 'Bearer '.app(Tokens::class)->issue($this->user)['access_token']];
    }

    public function test_empty_feed_and_results_require_bearer_identity()
    {
        $this->actingAs($this->user, 'web');
        foreach ([self::PATH, self::PATH.'/pieces?scope=highlights'] as $path) {
            $this->getJson($path)->assertUnauthorized()->assertJsonPath('error.code', 'unauthenticated');
        }
        $feed = $this->getJson(self::PATH, $this->headers)->assertOk()
            ->assertJsonPath('data.sections', [])->assertJsonStructure(['meta' => ['request_id']])
            ->assertHeader('Cache-Control', 'no-store, private')->assertHeaderMissing('Set-Cookie');
        $this->fixture('empty', $feed->json());
        $this->getJson(self::PATH.'/pieces?scope=highlights', $this->headers)->assertOk()
            ->assertJsonPath('data', ['pieces' => [], 'page' => 1, 'next_page' => null]);
    }

    public function test_explore_all_sections_use_explicit_targets_and_existing_media_rules()
    {
        [$past, $current, $video, $original, $tags] = Model::withoutEvents(function () {
            $composer = create(Composer::class, [
                'name' => 'Example Composer', 'cover_path' => 'fixtures/composer.jpg', 'biography' => 'A composer biography.',
                'curiosity' => 'A short profile note.', 'date_of_birth' => '1819-09-13', 'date_of_death' => '1896-05-20',
            ]);
            $tags = collect([
                'technique' => 'arpeggios', 'mood' => 'calm', 'genre' => 'folk', 'period' => 'romantic', 'level' => 'elementary',
            ])->map(fn ($name, $type) => create(Tag::class, compact('name', 'type')));
            $past = $this->piece($composer, $tags->pluck('id')->all(), [
                'name' => 'Morning study', 'highlighted_at' => now()->subWeek(),
            ]);
            $current = $this->piece($composer, $tags->pluck('id')->all(), [
                'name' => 'Evening study', 'highlighted_at' => now(),
            ]);
            $this->piece($composer, $tags->pluck('id')->all(), ['highlighted_at' => now()->addWeek()]);
            $video = create(Tutorial::class, ['piece_id' => $past->id, 'type' => 'Harmonic analysis',
                'category' => 'lesson', 'description' => '<b>Explore the opening harmony.</b>', 'video_url' => 'https://media.example.test/analysis.mp4']);
            $original = create(Post::class, ['slug' => 'a-piano-study', 'title' => 'A piano study', 'description' => 'A practice guide.',
                'cover_path' => 'fixtures/article.jpg', 'published_at' => now()->subDay()]);
            create(Post::class, ['title' => 'Unpublished draft']);
            create(Post::class, ['title' => 'Future article', 'published_at' => now()->addDay()]);
            return [$past, $current, $video, $original, $tags];
        });
        app(FreePickSchedule::class)->schedule($current);
        $member = Model::withoutEvents(fn () => create(User::class, ['super_user' => true]));

        $response = $this->getJson(self::PATH.'?user_id='.$member->id, $this->headers)->assertOk();
        $this->fixture('full', $response->json());
        $sections = collect($response->json('data.sections'))->keyBy('id');
        $this->assertSame(['categories', 'highlights', 'originals', 'periods', 'levels', 'featured_composer', 'harmony'], $sections->keys()->all());
        $this->assertSame((string) $tags['technique']->id, $sections['categories']['items'][0]['tags'][0]['target']['tag_ids'][0]);
        $this->assertSame((string) $tags['period']->id, $sections['periods']['items'][0]['id']);
        $this->assertNotEmpty($sections['periods']['items'][0]['artwork_url']);
        $this->assertSame(3, $sections['levels']['items'][0]['piece_count']);
        $this->assertSame([(string) $past->id], array_column($sections['highlights']['items'], 'id'));
        $this->assertSame('highlights', $sections['highlights']['action']['scope']);
        $this->assertSame($original->app_url, $sections['originals']['items'][0]['target']['web_url']);
        $this->assertSame('A short profile note.', $sections['featured_composer']['items'][0]['curiosity']);
        $this->assertSame(route('webapp.composers.show', $current->composer), $sections['featured_composer']['items'][0]['target']['web_url']);
        $lesson = $sections['harmony']['items'][0];
        $this->assertSame(['kind' => 'piece_lesson', 'piece_id' => (string) $past->id, 'video_id' => (string) $video->id], $lesson['target']);
        $this->assertSame('Explore the opening harmony.', $lesson['description']);
        $this->assertSame('membership_required', $lesson['access']['basis']);
        $response->assertDontSee('analysis.mp4');
        $results = $this->getJson(self::PATH.'/pieces?scope=highlights', $this->headers)->assertOk()
            ->assertJsonPath('data.pieces.0.id', (string) $past->id)
            ->assertJsonPath('data.next_page', null);
        $this->fixture('pieces', $results->json());
        $memberHeaders = ['Authorization' => 'Bearer '.app(Tokens::class)->issue($member)['access_token']];
        $this->getJson(self::PATH, $memberHeaders)->assertOk()->assertJsonPath('data.sections.6.items.0.access.full_media', true);
        $this->getJson('/api/v2/pieces/'.$past->id, $this->headers)->assertOk()
            ->assertJsonPath('data.media.lessons.0.videos.0.id', (string) $video->id)
            ->assertJsonPath('data.media.lessons.0.videos.0.mode', 'blocked')
            ->assertJsonPath('data.media.lessons.0.videos.0.url', null);
        $this->assertDatabaseCount('recently_viewed_pieces', 0);
        $this->assertDatabaseCount('activity_events', 0);
        Http::assertNothingSent();
    }

    public function test_results_combine_tag_groups_and_page_with_a_deterministic_order()
    {
        [$ids, $matching] = Model::withoutEvents(function () {
            $composer = create(Composer::class);
            $calm = create(Tag::class, ['type' => 'mood', 'name' => 'calm']);
            $bold = create(Tag::class, ['type' => 'mood', 'name' => 'bold']);
            $baroque = create(Tag::class, ['type' => 'period', 'name' => 'baroque']);
            $this->piece($composer, [$calm->id]);
            $this->piece($composer, [$baroque->id]);
            $matching = [];
            for ($index = 0; $index < 25; $index++) {
                $matching[] = (string) $this->piece($composer, [$index % 2 ? $calm->id : $bold->id, $baroque->id])->id;
            }
            return [[$calm->id, $bold->id, $baroque->id], array_reverse($matching)];
        });
        $url = self::PATH.'/pieces?'.http_build_query(['tag_ids' => $ids]);
        $page = $this->getJson($url, $this->headers)->assertOk()->assertJsonPath('data.next_page', 2);
        $this->assertSame(array_slice($matching, 0, 24), array_column($page->json('data.pieces'), 'id'));
        $second = $this->getJson($url.'&page=2', $this->headers)->assertOk()->assertJsonPath('data.next_page', null);
        $this->assertSame(array_slice($matching, 24), array_column($second->json('data.pieces'), 'id'));
        foreach (['', '?tag_ids[]=bogus', '?tag_ids[]=99999999', '?tag_ids[]=1&tag_ids[]=1', '?scope=other'] as $query) {
            $this->getJson(self::PATH.'/pieces'.$query, $this->headers)->assertUnprocessable()->assertJsonPath('error.code', 'invalid_request');
        }
    }

    public function test_partial_v2_feed_omits_missing_sections_without_replacing_legacy_rows()
    {
        Cache::put('app.explore', collect([
            ['label' => 'Originals', 'collection' => null, 'celltype' => 'original'],
        ]), now()->addDay());
        Model::withoutEvents(fn () => create(Post::class, ['title' => 'Published article', 'published_at' => now()]));
        $response = $this->getJson(self::PATH, $this->headers)->assertOk();
        $this->assertSame(['originals'], array_column($response->json('data.sections'), 'id'));
        $this->getJson('/api/explore')->assertOk()->assertExactJson([
            ['label' => 'Originals', 'collection' => null, 'celltype' => 'original'],
        ]);
    }

    private function piece(Composer $composer, array $tags, array $attributes = []): Piece
    {
        $piece = create(Piece::class, $attributes + [
            'composer_id' => $composer->id, 'cover_path' => 'fixtures/study.jpg', 'audio_path' => null,
            'catalogue_name' => 'Op.', 'catalogue_number' => 1, 'collection_name' => null, 'collection_number' => null,
        ]);
        $piece->tags()->attach($tags);
        return $piece;
    }

    private function fixture(string $kind, array $body): void
    {
        $directory = getenv('PIANOLIT_EXPLORE_FIXTURES');
        if ($directory) {
            $number = ['full' => '1', 'empty' => '2', 'pieces' => '3'][$kind];
            $body['meta']['request_id'] = '00000000-0000-4000-8000-00000000400'.$number;
            file_put_contents($directory.'/explore-'.$kind.'.json', json_encode($body, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)."\n");
        }
    }
}
