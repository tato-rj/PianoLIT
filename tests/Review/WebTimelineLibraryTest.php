<?php

namespace Tests\Review;

use App\{Piece, Tag, Timeline, TimelineEvent};
use App\Services\Timeline\WebTimeline;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\{DB, Http, Redis};

class WebTimelineLibraryTest extends ReviewTestCase
{
    protected $piece;
    private $identity = 0;

    public function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware([\App\Http\Middleware\Logs\RecordWebAppLog::class, \App\Http\Middleware\UpdateLocation::class]);
        Redis::shouldReceive('get')->andReturn(null);
        $this->piece = Model::withoutEvents(function () {
            return create(Piece::class, ['composed_in' => 1800, 'published_in' => 1830]);
        });
        foreach (['level' => 'elementary', 'period' => 'baroque', 'length' => 'short', 'mood' => 'happy'] as $type => $name) {
            $this->piece->tags()->attach(create(Tag::class, compact('type', 'name')));
        }
        $this->piece->composer->update(['name' => 'Test Composer', 'date_of_birth' => '1750-03-21', 'date_of_death' => '1840-07-28']);
    }

    protected function event(int $year, ?string $date = null, ?string $title = null)
    {
        $id = ++$this->identity;
        return TimelineEvent::create([
            'year' => $year, 'event_date' => $date, 'title' => $title ?: 'Shared event '.$id,
            'description' => 'Curated historical context.', 'source_id' => 'Q'.$id.':created:'.$year,
            'wikidata_id' => 'Q'.$id, 'event_kind' => 'created',
            'source_url' => 'https://en.wikipedia.org/wiki/Event_'.$id,
            'attribution' => 'Wikipedia contributors (CC BY-SA 4.0).',
        ]);
    }

    protected function timeline()
    {
        return (new WebTimeline)->forPiece($this->piece);
    }

    protected function undatedPiece(string $birth, ?string $death)
    {
        $this->piece->update(['composed_in' => null, 'published_in' => null]);
        $this->piece->composer->update(['date_of_birth' => $birth, 'date_of_death' => $death]);
    }

    public function test_composition_prefers_nearby_events_on_both_sides_and_caps_the_entire_timeline_at_eight()
    {
        foreach ([1789, 1790, 1796, 1797, 1798, 1799, 1800, 1801, 1802, 1803, 1804, 1810, 1811, 1830] as $year) $this->event($year);
        for ($i = 0; $i < 15; $i++) $this->event(1800);
        $legacy = TimelineEvent::where('year', 1799)->firstOrFail()->getAttributes();
        $legacy['title'] = 'Piece-specific old event';
        $this->piece->timelineEvents()->create($legacy);
        $timeline = $this->timeline();
        $this->assertCount(8, $timeline);
        $this->assertSame([1797, 1798, 1799, 1800, 1800, 1801, 1802, 1803], $timeline->pluck('year')->all());
        $this->assertCount(7, $timeline->where('highlight', false)->pluck('id')->unique());
        $this->assertSame(1800, $timeline->firstWhere('highlight', true)['year']);
        $this->assertSame('Test Composer was 50 years old', $timeline->firstWhere('highlight', true)['description']);
        $this->assertFalse($timeline->contains('title', 'Piece-specific old event'));
        Http::assertNothingSent();
    }

    public function test_publication_is_used_only_when_composition_is_unusable()
    {
        foreach ([1799, 1829, 1830, 1831] as $year) $this->event($year);
        foreach ([null, 0, 'unknown', 1800.5, 10000] as $invalid) {
            $this->piece->composed_in = $invalid;
            $timeline = $this->timeline();
            $this->assertSame([1829, 1830, 1830, 1831], $timeline->pluck('year')->all());
            $this->assertStringContainsString('was published', $timeline->firstWhere('highlight', true)['title']);
        }
    }

    public function test_window_bounds_sparse_sides_and_same_year_only_results()
    {
        foreach ([1789, 1790, 1810, 1811] as $year) $this->event($year);
        $this->assertSame([1790, 1800, 1810], $this->timeline()->pluck('year')->all());
        TimelineEvent::query()->delete();
        for ($year = 1801; $year <= 1810; $year++) $this->event($year);
        $this->assertSame(range(1800, 1807), $this->timeline()->pluck('year')->all());
        TimelineEvent::query()->delete();
        for ($i = 0; $i < 10; $i++) $this->event(1800, '1800-01-'.sprintf('%02d', $i + 1));
        $this->assertCount(8, $this->timeline());
        $this->assertCount(7, $this->timeline()->where('highlight', false));
    }

    public function test_lifetime_selection_spreads_across_years_even_with_a_dense_birth_year()
    {
        $this->undatedPiece('1685-03-21', '1750-07-28');
        $this->event(1684); $this->event(1751);
        for ($i = 0; $i < 60; $i++) $this->event(1685);
        for ($year = 1690; $year <= 1750; $year += 5) $this->event($year);
        $timeline = $this->timeline();
        $years = $timeline->pluck('year');
        $this->assertCount(8, $timeline);
        $this->assertCount(8, $years->unique());
        $this->assertSame(1685, $years->first());
        $this->assertSame(1750, $years->last());
        foreach ([[1685, 1700], [1701, 1717], [1718, 1734], [1735, 1750]] as $span) {
            $this->assertTrue($years->contains(function ($year) use ($span) { return $year >= $span[0] && $year <= $span[1]; }));
        }
        $this->assertCount(0, $timeline->where('highlight', true));
        $this->assertSame($timeline->all(), $this->timeline()->all(), 'Selections are stable across page visits.');
    }

    public function test_lifetime_respects_exact_boundaries_and_retains_year_only_events()
    {
        $this->undatedPiece('1800-03-21', '1800-07-28');
        $outside = [$this->event(1800, '1800-03-20')->id, $this->event(1800, '1800-07-29')->id];
        $inside = [$this->event(1800, '1800-03-21')->id, $this->event(1800, '1800-07-28')->id, $this->event(1800)->id];
        $this->assertEqualsCanonicalizing($inside, $this->timeline()->pluck('id')->all());
        $this->assertEmpty(array_intersect($outside, $this->timeline()->pluck('id')->all()));
    }

    public function test_missing_death_uses_following_decades_and_never_future_events()
    {
        $this->travelTo(\Carbon\Carbon::parse('2026-10-02'));
        $this->undatedPiece('1700-01-01', null);
        foreach ([1699, 1700, 1710, 1720, 1730, 1740, 1750, 1760, 1770, 1780, 1781, 2026] as $year) $this->event($year);
        $timeline = $this->timeline();
        $this->assertCount(8, $timeline);
        $this->assertSame(1700, $timeline->first()['year']);
        $this->assertSame(1780, $timeline->last()['year']);
        $this->assertFalse($timeline->contains('year', 1699));
        $this->assertFalse($timeline->contains('year', 1781));
        TimelineEvent::query()->delete();
        $this->undatedPiece('2000-03-21', null);
        $past = $this->event(2026, '2026-10-02');
        $this->event(2026, '2026-10-03');
        $this->event(2027); $this->event(1999);
        $this->assertSame([$past->id], $this->timeline()->pluck('id')->all());
        $this->travelBack();
    }

    public function test_empty_library_missing_birth_or_invalid_lifetime_never_invents_a_piece_date()
    {
        $this->assertCount(1, $this->timeline());
        $this->assertTrue($this->timeline()->first()['highlight']);
        $this->undatedPiece('1700-01-01', '1780-01-01');
        $this->assertCount(0, $this->timeline());
        $this->event(1750);
        $this->piece->composer->update(['date_of_birth' => null]);
        $this->assertCount(0, $this->timeline());
        $this->piece->composer->update(['date_of_birth' => '1800-01-01', 'date_of_death' => '1780-01-01']);
        $this->assertCount(0, $this->timeline());
        $html = view('webapp.piece.components.timeline', ['timeline' => $this->timeline()])->render();
        $this->assertStringContainsString('Historical events have not been added', $html);
        $this->assertStringNotContainsString('This piece', $html);
    }

    public function test_selected_content_is_live_curated_shared_content_without_wikimedia_or_mobile_changes()
    {
        $mobile = Timeline::for($this->piece->id, 4);
        $event = $this->event(1799, null, '<script>Shared curated event</script>');
        $this->event(1801);
        $url = route('webapp.pieces.timeline', $this->piece);
        $this->get($url)->assertOk()->assertSee('&lt;script&gt;Shared curated event&lt;/script&gt;', false)->assertSee('This piece');
        $this->get(route('webapp.pieces.show', $this->piece))->assertOk()->assertSee('Shared curated event');
        $other = Model::withoutEvents(function () { return create(Piece::class, ['composed_in' => 1800]); });
        $this->assertSame($this->timeline()->where('highlight', false)->pluck('id')->all(),
            (new WebTimeline)->forPiece($other)->where('highlight', false)->pluck('id')->all());
        $event->update(['title' => 'Updated curated title']);
        $this->get($url)->assertOk()->assertSee('Updated curated title')->assertDontSee('Shared curated event');
        $event->delete();
        $this->get($url)->assertOk()->assertDontSee('Updated curated title');
        $this->assertSame($mobile, (new \App\Http\Controllers\Api\PiecesController)->timeline($this->piece->id));
        $this->assertArrayNotHasKey('timeline_events', $this->piece->getAttributes());
        $this->assertFalse($this->piece->relationLoaded('timelineEvents'));
        Http::assertNothingSent();
    }

    public function test_date_selection_uses_two_library_queries_and_fetches_full_content_only_for_selected_rows()
    {
        for ($i = 0; $i < 50; $i++) $this->event(1799 + ($i % 3));
        DB::enableQueryLog(); DB::flushQueryLog();
        $timeline = $this->timeline();
        $queries = DB::getQueryLog(); DB::disableQueryLog();
        $this->assertCount(2, $queries);
        $this->assertStringContainsString('select "id", "year", "event_date"', $queries[0]['query']);
        $this->assertStringContainsString('select * from "timeline_events"', $queries[1]['query']);
        $this->assertCount(7, $queries[1]['bindings']);
        $this->assertCount(8, $timeline);
    }
}
