<?php

namespace Tests\Review;

use App\{Admin, User};
use App\Activity\{Recorder, VisitRemoval};
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\{DB, Http, Mail};
use Illuminate\Support\Str;

class VisitRemovalTest extends ReviewTestCase
{
    private function account(array $attributes = [])
    {
        return Model::withoutEvents(function () use ($attributes) { return create(User::class, $attributes); });
    }

    private function visit($user = null, $visitor = null)
    {
        $id = (string) Str::uuid();
        DB::table('activity_visits')->insert(['id' => $id, 'user_id' => optional($user)->id, 'visitor_id' => $visitor ?: (string) Str::uuid(), 'platform' => 'webapp', 'started_at' => now(), 'last_activity_at' => now()]);
        return $id;
    }

    private function page($visit)
    {
        $row = DB::table('activity_visits')->where('id', $visit)->first();
        $id = (string) Str::uuid();
        DB::table('activity_pages')->insert(['id' => $id, 'visit_id' => $visit, 'user_id' => $row->user_id, 'visitor_id' => $row->visitor_id, 'platform' => 'webapp', 'url' => '/discover', 'opened_at' => now()]);
        return $id;
    }

    private function event($visit, $page = null)
    {
        return DB::table('activity_events')->insertGetId(['event_id' => (string) Str::uuid(), 'visit_id' => $visit, 'page_id' => $page, 'platform' => 'webapp', 'type' => 'page_view', 'source' => 'server', 'occurred_at' => now(), 'received_at' => now()]);
    }

    public function test_admin_deletes_only_the_selected_visit_and_its_descendants()
    {
        $user = $this->account(); $visitor = (string) Str::uuid();
        $visit = $this->visit(null, $visitor); $page = $this->page($visit);
        DB::table('activity_visit_links')->insert(['visit_id' => $visit, 'user_id' => $user->id, 'reason' => 'login', 'linked_at_utc' => now()]);
        $events = [$this->event($visit, $page), $this->event($visit), $this->event(null, $page)];
        $siblings = [$this->visit(null, $visitor), $this->visit($user, $visitor)];
        $otherPage = $this->page($siblings[1]); $otherEvent = $this->event($siblings[1], $otherPage); $ungrouped = $this->event(null);
        $this->actingAs(create(Admin::class), 'admin');

        $this->delete(route('admin.analytics.users.visits.destroy', $visit), ['visit' => $siblings[1], 'user_id' => $user->id])
            ->assertRedirect(route('admin.analytics.users'))->assertSessionHas('status', 'Visit deleted.');

        $this->assertDatabaseMissing('activity_visits', ['id' => $visit]);
        $this->assertDatabaseMissing('activity_pages', ['id' => $page]);
        $this->assertDatabaseMissing('activity_visit_links', ['visit_id' => $visit]);
        foreach ($events as $id) $this->assertDatabaseMissing('activity_events', compact('id'));
        foreach ($siblings as $id) $this->assertDatabaseHas('activity_visits', compact('id'));
        $this->assertDatabaseHas('activity_pages', ['id' => $otherPage]);
        foreach ([$otherEvent, $ungrouped] as $id) $this->assertDatabaseHas('activity_events', compact('id'));
        $this->assertDatabaseHas('users', ['id' => $user->id]);
        $this->assertDatabaseCount('activity_purged_users', 0);
        $this->withExceptionHandling()->get(route('admin.analytics.users.journey', $visit))->assertNotFound();
        Mail::assertNothingSent(); Mail::assertNothingQueued(); Http::assertNothingSent();
    }

    public function test_an_account_owned_visit_can_also_be_deleted_without_removing_its_account()
    {
        $user = $this->account(); $visit = $this->visit($user); $page = $this->page($visit); $event = $this->event($visit, $page);
        $this->actingAs(create(Admin::class), 'admin')->delete(route('admin.analytics.users.visits.destroy', $visit))->assertRedirect(route('admin.analytics.users'));
        $this->assertDatabaseMissing('activity_visits', ['id' => $visit]);
        $this->assertDatabaseMissing('activity_pages', ['id' => $page]);
        $this->assertDatabaseMissing('activity_events', ['id' => $event]);
        $this->assertDatabaseHas('users', ['id' => $user->id]);
    }

    public function test_delete_requires_the_admin_guard_and_csrf_and_is_limited_to_the_admin_host()
    {
        $visit = $this->visit(); $url = route('admin.analytics.users.visits.destroy', $visit);
        $this->withExceptionHandling()->deleteJson($url)->assertUnauthorized();
        $this->actingAs($this->account(), 'web')->deleteJson($url)->assertUnauthorized();
        $this->actingAs(create(Admin::class), 'admin');
        $this->deleteJson(str_replace('admin.', 'my.', $url))->assertRedirect();
        $this->assertDatabaseHas('activity_visits', ['id' => $visit]);
        $this->app['env'] = 'review-csrf';
        try {
            $this->withSession(['_token' => 'review-visit-delete'])->deleteJson($url)->assertStatus(419);
            $this->withSession(['_token' => 'review-visit-delete'])->post($url, ['_token' => 'review-visit-delete', '_method' => 'DELETE'])->assertRedirect(route('admin.analytics.users'));
        } finally {
            $this->app['env'] = 'testing';
        }
        $this->assertDatabaseMissing('activity_visits', ['id' => $visit]);
    }

    public function test_missing_malformed_and_excluded_visits_return_404()
    {
        $excluded = $this->account(['email' => 'arthurvillar@gmail.com']); $visit = $this->visit($excluded);
        $this->actingAs(create(Admin::class), 'admin')->withExceptionHandling();
        foreach ([$visit, (string) Str::uuid(), 'not-a-uuid'] as $id) $this->delete(route('admin.analytics.users.visits.destroy', $id))->assertNotFound();
        $this->assertDatabaseHas('activity_visits', ['id' => $visit]);
    }

    public function test_a_failed_deletion_rolls_back_all_removed_activity_and_links()
    {
        $user = $this->account(); $visit = $this->visit($user); $page = $this->page($visit); $event = $this->event($visit, $page);
        DB::table('activity_visit_links')->insert(['visit_id' => $visit, 'user_id' => $user->id, 'reason' => 'login', 'linked_at_utc' => now()]);
        DB::statement("CREATE TRIGGER fail_visit_delete BEFORE DELETE ON activity_visits BEGIN SELECT RAISE(ABORT, 'forced visit failure'); END");
        try {
            (new VisitRemoval)->delete($visit);
            $this->fail('The forced database failure must propagate.');
        } catch (\Illuminate\Database\QueryException $exception) {
            $this->assertStringContainsString('forced visit failure', $exception->getMessage());
        }
        $this->assertDatabaseHas('activity_visits', ['id' => $visit]);
        $this->assertDatabaseHas('activity_pages', ['id' => $page]);
        $this->assertDatabaseHas('activity_events', ['id' => $event]);
        $this->assertDatabaseHas('activity_visit_links', ['visit_id' => $visit]);
    }

    public function test_the_journey_has_a_discreet_modal_trigger_and_an_explicit_delete_form()
    {
        $visit = $this->visit();
        $this->actingAs(create(Admin::class), 'admin')->get(route('admin.analytics.users.journey', $visit))->assertOk()
            ->assertSee('aria-label="Delete visit"', false)->assertSee('data-bs-target="#delete-visit-modal"', false)
            ->assertSee('id="delete-visit-modal-title"', false)->assertSee('This cannot be undone.')
            ->assertSee(route('admin.analytics.users.visits.destroy', $visit), false)
            ->assertSee('name="_token"', false)->assertSee('name="_method" value="DELETE"', false);
        $this->assertDatabaseHas('activity_visits', ['id' => $visit]);
    }

    public function test_late_browser_events_do_not_recreate_a_deleted_visit()
    {
        $visit = $this->visit(); $page = $this->page($visit); $storedPage = DB::table('activity_pages')->where('id', $page)->first();
        (new VisitRemoval)->delete($visit);
        (new Recorder)->browser(\Illuminate\Http\Request::create('http://my.'.config('app.short_url').'/activity/events'), $storedPage, [['type' => 'page_checkpoint', 'event_id' => (string) Str::uuid(), 'elapsed_ms' => 10000, 'sequence' => 1, 'metadata' => []]]);
        $this->assertDatabaseCount('activity_visits', 0);
        $this->assertDatabaseCount('activity_pages', 0);
        $this->assertDatabaseCount('activity_events', 0);
    }
}
