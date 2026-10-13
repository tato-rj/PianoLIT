<?php

namespace Tests\Review;

use App\{Admin, User, Piece};
use App\Activity\{Actions, Filters, Insights, Journey, People, Queries};
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\{Auth, Crypt, DB, Route};
use Illuminate\Support\Str;

class UserAnalyticsTest extends ReviewTestCase
{
    public function setUp(): void
    {
        parent::setUp();
        // Keep legacy persisted-history coverage alongside the default-gate suite.
        config(['activity.minimum_visit_seconds' => 0]);
        $this->travelTo(carbon('2026-10-12 19:30:00'));
        Route::middleware(['web', \App\Http\Middleware\Logs\RecordWebLog::class])->group(function () {
            Route::get('/review/journey', function () { return response('<html>Home</html>'); })->name('review.journey');
            Route::post('/review/journey-login/{user}', function (User $user) { Auth::guard('web')->login($user); return response()->noContent(); })->name('review.journey-login');
        });
    }

    private function account(array $attributes = [])
    {
        return Model::withoutEvents(function () use ($attributes) { return create(User::class, $attributes)->setAppends(['full_name']); });
    }

    private function visit($user = null, $at = '2026-10-12 19:14:00', $platform = 'webapp', $visitor = null)
    {
        $id = (string) Str::uuid();
        DB::table('activity_visits')->insert(['id' => $id, 'user_id' => optional($user)->id, 'visitor_id' => $visitor ?: (string) Str::uuid(), 'platform' => $platform, 'started_at' => $at, 'last_activity_at' => carbon($at)->addMinutes(8)]);
        return $id;
    }

    private function page($visit, $route = 'webapp.discover', $at = '2026-10-12 19:14:00')
    {
        $v = DB::table('activity_visits')->where('id', $visit)->first();
        $id = (string) Str::uuid();
        DB::table('activity_pages')->insert(['id' => $id, 'visit_id' => $visit, 'user_id' => $v->user_id, 'visitor_id' => $v->visitor_id, 'platform' => $v->platform, 'route_name' => $route, 'url' => 'https://my.pianolit.test/'.str_replace('.', '/', $route), 'opened_at' => $at, 'elapsed_ms' => 15000, 'visible_ms' => 12000]);
        return $id;
    }

    private function event($visit, $page, $type, $at = '2026-10-12 19:14:01', array $metadata = [], $piece = null)
    {
        $v = DB::table('activity_visits')->where('id', $visit)->first();
        return DB::table('activity_events')->insertGetId(['event_id' => (string) Str::uuid(), 'visit_id' => $visit, 'page_id' => $page, 'user_id' => $v->user_id, 'visitor_id' => $v->visitor_id, 'platform' => $v->platform, 'type' => $type, 'source' => in_array($type, ['page_view', 'favorite_changed', 'annotation_saved'], true) ? 'server' : 'browser', 'occurred_at' => $at, 'received_at' => '2026-10-12 19:29:00', 'resource_type' => $piece ? 'piece' : null, 'resource_id' => $piece ? $piece->id : null, 'metadata' => json_encode($metadata)]);
    }

    private function filters(array $values = [])
    {
        return new Filters(\Illuminate\Http\Request::create('/analytics/users', 'GET', $values));
    }

    public function test_activity_people_insights_and_all_profile_tabs_render_real_records()
    {
        $user = $this->account(['first_name' => 'Maria', 'last_name' => 'Rossi']);
        $piece = Model::withoutEvents(function () { return create(Piece::class, ['name' => 'Clair de lune']); });
        $visit = $this->visit($user); $page = $this->page($visit, 'webapp.pieces.show');
        $this->event($visit, $page, 'page_view', '2026-10-12 19:14:00', [], $piece);
        $this->event($visit, $page, 'media_progress', '2026-10-12 19:15:00', ['media_kind' => 'video', 'playback_id' => (string) Str::uuid(), 'unique_watched_percent' => 80, 'played_seconds' => 144, 'piece_id' => $piece->id]);
        $this->actingAs(create(Admin::class), 'admin');
        $this->get(route('admin.analytics.users.people'))->assertOk();
        foreach (['admin.analytics.users', 'admin.analytics.users.insights'] as $route) {
            foreach ([null => 'registered', 'all' => 'all', 'anonymous' => 'anonymous'] as $audience => $expected) {
                $this->get(route($route, $audience ? ['audience' => $audience] : []))->assertOk()
                    ->assertViewHas('filters', function ($filters) use ($expected) { return $filters->values['audience'] === $expected; });
            }
        }
        foreach (['overview', 'visits', 'music', 'account'] as $tab) $this->get(route('admin.analytics.users.profile', ['user' => $user->id, 'tab' => $tab]))->assertOk()->assertSee('Maria Rossi');
        $this->get(route('admin.analytics.users.journey', $visit))->assertOk()->assertSee('Clair de lune')->assertSee('80% unique content played')->assertSee('America/New_York')->assertDontSee('closed the browser');
        $this->assertDatabaseCount('activity_imports', 0);
    }

    public function test_confirmed_login_links_only_current_anonymous_visit_without_rewriting_history()
    {
        $user = $this->account();
        $this->get('/review/journey')->assertOk();
        $anonymous = DB::table('activity_visits')->first();
        $unrelated = $this->visit(null, '2026-10-12 18:00:00');
        $this->post('/review/journey-login/'.$user->id)->assertNoContent();
        $this->get('/review/journey')->assertOk();
        $this->assertDatabaseHas('activity_visit_links', ['visit_id' => $anonymous->id, 'user_id' => $user->id]);
        $this->assertDatabaseMissing('activity_visit_links', ['visit_id' => $unrelated]);
        $this->assertDatabaseHas('activity_visits', ['id' => $anonymous->id, 'user_id' => null]);
        $q = new Queries;
        $this->assertSame(2, $q->visits(null, $user->id)->count());
        $metrics = $q->metrics($this->filters(['audience' => 'all']));
        $this->assertSame(2, (int) $metrics['Unique visitors'], 'Linked anonymous/account visits count once; unrelated visitor remains distinct.');
        $this->assertDatabaseHas('activity_events', ['type' => 'login', 'user_id' => $user->id]);
    }

    public function test_filters_and_returning_counts_do_not_include_legacy_daily_totals()
    {
        $user = $this->account();
        $this->visit($user, '2026-10-10 10:00:00');
        $this->visit($user, '2026-10-12 10:00:00');
        $this->visit(null, '2026-10-12 10:00:00', 'web');
        $this->visit(null, '2026-10-11 10:00:00', 'app');
        $q = new Queries;
        $default = $q->metrics($this->filters());
        $this->assertSame(2, (int) $default['Visits']);
        $this->assertSame(1, (int) $default['Unique visitors']);
        $this->assertSame(4, (int) $q->metrics($this->filters(['audience' => 'all']))['Visits']);
        $this->assertSame(2, (int) $q->metrics($this->filters(['audience' => 'anonymous']))['Visits']);
        $metrics = $q->metrics($this->filters(['range' => 'today', 'platform' => 'webapp', 'audience' => 'registered']));
        $this->assertSame(1, (int) $metrics['Unique visitors']);
        $this->assertSame(1, (int) $metrics['Visits']);
        $this->assertSame(1, (int) $metrics['Returning visitors']);
        $this->assertSame(1, $q->visits($this->filters(['range' => 'yesterday', 'audience' => 'all']))->count());
        $this->assertSame(1, $q->visits($this->filters(['range' => 'today', 'audience' => 'anonymous']))->count());
    }

    public function test_journey_orders_actions_by_occurrence_not_receipt_and_does_not_invent_page_associations()
    {
        $visit = $this->visit(); $page = $this->page($visit);
        $late = $this->event($visit, $page, 'click', '2026-10-12 19:16:00', ['label' => 'Favorites']);
        $early = $this->event($visit, $page, 'filter_change', '2026-10-12 19:15:00', ['value' => 'Intermediate']);
        $unplaced = $this->event($visit, null, 'score_opened', null);
        $data = (new Journey)->get($visit);
        $this->assertSame([$early, $late], $data['groups'][0]['actions']->pluck('id')->all());
        $this->assertSame([$unplaced], collect($data['unplaced']->items())->pluck('id')->all());
        $this->actingAs(create(Admin::class), 'admin');
        $this->get(route('admin.analytics.users.journey', $visit))->assertOk()->assertSee('Actions without a recorded page')->assertSee('receipt time; occurrence unknown');
    }

    public function test_journey_collapses_duplicate_starts_and_transitions_into_one_measured_playback()
    {
        $visit = $this->visit(); $page = $this->page($visit);
        $piece = Model::withoutEvents(function () { return create(Piece::class, ['name' => 'A sad song']); });
        $metadata = ['media_kind' => 'video', 'media_id' => 'piece-performance', 'piece_id' => $piece->id, 'playback_id' => (string) Str::uuid(), 'duration_seconds' => 88.468027, 'played_seconds' => 0];
        $this->event($visit, $page, 'media_play', '2026-10-12 19:15:00', $metadata);
        $this->event($visit, $page, 'media_play', '2026-10-12 19:15:00', array_merge($metadata, ['played_seconds' => 0.517]));
        $this->event($visit, $page, 'media_pause', '2026-10-12 19:15:01', array_merge($metadata, ['played_seconds' => 0.517, 'unique_watched_percent' => 0.417]));
        $this->event($visit, $page, 'media_seek', '2026-10-12 19:15:02', $metadata);
        $data = (new Journey)->get($visit);
        $this->assertCount(1, $data['groups'][0]['media']);
        $playback = $data['groups'][0]['media']->first();
        $this->assertSame(0.517, $playback['watched'], 'Cumulative observations use the maximum, not the sum or media length.');
        $this->assertSame(88.468027, $playback['duration']);
        $this->assertTrue($playback['seeked']);
        $this->assertCount(0, $data['groups'][0]['actions']);
        $this->actingAs(create(Admin::class), 'admin');
        $response = $this->get(route('admin.analytics.users.journey', $visit))->assertOk()->assertSee('Less than 1s playing')->assertDontSee('Full media length:')->assertSee('A sad song')->assertDontSee('Started performance video');
        $this->assertSame(1, substr_count($response->getContent(), 'Played performance video'));
        foreach (['media_progress', 'media_pause', 'media_end'] as $type) {
            $this->event($visit, $page, $type, '2026-10-12 19:16:29', array_merge($metadata, ['played_seconds' => 89, 'unique_watched_percent' => 100]));
        }
        $response = $this->get(route('admin.analytics.users.journey', $visit))->assertOk()->assertSee('1m 29s playing')->assertSee('100% unique content played')
            ->assertDontSee('Paused performance video')->assertDontSee('Reached the end of performance video')->assertDontSee('Seeking was recorded')
            ->assertSeeInOrder(['class="ua-card ua-summary"', 'class="ua-card ua-identity"'], false)->assertSee('Visit summary');
        $this->assertSame(1, substr_count($response->getContent(), 'Played performance video'));
        $this->assertSame(1, substr_count($response->getContent(), '<meter min="0" max="100" value="100"'));
        $this->assertSame(1, substr_count($response->getContent(), 'class="ua-card ua-summary"'));
        $this->event($visit, $page, 'score_opened', '2026-10-12 19:14:30');
        $this->event($visit, $page, 'favorite_changed', '2026-10-12 19:15:30', ['result' => true]);
        $moments = (new Journey)->get($visit)['groups'][0]['moments'];
        $this->assertSame(['score_opened', 'media_play', 'favorite_changed'], $moments->pluck('event.type')->all(), 'The consolidated playback stays at its first time among other actions.');
        $this->event($visit, $page, 'media_play', '2026-10-12 19:16:00', array_merge($metadata, ['playback_id' => (string) Str::uuid()]));
        $this->assertCount(2, (new Journey)->get($visit)['groups'][0]['media'], 'Separate playbacks are not hidden.');
    }

    public function test_partial_visits_keep_timing_unknown_and_technical_details_exclude_secrets()
    {
        $visit = $this->visit(); $page = $this->page($visit);
        $this->event($visit, $page, 'click', '2026-10-12 19:15:00', ['label' => '<script>bad</script>', 'password' => 'private-password', 'token' => 'private-token', 'marks' => 'private-annotation', 'filters' => ['email' => 'private@example.test', 'level' => 'beginner', 'notes' => 'private-notes', 'nested' => ['marks' => 'private-marks']]]);
        $this->actingAs(create(Admin::class), 'admin');
        $this->get(route('admin.analytics.users.journey', $visit))->assertOk()->assertSee('Page visibility was not recorded')->assertSee('&lt;script&gt;bad&lt;/script&gt;', false)->assertDontSee('private-password')->assertDontSee('private-token')->assertDontSee('private-annotation')->assertDontSee('private@example.test')->assertDontSee('private-notes')->assertDontSee('private-marks');
    }

    public function test_visit_pagination_keeps_multiple_same_day_visits_separate_and_queries_bounded()
    {
        $user = $this->account();
        for ($i = 0; $i < 33; $i++) $this->visit($user, carbon('2026-10-12 10:00:00')->addMinutes($i)->toDateTimeString());
        DB::enableQueryLog(); DB::flushQueryLog();
        $visits = (new Queries)->visitList($this->filters());
        $count = count(DB::getQueryLog()); DB::disableQueryLog();
        $this->assertCount(25, $visits);
        $this->assertSame(33, $visits->total());
        $this->assertLessThanOrEqual(5, $count);
        $this->assertCount(25, $visits->pluck('id')->unique());
    }

    public function test_journey_page_and_action_payloads_are_paginated_and_bounded()
    {
        $visit = $this->visit();
        for ($i = 0; $i < 24; $i++) $this->page($visit, 'webapp.discover', carbon('2026-10-12 10:00:00')->addMinutes($i)->toDateTimeString());
        $data = (new Journey)->get($visit);
        $this->assertCount(20, $data['pages']);
        $this->assertSame(24, $data['pages']->total());
        $this->assertSame(24, $data['visit']->pages_count);
    }

    public function test_people_search_filters_and_sort_use_existing_account_data()
    {
        $user = $this->account(['first_name' => 'Lena', 'email' => 'lena@example.test', 'origin' => 'ios', 'super_user' => true]);
        $this->account(['first_name' => 'Other']);
        $this->visit($user); $this->visit($user);
        $request = \Illuminate\Http\Request::create('/', 'GET', ['search' => 'lena@', 'origin' => 'ios', 'status' => 'Super user', 'activity' => 'repeat', 'sort' => 'engagement']);
        $people = (new People)->directory($request);
        $this->assertSame([$user->id], $people->pluck('id')->all());
        $this->assertSame(2, (int) $people[0]->visits_count);
        $endOnly = \Illuminate\Http\Request::create('/', 'GET', ['active_to' => '2026-10-12', 'registered_to' => '2026-10-12']);
        $this->assertSame(1, (new People)->directory($endOnly)->total());
    }

    public function test_non_admins_cannot_read_any_analytics_surface_and_filters_are_validated()
    {
        $user = $this->account(); $visit = $this->visit($user);
        $this->withExceptionHandling()->actingAs($user, 'web');
        foreach ([route('admin.analytics.users'), route('admin.analytics.users.people'), route('admin.analytics.users.insights'), route('admin.analytics.users.profile', $user->id), route('admin.analytics.users.journey', $visit)] as $url) $this->get($url)->assertRedirect();
        $this->actingAs(create(Admin::class), 'admin');
        $this->getJson(route('admin.analytics.users', ['range' => 'custom', 'from' => '2020-01-01', 'to' => '2026-10-12']))->assertStatus(422);
        $this->getJson(route('admin.analytics.users.people', ['sort' => 'password']))->assertStatus(422);
        $this->getJson(route('admin.analytics.users', ['platform' => 'forged']))->assertStatus(422);
    }

    public function test_passive_foreground_checkpoints_do_not_keep_an_idle_visit_alive()
    {
        $this->get('/review/journey')->assertOk(); $page = DB::table('activity_pages')->first();
        $this->travel(31)->minutes();
        $this->postJson('/activity/events', ['page_token' => Crypt::encryptString($page->id), 'events' => [['id' => (string) Str::uuid(), 'type' => 'page_checkpoint', 'elapsed_ms' => 1860000, 'sequence' => 1, 'metadata' => ['visible_ms' => 1860000]]]])->assertNoContent();
        $this->get('/review/journey')->assertOk();
        $this->assertDatabaseCount('activity_visits', 2);
    }

    public function test_retry_cannot_change_page_timing_or_identity_and_new_utc_fields_preserve_old_times()
    {
        $this->get('/review/journey')->assertOk(); $page = DB::table('activity_pages')->first();
        $this->travel(20)->seconds();
        $event = ['id' => (string) Str::uuid(), 'type' => 'page_checkpoint', 'elapsed_ms' => 1000, 'sequence' => 1, 'metadata' => ['visible_ms' => 1000]];
        $this->postJson('/activity/events', ['page_token' => Crypt::encryptString($page->id), 'events' => [$event]])->assertNoContent();
        $event['elapsed_ms'] = 20000; $event['metadata']['visible_ms'] = 20000;
        $this->postJson('/activity/events', ['page_token' => Crypt::encryptString($page->id), 'events' => [$event]])->assertNoContent();
        $this->assertSame(1000, (int) DB::table('activity_pages')->value('visible_ms'));
        $stored = DB::table('activity_events')->where('event_id', $event['id'])->first();
        $this->assertSame('2026-10-12 19:30:01.000', $stored->occurred_at);
        $this->assertSame('2026-10-12 23:30:01.000', $stored->occurred_at_utc);
    }

    public function test_utc_offsets_and_inactivity_boundaries_survive_the_repeated_daylight_saving_hour()
    {
        $this->travelTo(\Carbon\Carbon::parse('2026-11-01T05:55:00Z'));
        $this->get('/review/journey')->assertOk(); $page = DB::table('activity_pages')->first();
        $this->assertSame('2026-11-01 05:55:00.000', DB::table('activity_visits')->value('last_interaction_at_utc'));
        $this->travelTo(\Carbon\Carbon::parse('2026-11-01T06:15:00Z'));
        $id = (string) Str::uuid();
        $this->postJson('/activity/events', ['page_token' => Crypt::encryptString($page->id), 'events' => [['id' => $id, 'type' => 'page_checkpoint', 'elapsed_ms' => 1200000, 'sequence' => 1]]])->assertNoContent();
        $stored = DB::table('activity_events')->where('event_id', $id)->first();
        $this->assertSame('2026-11-01 01:15:00.000', $stored->occurred_at);
        $this->assertSame('2026-11-01 06:15:00.000', $stored->occurred_at_utc);
        $this->assertSame('2026-11-01 06:15:00.000', DB::table('activity_visits')->value('last_activity_at_utc'));
        $this->assertSame('2026-11-01 05:55:00.000', DB::table('activity_visits')->value('last_interaction_at_utc'));
        $this->travelTo(\Carbon\Carbon::parse('2026-11-01T06:31:00Z'));
        $this->get('/review/journey')->assertOk();
        $this->assertDatabaseCount('activity_visits', 2);
    }

    public function test_confirmed_invoice_is_idempotent_ungrouped_and_has_no_payment_payload()
    {
        $user = $this->account();
        $source = DB::table('stripe_memberships')->insertGetId(['stripe_id' => 'cus_fixture', 'plan' => 'monthly', 'status' => 'active', 'created_at' => now(), 'updated_at' => now()]);
        DB::table('memberships')->insert(['user_id' => $user->id, 'source_type' => \App\Billing\Sources\Stripe::class, 'source_id' => $source, 'created_at' => now(), 'updated_at' => now()]);
        $payload = ['created' => now()->timestamp, 'data' => ['object' => ['id' => 'in_fixture', 'subscription' => 'sub_fixture', 'customer' => 'cus_fixture', 'paid' => true, 'amount_paid' => 900, 'card' => 'private-card', 'email' => 'private@example.test']]];
        Actions::paidInvoice($payload); Actions::paidInvoice($payload);
        $this->assertDatabaseCount('activity_events', 1);
        $this->assertDatabaseHas('activity_events', ['user_id' => $user->id, 'type' => 'subscription_paid', 'visit_id' => null]);
        $this->assertSame('{"status":"paid"}', DB::table('activity_events')->value('metadata'));
        $this->actingAs(create(Admin::class), 'admin')->get(route('admin.analytics.users.profile', ['user' => $user->id, 'tab' => 'account']))->assertOk()->assertSee('Subscription payment confirmed');
    }

    public function test_saved_state_is_not_reconstructed_as_historical_favorite_actions()
    {
        $user = $this->account();
        $piece = Model::withoutEvents(function () { return create(Piece::class); });
        DB::table('favorites')->insert(['user_id' => $user->id, 'piece_id' => $piece->id, 'created_at' => now(), 'updated_at' => now()]);
        $data = (new People)->profile($user, 'overview', $this->filters());
        $this->assertSame(1, $data['stats']->favorites);
        $this->assertSame(0, (int) $data['stats']->visits);
        $this->assertFalse($data['engagement']->has('favorite_changed'));
        $insights = (new Insights)->get($this->filters());
        $this->assertNull($insights['engagement']['meaningful_playback']);
        $this->assertNull($insights['engagement']['synthesia']);
        $this->assertNull($insights['engagement']['favorites_added']);
    }

    public function test_successful_folder_and_annotation_changes_record_confirmed_counts_without_private_contents()
    {
        $this->withoutMiddleware([\App\Http\Middleware\UpdateLocation::class]);
        $user = $this->account(['super_user' => true]);
        $piece = Model::withoutEvents(function () { return create(Piece::class, ['score_path' => 'scores/analytics.pdf', 'score_url' => null]); });
        $this->actingAs($user, 'web')->get('http://my.'.config('app.short_url').'/review/journey')->assertOk();
        $this->postJson(route('webapp.users.favorites.folders.store'), ['name' => 'Practice', 'piece_id' => $piece->id])->assertOk();
        $folder = DB::table('favorite_folders')->where('user_id', $user->id)->first();
        $this->patch(route('webapp.users.favorites.folders.update', $folder->id), ['name' => 'Repertoire', 'description' => 'private-folder-description'])->assertRedirect();
        $this->assertDatabaseHas('activity_events', ['type' => 'folder_created', 'resource_id' => $folder->id, 'source' => 'server']);
        $this->assertDatabaseHas('activity_events', ['type' => 'folder_renamed', 'resource_id' => $folder->id, 'source' => 'server']);
        $this->assertDatabaseHas('activity_events', ['type' => 'favorite_changed', 'resource_id' => $piece->id, 'source' => 'server']);
        $this->assertLessThan(DB::table('activity_events')->where('type', 'favorite_changed')->value('id'), DB::table('activity_events')->where('type', 'folder_created')->value('id'));
        $url = route('webapp.pieces.score.annotations.update', $piece->id);
        $payload = ['score' => hash('sha256', $piece->score_path), 'fingerprint' => str_repeat('a', 32), 'revision' => 0,
            'marks' => [['id' => 'one', 'type' => 'text', 'page' => 1, 'color' => '#1565c0', 'x' => .2, 'y' => .3, 'size' => .03, 'text' => 'private-score-text']]];
        $this->putJson($url, $payload)->assertExactJson(['revision' => 1]);
        $this->putJson($url, $payload)->assertExactJson(['revision' => 1]);
        $payload['revision'] = 1; $payload['marks'][0]['text'] = 'private-edited-text';
        $this->putJson($url, $payload)->assertExactJson(['revision' => 2]);
        $payload['revision'] = 2; $payload['marks'] = [];
        $this->putJson($url, $payload)->assertExactJson(['revision' => 3]);
        $events = DB::table('activity_events')->where('type', 'annotation_saved')->orderBy('id')->get();
        $this->assertCount(3, $events);
        $this->assertSame(1, json_decode($events[0]->metadata, true)['created_count']);
        $this->assertSame(1, json_decode($events[1]->metadata, true)['edited_count']);
        $this->assertSame(1, json_decode($events[2]->metadata, true)['removed_count']);
        $this->assertStringNotContainsString('private-', DB::table('activity_events')->pluck('metadata')->implode(' '));
        $this->delete(route('webapp.users.favorites.folders.delete', $folder->id))->assertRedirect();
        $this->assertDatabaseHas('activity_events', ['type' => 'folder_deleted', 'resource_id' => $folder->id, 'source' => 'server']);
    }

    public function test_conversion_steps_require_ordered_identity_and_confirmed_billing_outcomes()
    {
        $user = $this->account(); $other = $this->account();
        $visit = $this->visit($user); $page = $this->page($visit);
        $otherVisit = $this->visit($other);
        $this->event($visit, $page, 'premium_restriction', '2026-10-12 19:14:01');
        $this->event($visit, $page, 'premium_opened', '2026-10-12 19:14:02');
        $trial = $this->event($visit, $page, 'trial_started', '2026-10-12 19:14:03');
        DB::table('activity_events')->where('id', $trial)->update(['source' => 'server']);
        $this->event($visit, $page, 'subscription_paid', '2026-10-12 19:14:04'); // Forged client outcome must not count.
        $foreign = $this->event($otherVisit, null, 'subscription_paid', '2026-10-12 19:14:04');
        DB::table('activity_events')->where('id', $foreign)->update(['source' => 'server']);
        $first = collect((new Insights)->get($this->filters())['conversions'])->keyBy('from');
        $this->assertSame(1, (int) $first['premium_restriction']['completed']);
        $this->assertSame(0, (int) $first['trial_started']['completed']);
        $paid = $this->event($visit, null, 'subscription_paid', '2026-10-12 19:15:00');
        DB::table('activity_events')->where('id', $paid)->update(['source' => 'server', 'visit_id' => null]);
        $second = collect((new Insights)->get($this->filters())['conversions'])->keyBy('from');
        $this->assertSame(1, (int) $second['trial_started']['completed']);
        $this->assertSame(1, (int) $second['premium_opened']['completed']);
    }

    public function test_purge_also_removes_activity_reliably_linked_from_anonymous_visits()
    {
        $user = $this->account(); $visit = $this->visit(); $page = $this->page($visit);
        $this->event($visit, $page, 'click');
        DB::table('activity_visit_links')->insert(['visit_id' => $visit, 'user_id' => $user->id, 'linked_at_utc' => now('UTC'), 'reason' => 'login']);
        $other = $this->visit();
        $this->actingAs(create(Admin::class), 'admin')->delete(route('admin.users.purge', $user->id))->assertRedirect();
        $this->assertDatabaseMissing('activity_visits', ['id' => $visit]);
        $this->assertDatabaseMissing('activity_pages', ['id' => $page]);
        $this->assertDatabaseMissing('activity_events', ['visit_id' => $visit]);
        $this->assertDatabaseHas('activity_visits', ['id' => $other]);
    }

    public function test_browser_accepts_additive_music_events_but_cannot_claim_server_account_outcomes()
    {
        $this->withExceptionHandling();
        $this->get('/review/journey')->assertOk(); $page = DB::table('activity_pages')->first();
        $this->travel(2)->seconds();
        $types = ['media_rate', 'score_opened', 'score_progress', 'score_page', 'premium_restriction', 'premium_opened', 'registration_started', 'match_completed'];
        $events = [];
        foreach ($types as $index => $type) $events[] = ['id' => (string) Str::uuid(), 'type' => $type, 'elapsed_ms' => 1000, 'sequence' => $index + 1, 'metadata' => ['playback_id' => (string) Str::uuid(), 'unique_watched_percent' => 20, 'unique_watched_seconds' => 12]];
        $token = Crypt::encryptString($page->id);
        $this->postJson('/activity/events', ['page_token' => $token, 'events' => $events])->assertNoContent();
        foreach ($types as $type) $this->assertDatabaseHas('activity_events', ['type' => $type, 'source' => 'browser', 'page_id' => $page->id]);
        foreach (['subscription_paid', 'registration_completed', 'annotation_saved', 'favorite_changed'] as $type) {
            $events[0]['id'] = (string) Str::uuid(); $events[0]['type'] = $type;
            $this->postJson('/activity/events', ['page_token' => $token, 'events' => [$events[0]]])->assertStatus(422);
            $this->assertDatabaseMissing('activity_events', ['type' => $type]);
        }
    }

    public function test_analytics_migration_upgrade_and_rollback_preserve_original_history()
    {
        $visit = $this->visit(); $page = $this->page($visit);
        $event = $this->event($visit, $page, 'page_view');
        $migration = new \ExtendActivityForUserAnalytics;
        $migration->down();
        $this->assertDatabaseHas('activity_visits', ['id' => $visit]);
        $this->assertDatabaseHas('activity_pages', ['id' => $page]);
        $this->assertDatabaseHas('activity_events', ['id' => $event]);
        $this->assertFalse(\Schema::hasColumn('activity_events', 'occurred_at_utc'));
        $migration->up();
        $this->assertTrue(\Schema::hasColumn('activity_visits', 'last_interaction_at_utc'));
        $this->assertDatabaseHas('activity_events', ['id' => $event, 'occurred_at_utc' => null]);
    }
}
