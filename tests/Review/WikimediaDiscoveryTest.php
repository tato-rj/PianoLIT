<?php

namespace Tests\Review;

use App\Admin;
use App\Services\Timeline\WikimediaDiscovery;
use Illuminate\Support\Facades\{Cache, Http, Redis};

class WikimediaDiscoveryTest extends ReviewTestCase
{
    public function setUp(): void
    {
        parent::setUp();
        Redis::shouldReceive('get')->andReturn(null);
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
        return $this->postJson(route('admin.timeline-events.discover'), ['reference_year' => $year, 'search_id' => $searchId]);
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
        $pool = (new WikimediaDiscovery)->batch(1730, 3)['events'];
        $this->assertSame(['Q1', 'Q2', 'Q3', 'Q4', 'Q5', 'Q6'], array_column($pool, 'wikidata_id'));
        $this->assertCount(6, $pool);
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
        $pool = (new WikimediaDiscovery)->batch(1730, 10)['events'];
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
        $events = $service->enrich($service->batch(1730, 3)['events']);
        $this->assertSame('1730-01-01', $events[0]['event_date']);
        $this->assertSame('Wikipedia introduction.', $events[0]['description']);
        $this->assertSame('Artist name', $events[0]['image_credit']);
        $this->assertSame('https://upload.wikimedia.org/480px-Painting.jpg', $events[0]['image_url']);
        $this->assertSame('Public domain', $events[0]['image_license']);
        $before = Http::recorded()->count();
        $again = (new WikimediaDiscovery)->enrich((new WikimediaDiscovery)->batch(1730, 3)['events']);
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
        $pool = $service->batch(1730, 5)['events'];
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
            $this->assertMatchesRegularExpression('/LIMIT (10|30) OFFSET/', $pair[0]['query']);
            $this->assertStringNotContainsString('ORDER BY', $pair[0]['query']);
        }
    }

    public function test_optional_summary_failure_keeps_candidates_and_logs_real_error()
    {
        $this->fakeDiscovery();
        $service = new WikimediaDiscovery;
        $pool = $service->select($service->batch(1730, 5)['events']);
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
        $first = $this->discover(null, 1800)->assertOk()->assertJsonPath('count', 10)->assertJsonPath('range', 10);
        $id = $first->json('search_id');
        $shown = app('session')->get('timeline_event_searches.'.$id.'.shown');
        $this->assertEmpty(array_filter($shown, function ($event) { return $event['year'] < 1800; }));
        $this->assertGreaterThanOrEqual(3, count(array_filter($shown, function ($event) { return $event['year'] > 1800; })));
        $this->assertNotEmpty(array_filter($shown, function ($event) { return $event['year'] === 1800; }));
        $this->discover($id, 1800)->assertOk()->assertJsonPath('count', 10);
        $all = app('session')->get('timeline_event_searches.'.$id.'.shown');
        $this->assertCount(20, $all);
        $service = new WikimediaDiscovery;
        $identities = array_map([$service, 'identity'], $all);
        $this->assertCount(20, array_unique($identities));
    }

}
