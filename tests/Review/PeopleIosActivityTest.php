<?php

namespace Tests\Review;

use App\{Admin, User};
use App\Activity\People;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class PeopleIosActivityTest extends ReviewTestCase
{
    private function account(array $attributes = [])
    {
        return Model::withoutEvents(function () use ($attributes) { return create(User::class, $attributes); });
    }

    private function requestEvent($userId, $at, array $attributes = [])
    {
        return DB::table('activity_events')->insertGetId($attributes + [
            'event_id' => (string) Str::uuid(), 'user_id' => $userId, 'platform' => 'app', 'type' => 'request',
            'source' => 'server', 'occurred_at' => $at, 'received_at' => '2026-10-19 11:55:00', 'metadata' => '{"identity_verified":false}',
        ]);
    }

    private function visit($user, $at)
    {
        $id = (string) Str::uuid();
        DB::table('activity_visits')->insert(['id' => $id, 'user_id' => $user->id, 'visitor_id' => (string) Str::uuid(), 'platform' => 'webapp', 'started_at' => $at, 'last_activity_at' => $at]);
        return $id;
    }

    private function directory(array $parameters = [])
    {
        return (new People)->directory(Request::create('/', 'GET', $parameters));
    }

    public function test_default_people_list_sorts_current_and_imported_ios_accounts_with_browser_accounts()
    {
        $this->travelTo(carbon('2026-10-19 12:00:00'));
        $ios = $this->account(['first_name' => 'Alex', 'last_name' => 'Rivera', 'email' => 'alex@example.com']);
        $browser = $this->account(); $idle = $this->account();
        $this->visit($browser, '2026-10-19 10:00:00');
        $this->requestEvent($ios->id, '2026-10-18 10:00:00', ['type' => 'legacy_request', 'source' => 'redis']);
        $this->requestEvent($ios->id, '2026-10-19 11:00:00');
        $people = $this->directory();
        $this->assertSame([$ios->id, $browser->id, $idle->id], $people->pluck('id')->all());
        $this->assertSame('2026-10-19 11:00:00', $people[0]->last_activity);
        $this->assertSame(2, (int) $people[0]->ios_requests_count);
        $this->assertSame(0, (int) $people[0]->visits_count);
        $this->assertSame(0, (int) $people[0]->pieces_count);
        $this->actingAs(create(Admin::class), 'admin');
        $response = $this->get(route('admin.analytics.users.people'))->assertOk()->assertSee('iOS requests')->assertSee('Last activity')->assertSee('Alex Rivera')
            ->assertDontSee('Visit data excludes unverified mobile history.');
        $link = route('admin.analytics.users.profile', ['user' => $ios->id, 'tab' => 'visits', 'range' => 'all', 'platform' => 'app']).'#ungrouped-history';
        $response->assertSee($link);
        $this->assertSame(2, $this->get($link)->assertOk()->viewData('history')->total());
        $this->assertDatabaseCount('activity_visits', 1);
        $this->assertDatabaseCount('activity_visit_links', 0);
        $this->assertDatabaseHas('activity_events', ['user_id' => $ios->id, 'visit_id' => null, 'metadata' => '{"identity_verified":false}']);
    }

    public function test_mixed_accounts_use_the_newest_real_date_without_multiplying_counts_or_piece_opens()
    {
        $user = $this->account();
        $first = $this->visit($user, '2026-10-18 09:00:00');
        $this->visit($user, '2026-10-19 11:00:00');
        $this->requestEvent($user->id, '2026-10-18 08:00:00', ['visit_id' => $first, 'platform' => 'webapp', 'type' => 'page_view', 'resource_type' => 'piece', 'resource_id' => '42']);
        $this->requestEvent($user->id, '2026-10-19 10:00:00');
        $this->requestEvent($user->id, '2026-10-19 10:30:00', ['type' => 'legacy_request', 'source' => 'redis']);
        $row = $this->directory()[0];
        $this->assertSame('2026-10-19 11:00:00', $row->last_activity);
        $this->assertSame(2, (int) $row->visits_count);
        $this->assertSame(2, (int) $row->ios_requests_count);
        $this->assertSame(1, (int) $row->pieces_count);
        $this->requestEvent($user->id, '2026-10-19 11:30:00');
        $row = $this->directory()[0];
        $this->assertSame('2026-10-19 11:30:00', $row->last_activity);
        $this->assertSame('2026-10-19 11:00:00', $row->last_visit_activity);
        $this->assertSame(3, (int) $row->ios_requests_count);
        $this->assertSame(2, (int) $row->visits_count);
    }

    public function test_activity_dates_and_no_activity_include_ios_while_visit_filters_keep_browser_meaning()
    {
        $ios = $this->account(); $browser = $this->account(); $idle = $this->account();
        $this->requestEvent($ios->id, '2026-10-19 23:59:59', ['type' => 'legacy_request', 'source' => 'redis']);
        $this->visit($browser, '2026-10-18 10:00:00');
        $this->assertSame([$ios->id], $this->directory(['active_from' => '2026-10-19', 'active_to' => '2026-10-19'])->pluck('id')->all());
        $this->assertSame([$browser->id], $this->directory(['active_to' => '2026-10-18'])->pluck('id')->all());
        $this->assertSame([$idle->id], $this->directory(['activity' => 'none'])->pluck('id')->all());
        $this->assertSame([$browser->id], $this->directory(['activity' => 'one'])->pluck('id')->all());
        $this->assertSame(0, $this->directory(['activity' => 'repeat'])->total());
    }

    public function test_undated_requests_are_counted_without_using_receipt_dates_and_excluded_accounts_stay_hidden()
    {
        $user = $this->account();
        $excluded = $this->account(['email' => ' Elena.Dare@Gmail.com ']);
        $this->requestEvent($user->id, null, ['type' => 'legacy_request', 'source' => 'redis']);
        $this->requestEvent($user->id, '2026-10-19 10:00:00', ['type' => 'legacy_unparsed', 'source' => 'redis']);
        $this->requestEvent($user->id, '2026-10-19 10:01:00', ['type' => 'media_play']);
        $this->requestEvent($user->id, '2026-10-19 10:02:00', ['platform' => 'web']);
        $this->requestEvent($excluded->id, '2026-10-19 11:00:00');
        $this->requestEvent(null, '2026-10-19 11:01:00');
        $this->requestEvent(999999, '2026-10-19 11:02:00');
        $people = $this->directory();
        $this->assertSame([$user->id], $people->pluck('id')->all());
        $this->assertSame(1, (int) $people[0]->ios_requests_count);
        $this->assertNull($people[0]->last_activity);
        $this->assertSame(0, $this->directory(['activity' => 'none'])->total());
        $this->assertSame(0, $this->directory(['active_from' => '2026-10-19'])->total());
        $this->actingAs(create(Admin::class), 'admin');
        $this->get(route('admin.analytics.users.people'))->assertOk()->assertSee('No dated activity')->assertDontSee($excluded->email);
    }

    public function test_ios_ordering_and_pagination_preserve_existing_account_filters_with_bounded_queries()
    {
        for ($i = 0; $i < 13; $i++) {
            $user = $this->account(['first_name' => 'Legacy', 'origin' => 'ios', 'super_user' => true]);
            $this->requestEvent($user->id, carbon('2026-10-19 10:00:00')->addMinutes($i)->toDateTimeString());
        }
        $this->account(['first_name' => 'Other']);
        $parameters = ['search' => 'Legacy', 'origin' => 'ios', 'status' => 'Super user', 'sort' => 'last_active', 'active_from' => '2026-10-19'];
        DB::flushQueryLog(); DB::enableQueryLog();
        $people = $this->directory($parameters);
        $this->assertCount(2, DB::getQueryLog(), 'One grouped count and one page query; no per-account activity queries.');
        DB::disableQueryLog();
        $this->assertSame(13, $people->total()); $this->assertCount(12, $people);
        $this->actingAs(create(Admin::class), 'admin');
        $response = $this->get(route('admin.analytics.users.people', $parameters))->assertOk();
        $nextUrl = $response->viewData('people')->url(2);
        parse_str(parse_url($nextUrl, PHP_URL_QUERY), $query);
        foreach ($parameters as $key => $value) $this->assertSame($value, $query[$key]);
        $last = $this->get($nextUrl)->assertOk()->viewData('people');
        $this->assertCount(1, $last);
        $this->assertCount(0, $people->pluck('id')->intersect($last->pluck('id')));
    }
}
