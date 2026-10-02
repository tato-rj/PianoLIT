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

    private function stagedDiscovery(array $data, array $pages = []): void
    {
        $entities = [];
        foreach ($data['results']['bindings'] as $row) {
            $id = basename($row['item']['value']);
            $property = basename($row['property']['value']);
            $entity = $entities[$id] ?? [
                'labels' => ['en' => ['value' => $row['itemLabel']['value']]],
                'descriptions' => ['en' => ['value' => $row['itemDescription']['value']]],
                'sitelinks' => ['enwiki' => ['title' => str_replace('_', ' ', rawurldecode(substr(parse_url($row['article']['value'], PHP_URL_PATH), 6)))]],
                'claims' => [],
            ];
            $entity['claims'][$property][] = ['mainsnak' => ['datavalue' => ['value' => ['time' => '+'.$row['date']['value'], 'precision' => (int) $row['precision']['value']]]]];
            foreach (['class' => 'P31', 'occupation' => 'P106'] as $field => $prop) {
                if (isset($row[$field])) $entity['claims'][$prop][] = ['mainsnak' => ['datavalue' => ['value' => ['id' => basename($row[$field]['value'])]]]];
            }
            if (isset($row['category']) && $row['category']['value'] === '1') {
                $entity['claims']['P31'][] = ['mainsnak' => ['datavalue' => ['value' => ['id' => 'Q2188189']]]];
            }
            $entities[$id] = $entity;
        }
        $this->fakeHttp(function ($request) use ($data, $entities, $pages) {
            if (strpos($request->url(), WikimediaDiscovery::QUERY_ENDPOINT) === 0) {
                preg_match('/\?item wdt:(P\d+) \?date/', $request['query'], $property);
                preg_match('/LIMIT (\d+) OFFSET (\d+)/', $request['query'], $paging);
                preg_match_all('/\"(\d{4}-[^\"]+)\"\^\^xsd:dateTime/', $request['query'], $bounds);
                $rows = array_values(array_filter($data['results']['bindings'], function ($row) use ($property, $bounds) {
                    return basename($row['property']['value']) === $property[1] && $row['date']['value'] >= $bounds[1][0] && $row['date']['value'] <= $bounds[1][1];
                }));
                return Http::response(['results' => ['bindings' => array_slice($rows, (int) $paging[2], (int) $paging[1])]]);
            }
            if (strpos($request->url(), WikimediaDiscovery::ENTITY_ENDPOINT) === 0) {
                return Http::response(['entities' => array_intersect_key($entities, array_flip(explode('|', $request['ids'])))]);
            }
            foreach ($pages as $endpoint => $response) if (strpos($request->url(), $endpoint) === 0) return Http::response($response);
            return Http::response(['query' => ['pages' => []]]);
        });
    }

    private function fakeDiscovery($count = 30)
    {
        $this->stagedDiscovery($this->bindings($count));
    }

    private function discover($searchId = null, $year = 1730)
    {
        return $this->postJson(route('admin.pieces.timeline.discover', $this->piece), ['reference_year' => $year, 'search_id' => $searchId]);
    }

    private function candidate($searchId, $sourceId = 'Q1:work:1730', $piece = null)
    {
        return $this->postJson(route('admin.pieces.timeline.store', $piece ?: $this->piece), ['search_id' => $searchId, 'source_id' => $sourceId]);
    }

    public function test_admin_page_prefills_known_year_and_keeps_session_guard()
    {
        $response = $this->get(route('admin.pieces.timeline.edit', $this->piece))->assertOk()->assertSee('Reference year')->assertSee('Saved timeline');
        $this->assertMatchesRegularExpression('/<input id="reference-year"[^>]*value="1730"/', $response->getContent());
        $this->piece->update(['composed_in' => null]);
        $response = $this->get(route('admin.pieces.timeline.edit', $this->piece))->assertOk();
        $this->assertMatchesRegularExpression('/<input id="reference-year"[^>]*value="1732"/', $response->getContent());
        $this->piece->update(['published_in' => null]);
        $response = $this->get(route('admin.pieces.timeline.edit', $this->piece))->assertOk();
        $this->assertMatchesRegularExpression('/<input id="reference-year"[^>]*value=""/', $response->getContent());
        auth('admin')->logout();
        $this->actingAs(Model::withoutEvents(function () { return create(User::class); }), 'web');
        $this->withExceptionHandling()->get(route('admin.pieces.timeline.edit', $this->piece))->assertRedirect(route('admin.login.show'));
        $this->discover()->assertUnauthorized();
        Http::assertNothingSent();
    }

    public function test_ten_more_excludes_shown_and_saved_and_save_is_idempotent()
    {
        $this->fakeDiscovery();
        $first = $this->discover()->assertOk()->assertJsonPath('count', 10)->assertJsonPath('range', 5);
        Http::assertSent(function ($request) {
            return strpos($request->url(), WikimediaDiscovery::QUERY_ENDPOINT) === 0
                && strpos($request['query'], '1725-01-01T00:00:00Z') !== false;
        });
        Http::assertSent(function ($request) {
            return strpos($request->url(), WikimediaDiscovery::QUERY_ENDPOINT) === 0
                && strpos($request['query'], '1735-12-31T23:59:59Z') !== false;
        });
        $id = $first->json('search_id');
        $first->assertSee('Musical work 1');
        $this->assertStringContainsString('data-year="1730"', $first->json('html'));
        $this->assertStringContainsString('data-date="1730-01-01"', $first->json('html'));
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
        // Each lightweight date page is fetched once, including when a new search reuses it.
        $queries = Http::recorded(function ($request) { return strpos($request->url(), WikimediaDiscovery::QUERY_ENDPOINT) === 0; });
        $this->assertSame($queries->count(), $queries->unique(function ($pair) { return $pair[0]['query']; })->count());
    }

    public function test_more_batches_keep_five_year_window_and_reject_forged_or_cross_piece_candidates()
    {
        $this->fakeDiscovery(100);
        $first = $this->discover()->assertOk()->assertJsonPath('count', 10)->assertJsonPath('range', 5);
        $id = $first->json('search_id');
        for ($i = 0; $i < 4; $i++) $this->discover($id)->assertOk()->assertJsonPath('count', 10)->assertJsonPath('range', 5);
        Http::assertSent(function ($request) { return strpos($request->url(), WikimediaDiscovery::QUERY_ENDPOINT) === 0 && strpos($request['query'], 'OFFSET 40') !== false; });
        foreach (Http::recorded(function ($request) { return strpos($request->url(), WikimediaDiscovery::QUERY_ENDPOINT) === 0; }) as $pair) {
            $this->assertMatchesRegularExpression('/1725-01-01|1730-01-01|1731-01-01/', $pair[0]['query']);
            $this->assertMatchesRegularExpression('/1729-12-31|1730-12-31|1735-12-31/', $pair[0]['query']);
            $this->assertStringNotContainsString('OPTIONAL', $pair[0]['query']);
            $this->assertStringNotContainsString('SERVICE', $pair[0]['query']);
            $this->assertStringNotContainsString('UNION', $pair[0]['query']);
        }
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
        $event = $this->piece->timelineEvents()->firstOrFail();
        $this->deleteJson(route('admin.pieces.timeline.destroy', [$this->piece, $event]))->assertOk()->assertJsonPath('count', 0)->assertJsonPath('source_id', 'Q1:work:1730');
        $this->assertDatabaseCount('piece_timeline_events', 0);
        $this->candidate($id)->assertOk()->assertJsonPath('count', 1);
        DB::table('pieces')->where('id', $this->piece->id)->delete();
        $this->assertDatabaseCount('piece_timeline_events', 0);
    }

    public function test_wikimedia_failure_retry_and_optional_summary_failure()
    {
        $this->fakeHttp([WikimediaDiscovery::QUERY_ENDPOINT.'*' => Http::response([], 503)]);
        $this->discover()->assertStatus(503)->assertJsonPath('message', 'Wikimedia is unavailable right now. Please try again shortly.');
        Cache::flush();
        $this->fakeDiscovery();
        // Optional summary failures are covered separately by the client failure suite.
        $this->discover()->assertOk()->assertJsonPath('count', 10);
        Http::assertSent(function ($request) { return strpos($request->header('User-Agent')[0], 'PianoLIT/') === 0; });
    }

    public function test_public_uses_only_database_and_mobile_timeline_is_unchanged()
    {
        $this->piece->update(['cover_path' => 'pieces/timeline-cover.jpg']);
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
        $ownEvent = $events->firstWhere('highlight', true);
        $this->assertSame(1730, $ownEvent['year']);
        $this->assertNull($ownEvent['image_url']);
        $this->assertStringNotContainsString('<img', view('webapp.piece.components.event', ['event' => $ownEvent])->render());
        $this->piece->update(['composed_in' => null]);
        $this->assertSame(1732, (new WebTimeline)->forPiece($this->piece)->firstWhere('highlight', true)['year']);
        $this->piece->update(['published_in' => null]);
        $this->assertNull((new WebTimeline)->forPiece($this->piece)->firstWhere('highlight', true));
        $this->assertArrayNotHasKey('timeline_events', $this->piece->getAttributes());
    }

    public function test_piece_event_shows_composer_age_only_with_usable_lifetime_dates()
    {
        $composer = $this->piece->composer;
        $composer->update(['name' => 'Johann Sebastian Bach', 'date_of_birth' => '1685-03-21', 'date_of_death' => '1750-07-28']);
        $this->piece->update(['composed_in' => 1727]);
        $ownEvent = function () { return (new WebTimeline)->forPiece($this->piece)->firstWhere('highlight', true); };
        $event = $ownEvent();
        $this->assertSame('Johann Sebastian Bach was 42 years old', $event['description']);
        $html = view('webapp.piece.components.event', compact('event'))->render();
        $this->assertStringContainsString('class="piece-timeline-label">This piece</span>', $html);
        $this->assertStringNotContainsString('<img', $html);
        $this->piece->update(['composed_in' => null, 'published_in' => 1732]);
        $this->assertSame('Johann Sebastian Bach was 47 years old', $ownEvent()['description']);
        $this->piece->update(['published_in' => 1751]);
        $this->assertSame('Johann Sebastian Bach', $ownEvent()['description']);
        $this->piece->update(['published_in' => 1684]);
        $this->assertSame('Johann Sebastian Bach', $ownEvent()['description']);
        $this->piece->update(['published_in' => 1686]);
        $this->assertSame('Johann Sebastian Bach was 1 year old', $ownEvent()['description']);
        $composer->update(['date_of_birth' => null]);
        $this->assertSame('Johann Sebastian Bach', $ownEvent()['description']);
        Http::assertNothingSent();
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
        $this->stagedDiscovery($data);
        $pool = (new WikimediaDiscovery)->pool(1730, 3);
        $this->assertSame(['Q1', 'Q2', 'Q3', 'Q4', 'Q5', 'Q6'], array_column($pool, 'wikidata_id'));
        $this->assertCount(6, $pool);
    }

    public function test_batches_mix_world_events_with_culture_and_keep_unselected_candidates()
    {
        $data = $this->bindings(30);
        $world = $this->bindings(6, 1730, 101)['results']['bindings'];
        foreach ($world as $index => &$row) {
            unset($row['category']);
            $row['property']['value'] = 'http://www.wikidata.org/prop/direct/P580';
            $row['itemLabel']['value'] = 'World event '.($index + 1);
            $row['itemDescription']['value'] = ['war', 'treaty', 'revolution', 'expedition', 'earthquake', 'rebellion'][$index];
            $row['sitelinks']['value'] = '60';
        }
        unset($row);
        $data['results']['bindings'] = array_merge($data['results']['bindings'], $world, [$world[0]]);
        $data['results']['bindings'][36]['property']['value'] = 'http://www.wikidata.org/prop/direct/P585';
        $this->stagedDiscovery($data);
        $first = $this->discover()->assertOk()->assertJsonPath('count', 10);
        for ($i = 1; $i <= 3; $i++) $this->assertStringContainsString('World event '.$i, $first->json('html'));
        $this->assertStringNotContainsString('World event 4', $first->json('html'));
        $id = $first->json('search_id');
        $this->candidate($id, 'Q101:event:1730')->assertOk();
        $second = $this->discover($id)->assertOk()->assertJsonPath('count', 10);
        for ($i = 4; $i <= 6; $i++) $this->assertStringContainsString('World event '.$i, $second->json('html'));
        $this->assertStringNotContainsString('data-candidate="Q101:event:1730"', $second->json('html'));
        $this->assertStringContainsString('data-candidate="Q8:work:1730"', $second->json('html'));
        $third = $this->discover($id)->assertOk()->assertJsonPath('count', 10);
        $this->assertStringNotContainsString('World event', $third->json('html'));
        Http::assertSent(function ($request) {
            return strpos($request->url(), WikimediaDiscovery::QUERY_ENDPOINT) === 0 && strpos($request['query'], '?item wdt:P580 ?date') !== false;
        });
        $this->assertArrayNotHasKey('world_event', $this->app['session']->get('piece_timeline_searches.'.$id.'.shown.Q101:event:1730'));
        // Existing curated content may use the earlier inception-based identifier.
        $legacy = $this->piece->timelineEvents()->firstOrFail()->toArray();
        $legacy['wikidata_id'] = 'Q106';
        $legacy['source_id'] = 'Q106:work:1730';
        $legacy['event_kind'] = 'created';
        $this->piece->timelineEvents()->create($legacy);
        $new = $this->discover()->assertOk()->assertJsonPath('count', 10);
        $this->assertStringNotContainsString('data-candidate="Q106:event:1730"', $new->json('html'));
        $this->assertStringNotContainsString('data-candidate="Q101:event:1730"', $new->json('html'));
    }

    public function test_world_context_keeps_notable_battles_but_filters_minor_history()
    {
        $data = $this->bindings(8);
        foreach ($data['results']['bindings'] as &$row) {
            unset($row['category']);
            $row['property']['value'] = 'http://www.wikidata.org/prop/direct/P585';
            $row['itemDescription']['value'] = 'historical event';
            $row['sitelinks']['value'] = '100';
        }
        unset($row);
        $data['results']['bindings'][0]['itemLabel']['value'] = 'Major Battle';
        $data['results']['bindings'][1]['itemLabel']['value'] = 'Minor Battle';
        $data['results']['bindings'][1]['sitelinks']['value'] = '20';
        $data['results']['bindings'][2]['itemLabel']['value'] = 'Political appointment';
        $data['results']['bindings'][3]['itemLabel']['value'] = 'A skirmish';
        $data['results']['bindings'][4]['sitelinks']['value'] = '20';
        // Direct Wikidata classes work even without a matching English description.
        foreach ([5 => 'Q198', 6 => 'Q131569', 7 => 'Q124734'] as $index => $class) {
            $data['results']['bindings'][$index]['itemDescription']['value'] = 'Significant event';
            $data['results']['bindings'][$index]['class']['value'] = 'http://www.wikidata.org/entity/'.$class;
        }
        $this->stagedDiscovery($data);
        $pool = (new WikimediaDiscovery)->pool(1730, 10);
        $this->assertSame(['Q1', 'Q6', 'Q7', 'Q8'], array_column($pool, 'wikidata_id'));
        $this->assertSame([true, true, true, true], array_column($pool, 'world_event'));
    }

    public function test_images_and_credits_are_saved_as_plain_content_and_date_precision_is_preserved()
    {
        $bindings = $this->bindings(1);
        $bindings['results']['bindings'][0]['precision']['value'] = '11';
        $this->stagedDiscovery($bindings, [
            WikimediaDiscovery::WIKIPEDIA_ENDPOINT => ['query' => ['pages' => [[
                'title' => 'Musical work 1', 'extract' => 'Wikipedia introduction.', 'pageimage' => 'Artist_painting.jpg',
                'thumbnail' => ['source' => 'https://upload.wikimedia.org/thumb.jpg'],
            ]]]],
            WikimediaDiscovery::COMMONS_ENDPOINT => ['query' => ['pages' => [[
                'title' => 'File:Artist painting.jpg', 'imageinfo' => [[
                    'thumburl' => 'https://upload.wikimedia.org/480px-Painting.jpg',
                    'descriptionurl' => 'https://commons.wikimedia.org/wiki/File:Painting.jpg',
                    'extmetadata' => ['Artist' => ['value' => '<a href="/artist">Artist name</a>'], 'LicenseShortName' => ['value' => 'Public domain']],
                ]],
            ]]]],
        ]);
        $service = new WikimediaDiscovery;
        $events = $service->enrich($service->pool(1730, 3));
        $this->assertSame('1730-01-01', $events[0]['event_date']);
        $this->assertSame('Wikipedia introduction.', $events[0]['description']);
        $this->assertSame('Artist name', $events[0]['image_credit']);
        $this->assertSame('https://upload.wikimedia.org/480px-Painting.jpg', $events[0]['image_url']);
        $this->assertSame('Public domain', $events[0]['image_license']);
        $before = Http::recorded()->count();
        $again = (new WikimediaDiscovery)->enrich((new WikimediaDiscovery)->pool(1730, 3));
        $this->assertSame($events, $again);
        $this->assertSame($before, Http::recorded()->count());
    }

    public function test_metadata_shortlist_is_bounded_and_only_selected_events_receive_summaries()
    {
        $data = ['results' => ['bindings' => []]];
        foreach (WikimediaDiscovery::PROPERTIES as $index => $property) {
            $rows = $this->bindings(20, 1730, 1000 + $index * 100)['results']['bindings'];
            foreach ($rows as &$row) $row['property']['value'] = 'http://www.wikidata.org/prop/direct/'.$property;
            unset($row);
            $data['results']['bindings'] = array_merge($data['results']['bindings'], $rows);
        }
        $this->stagedDiscovery($data);
        $service = new WikimediaDiscovery;
        $pool = $service->pool(1730, 5);
        $entityCalls = Http::recorded(function ($request) { return strpos($request->url(), WikimediaDiscovery::ENTITY_ENDPOINT) === 0; });
        $this->assertCount(2, $entityCalls);
        $ids = [];
        foreach ($entityCalls as $pair) {
            $chunk = explode('|', $pair[0]['ids']);
            $this->assertLessThanOrEqual(50, count($chunk));
            $ids = array_merge($ids, $chunk);
        }
        $this->assertCount(60, $ids);
        $service->enrich($service->select($pool));
        Http::assertSent(function ($request) { return strpos($request->url(), WikimediaDiscovery::WIKIPEDIA_ENDPOINT) === 0 && count(explode('|', $request['titles'])) === 10; });
        foreach (Http::recorded(function ($request) { return strpos($request->url(), WikimediaDiscovery::QUERY_ENDPOINT) === 0; }) as $pair) {
            $this->assertStringContainsString('SELECT ?item ?date ?sitelinks', $pair[0]['query']);
            $this->assertMatchesRegularExpression('/LIMIT (10|15) OFFSET/', $pair[0]['query']);
            $this->assertStringNotContainsString('ORDER BY', $pair[0]['query']);
        }
    }

    public function test_optional_summary_failure_keeps_candidates_and_logs_real_error()
    {
        $this->fakeDiscovery();
        $service = new WikimediaDiscovery;
        $pool = $service->select($service->pool(1730, 5));
        $this->fakeHttp([WikimediaDiscovery::WIKIPEDIA_ENDPOINT.'*' => Http::response('Upstream unavailable', 503)]);
        \Illuminate\Support\Facades\Log::spy();
        $candidates = (new WikimediaDiscovery)->enrich($pool);
        $this->assertCount(10, $candidates);
        $this->assertNull($candidates[0]['image_url']);
        $this->assertSame($pool[0]['title'], $candidates[0]['title']);
        Http::assertSentCount(2); // One bounded retry, then a short endpoint cooldown.
        \Illuminate\Support\Facades\Log::shouldHaveReceived('warning')->with('Timeline Wikimedia request failed', \Mockery::on(function ($context) {
            return $context['endpoint'] === WikimediaDiscovery::WIKIPEDIA_ENDPOINT && $context['http_status'] === 503 && $context['error_message'] === 'Upstream unavailable';
        }))->twice();
    }

    public function test_reference_year_cannot_crowd_future_events_out_of_the_shortlist()
    {
        $data = $this->bindings(20, 1800);
        $data['results']['bindings'] = array_merge($data['results']['bindings'],
            $this->bindings(15, 1797, 101)['results']['bindings'], $this->bindings(15, 1804, 201)['results']['bindings']);
        $this->stagedDiscovery($data);
        $first = $this->discover(null, 1800)->assertOk()->assertJsonPath('count', 10)->assertJsonPath('range', 5);
        $id = $first->json('search_id');
        $shown = app('session')->get('piece_timeline_searches.'.$id.'.shown');
        $this->assertGreaterThanOrEqual(3, count(array_filter($shown, function ($event) { return $event['year'] < 1800; })));
        $this->assertGreaterThanOrEqual(3, count(array_filter($shown, function ($event) { return $event['year'] > 1800; })));
        $this->assertNotEmpty(array_filter($shown, function ($event) { return $event['year'] === 1800; }));
        $this->discover($id, 1800)->assertOk()->assertJsonPath('count', 10);
        $all = app('session')->get('piece_timeline_searches.'.$id.'.shown');
        $this->assertCount(20, $all);
        $service = new WikimediaDiscovery;
        $identities = array_map([$service, 'identity'], $all);
        $this->assertCount(20, array_unique($identities));
    }

    public function test_date_balance_preserves_world_quota_and_fills_sparse_periods()
    {
        $pool = [];
        foreach ([1800 => 20, 1799 => 5, 1801 => 5] as $year => $count) {
            for ($i = 0; $i < $count; $i++) $pool[] = ['source_id' => $year.':'.$i, 'year' => $year, 'rank' => $year === 1800 ? 0 : 10, 'world_event' => false];
        }
        for ($i = 0; $i < 3; $i++) $pool[] = ['source_id' => 'world:'.$i, 'year' => 1800, 'rank' => 50, 'world_event' => true];
        $service = new WikimediaDiscovery;
        $selected = $service->select($pool, 10, 1800);
        $this->assertCount(10, $selected);
        $this->assertCount(3, array_filter($selected, function ($event) { return $event['world_event']; }));
        $this->assertCount(3, array_filter($selected, function ($event) { return $event['year'] < 1800; }));
        $this->assertCount(3, array_filter($selected, function ($event) { return $event['year'] > 1800; }));
        $this->assertCount(10, array_unique(array_column($selected, 'source_id')));
        $sparse = array_values(array_filter($pool, function ($event) { return $event['year'] <= 1800; }));
        $this->assertCount(10, $service->select($sparse, 10, 1800));
        $this->assertFalse($service->needsCandidates($sparse, 1800, ['before' => true, 'after' => false]));
        $this->assertTrue($service->needsCandidates($sparse, 1800));
    }
}
