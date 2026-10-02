<?php

namespace Tests\Review;

use App\{Admin, TimelineEvent};
use App\Services\Timeline\WikimediaDiscovery;
use Illuminate\Support\Facades\{Http, Redis};

class TimelineTypesTest extends ReviewTestCase
{
    public function setUp(): void
    {
        parent::setUp();
        Redis::shouldReceive('get')->andReturn(null);
        $this->actingAs(create(Admin::class), 'admin');
        $rows = []; $entities = [];
        $subjects = [
            'music' => ['P571', 'Q2188189', 'A musical work.'],
            'art' => ['P571', 'Q838948', 'An architectural artwork.'],
            'literature' => ['P577', 'Q7725634', 'A literary work.'],
            'science' => ['P577', 'Q11862829', 'A scientific discovery.'],
            'history' => ['P585', 'Q13418847', 'An important historical event.'],
            'people' => ['P569', 'Q5', 'A highly notable public figure.'],
        ];
        // Interleave subjects to exercise date-query shortlists as well as classification.
        for ($i = 0; $i < 24; $i++) {
            foreach ($subjects as $type => [$property, $class, $description]) {
                $qid = 'Q'.(100 + $i * 6 + array_search($type, array_keys($subjects)));
                $rows[$property][] = ['item' => ['value' => 'http://www.wikidata.org/entity/'.$qid],
                    'date' => ['value' => '1801-06-15T00:00:00Z'], 'sitelinks' => ['value' => '100']];
                $entities[$qid] = ['labels' => ['en' => ['value' => ucfirst($type).' example '.$i]],
                    'descriptions' => ['en' => ['value' => $description]],
                    'sitelinks' => ['enwiki' => ['title' => ucfirst($type).' example '.$i]],
                    'claims' => [
                        'P31' => [['mainsnak' => ['datavalue' => ['value' => ['id' => $class]]]]],
                        $property => [['mainsnak' => ['datavalue' => ['value' => ['time' => '+1801-06-15T00:00:00Z', 'precision' => 11]]]]],
                    ]];
            }
        }
        // A composer/writer birth belongs to both subjects, rather than only Music.
        $rows['P569'][] = ['item' => ['value' => 'http://www.wikidata.org/entity/Q999'],
            'date' => ['value' => '1800-01-01T00:00:00Z'], 'sitelinks' => ['value' => '100']];
        $entities['Q999'] = ['labels' => ['en' => ['value' => 'Composer and writer']],
            'descriptions' => ['en' => ['value' => 'Composer and writer']],
            'sitelinks' => ['enwiki' => ['title' => 'Composer and writer']]];
        Http::swap(new \Illuminate\Http\Client\Factory);
        Http::fake(function ($request) use ($rows, $entities) {
            if (strpos($request->url(), WikimediaDiscovery::QUERY_ENDPOINT) === 0) {
                preg_match('/wdt:(P\d+)/', $request['query'], $property);
                preg_match_all('/"(\d{4}-[^"]+)"\^\^xsd:dateTime/', $request['query'], $bounds);
                preg_match('/LIMIT (\d+) OFFSET (\d+)/', $request['query'], $paging);
                $valid = array_values(array_filter($rows[$property[1]] ?? [], function ($row) use ($bounds) {
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

    private function discover(array $types, ?string $id = null)
    {
        return $this->postJson(route('admin.timeline-events.discover'), [
            'reference_year' => 1800, 'types' => $types, 'search_id' => $id,
        ]);
    }

    private function shown(string $id): array
    {
        return app('session')->get('timeline_event_searches.'.$id.'.shown');
    }

    public function test_selected_subject_filters_real_metadata_and_more_excludes_shown_and_saved()
    {
        $first = $this->discover(['science'])->assertOk()->assertJsonPath('count', 10);
        $id = $first->json('search_id');
        $shown = $this->shown($id);
        foreach ($shown as $candidate) {
            $this->assertStringStartsWith('Science example', $candidate['title']);
            $this->assertArrayNotHasKey('types', $candidate);
            $this->assertArrayNotHasKey('relevance_rank', $candidate);
        }
        $sourceId = array_key_first($shown);
        $this->postJson(route('admin.timeline-events.store'), ['search_id' => $id, 'source_id' => $sourceId])->assertOk();
        $this->discover(['science'], $id)->assertOk()->assertJsonPath('count', 10);
        $all = $this->shown($id);
        $this->assertCount(20, $all);
        $this->assertCount(20, array_unique(array_column($all, 'wikidata_id')));
        foreach ($all as $candidate) $this->assertStringStartsWith('Science example', $candidate['title']);
        $fresh = $this->discover(['science'])->assertOk();
        $this->assertArrayNotHasKey($sourceId, $this->shown($fresh->json('search_id')));
        $this->assertDatabaseCount('timeline_events', 1);
        foreach (Http::recorded(function ($request) { return strpos($request->url(), WikimediaDiscovery::WIKIPEDIA_ENDPOINT) === 0; }) as $pair) {
            foreach (explode('|', $pair[0]['titles']) as $title) $this->assertStringStartsWith('Science example', $title);
        }
    }

    public function test_all_selected_types_get_representation_and_history_only_has_no_cultural_quota()
    {
        $first = $this->discover(array_keys(WikimediaDiscovery::TYPES))->assertOk()->assertJsonPath('count', 10);
        $titles = implode(' ', array_column($this->shown($first->json('search_id')), 'title'));
        foreach (array_keys(WikimediaDiscovery::TYPES) as $type) $this->assertStringContainsString(ucfirst($type).' example', $titles);
        $history = $this->discover(['history'])->assertOk()->assertJsonPath('count', 10);
        foreach ($this->shown($history->json('search_id')) as $candidate) $this->assertStringStartsWith('History example', $candidate['title']);
    }

    public function test_multi_subject_people_match_either_subject_and_other_people_excludes_composers()
    {
        $literature = $this->discover(['literature'])->assertOk();
        $literatureState = app('session')->get('timeline_event_searches.'.$literature->json('search_id'));
        $this->assertTrue(collect(array_merge($literatureState['pool'], array_values($literatureState['shown'])))->contains('wikidata_id', 'Q999'));
        $music = $this->discover(['music'])->assertOk();
        $musicState = app('session')->get('timeline_event_searches.'.$music->json('search_id'));
        $this->assertTrue(collect(array_merge($musicState['pool'], array_values($musicState['shown'])))->contains('wikidata_id', 'Q999'));
        $people = $this->discover(['people'])->assertOk()->assertJsonPath('count', 10);
        foreach ($this->shown($people->json('search_id')) as $candidate) $this->assertStringStartsWith('People example', $candidate['title']);
    }

    public function test_type_choices_are_validated_and_bound_to_the_search_cursor()
    {
        $this->withExceptionHandling();
        foreach ([[], ['unknown'], ['science', 'science'], ['science', null]] as $types) $this->discover($types)->assertUnprocessable();
        $this->postJson(route('admin.timeline-events.discover'), ['reference_year' => 1800, 'types' => 'music'])->assertUnprocessable();
        Http::assertNothingSent();
        $first = $this->discover(['history', 'science'])->assertOk();
        $id = $first->json('search_id');
        $this->discover(['science', 'history'], $id)->assertOk(); // Input ordering is immaterial.
        $this->discover(['music'], $id)->assertUnprocessable()->assertJsonValidationErrors('types');
        $fresh = $this->discover(['music'])->assertOk();
        $this->assertNotSame($id, $fresh->json('search_id'));
    }

    public function test_selection_balances_subjects_across_both_sides_of_a_piece_year()
    {
        $pool = [];
        foreach ([1799, 1801] as $year) {
            foreach (array_keys(WikimediaDiscovery::TYPES) as $index => $type) {
                $pool[] = ['year' => $year, 'source_id' => $year.':'.$type, 'types' => [$type], 'rank' => $index * 7, 'relevance_rank' => 0];
            }
        }
        $selected = (new WikimediaDiscovery)->select($pool, 10, 1800, array_keys(WikimediaDiscovery::TYPES));
        $this->assertCount(10, $selected);
        $this->assertCount(10, array_unique(array_column($selected, 'source_id')));
        $counts = array_count_values(array_merge(...array_column($selected, 'types')));
        $this->assertEqualsCanonicalizing(array_keys(WikimediaDiscovery::TYPES), array_keys($counts));
        $this->assertLessThanOrEqual(2, max($counts));
        $this->assertGreaterThanOrEqual(3, collect($selected)->where('year', 1799)->count());
        $this->assertGreaterThanOrEqual(3, collect($selected)->where('year', 1801)->count());
    }

    public function test_legacy_cursor_rebuilds_classified_pool_without_repeating_shown_events()
    {
        $types = array_keys(WikimediaDiscovery::TYPES);
        $first = $this->discover($types)->assertOk();
        $id = $first->json('search_id');
        $state = app('session')->get('timeline_event_searches.'.$id);
        unset($state['types']);
        $state['pool'] = array_values($state['shown']); // Legacy candidates lack type metadata.
        $state['has_more'] = false;
        $state['batch_index'] = 23;
        $this->withSession(['timeline_event_searches' => [$id => $state]]);
        $this->discover($types, $id)->assertOk()->assertJsonPath('count', 10);
        $this->assertCount(20, $this->shown($id));
        $this->assertCount(20, array_unique(array_column($this->shown($id), 'wikidata_id')));
    }

    public function test_sparse_subject_never_fills_empty_slots_with_unselected_types()
    {
        $service = \Mockery::mock(WikimediaDiscovery::class)->makePartial();
        $service->shouldReceive('batch')->once()->andReturn([
            'events' => [['year' => 1801, 'source_id' => 'Q1:work:1801', 'wikidata_id' => 'Q1',
                'event_kind' => 'created', 'types' => ['music'], 'rank' => 0]],
            'complete' => true, 'has_more' => false, 'next_batch' => 4, 'periods' => ['before' => false, 'after' => true],
        ]);
        $this->app->instance(WikimediaDiscovery::class, $service);
        $response = $this->discover(['science'])->assertOk()->assertJsonPath('count', 0)->assertJsonPath('has_more', false);
        $this->assertCount(0, $this->shown($response->json('search_id')));
        Http::assertNothingSent();
    }
}
