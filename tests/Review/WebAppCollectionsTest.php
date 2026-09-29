<?php

namespace Tests\Review;

use App\{Piece, Playlist, Tag, Tutorial};
use App\Services\WebApp\Collections;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\{Cache, DB, Redis};

class WebAppCollectionsTest extends ReviewTestCase
{
    public function setUp(): void
    {
        parent::setUp();
        Redis::shouldReceive('get')->andReturn(null);
        $this->withoutMiddleware([
            \App\Http\Middleware\Logs\RecordAppLog::class,
            \App\Http\Middleware\Logs\RecordWebAppLog::class,
            \App\Http\Middleware\UpdateLocation::class,
        ]);
    }

    private function playlist($name, $count = 6, $withTutorials = 6, $group = null)
    {
        return Model::withoutEvents(function () use ($name, $count, $withTutorials, $group) {
            $playlist = create(Playlist::class, ['name' => $name, 'group' => $group, 'order' => 7]);
            for ($i = 0; $i < $count; $i++) {
                $piece = create(Piece::class);
                foreach (['level' => 'beginner', 'period' => 'romantic', 'length' => 'short'] as $type => $tagName) {
                    $piece->tags()->attach(Tag::firstOrCreate(['type' => $type, 'name' => $tagName]));
                }
                if ($i < $withTutorials) create(Tutorial::class, ['piece_id' => $piece->id]);
                $playlist->pieces()->attach($piece->id);
            }
            return $playlist;
        });
    }

    public function test_guest_page_has_real_counts_links_and_unavailable_books_without_personal_progress()
    {
        $featured = $this->playlist('Lullabies', 7, 6);
        $this->playlist('Great for Beginners');
        $this->playlist('Hidden gems');
        $this->playlist("Burgmüller's studies");
        $response = $this->get(route('webapp.playlists', ['user_id' => 123]));
        $response->assertOk()->assertSee('Collections')->assertSee('The PianoLit path')
            ->assertSee('What would you like to play next?')->assertSee('Coming soon')
            ->assertSee('6 pieces')->assertDontSee('7 pieces')->assertDontSee('YOUR PATH')
            ->assertDontSee('Continue')->assertDontSee('THIS WEEK')
            ->assertSee(route('webapp.playlists.show', $featured), false);
        $this->assertGuest('web');
        $this->assertSame(8, substr_count($response->getContent(), 'collections-coming-soon'));
        $response->assertSeeInOrder(array_map(function ($number) {
            return 'Piano Solos · Book '.$number;
        }, range(1, 8)))->assertSee('Late intermediate to early advanced')
            ->assertSee('Early advanced')->assertSee('Previous books')->assertSee('Next books');
        $this->assertLessThan(strpos($response->getContent(), 'inspiration-heading'), strpos($response->getContent(), 'id="pianolit-path"'));

        // Optional browser fixture from the isolated SQLite suite, never the local catalog.
        if ($destination = getenv('COLLECTIONS_PREVIEW_PATH')) {
            Model::withoutEvents(function () {
                foreach ([
                    'Lullabies' => 'Gentle piano pieces for quiet moments.',
                    'Great for Beginners' => 'Beautiful pieces for your first steps at the piano.',
                    'Hidden gems' => 'Discover a new favorite beyond the familiar.',
                    "Burgmüller's studies" => 'Build your technique through expressive little studies.',
                ] as $name => $subtitle) {
                    Playlist::where('name', $name)->update(['subtitle' => $subtitle]);
                }
            });
            foreach (['Sunday morning', 'At night', 'Wild flowers', 'True romance'] as $name) $this->playlist($name);
            $html = $this->get(route('webapp.playlists'))->getContent();
            $html = str_replace('http://my.localhost', 'http://my.pianolit.test', $html);
            $html = str_replace(['href="/css/', 'src="/js/'], ['href="http://my.pianolit.test/css/', 'src="http://my.pianolit.test/js/'], $html);
            file_put_contents($destination, $html);
        }
    }

    public function test_web_read_does_not_write_order_or_mutate_mobile_payloads()
    {
        $playlist = $this->playlist('Lullabies');
        $journey = $this->playlist('Basic 1', 6, 6, 'journey');
        Cache::put('app.playlists.order', [$playlist->id], 60);
        $url = route('api.playlists.index');
        $before = $this->getJson($url)->assertOk()->json();
        $journeyBefore = $this->getJson(route('api.playlists.index', ['group' => 'journey']))->assertOk()->json();
        $piecesUrl = route('api.playlists.show', $playlist);
        $piecesBefore = $this->getJson($piecesUrl)->assertOk()->json();
        Cache::forget('app.playlists.order');
        DB::enableQueryLog();
        DB::flushQueryLog();
        $data = app(Collections::class)->data();
        $queries = DB::getQueryLog();
        DB::disableQueryLog();
        $this->assertCount(1, $queries);
        $this->assertStringStartsWith('select', strtolower($queries[0]['query']));
        $this->assertSame(7, $playlist->fresh()->order);
        $this->assertSame(7, $journey->fresh()->order);
        $this->assertFalse($data['featured']['playlist']->relationLoaded('pieces'));
        Cache::put('app.playlists.order', [$playlist->id], 60);
        $this->assertSame($before, $this->getJson($url)->assertOk()->json());
        $this->assertSame($journeyBefore, $this->getJson(route('api.playlists.index', ['group' => 'journey']))->assertOk()->json());
        $this->assertSame($piecesBefore, $this->getJson($piecesUrl)->assertOk()->json());
        $this->assertArrayHasKey('cover_image', $before[0]);
        $this->assertArrayHasKey('pieces', $before[0]);
    }

    public function test_eligibility_fallback_and_empty_state()
    {
        $this->get(route('webapp.playlists'))->assertOk()->assertSee('More music is on its way');
        $this->playlist('Too few pieces', 5, 5);
        $this->playlist('Too few tutorials', 6, 4);
        $this->playlist('Old journey', 6, 6, 'journey');
        $eligible = $this->playlist('Unmapped collection', 6, 5);
        $data = app(Collections::class)->data();
        $this->assertCount(1, $data['playlists']);
        $this->assertSame($eligible->id, $data['featured']['playlist']->id);
        $this->assertSame(5, $data['featured']['playlist']->pieces_count);
        $this->assertSame(asset('images/webapp/collections/featured.webp'), $data['featured']['image']);
        $this->assertTrue($data['categories']->isEmpty());
        $this->get(route('webapp.playlists'))->assertOk()->assertSee('Unmapped collection')
            ->assertDontSee('Too few pieces')->assertDontSee('Old journey');
    }
}
