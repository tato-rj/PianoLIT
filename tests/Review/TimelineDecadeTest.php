<?php

namespace Tests\Review;

use App\Admin;
use App\Services\Timeline\WikimediaDiscovery;
use Illuminate\Support\Facades\{Http, Redis};
use Illuminate\Support\Str;

class TimelineDecadeTest extends ReviewTestCase
{
    public function setUp(): void
    {
        parent::setUp();
        Redis::shouldReceive('get')->andReturn(null);
        $this->actingAs(create(Admin::class), 'admin');
        $this->fakeDiscovery();
    }

    private function fakeDiscovery(): void
    {
        $dates = [1 => '1799-12-31', 2 => '1811-01-01', 3 => '1800-01-01', 4 => '1810-12-31'];
        for ($id = 100; $id < 140; $id++) $dates[$id] = (1801 + ($id % 9)).'-06-15';
        $rows = []; $entities = [];
        foreach ($dates as $id => $date) {
            $rows[] = ['item' => ['value' => 'http://www.wikidata.org/entity/Q'.$id],
                'date' => ['value' => $date.'T00:00:00Z'], 'sitelinks' => ['value' => '50']];
            $entities['Q'.$id] = ['labels' => ['en' => ['value' => 'Musical work '.$id]],
                'descriptions' => ['en' => ['value' => 'An important musical composition.']],
                'sitelinks' => ['enwiki' => ['title' => 'Musical work '.$id]],
                'claims' => ['P571' => [['mainsnak' => ['datavalue' => ['value' => ['time' => '+'.$date.'T00:00:00Z', 'precision' => 11]]]]]]];
        }
        Http::swap(new \Illuminate\Http\Client\Factory);
        Http::fake(function ($request) use ($rows, $entities) {
            if (strpos($request->url(), WikimediaDiscovery::QUERY_ENDPOINT) === 0) {
                if (strpos($request['query'], 'wdt:P571') === false) return Http::response(['results' => ['bindings' => []]]);
                preg_match_all('/"(\d{4}-[^"]+)"\^\^xsd:dateTime/', $request['query'], $bounds);
                preg_match('/LIMIT (\d+) OFFSET (\d+)/', $request['query'], $paging);
                $valid = array_values(array_filter($rows, function ($row) use ($bounds) {
                    return $row['date']['value'] >= $bounds[1][0] && $row['date']['value'] <= $bounds[1][1];
                }));
                return Http::response(['results' => ['bindings' => array_slice($valid, (int) $paging[2], (int) $paging[1])]]);
            }
            if (strpos($request->url(), WikimediaDiscovery::ENTITY_ENDPOINT) === 0) {
                return Http::response(['entities' => array_intersect_key($entities, array_flip(explode('|', $request['ids'])))]);
            }
            return Http::response(['query' => ['pages' => []]]);
        });
    }

    private function discover(?string $id = null, int $year = 1800)
    {
        return $this->postJson(route('admin.timeline-events.discover'), ['reference_year' => $year, 'search_id' => $id]);
    }

    public function test_library_queries_only_reference_year_through_ten_years_after_and_more_stays_inside()
    {
        $first = $this->discover()->assertOk()->assertJsonPath('count', 10)->assertJsonPath('range', 10)
            ->assertJsonPath('start_year', 1800)->assertJsonPath('end_year', 1810);
        $id = $first->json('search_id');
        $this->discover($id)->assertOk()->assertJsonPath('count', 10);
        $shown = app('session')->get('timeline_event_searches.'.$id.'.shown');
        $this->assertCount(20, $shown);
        $this->assertCount(20, array_unique(array_column($shown, 'wikidata_id')));
        foreach ($shown as $event) {
            $this->assertGreaterThanOrEqual(1800, $event['year']);
            $this->assertLessThanOrEqual(1810, $event['year']);
        }
        $this->assertDatabaseCount('timeline_events', 0);
        foreach (Http::recorded(function ($request) { return strpos($request->url(), WikimediaDiscovery::QUERY_ENDPOINT) === 0; }) as $pair) {
            $this->assertMatchesRegularExpression('/1800-01-01|1801-01-01/', $pair[0]['query']);
            $this->assertMatchesRegularExpression('/1800-12-31|1810-12-31/', $pair[0]['query']);
            $this->assertStringNotContainsString('1799', $pair[0]['query']);
            $this->assertStringNotContainsString('1811', $pair[0]['query']);
        }
    }

    public function test_forward_batches_include_both_boundary_dates_and_exclude_outside_dates()
    {
        $service = new WikimediaDiscovery;
        $forward = [];
        for ($batch = 0; $batch < 4; $batch++) $forward = array_merge($forward, $service->batch(1800, 10, $batch)['events']);
        $this->assertFalse(collect($forward)->contains('wikidata_id', 'Q1'));
        $this->assertFalse(collect($forward)->contains('wikidata_id', 'Q2'));
        $this->assertSame('1800-01-01', collect($forward)->firstWhere('wikidata_id', 'Q3')['event_date']);
        $this->assertSame('1810-12-31', collect($forward)->firstWhere('wikidata_id', 'Q4')['event_date']);
    }

    public function test_old_open_search_restarts_forward_pools_but_preserves_shown_exclusions()
    {
        $id = (string) Str::uuid();
        $this->withSession(['timeline_event_searches' => [$id => [
            'year' => 1800, 'expires' => time() + 7200, 'batch_index' => 23, 'has_more' => false,
            'pool' => [['source_id' => 'Q1:work:1799', 'wikidata_id' => 'Q1', 'event_kind' => 'created', 'year' => 1799]],
            'shown' => ['Q3:work:1800' => ['source_id' => 'Q3:work:1800', 'wikidata_id' => 'Q3', 'event_kind' => 'created', 'year' => 1800]],
        ]]]);
        $this->discover($id)->assertOk()->assertJsonPath('count', 10)->assertJsonPath('start_year', 1800)->assertJsonPath('end_year', 1810);
        $state = app('session')->get('timeline_event_searches.'.$id);
        $this->assertArrayHasKey('Q3:work:1800', $state['shown']);
        $this->assertArrayNotHasKey('Q1:work:1799', $state['shown']);
        $this->assertCount(11, $state['shown']);
        foreach ($state['pool'] as $event) $this->assertGreaterThanOrEqual(1800, $event['year']);
    }

    public function test_forward_window_clamps_at_supported_calendar_limit()
    {
        $this->discover(null, 9995)->assertOk()->assertJsonPath('start_year', 9995)->assertJsonPath('end_year', 9999);
        Http::assertSent(function ($request) {
            return strpos($request->url(), WikimediaDiscovery::QUERY_ENDPOINT) === 0 && strpos($request['query'], '9999-12-31') !== false;
        });
    }
}
