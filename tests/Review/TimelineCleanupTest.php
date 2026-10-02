<?php

namespace Tests\Review;

use App\{Admin, Piece, Timeline, TimelineEvent};
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\{DB, Http, Schema};

class TimelineCleanupTest extends ReviewTestCase
{
    public function test_fresh_schema_and_admin_routes_only_expose_shared_curation()
    {
        $this->assertFalse(Schema::hasTable('piece_timeline_events'));
        $this->assertTrue(Schema::hasTable('timeline_events'));
        $this->assertTrue(Schema::hasTable('timelines'));
        $routes = app('router')->getRoutes();
        $this->assertNotNull($routes->getByName('admin.timeline-events.index'));
        $obsoleteRoutes = [];
        foreach ($routes as $route) {
            if (strpos((string) $route->getName(), 'admin.pieces.timeline.') === 0) $obsoleteRoutes[] = $route->getName();
        }
        $this->assertEmpty($obsoleteRoutes);
        $this->assertNotNull($routes->getByName('api.pieces.timeline'));
        $this->assertNotNull($routes->getByName('webapp.pieces.timeline'));
        $this->assertFalse(method_exists(Piece::class, 'timelineEvents'));
        Http::assertNothingSent();
    }

    public function test_upgrade_drops_only_obsolete_content_and_rollback_restores_empty_schema()
    {
        $piece = Model::withoutEvents(function () { return create(Piece::class, ['composed_in' => 1800]); });
        $shared = TimelineEvent::create([
            'year' => 1800, 'title' => 'Keep shared content', 'description' => 'Curated description',
            'source_id' => 'Q1:work:1800', 'wikidata_id' => 'Q1', 'event_kind' => 'created',
            'source_url' => 'https://en.wikipedia.org/wiki/Event', 'attribution' => 'Wikipedia contributors',
        ]);
        $mobile = Timeline::create(['creator_id' => create(Admin::class)->id, 'year' => 1800,
            'type' => 'history', 'event' => 'Keep legacy mobile content']);
        $mobileBefore = Timeline::for($piece->id, 4);
        require_once database_path('migrations/2026_10_02_130000_drop_piece_timeline_events_table.php');
        $migration = new \DropPieceTimelineEventsTable;
        $migration->down(); // Simulate an already-migrated installation with old records.
        $old = $shared->getAttributes();
        unset($old['source_identity']);
        DB::table('piece_timeline_events')->insert($old + ['piece_id' => $piece->id]);
        $this->assertDatabaseCount('piece_timeline_events', 1);
        $migration->up();
        $migration->up(); // Safe when the obsolete table was already removed.
        $this->assertFalse(Schema::hasTable('piece_timeline_events'));
        $this->assertSame('Keep shared content', $shared->fresh()->title);
        $this->assertSame('Keep legacy mobile content', $mobile->fresh()->event);
        $this->assertSame($mobileBefore, Timeline::for($piece->id, 4));
        $migration->down();
        $migration->down();
        $this->assertDatabaseCount('piece_timeline_events', 0);
        $this->assertDatabaseCount('timeline_events', 1);
        $this->assertDatabaseCount('timelines', 1);
        $this->assertTrue(Schema::hasColumn('piece_timeline_events', 'piece_id'));
        $migration->up();
        Http::assertNothingSent();
    }
}
