<?php

namespace Tests\Review;

use App\{Admin, Piece, Timeline, TimelineEvent, User};
use App\Services\Timeline\{WebTimeline, WikimediaDiscovery};
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\{Http, Redis, Schema};
use Illuminate\Support\Str;

class TimelineEventsTest extends ReviewTestCase
{
    public function setUp(): void
    {
        parent::setUp();
        Redis::shouldReceive('get')->andReturn(null);
        $this->actingAs(create(Admin::class), 'admin');
    }

    protected function candidate(int $id, int $year = 1800, string $kind = 'created'): array
    {
        return [
            'year' => $year, 'event_date' => null, 'title' => 'Library event '.$id,
            'description' => 'A notable musical work.', 'source_id' => 'Q'.$id.':'.$kind.':'.$year,
            'wikidata_id' => 'Q'.$id, 'event_kind' => $kind,
            'source_url' => 'https://en.wikipedia.org/wiki/Event_'.$id,
            'image_url' => 'https://upload.wikimedia.org/wikipedia/commons/thumb/example.jpg',
            'image_source_url' => 'https://commons.wikimedia.org/wiki/File:Example.jpg',
            'image_credit' => 'Wikimedia contributor', 'image_license' => 'CC BY-SA 4.0',
            'image_license_url' => 'https://creativecommons.org/licenses/by-sa/4.0/',
            'attribution' => 'Wikidata (CC0); Wikipedia contributors (CC BY-SA 4.0).',
        ];
    }

    protected function fakeDiscovery(): void
    {
        $events = [];
        for ($id = 1; $id <= 40; $id++) {
            $events[] = $this->candidate($id, 1799 + ($id % 3)) + ['rank' => $id, 'world_event' => false];
        }
        $service = \Mockery::mock(WikimediaDiscovery::class)->makePartial();
        $service->shouldReceive('batch')->andReturn(['events' => $events, 'complete' => true,
            'next_batch' => 1, 'has_more' => false, 'periods' => ['before' => true, 'after' => true]]);
        $service->shouldReceive('enrich')->andReturnUsing(function ($selected) {
            return array_map(function ($event) { unset($event['rank'], $event['world_event']); return $event; }, $selected);
        });
        $this->app->instance(WikimediaDiscovery::class, $service);
    }

    protected function discover(?string $searchId = null, int $year = 1800)
    {
        return $this->postJson(route('admin.timeline-events.discover'), ['reference_year' => $year, 'search_id' => $searchId]);
    }

    protected function saveCandidate(array $candidate)
    {
        $id = (string) Str::uuid();
        $this->withSession(['timeline_event_searches' => [$id => [
            'piece_id' => null, 'year' => $candidate['year'], 'expires' => time() + 7200,
            'shown' => [$candidate['source_id'] => $candidate],
        ]]]);
        return $this->postJson(route('admin.timeline-events.store'), [
            'search_id' => $id, 'source_id' => $candidate['source_id'],
            'title' => 'Forged title', 'source_identity' => 'forged', 'piece_id' => 123,
            'image_url' => 'https://example.com/forged.jpg',
        ]);
    }

    public function test_library_page_has_blank_year_shared_routes_and_admin_authorization()
    {
        $this->assertFalse(Schema::hasColumn('timeline_events', 'piece_id'));
        $this->assertEqualsCanonicalizing(array_diff(Schema::getColumnListing('piece_timeline_events'), ['piece_id']),
            array_diff(Schema::getColumnListing('timeline_events'), ['source_identity']));
        $response = $this->get(route('admin.timeline-events.index'))->assertOk()
            ->assertSee('Timeline events')->assertDontSee('Timeline events ·')->assertSee('Shared event library')->assertSee('Saved events');
        $this->assertMatchesRegularExpression('/<input id="reference-year"[^>]*value=""/', $response->getContent());
        $response->assertSee('data-search-key="library"', false)->assertSee(route('admin.timeline-events.discover'));
        $response->assertDontSee('Edit piece');
        auth('admin')->logout();
        $this->actingAs(Model::withoutEvents(function () { return create(User::class); }), 'web');
        $this->withExceptionHandling()->get(route('admin.timeline-events.index'))->assertRedirect(route('admin.login.show'));
        $this->discover()->assertUnauthorized();
        $this->postJson(route('admin.timeline-events.store'), [])->assertUnauthorized();
        $this->patchJson(route('admin.timeline-events.update', 1), [])->assertUnauthorized();
        $this->deleteJson(route('admin.timeline-events.destroy', 1))->assertUnauthorized();
        Http::assertNothingSent();
    }

    public function test_discovery_is_curated_and_more_excludes_shown_and_globally_saved_events()
    {
        $saved = $this->candidate(1, 1780, 'published');
        TimelineEvent::create($saved); // Same Wikidata context, a different curated year/kind.
        $this->fakeDiscovery();
        $first = $this->discover()->assertOk()->assertJsonPath('count', 10)->assertJsonPath('range', 5);
        $id = $first->json('search_id');
        $shown = app('session')->get('timeline_event_searches.'.$id.'.shown');
        $this->assertFalse(collect($shown)->contains('wikidata_id', 'Q1'));
        $this->assertDatabaseCount('timeline_events', 1);
        $this->assertDatabaseCount('piece_timeline_events', 0);
        $second = $this->discover($id)->assertOk()->assertJsonPath('count', 10);
        $all = app('session')->get('timeline_event_searches.'.$id.'.shown');
        $this->assertCount(20, $all);
        $this->assertCount(20, array_unique(array_column($all, 'wikidata_id')));
        $this->assertFalse(collect($all)->contains('wikidata_id', 'Q1'));
        $this->assertDatabaseCount('timeline_events', 1);
        Http::assertNothingSent();
    }

    public function test_saving_is_idempotent_uses_server_snapshot_and_preserves_curated_changes()
    {
        $candidate = $this->candidate(1);
        $first = $this->saveCandidate($candidate)->assertOk()->assertJsonPath('count', 1);
        $event = TimelineEvent::firstOrFail();
        $this->assertSame($candidate['title'], $event->title);
        $this->assertSame($candidate['image_url'], $event->image_url);
        $this->assertSame('Q1:context', $event->source_identity);
        $this->assertSame($candidate['image_credit'], $event->image_credit);
        $this->assertStringContainsString(route('admin.timeline-events.update', $event), $first->json('html'));
        $this->assertStringContainsString(route('admin.timeline-events.destroy', $event), $first->json('html'));
        $event->update(['year' => 1798, 'title' => 'Curated title']);
        $this->saveCandidate($candidate)->assertOk()->assertJsonPath('id', $event->id)->assertJsonPath('count', 1);
        $this->saveCandidate($this->candidate(1, 1801, 'published'))->assertOk()->assertJsonPath('id', $event->id);
        $this->assertDatabaseCount('timeline_events', 1);
        $this->assertDatabaseHas('timeline_events', ['id' => $event->id, 'year' => 1798, 'title' => 'Curated title']);
        $this->assertDatabaseCount('piece_timeline_events', 0);
    }

    public function test_database_uniqueness_blocks_alternate_date_claims_but_keeps_birth_and_death()
    {
        TimelineEvent::create($this->candidate(1));
        try {
            TimelineEvent::create($this->candidate(1, 1801, 'published'));
            $this->fail('The stable identity index must reject duplicate contextual events.');
        } catch (QueryException $e) {
            $this->assertDatabaseCount('timeline_events', 1);
        }
        $birth = $this->candidate(1, 1750, 'birth');
        $death = $this->candidate(1, 1830, 'death');
        $this->saveCandidate($birth)->assertOk()->assertJsonPath('count', 2);
        $this->saveCandidate($death)->assertOk()->assertJsonPath('count', 3);
        try {
            // Even a mismatched stable identity cannot reuse an existing source ID.
            $forged = $this->candidate(2); $forged['source_id'] = $birth['source_id'];
            TimelineEvent::create($forged);
            $this->fail('The source-ID index must reject duplicate sources.');
        } catch (QueryException $e) {
            $this->assertDatabaseCount('timeline_events', 3);
        }
    }

    public function test_save_race_recovers_the_winning_row_from_the_unique_index()
    {
        $candidate = $this->candidate(1);
        $inserted = false;
        \Illuminate\Support\Facades\DB::listen(function ($query) use (&$inserted, $candidate) {
            // Another admin saves after our initial lookup but before our INSERT.
            if (!$inserted && strpos($query->sql, 'select * from "timeline_events"') === 0) {
                $inserted = true;
                \Illuminate\Support\Facades\DB::table('timeline_events')->insert($candidate + ['source_identity' => 'Q1:context']);
            }
        });
        $this->saveCandidate($candidate)->assertOk()->assertJsonPath('count', 1);
        $this->assertTrue($inserted);
        $this->assertDatabaseCount('timeline_events', 1);
    }

    public function test_global_and_piece_cursors_cannot_cross_or_save_expired_and_forged_candidates()
    {
        $this->withExceptionHandling();
        $this->fakeDiscovery();
        $piece = Model::withoutEvents(function () { return create(Piece::class); });
        $pieceId = $this->postJson(route('admin.pieces.timeline.discover', $piece), ['reference_year' => 1800])->assertOk()->json('search_id');
        $this->postJson(route('admin.timeline-events.store'), ['search_id' => $pieceId, 'source_id' => 'Q1:created:1800'])
            ->assertUnprocessable()->assertJsonValidationErrors('source_id');
        $this->discover($pieceId)->assertUnprocessable()->assertJsonValidationErrors('search_id');
        $id = $this->discover()->assertOk()->json('search_id');
        $state = app('session')->get('timeline_event_searches.'.$id);
        $source = array_key_first($state['shown']);
        $this->postJson(route('admin.pieces.timeline.store', $piece), ['search_id' => $id, 'source_id' => $source])->assertUnprocessable();
        $this->discover($id, 1900)->assertUnprocessable()->assertJsonValidationErrors('search_id');
        $this->postJson(route('admin.timeline-events.store'), ['search_id' => $id, 'source_id' => 'Q999:created:1800'])
            ->assertUnprocessable()->assertJsonValidationErrors('source_id');
        $state['expires'] = time() - 1;
        $this->withSession(['timeline_event_searches' => [$id => $state]]);
        $this->postJson(route('admin.timeline-events.store'), ['search_id' => $id, 'source_id' => $source])->assertUnprocessable();
        $this->discover(null, 0)->assertUnprocessable()->assertJsonValidationErrors('reference_year');
        $this->assertDatabaseCount('timeline_events', 0);
    }

    public function test_edit_validation_chronological_display_and_removal()
    {
        $event = TimelineEvent::create($this->candidate(1));
        $older = TimelineEvent::create($this->candidate(2, 1790));
        $this->get(route('admin.timeline-events.index'))->assertOk()->assertSeeInOrder(['Library event 2', 'Library event 1']);
        $url = route('admin.timeline-events.update', $event);
        $data = ['year' => 1797, 'event_date' => '1797-03-12', 'title' => '<script>Curated title</script>',
            'description' => 'Curated description', 'image_url' => '', 'source_id' => 'forged', 'wikidata_id' => 'Q999'];
        $this->patch($url, $data)->assertRedirect(route('admin.timeline-events.index'));
        $this->assertDatabaseHas('timeline_events', ['id' => $event->id, 'year' => 1797, 'event_date' => '1797-03-12',
            'source_identity' => 'Q1:context', 'wikidata_id' => 'Q1', 'image_url' => null]);
        $this->get(route('admin.timeline-events.index'))->assertOk()->assertSee('&lt;script&gt;Curated title&lt;/script&gt;', false);
        $this->withExceptionHandling()->patchJson($url, array_merge($data, ['event_date' => '1798-01-01']))
            ->assertUnprocessable()->assertJsonValidationErrors('event_date');
        $this->patchJson($url, array_merge($data, ['image_url' => 'http://example.com/image.jpg']))
            ->assertUnprocessable()->assertJsonValidationErrors('image_url');
        $this->patchJson($url, array_merge($data, ['year' => [1797], 'event_date' => ['1797-03-12']]))
            ->assertUnprocessable()->assertJsonValidationErrors(['year', 'event_date']);
        $this->deleteJson(route('admin.timeline-events.destroy', $event))->assertOk()->assertJsonPath('count', 1)->assertJsonPath('source_id', $event->source_id);
        $this->delete(route('admin.timeline-events.destroy', $older))->assertRedirect(route('admin.timeline-events.index'));
        $this->assertDatabaseCount('timeline_events', 0);
        $this->saveCandidate($this->candidate(1))->assertOk()->assertJsonPath('count', 1);
    }

    public function test_library_does_not_change_public_or_mobile_piece_timelines()
    {
        $piece = Model::withoutEvents(function () { return create(Piece::class, ['composed_in' => 1800]); });
        $mobile = Timeline::for($piece->id, 4);
        $web = (new WebTimeline)->forPiece($piece)->all();
        $this->saveCandidate($this->candidate(1))->assertOk();
        $this->assertSame($mobile, (new \App\Http\Controllers\Api\PiecesController)->timeline($piece->id));
        $this->assertSame($web, (new WebTimeline)->forPiece($piece)->all());
        $this->assertDatabaseCount('piece_timeline_events', 0);
        Http::assertNothingSent();
    }
}
