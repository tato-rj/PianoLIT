<?php

namespace Tests\Review;

use App\{Admin, Piece, Timeline, User};
use App\Services\Timeline\{WebTimeline, WikimediaDiscovery};
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\{Cache, DB, Http, Redis};

class PieceTimelineTest extends ReviewTestCase
{
    protected $piece;

    public function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware([\App\Http\Middleware\Logs\RecordWebAppLog::class, \App\Http\Middleware\UpdateLocation::class]);
        Redis::shouldReceive('get')->andReturn(null);
        $this->piece = Model::withoutEvents(function () { return create(Piece::class, ['composed_in' => 1730, 'published_in' => 1732]); });
        $this->actingAs(create(Admin::class), 'admin');
    }

    private function fakeHttp($callback = null)
    {
        // This Laravel version appends fake callbacks. Reset the base catch-all fake.
        Http::swap(new \Illuminate\Http\Client\Factory);
        Http::fake($callback);
    }

    private function bindings($count = 30, $year = 1730, $first = 1)
    {
        $rows = [];
        for ($i = $first; $i < $first + $count; $i++) {
            $values = [
                'item' => 'http://www.wikidata.org/entity/Q'.$i,
                'property' => 'http://www.wikidata.org/prop/direct/P571',
                'date' => $year.'-01-01T00:00:00Z', 'precision' => '9',
                'itemLabel' => 'Musical work '.$i, 'itemDescription' => 'Important cultural work '.$i,
                'article' => 'https://en.wikipedia.org/wiki/Musical_work_'.$i,
                'category' => '1', 'sitelinks' => '40',
            ];
            $rows[] = array_map(function ($value) { return ['value' => (string) $value]; }, $values);
        }
        return ['results' => ['bindings' => $rows]];
    }

    private function fakeDiscovery($count = 30)
    {
        $this->fakeHttp([
            WikimediaDiscovery::QUERY_ENDPOINT.'*' => Http::response($this->bindings($count)),
            '*' => Http::response(['query' => ['pages' => []]]),
        ]);
    }

    private function discover($searchId = null, $year = 1730)
    {
        return $this->postJson(route('admin.pieces.timeline.discover', $this->piece), ['reference_year' => $year, 'search_id' => $searchId]);
    }

    private function candidate($searchId, $sourceId = 'Q1:work:1730', $piece = null)
    {
        return $this->postJson(route('admin.pieces.timeline.store', $piece ?: $this->piece), ['search_id' => $searchId, 'source_id' => $sourceId]);
    }

    public function test_admin_page_manual_year_and_session_guard()
    {
        $response = $this->get(route('admin.pieces.timeline.edit', $this->piece))->assertOk()->assertSee('Reference year')->assertSee('Saved timeline');
        $this->assertStringNotContainsString('value="1730"', $response->getContent());
        auth('admin')->logout();
        $this->actingAs(Model::withoutEvents(function () { return create(User::class); }), 'web');
        $this->withExceptionHandling()->get(route('admin.pieces.timeline.edit', $this->piece))->assertRedirect(route('admin.login.show'));
        $this->discover()->assertUnauthorized();
        Http::assertNothingSent();
    }

    public function test_ten_more_excludes_shown_and_saved_and_save_is_idempotent()
    {
        $this->fakeDiscovery();
        $first = $this->discover()->assertOk()->assertJsonPath('count', 10);
        $id = $first->json('search_id');
        $first->assertSee('Musical work 1');
        $this->assertDatabaseCount('piece_timeline_events', 0);
        $this->candidate($id)->assertOk();
        $this->candidate($id)->assertOk();
        $this->assertDatabaseCount('piece_timeline_events', 1);
        $this->assertDatabaseHas('piece_timeline_events', ['piece_id' => $this->piece->id, 'event_date' => null, 'title' => 'Musical work 1 was created']);
        $second = $this->discover($id)->assertOk()->assertJsonPath('count', 10);
        $this->assertStringNotContainsString('data-candidate="Q1:work:1730"', $second->json('html'));
        $this->assertStringContainsString('data-candidate="Q11:work:1730"', $second->json('html'));
        $new = $this->discover()->assertOk();
        $this->assertStringNotContainsString('data-candidate="Q1:work:1730"', $new->json('html'));
        Http::assertSentCount(4); // One cached Wikidata pool, three optional summary requests.
    }

    public function test_progressively_widens_range_and_rejects_forged_or_cross_piece_candidates()
    {
        $ranges = [];
        $this->fakeHttp(function ($request) use (&$ranges) {
            if (strpos($request->url(), WikimediaDiscovery::QUERY_ENDPOINT) !== 0) return Http::response(['query' => ['pages' => []]]);
            $ranges[] = $request['query'];
            return Http::response(count($ranges) === 1 ? $this->bindings(3) : $this->bindings(25));
        });
        $first = $this->discover()->assertOk()->assertJsonPath('count', 10)->assertJsonPath('range', 7);
        $this->assertCount(2, $ranges);
        $this->assertStringContainsString('1727-01-01', $ranges[0]);
        $this->assertStringContainsString('1723-01-01', $ranges[1]);
        $id = $first->json('search_id');
        $this->withExceptionHandling();
        $this->candidate($id, 'Q999:work:1730')->assertUnprocessable();
        $other = Model::withoutEvents(function () { return create(Piece::class); });
        $this->candidate($id, 'Q1:work:1730', $other)->assertUnprocessable();
        $this->discover($id, 1800)->assertUnprocessable();
        $this->discover(null, '1730 UNION')->assertUnprocessable();
    }

    public function test_curated_updates_dates_ownership_and_cascade()
    {
        $this->fakeDiscovery();
        $id = $this->discover()->json('search_id');
        $this->candidate($id);
        $event = $this->piece->timelineEvents()->firstOrFail();
        $data = ['year' => 1727, 'event_date' => '1727-04-11', 'title' => '<script>Curated title</script>', 'description' => 'Our curated description.'];
        $url = route('admin.pieces.timeline.update', [$this->piece, $event]);
        $this->patch($url, $data)->assertRedirect();
        $this->get(route('admin.pieces.timeline.edit', $this->piece))->assertOk()->assertSee('&lt;script&gt;Curated title&lt;/script&gt;', false);
        $this->assertSame('Q1:work:1730', $event->fresh()->source_id);
        $this->withExceptionHandling()->patchJson($url, array_merge($data, ['event_date' => '1730-01-01']))->assertUnprocessable();
        $this->patchJson($url, array_merge($data, ['image_url' => 'javascript:alert(1)']))->assertUnprocessable();
        $other = Model::withoutEvents(function () { return create(Piece::class); });
        $this->patchJson(route('admin.pieces.timeline.update', [$other, $event]), $data)->assertNotFound();
        $this->delete(route('admin.pieces.timeline.destroy', [$other, $event]))->assertNotFound();
        $this->delete(route('admin.pieces.timeline.destroy', [$this->piece, $event]))->assertRedirect();
        $this->assertDatabaseCount('piece_timeline_events', 0);
        $this->candidate($id);
        DB::table('pieces')->where('id', $this->piece->id)->delete();
        $this->assertDatabaseCount('piece_timeline_events', 0);
    }

    public function test_wikimedia_failure_retry_and_optional_summary_failure()
    {
        $this->fakeHttp([WikimediaDiscovery::QUERY_ENDPOINT.'*' => Http::response([], 503)]);
        $this->discover()->assertStatus(503)->assertJsonPath('message', 'Wikimedia is unavailable right now. Please try again shortly.');
        $this->fakeHttp([WikimediaDiscovery::QUERY_ENDPOINT.'*' => Http::response($this->bindings()), '*' => Http::response([], 503)]);
        $this->discover()->assertOk()->assertJsonPath('count', 10);
        Http::assertSent(function ($request) { return strpos($request->header('User-Agent')[0], 'PianoLITTimeline/') === 0; });
    }

    public function test_public_uses_only_database_and_mobile_timeline_is_unchanged()
    {
        $legacyBefore = Timeline::for($this->piece->id, 4);
        $this->fakeDiscovery();
        $id = $this->discover()->json('search_id');
        $this->candidate($id);
        $this->assertSame($legacyBefore, Timeline::for($this->piece->id, 4));
        $this->assertSame($legacyBefore, (new \App\Http\Controllers\Api\PiecesController)->timeline($this->piece->id));
        $this->fakeHttp();
        $url = route('webapp.pieces.timeline', $this->piece);
        $this->get($url)->assertOk()->assertSee('Musical work 1 was created')->assertSee('This piece');
        Http::assertNothingSent();
        $events = (new WebTimeline)->forPiece($this->piece);
        $this->assertSame(1730, $events->firstWhere('highlight', true)['year']);
        $this->piece->update(['composed_in' => null]);
        $this->assertSame(1732, (new WebTimeline)->forPiece($this->piece)->firstWhere('highlight', true)['year']);
        $this->piece->update(['published_in' => null]);
        $this->assertNull((new WebTimeline)->forPiece($this->piece)->firstWhere('highlight', true));
        $this->assertArrayNotHasKey('timeline_events', $this->piece->getAttributes());
    }

    public function test_cultural_ranking_filters_minor_events_and_collapses_same_year_work_dates()
    {
        $data = $this->bindings(8);
        $descriptions = ['musical work', 'painting', 'novel', 'invention', 'historical event', 'highly notable person', 'administrative event', 'minor official'];
        foreach ($data['results']['bindings'] as $index => &$row) {
            unset($row['category']);
            $row['itemDescription']['value'] = $descriptions[$index];
            $row['sitelinks']['value'] = '80';
        }
        unset($row);
        $data['results']['bindings'][5]['property']['value'] = 'http://www.wikidata.org/prop/direct/P569';
        $data['results']['bindings'][6]['itemLabel']['value'] = 'Political appointment';
        $data['results']['bindings'][7]['sitelinks']['value'] = '2';
        $duplicate = $data['results']['bindings'][0];
        $duplicate['property']['value'] = 'http://www.wikidata.org/prop/direct/P577';
        $data['results']['bindings'][] = $duplicate;
        $this->fakeHttp([WikimediaDiscovery::QUERY_ENDPOINT.'*' => Http::response($data)]);
        $pool = (new WikimediaDiscovery)->pool(1730, 3);
        $this->assertSame(['Q1', 'Q2', 'Q3', 'Q4', 'Q5', 'Q6'], array_column($pool, 'wikidata_id'));
        $this->assertCount(6, $pool);
    }

    public function test_images_and_credits_are_saved_as_plain_content_and_date_precision_is_preserved()
    {
        $bindings = $this->bindings(1);
        $bindings['results']['bindings'][0]['precision']['value'] = '11';
        $this->fakeHttp([
            WikimediaDiscovery::QUERY_ENDPOINT.'*' => Http::response($bindings),
            WikimediaDiscovery::WIKIPEDIA_ENDPOINT.'*' => Http::response(['query' => ['pages' => [[
                'title' => 'Musical work 1', 'extract' => 'Wikipedia introduction.', 'pageimage' => 'Artist_painting.jpg',
                'thumbnail' => ['source' => 'https://upload.wikimedia.org/thumb.jpg'],
            ]]]]),
            WikimediaDiscovery::COMMONS_ENDPOINT.'*' => Http::response(['query' => ['pages' => [[
                'title' => 'File:Artist painting.jpg', 'imageinfo' => [[
                    'thumburl' => 'https://upload.wikimedia.org/480px-Painting.jpg',
                    'descriptionurl' => 'https://commons.wikimedia.org/wiki/File:Painting.jpg',
                    'extmetadata' => ['Artist' => ['value' => '<a href="/artist">Artist name</a>'], 'LicenseShortName' => ['value' => 'Public domain']],
                ]],
            ]]]]),
        ]);
        $service = new WikimediaDiscovery;
        $events = $service->enrich($service->pool(1730, 3));
        $this->assertSame('1730-01-01', $events[0]['event_date']);
        $this->assertSame('Wikipedia introduction.', $events[0]['description']);
        $this->assertSame('Artist name', $events[0]['image_credit']);
        $this->assertSame('https://upload.wikimedia.org/480px-Painting.jpg', $events[0]['image_url']);
        $this->assertSame('Public domain', $events[0]['image_license']);
    }
}
