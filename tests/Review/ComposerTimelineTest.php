<?php

namespace Tests\Review;

use App\{Composer, Piece, TimelineEvent};
use App\Services\Timeline\WebTimeline;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\{DB, Http, Redis};

class ComposerTimelineTest extends ReviewTestCase
{
    public function setUp(): void
    {
        parent::setUp();
        Redis::shouldReceive('get')->andReturn(null);
        $this->withoutMiddleware([
            \App\Http\Middleware\Logs\RecordWebAppLog::class,
            \App\Http\Middleware\UpdateLocation::class,
        ]);
        $this->travelTo(\Carbon\Carbon::parse('2026-10-12'));
    }

    protected function composer($birth = '1800-03-21', $death = '1840-07-28')
    {
        return Model::withoutEvents(function () use ($birth, $death) {
            return create(Composer::class, [
                'name' => 'Timeline Composer', 'date_of_birth' => $birth, 'date_of_death' => $death,
                'biography' => 'The existing composer biography.', 'curiosity' => 'The existing composer curiosity.',
                'country_id' => null,
            ]);
        });
    }

    protected function event($year, $date = null, $attributes = [])
    {
        $identity = TimelineEvent::count() + 1;
        return TimelineEvent::create(array_merge([
            'year' => $year, 'event_date' => $date, 'title' => 'Historical event '.$identity,
            'description' => 'Curated historical context.', 'source_id' => 'Q'.$identity.':created:'.$year,
            'wikidata_id' => 'Q'.$identity, 'event_kind' => 'created',
            'source_url' => 'https://en.wikipedia.org/wiki/Event_'.$identity,
            'attribution' => 'Wikipedia contributors (CC BY-SA 4.0).',
        ], $attributes));
    }

    public function test_profile_shows_the_shared_timeline_below_current_content_without_piece_milestones()
    {
        $composer = $this->composer();
        Model::withoutEvents(function () use ($composer) {
            create(Piece::class, ['composer_id' => $composer->id, 'composed_in' => 1810, 'published_in' => 1850]);
            $peer = create(Composer::class, ['name' => 'Contemporary peer', 'date_of_birth' => '1801-01-01', 'date_of_death' => null, 'country_id' => null]);
            create(Piece::class, ['composer_id' => $peer->id]);
        });
        $this->event(1799, null, ['title' => 'Before the lifetime']);
        $this->event(1800, '1800-03-20', ['title' => 'Before the birthday']);
        $first = $this->event(1800, '1800-03-21');
        $artwork = $this->event(1820, null, [
            'title' => 'Curated <artwork> & context',
            'image_url' => 'https://upload.wikimedia.org/timeline-example.jpg',
            'image_source_url' => 'https://commons.wikimedia.org/wiki/File:Example.jpg',
            'image_credit' => 'Example artist', 'image_license' => 'Public domain',
        ]);
        $last = $this->event(1840, '1840-07-28');
        $approximate = $this->event(1840);
        $this->event(1840, '1840-07-29', ['title' => 'After the death date']);
        $this->event(1850, null, ['title' => 'After the lifetime']);

        $response = $this->get(route('webapp.composers.show', $composer));
        $response->assertOk()->assertViewHas('timeline', function ($events) use ($first, $artwork, $last, $approximate) {
            return $events->where('highlight', true)->isEmpty()
                && $events->pluck('id')->filter()->sort()->values()->all() === collect([$first->id, $artwork->id, $last->id, $approximate->id])->sort()->values()->all();
        })->assertSeeInOrder([
            'The existing composer curiosity.', 'The existing composer biography.', 'Contemporary peer',
            'id="composer-timeline-title"', 'Timeline Composer was born', 'Curated &lt;artwork&gt; &amp; context', 'Timeline Composer died',
        ], false)->assertSee('Sources &amp; credits', false)
            ->assertSee($artwork->image_url, false)->assertSee($artwork->source_url, false)
            ->assertSee($artwork->image_source_url, false)->assertSee('Public domain')
            ->assertDontSee('This piece')->assertDontSee('was composed')->assertDontSee('was published')
            ->assertDontSee('Before the lifetime')->assertDontSee('Before the birthday')
            ->assertDontSee('After the death date')->assertDontSee('After the lifetime')
            ->assertDontSee('tab-timeline');
        $this->assertGuest('web');
        Http::assertNothingSent();
    }

    public function test_composer_without_pieces_uses_stable_spread_and_two_bulk_library_reads()
    {
        $composer = $this->composer('1750-03-21', '1840-07-28');
        for ($index = 0; $index < 30; $index++) $this->event(1750);
        for ($year = 1755; $year <= 1840; $year += 5) $this->event($year);

        DB::enableQueryLog();
        DB::flushQueryLog();
        $timeline = (new WebTimeline)->forComposer($composer);
        $queries = DB::getQueryLog();
        DB::disableQueryLog();
        $this->assertCount(2, $queries);
        $this->assertStringContainsString('select "id", "year", "event_date"', $queries[0]['query']);
        $this->assertCount(8, $queries[1]['bindings']);
        $this->assertCount(10, $timeline);
        $this->assertCount(8, $timeline->pluck('id')->filter());
        $this->assertCount(8, $timeline->filter(function ($event) { return isset($event['id']); })->pluck('year')->unique());
        $this->assertSame('birth', $timeline->first()['composer_milestone']);
        $this->assertSame('death', $timeline->last()['composer_milestone']);
        $this->assertSame($timeline->all(), (new WebTimeline)->forComposer($composer)->all());
        $pieceWithoutDates = (new Piece)->setRelation('composer', $composer);
        $this->assertSame($timeline->all(), (new WebTimeline)->forPiece($pieceWithoutDates)->all());
        $this->get(route('webapp.composers.show', $composer))->assertOk()->assertSee('Historical timeline');
        $this->assertDatabaseCount('pieces', 0);
        Http::assertNothingSent();
    }

    public function test_partial_lifetimes_reuse_piece_bounds_and_exclude_future_events()
    {
        $composer = $this->composer('1700-01-01', null);
        foreach ([1699, 1700, 1725, 1750, 1751, 1999, 2000, 2027] as $year) $this->event($year);
        $past = $this->event(2026, '2026-10-12');
        $this->event(2026, '2026-10-13');
        $service = new WebTimeline;
        $this->assertSame([1700, 1700, 1725, 1750], $service->forComposer($composer)->pluck('year')->all());
        $composer->update(['date_of_birth' => null, 'date_of_death' => '1750-07-28']);
        $this->assertSame([1700, 1725, 1750, 1750], $service->forComposer($composer)->pluck('year')->all());
        $composer->update(['date_of_birth' => '2000-03-21', 'date_of_death' => null]);
        $timeline = $service->forComposer($composer);
        $this->assertSame([2000, 2000, 2026], $timeline->pluck('year')->all());
        $this->assertSame($past->id, $timeline->last()['id']);
        $this->assertFalse($timeline->contains('composer_milestone', 'death'));
    }

    public function test_empty_library_keeps_lifetime_markers_and_unusable_dates_hide_the_section()
    {
        $composer = $this->composer();
        $url = route('webapp.composers.show', $composer);
        $this->get($url)->assertOk()->assertSeeInOrder(['Timeline Composer was born', 'Timeline Composer died']);
        foreach ([[null, null], ['1840-07-29', '1840-07-28'], ['2027-01-01', null], [null, '2027-01-01']] as [$birth, $death]) {
            $composer->update(['date_of_birth' => $birth, 'date_of_death' => $death]);
            $this->get($url)->assertOk()->assertDontSee('id="composer-timeline-title"', false)->assertDontSee('Historical timeline')
                ->assertSee('The existing composer biography.')->assertSee('Discover pieces');
        }
        Http::assertNothingSent();
    }
}
