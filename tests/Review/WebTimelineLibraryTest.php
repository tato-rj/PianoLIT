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

    protected function undatedPiece(?string $birth, ?string $death)
    {
        $this->piece->update(['composed_in' => null, 'published_in' => null]);
        $this->piece->composer->update(['date_of_birth' => $birth, 'date_of_death' => $death]);
    }

    public function test_composer_lifetime_always_drives_selection_and_piece_dates_are_milestones()
    {
        foreach ([1749, 1750, 1770, 1790, 1800, 1810, 1830, 1840, 1841] as $year) $this->event($year);
        $timeline = $this->timeline();
        $this->assertCount(8, $timeline);
        $this->assertSame('birth', $timeline->first()['composer_milestone']);
        $this->assertSame('death', $timeline->last()['composer_milestone']);
        $this->assertSame([1800, 1830], $timeline->where('highlight', true)->pluck('year')->all());
        $this->assertSame('Test Composer was 50 years old', $timeline->firstWhere('highlight', true)['description']);
        $context = $timeline->filter(function ($event) { return isset($event['id']); });
        $this->assertCount(4, $context);
        $this->assertSame(1750, $context->first()['year']);
        $this->assertSame(1840, $context->last()['year']);
        $this->piece->update(['composed_in' => 1770, 'published_in' => 1790]);
        $this->assertSame($context->pluck('id')->all(), $this->timeline()->filter(function ($event) { return isset($event['id']); })->pluck('id')->all());
        Http::assertNothingSent();
    }

    public function test_piece_dates_combine_in_the_same_year_and_invalid_values_add_no_milestone()
    {
        $this->piece->update(['composed_in' => 1800, 'published_in' => 1800]);
        $pieceEvent = $this->timeline()->firstWhere('highlight', true);
        $this->assertStringContainsString('was composed and published', $pieceEvent['title']);
        $this->assertCount(1, $this->timeline()->where('highlight', true));
        foreach ([null, 0, 'unknown', 1800.5, 10000] as $invalid) {
            $this->piece->composed_in = $invalid;
            $timeline = $this->timeline();
            $this->assertCount(1, $timeline->where('highlight', true));
            $this->assertStringContainsString('was published', $timeline->firstWhere('highlight', true)['title']);
            $this->assertSame('birth', $timeline->first()['composer_milestone']);
        }
    }

    public function test_posthumous_publication_is_shown_after_death_without_changing_library_period()
    {
        $this->piece->update(['composed_in' => 1800, 'published_in' => 1850]);
        $this->event(1840); $this->event(1850);
        $timeline = $this->timeline();
        $this->assertSame([1750, 1800, 1840, 1840, 1850], $timeline->pluck('year')->all());
        $this->assertStringContainsString('was published', $timeline->last()['title']);
        $this->assertSame('Test Composer', $timeline->last()['description']);
        $this->assertCount(1, $timeline->filter(function ($event) { return isset($event['id']); }));
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
        $this->assertCount(8, $years);
        $this->assertSame(1685, $years->first());
        $this->assertSame(1750, $years->last());
        $this->assertSame('birth', $timeline->first()['composer_milestone']);
        $this->assertSame('death', $timeline->last()['composer_milestone']);
        $this->assertCount(6, $timeline->filter(function ($event) { return isset($event['id']); }));
        $this->assertCount(6, $timeline->filter(function ($event) { return isset($event['id']); })->pluck('year')->unique());
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
        $timeline = $this->timeline();
        $this->assertEqualsCanonicalizing($inside, $timeline->filter(function ($event) { return isset($event['id']); })->pluck('id')->all());
        $this->assertSame('1800-03-21', $timeline->first()['event_date']);
        $this->assertSame('1800-07-28', $timeline->last()['event_date']);
        $this->assertEmpty(array_intersect($outside, $this->timeline()->pluck('id')->all()));
    }

    public function test_missing_death_uses_following_decades_and_never_future_events()
    {
        $this->travelTo(\Carbon\Carbon::parse('2026-10-02'));
        $this->undatedPiece('1700-01-01', null);
        foreach ([1699, 1700, 1705, 1710, 1715, 1720, 1730, 1740, 1750, 1751, 1780, 2026] as $year) $this->event($year);
        $timeline = $this->timeline();
        $this->assertCount(8, $timeline);
        $this->assertSame(1700, $timeline->first()['year']);
        $this->assertSame(1750, $timeline->last()['year']);
        $this->assertFalse($timeline->contains('year', 1699));
        $this->assertFalse($timeline->contains('year', 1751));
        TimelineEvent::query()->delete();
        $this->undatedPiece('2000-03-21', null);
        $past = $this->event(2026, '2026-10-02');
        $this->event(2026, '2026-10-03');
        $this->event(2027); $this->event(1999);
        $this->assertSame('birth', $this->timeline()->first()['composer_milestone']);
        $this->assertSame([$past->id], $this->timeline()->filter(function ($event) { return isset($event['id']); })->pluck('id')->all());
        $this->assertFalse($this->timeline()->contains('composer_milestone', 'death'));
        $this->travelBack();
    }

    public function test_death_only_uses_previous_fifty_years_and_missing_dates_hide_the_tab()
    {
        $this->undatedPiece(null, '1800-07-28');
        foreach ([1749, 1750, 1760, 1770, 1780, 1790, 1800, 1801] as $year) $this->event($year);
        $timeline = $this->timeline();
        $this->assertSame(1750, $timeline->first()['year']);
        $this->assertSame('death', $timeline->last()['composer_milestone']);
        $this->assertFalse($timeline->contains('year', 1749));
        $this->assertFalse($timeline->contains('year', 1801));
        $this->assertFalse($timeline->contains('composer_milestone', 'birth'));
        $period = (new WebTimeline)->periodForPiece($this->piece);
        $this->assertSame([1750, 1800], [$period['start_year'], $period['end_year']]);
        $this->get(route('webapp.pieces.show', $this->piece))->assertOk()->assertSee("Test Composer's world", false)->assertSee('1750–1800');
        $this->piece->composer->update(['date_of_death' => null]);
        $this->piece->update(['composed_in' => 1770]);
        $this->assertCount(0, $this->timeline());
        $this->get(route('webapp.pieces.show', $this->piece))->assertOk()->assertDontSee('href="#tab-timeline"', false)->assertDontSee('Historical timeline');
        $this->piece->composer->update(['date_of_birth' => '1800-01-01', 'date_of_death' => '1780-01-01']);
        $this->assertCount(0, $this->timeline());
    }

    public function test_empty_library_still_shows_composer_boundaries_and_known_piece_dates()
    {
        $this->assertCount(4, $this->timeline());
        $this->assertSame('Test Composer was born', $this->timeline()->first()['title']);
        $this->assertSame('Test Composer died', $this->timeline()->last()['title']);
        $this->undatedPiece('1700-01-01', '1780-01-01');
        $this->assertCount(2, $this->timeline());
    }

    public function test_selected_content_is_live_curated_shared_content_without_wikimedia_or_mobile_changes()
    {
        $mobile = Timeline::for($this->piece->id, 4);
        $event = $this->event(1799, null, '<script>Shared curated event</script>');
        $this->event(1801);
        $url = route('webapp.pieces.timeline', $this->piece);
        $this->get($url)->assertOk()->assertSee('&lt;script&gt;Shared curated event&lt;/script&gt;', false)->assertSee('This piece');
        $this->get(route('webapp.pieces.show', $this->piece))->assertOk()->assertSee('Shared curated event');
        $other = Model::withoutEvents(function () { return create(Piece::class, ['composed_in' => 1800, 'published_in' => 1830, 'composer_id' => $this->piece->composer_id]); });
        $this->assertSame($this->timeline()->filter(function ($event) { return isset($event['id']); })->pluck('id')->all(),
            (new WebTimeline)->forPiece($other)->filter(function ($event) { return isset($event['id']); })->pluck('id')->all());
        $event->update(['title' => 'Updated curated title']);
        $this->get($url)->assertOk()->assertSee('Updated curated title')->assertDontSee('Shared curated event');
        $event->delete();
        $this->get($url)->assertOk()->assertDontSee('Updated curated title');
        $this->assertSame($mobile, (new \App\Http\Controllers\Api\PiecesController)->timeline($this->piece->id));
        $this->assertArrayNotHasKey('timeline_events', $this->piece->getAttributes());
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
        $this->assertCount(4, $queries[1]['bindings']);
        $this->assertCount(8, $timeline);
    }

    public function test_piece_event_shows_composer_age_only_with_usable_lifetime_dates()
    {
        $composer = $this->piece->composer;
        $composer->update(['name' => 'Johann Sebastian Bach', 'date_of_birth' => '1685-03-21', 'date_of_death' => '1750-07-28']);
        $this->piece->update(['composed_in' => 1727, 'published_in' => null]);
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

    public function test_lifetime_markers_render_first_and_last_without_a_piece_highlight_or_mobile_changes()
    {
        $this->undatedPiece('1685-03-21', '1750-07-28');
        $this->piece->composer->update(['name' => 'Johann Sebastian Bach']);
        $this->event(1685); $this->event(1727, null, 'Curated musical context'); $this->event(1750);
        $mobile = (new \App\Http\Controllers\Api\PiecesController)->timeline($this->piece->id);
        $this->get(route('webapp.pieces.timeline', $this->piece))->assertOk()
            ->assertSeeInOrder(['Johann Sebastian Bach was born', 'Curated musical context', 'Johann Sebastian Bach died'])
            ->assertSee('Beginning of the composer’s lifetime.')->assertSee('End of the composer’s lifetime.')
            ->assertDontSee('This piece');
        $this->get(route('webapp.pieces.show', $this->piece))->assertOk()
            ->assertSee('Johann Sebastian Bach was born')->assertSee('Johann Sebastian Bach died');
        $this->assertSame($mobile, (new \App\Http\Controllers\Api\PiecesController)->timeline($this->piece->id));
        $this->assertDatabaseCount('timeline_events', 3);
        foreach (['composed_in', 'published_in'] as $field) {
            $this->piece->update([$field => 1727]);
            $this->assertTrue($this->timeline()->contains('composer_milestone', 'birth'));
            $this->assertTrue($this->timeline()->contains('composer_milestone', 'death'));
            $this->assertCount(1, $this->timeline()->where('highlight', true));
            $this->piece->update([$field => null]);
        }
        Http::assertNothingSent();
    }

    public function test_every_rendered_event_alternates_including_death_and_piece_milestones()
    {
        foreach ([[1, null, null], [2, null, null], [1, 1800, null], [2, 1800, 1810]] as [$count, $composed, $published]) {
            TimelineEvent::query()->delete();
            for ($index = 0; $index < $count; $index++) $this->event(1790 + $index);
            $this->piece->update(['composed_in' => $composed, 'published_in' => $published]);
            $timeline = $this->timeline();
            $html = view('webapp.piece.components.timeline', [
                'piece' => $this->piece, 'timeline' => $timeline,
                'timelinePeriod' => (new WebTimeline)->periodForPiece($this->piece),
            ])->render();
            $dom = new \DOMDocument;
            @$dom->loadHTML($html);
            $articles = $dom->getElementsByTagName('article');
            $this->assertSame($timeline->count(), $articles->length);
            $previousLeft = null;
            foreach ($articles as $article) {
                $left = strpos($article->getAttribute('class'), 'piece-timeline-left') !== false;
                if ($previousLeft !== null) $this->assertNotSame($previousLeft, $left, 'Adjacent entries must alternate, including milestones.');
                $previousLeft = $left;
            }
            $this->assertSame('death', $timeline->last()['composer_milestone']);
        }
    }

    public function test_unknown_death_adds_only_birth_and_invalid_exact_dates_add_no_markers()
    {
        $this->undatedPiece('1800-03-21', null);
        $this->assertCount(1, $this->timeline());
        $this->assertSame('birth', $this->timeline()->first()['composer_milestone']);
        foreach (range(1801, 1812) as $year) $this->event($year);
        $this->assertCount(8, $this->timeline());
        $this->assertCount(7, $this->timeline()->filter(function ($event) { return isset($event['id']); }));
        $this->assertFalse($this->timeline()->contains('composer_milestone', 'death'));
        $this->piece->composer->update(['date_of_death' => '1800-03-20']);
        $this->assertCount(0, $this->timeline());
        $this->piece->composer->update(['date_of_birth' => now()->addYear()->toDateString(), 'date_of_death' => null]);
        $this->assertCount(0, $this->timeline());
        Http::assertNothingSent();
    }
}
