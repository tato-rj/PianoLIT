<?php

namespace Tests\Review;

use App\{Admin, FreePick, Piece, User};
use App\Api\Api;
use App\Services\FreePickSchedule;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\{Cache, DB, Mail, Redis, Schema};

class LegacyFreePickPiece extends Piece
{
    protected $table = 'pieces';
    public function getForeignKey() { return 'piece_id'; }
    public function tags() { return $this->belongsToMany(\App\Tag::class, 'piece_tag', 'piece_id', 'tag_id'); }
    public function getIsFreeAttribute($value = null) { return (bool) ($this->attributes['is_free'] ?? false); }
    public function getHighlightedAtAttribute($value) { return $value ? $this->asDateTime($value) : null; }
}

class ScheduleFeedApi extends Api
{
    private function emptyRow($title) { return ['row' => 'gallery', 'type' => 'piece', 'title' => $title, 'content' => []]; }
    public function composers($title) { return $this->emptyRow($title); }
    public function latest($title) { return $this->emptyRow($title); }
    public function women($title) { return $this->emptyRow($title); }
    public function ranking($ranking, $title) { return $this->emptyRow($title); }
    public function ethnicity($ethnicity, $title) { return $this->emptyRow($title); }
    public function tag($title) { return $this->emptyRow($title); }
    public function similar($title, $piece) { return $this->emptyRow($title); }
    public function suggestions($title) { return $this->emptyRow($title); }
}

class FreePickScheduleTest extends ReviewTestCase
{
    private $first, $second;

    public function setUp(): void
    {
        parent::setUp();
        $this->travelTo(carbon('2026-10-20 09:00:00'));
        Redis::shouldReceive('get')->andReturn(null);
        Model::withoutEvents(function () {
            $this->first = create(Piece::class, ['cover_path' => 'covers/first.jpg', 'highlighted_at' => now()->subWeek()]);
            $this->second = create(Piece::class, ['cover_path' => 'covers/second.jpg', 'highlighted_at' => null]);
            foreach (['level' => 'elementary', 'period' => 'classical', 'length' => 'short'] as $type => $name) {
                $tag = create(\App\Tag::class, compact('type', 'name'));
                $this->first->tags()->attach($tag);
                $this->second->tags()->attach($tag);
            }
        });
        $this->actingAs(create(Admin::class, ['role' => 'manager']), 'admin');
    }

    public function test_immediate_and_future_choices_rotate_exactly_at_the_scheduled_time_without_a_worker()
    {
        $this->assertSame(0, Piece::free()->count());
        $this->assertSame(2, Piece::free(false)->count());
        $this->post(route('admin.pieces.schedule', $this->first), ['mode' => 'now'])->assertRedirect(route('admin.pieces.index'));
        $firstKey = Api::freePickCacheKey('app.discover');
        $this->post(route('admin.pieces.schedule', $this->second), ['mode' => 'later', 'scheduled_at' => '2026-10-21T10:30'])->assertSessionHasNoErrors();
        $this->assertTrue($this->first->fresh()->is_free);
        $this->assertFalse($this->second->fresh()->is_free);
        $this->assertNull($this->second->fresh()->highlighted_at);
        $this->assertSame([$this->first->id], Piece::free()->pluck('id')->all());
        $this->assertSame([$this->second->id], Piece::free(false)->pluck('id')->all());
        $this->travelTo(carbon('2026-10-21 10:29:59'));
        $this->assertTrue($this->first->is_free);
        $this->travelTo(carbon('2026-10-21 10:30:00'));
        $this->assertFalse($this->first->is_free);
        $this->assertTrue($this->second->is_free);
        $this->assertSame('2026-10-21 10:30:00', $this->second->fresh()->highlighted_at->toDateTimeString());
        $this->assertNull(DB::table('pieces')->where('id', $this->second->id)->value('highlighted_at'), 'The current JSON timestamp does not need a history write.');
        $this->assertSame([$this->second->id], Piece::free()->pluck('id')->all());
        $this->assertNotSame($firstKey, Api::freePickCacheKey('app.discover'));
        $this->assertNotSame($firstKey, Api::freePickCacheKey('app.discover'.Api::WEB_DISCOVER_CACHE_SUFFIX));
        $this->assertSame(1, app(FreePickSchedule::class)->syncHistory());
        $this->assertSame(0, app(FreePickSchedule::class)->syncHistory());
        $this->assertSame('2026-10-21 10:30:00', $this->second->fresh()->highlighted_at->toDateTimeString());
        Mail::assertNothingSent();
        Mail::assertNothingQueued();
    }

    public function test_scheduling_validates_cover_time_shape_and_conflicting_reservations_without_mutation()
    {
        $this->withExceptionHandling();
        foreach ([[], ['mode' => ['later']], ['mode' => 'bad'], ['mode' => 'later'], ['mode' => 'later', 'scheduled_at' => '2026-10-19T09:00'], ['mode' => 'later', 'scheduled_at' => ['bad']]] as $data) {
            $this->postJson(route('admin.pieces.schedule', $this->first), $data)->assertUnprocessable();
        }
        $this->assertSame(0, FreePick::count());
        $this->first->updateQuietly(['cover_path' => null]);
        $this->postJson(route('admin.pieces.schedule', $this->first), ['mode' => 'now'])->assertUnprocessable();
        $this->assertSame(0, FreePick::count());
        $this->post(route('admin.pieces.schedule', $this->second), ['mode' => 'later', 'scheduled_at' => '2026-10-22T09:00'])->assertSessionHasNoErrors();
        $this->first->updateQuietly(['cover_path' => 'covers/first.jpg']);
        $this->postJson(route('admin.pieces.schedule', $this->first), ['mode' => 'later', 'scheduled_at' => '2026-10-22T09:00'])->assertUnprocessable()->assertJsonValidationErrors('scheduled_at');
        $this->assertSame(1, FreePick::count());
        // The database also prevents a conflicting booking, independently of validation.
        $this->expectException(\Illuminate\Database\QueryException::class);
        FreePick::create(['piece_id' => $this->first->id, 'scheduled_at' => '2026-10-22 13:00:00', 'reserved_at' => '2026-10-22 13:00:00']);
    }

    public function test_failed_history_update_rolls_back_the_immediate_selection()
    {
        app(FreePickSchedule::class)->schedule($this->first);
        DB::unprepared('CREATE TRIGGER reject_freepick_date BEFORE UPDATE OF highlighted_at ON pieces WHEN NEW.id = '.$this->second->id." BEGIN SELECT RAISE(ABORT, 'fixture failure'); END");
        try {
            app(FreePickSchedule::class)->schedule($this->second);
            $this->fail('The failing history update should abort the transaction.');
        } catch (\Illuminate\Database\QueryException $exception) {
            $this->assertSame(1, FreePick::count());
            $this->assertTrue($this->first->is_free);
            $this->assertFalse($this->second->is_free);
        } finally { DB::unprepared('DROP TRIGGER reject_freepick_date'); }
    }

    public function test_admin_schedule_preserves_filters_uses_route_identity_and_restores_validation_modal()
    {
        $parameters = ['piece' => $this->first, 'search' => $this->first->name, 'sort' => 'id_asc'];
        $this->post(route('admin.pieces.schedule', $parameters), ['mode' => 'now', 'piece_id' => $this->second->id])
            ->assertRedirect(route('admin.pieces.index', array_diff_key($parameters, ['piece' => true])));
        $this->assertTrue($this->first->is_free);
        $this->withExceptionHandling();
        $this->from(route('admin.pieces.index'))->post(route('admin.pieces.schedule', $this->first), ['mode' => 'later', 'scheduled_at' => '2026-10-19T12:00'])
            ->assertRedirect(route('admin.pieces.index'))->assertSessionHasErrors('scheduled_at', null, 'freepick');
        $this->get(route('admin.pieces.index'))->assertOk()->assertSee('data-restore="true"', false)->assertSee('value="2026-10-19T12:00"', false);
        auth('admin')->logout();
        $this->post(route('admin.pieces.schedule', $this->first), ['mode' => 'now'])->assertRedirect(route('admin.login.show'));
    }

    public function test_migration_preserves_legacy_ios_piece_json_and_explicit_current_selection()
    {
        app(FreePickSchedule::class)->schedule($this->first);
        $migration = new \CreateFreePicksTable;
        $migration->down();
        // A newer historical date must not replace the old explicitly selected pick.
        DB::table('pieces')->where('id', $this->second->id)->update(['highlighted_at' => now()->addDay()]);
        $before = LegacyFreePickPiece::with(['tags', 'favorites', 'tutorials'])->find($this->first->id)->toArray();
        $migration->up();
        app(FreePickSchedule::class)->forgetCurrent();
        $after = Piece::with(['tags', 'favorites', 'tutorials'])->find($this->first->id)->toArray();
        $this->assertEquals($before, $after, 'All existing mobile piece keys, values, types, and relationships are retained.');
        $this->assertTrue($after['is_free']);
        $this->assertFalse($this->second->fresh()->toArray()['is_free']);
        $this->assertFalse(Schema::hasColumn('pieces', 'is_free'));
        foreach (['free_picks', 'scheduled_at', 'reserved_at', 'activated_at', 'retired_at'] as $key) $this->assertArrayNotHasKey($key, $after);
        $this->assertSame(2, FreePick::count(), 'Migration retains the existing selections without inventing a duplicate cutover selection.');
        $this->assertSame([$this->first->id], Piece::free()->pluck('id')->all());
        $migration->down();
        $this->assertTrue((bool) DB::table('pieces')->where('id', $this->first->id)->value('is_free'));
        $migration->up();
    }

    public function test_existing_ios_freepick_row_and_history_pagination_keep_the_same_contract()
    {
        app(FreePickSchedule::class)->schedule($this->first);
        $row = (new Api)->for('app')->order(0)->free('Free weekly pick');
        $this->assertSame(['row', 'type', 'title', 'content'], array_keys($row));
        $this->assertSame('freepick', $row['row']);
        $this->assertSame('piece', $row['type']);
        $this->assertCount(1, $row['content']);
        $this->assertSame($this->first->id, $row['content'][0]->id);
        $this->assertTrue($row['content'][0]->toArray()['is_free']);
        $this->assertSame(route('api.pieces.find'), $row['content'][0]->toArray()['source']);
        $controller = new \App\Http\Controllers\Api\PiecesController;
        $page = $controller->freepicks(request());
        $this->assertSame(5, $page->perPage());
        $this->assertSame([$this->first->id], $page->pluck('id')->all());
        $this->assertEqualsCanonicalizing(['current_page', 'data', 'first_page_url', 'from', 'last_page', 'last_page_url', 'links', 'next_page_url', 'path', 'per_page', 'prev_page_url', 'to', 'total'], array_keys($page->toArray()));
        $this->patch(route('admin.pieces.highlight', $this->second))->assertRedirect();
        $this->assertTrue($this->second->is_free, 'The existing named admin route delegates to the new system.');
    }

    public function test_cached_mobile_and_web_discover_feeds_switch_to_the_due_piece()
    {
        app(FreePickSchedule::class)->schedule($this->first);
        app(FreePickSchedule::class)->schedule($this->second, now()->addDay());
        $api = new ScheduleFeedApi;
        $before = [];
        foreach (['app', 'webapp'] as $platform) {
            $row = $api->for($platform)->discover()->first();
            $this->assertSame($this->first->id, $row['content'][0]->id);
            $before[$platform] = array_keys($row['content'][0]->toArray());
        }
        $this->travel(1)->days();
        foreach (['app', 'webapp'] as $platform) {
            $row = $api->for($platform)->discover()->first();
            $this->assertSame($this->second->id, $row['content'][0]->id);
            $this->assertEqualsCanonicalizing($before[$platform], array_keys($row['content'][0]->toArray()));
            $this->assertTrue($row['content'][0]->is_free);
        }
    }

    public function test_utc_storage_keeps_the_selection_stable_across_the_daylight_saving_clock_change()
    {
        $at = \Carbon\Carbon::parse('2026-11-01 01:30:00', 'America/New_York');
        $pick = app(FreePickSchedule::class)->schedule($this->second, $at);
        $this->assertSame($at->copy()->utc()->toDateTimeString(), DB::table('free_picks')->where('id', $pick->id)->value('scheduled_at'));
        $this->travelTo($at->copy()->subSecond());
        $this->assertFalse($this->second->is_free);
        $this->travelTo($at);
        $this->assertTrue($this->second->is_free);
        $this->travelTo(\Carbon\Carbon::parse('2026-11-01 06:00:00', 'UTC'));
        $this->assertTrue($this->second->is_free, 'Turning the local clock back does not restore the previous pick.');
    }

    public function test_current_lookup_is_reused_across_clock_ticks_and_rechecks_the_next_due_boundary()
    {
        app(FreePickSchedule::class)->schedule($this->first);
        app(FreePickSchedule::class)->schedule($this->second, now()->addSeconds(2));
        DB::enableQueryLog();
        DB::flushQueryLog();
        $ids = [app(FreePickSchedule::class)->current()['piece_id']];
        $this->travel(1)->seconds();
        $ids[] = app(FreePickSchedule::class)->current()['piece_id'];
        $this->travel(1)->seconds();
        $ids[] = app(FreePickSchedule::class)->current()['piece_id'];
        $queries = DB::getQueryLog();
        DB::disableQueryLog();
        $this->assertSame([$this->first->id, $this->first->id, $this->second->id], $ids);
        $this->assertCount(2, $queries, 'One initial lookup and one boundary refresh; no query for ordinary clock ticks.');
    }
}
