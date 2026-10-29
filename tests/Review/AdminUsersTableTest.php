<?php

namespace Tests\Review;

use App\{Admin, Location, User};
use App\Billing\Sources\{Apple, Stripe};
use App\Services\Admin\UsersTable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AdminUsersTableTest extends ReviewTestCase
{
    protected function accounts($count = 30)
    {
        return Model::withoutEvents(function () use ($count) {
            return collect(range(0, $count - 1))->map(function ($index) {
                $user = User::create([
                    'first_name' => sprintf('Account %02d', $index), 'last_name' => 'Sample',
                    'email' => 'account'.$index.'@example.test', 'password' => 'unused-account-secret',
                    'origin' => $index % 2 ? 'web' : 'ios', 'email_verified_at' => now(),
                    'created_at' => now()->subMinutes($index),
                ]);
                create(Location::class, ['user_id' => $user->id, 'countryCode' => 'US']);
                if ($index % 2) {
                    $source = Stripe::create(['stripe_id' => 'unused-customer-secret', 'plan' => 'monthly',
                        'status' => 'active', 'renews_at' => now()->addMonth()]);
                } else {
                    $source = Apple::create(['plan' => 'com.pianolit.monthlyplan', 'renews_at' => now()->addMonth(),
                        'created_at' => now()->subMonth(), 'latest_receipt' => str_repeat('unused-receipt-secret', 100),
                        'password' => 'unused-apple-secret']);
                }
                $user->membership()->create(['source_type' => get_class($source), 'source_id' => $source->id]);
                return $user;
            });
        });
    }

    protected function table(array $parameters = [])
    {
        return $this->get(route('admin.users.index', $parameters));
    }

    protected function xpath($response)
    {
        $dom = new \DOMDocument;
        @$dom->loadHTML($response->getContent());
        return new \DOMXPath($dom);
    }

    public function test_search_matches_saved_countries_regions_and_cities_without_requiring_a_location()
    {
        $users = $this->accounts(4);
        Location::query()->update(['countryName' => 'Elsewhere', 'countryCode' => 'ZZ',
            'regionName' => 'Other region', 'regionCode' => 'XX', 'cityName' => 'Other city']);
        $users[0]->location()->update(['countryName' => 'United States', 'countryCode' => 'US',
            'regionName' => 'Colorado', 'regionCode' => 'ZQ', 'cityName' => 'Denver']);
        $users[1]->location()->update(['countryName' => 'Albania', 'countryCode' => 'AL',
            'regionName' => 'Vlorë', 'cityName' => 'Vlorë']);
        $users[2]->location()->delete();
        $users[3]->location()->update(['cityName' => '100%_! <script>Town</script>']);
        $this->actingAs(create(Admin::class), 'admin');

        foreach (['denVER', 'Colorado', 'United States', 'US', 'ZQ', 'Denver Colorado', 'Account 00 Denver'] as $search) {
            $page = $this->table(compact('search'))->assertOk()->viewData('users');
            $this->assertSame([$users[0]->id], $page->pluck('id')->all(), $search);
        }
        $this->assertSame([$users[1]->id], $this->table(['search' => 'Vlorë Albania'])->assertOk()->viewData('users')->pluck('id')->all());
        $this->assertSame(0, $this->table(['search' => 'Colorado Albania'])->assertOk()->viewData('users')->total());
        $this->assertSame([$users[2]->id], $this->table(['search' => $users[2]->email])->assertOk()->viewData('users')->pluck('id')->all());
        foreach (['%', '_', '!', '%_!'] as $search) {
            $response = $this->table(compact('search'))->assertOk();
            $this->assertSame([$users[3]->id], $response->viewData('users')->pluck('id')->all());
            $response->assertSee('100%_! &lt;script&gt;Town&lt;/script&gt;', false)->assertDontSee('<script>Town</script>', false);
        }
        $this->table(['search' => 'Denver'])->assertOk()->assertSee('Denver, Colorado, United States');
        $this->table(['search' => 'Vlorë'])->assertOk()->assertSee('Vlorë, Albania')->assertDontSee('Vlorë, Vlorë');
        $this->table()->assertOk()->assertSee('Name, email, ID, country, region or city');
    }

    public function test_location_search_keeps_sql_pagination_sorting_and_bounded_queries()
    {
        $users = $this->accounts(30);
        Location::query()->update(['countryName' => 'Elsewhere', 'countryCode' => 'ZZ', 'cityName' => null]);
        Location::whereIn('user_id', $users->take(25)->pluck('id'))->update(['cityName' => 'Denver']);
        $this->actingAs(create(Admin::class), 'admin');
        $first = $this->table(['search' => 'Denver', 'sort' => 'id_asc', 'per_page' => 24])->assertOk()->viewData('users');
        $next = $this->table(['search' => 'Denver', 'sort' => 'id_asc', 'per_page' => 24, 'page' => 2])->assertOk()->viewData('users');
        $this->assertSame(25, $first->total());
        $this->assertSame($users->take(24)->pluck('id')->all(), $first->pluck('id')->all());
        $this->assertSame([$users[24]->id], $next->pluck('id')->all());
        parse_str(parse_url($next->url(1), PHP_URL_QUERY), $query);
        $this->assertSame(['search' => 'Denver', 'sort' => 'id_asc', 'per_page' => '24', 'page' => '1'], $query);
        foreach ([12, 24] as $perPage) {
            DB::enableQueryLog(); DB::flushQueryLog();
            $data = (new UsersTable)->get(Request::create('/users', 'GET', ['search' => 'Denver', 'per_page' => $perPage]));
            view('admin.pages.users.table.rows', $data)->render();
            $queries = DB::getQueryLog(); DB::disableQueryLog();
            $this->assertCount(6, $queries);
            $this->assertStringContainsString('exists', strtolower($queries[0]['query']));
            $this->assertCount($perPage, $data['users']);
        }
    }

    public function test_the_initial_page_contains_twelve_complete_rows_without_datatables()
    {
        $users = $this->accounts();
        $this->actingAs(create(Admin::class), 'admin');
        $response = $this->table()->assertOk();
        $xpath = $this->xpath($response);
        $rows = $xpath->query('//table[@id="users-table"]/tbody/tr');
        $this->assertCount(12, $rows);
        $this->assertCount(0, $xpath->query('//section[contains(@class,"admin-table")]//h2'));
        $this->assertSame((string) $users[0]->id, trim($xpath->query('./td[2]', $rows[0])[0]->textContent));
        $this->assertSame((string) $users[11]->id, trim($xpath->query('./td[2]', $rows[11])[0]->textContent));
        $this->assertCount(6, $xpath->query('./td', $rows[0]));
        $this->assertCount(0, $xpath->query('//table[@id="users-table"]//input'));
        $this->assertCount(1, $xpath->query('//table[@id="users-table"]/ancestor::section[contains(@class,"admin-table")]'));
        $this->assertSame(30, $response->viewData('users')->total());
        $response->assertDontSee('Delete selected')->assertDontSee('selectedUserIds')->assertDontSee('check-datatable-')
            ->assertDontSee('cdn.datatables.net')->assertDontSee('data-admin-datatable')->assertDontSee('admin-table-loading.js')
            ->assertDontSee('deferLoading')->assertSee('Showing 1–12 of 30 results');
        $this->assertSame('descending', $xpath->query('//table/thead/tr/th[1]')[0]->getAttribute('aria-sort'));
        $this->assertCount(1, $xpath->query('//nav[@aria-label="Users pagination"]//a[@aria-label="Next page"]'));
        $this->assertCount(0, $xpath->query('//form[contains(@class,"admin-table__filters")]//input[@name="page"]'));
    }

    public function test_pagination_and_search_cover_the_full_directory_and_keep_url_state()
    {
        $users = $this->accounts();
        $users[29]->update(['first_name' => 'Distant', 'last_name' => 'Rivera', 'email' => 'unique@example.test']);
        $this->actingAs(create(Admin::class), 'admin');
        $first = $this->table()->assertOk()->viewData('users');
        $second = $this->table(['page' => 2])->assertOk()->viewData('users');
        $this->assertCount(12, $first); $this->assertCount(12, $second);
        $this->assertCount(0, $first->pluck('id')->intersect($second->pluck('id')));
        $this->assertCount(6, $this->table(['page' => 3])->assertOk()->viewData('users'));
        $this->assertCount(24, $this->table(['per_page' => 24])->assertOk()->viewData('users'));
        foreach (['Distant Rivera', 'unique@example.test', 'Rivera'] as $search) {
            $page = $this->table(compact('search'))->assertOk()->viewData('users');
            $this->assertSame(1, $page->total()); $this->assertSame($users[29]->id, $page->first()->id);
        }
        $this->assertSame(15, $this->table(['search' => 'IOS'])->assertOk()->viewData('users')->total());
        foreach (['no-match', '%', '_', "' OR 1=1 --"] as $search) {
            $this->assertSame(0, $this->table(compact('search'))->assertOk()->viewData('users')->total());
        }
        $response = $this->table(['search' => 'Account', 'sort' => 'name_asc', 'page' => 2, 'per_page' => 24])->assertOk();
        $url = $response->viewData('users')->url(1);
        parse_str(parse_url($url, PHP_URL_QUERY), $query);
        $this->assertSame(['search' => 'Account', 'sort' => 'name_asc', 'per_page' => '24', 'page' => '1'], $query);
        $sortUrl = $this->xpath($response)->query('//table/thead/tr/th[3]/a')[0]->getAttribute('href');
        parse_str(parse_url($sortUrl, PHP_URL_QUERY), $query);
        $this->assertArrayNotHasKey('page', $query);
        $this->assertSame('name_desc', $query['sort']); $this->assertSame('24', $query['per_page']);
        $this->get($sortUrl)->assertOk();
    }

    public function test_super_status_can_be_managed_from_the_edit_user_page_only()
    {
        $user = $this->accounts(1)->first();
        $this->actingAs(create(Admin::class), 'admin');
        foreach ([false, true] as $enabled) {
            $user->update(['super_user' => $enabled]);
            $response = $this->get(route('admin.users.show', $user))->assertOk();
            $dom = new \DOMDocument;
            @$dom->loadHTML($response->getContent());
            $xpath = new \DOMXPath($dom);
            $controls = $xpath->query('//input[@data-user-super-status]');
            $this->assertCount(1, $controls);
            $this->assertSame($enabled, $controls[0]->hasAttribute('checked'));
            $this->assertSame(route('admin.users.super-status', $user), $controls[0]->getAttribute('data-url'));
            $this->assertStringNotContainsString('status-toggle', $controls[0]->getAttribute('class'));
        }
    }

    public function test_all_enabled_sorts_keep_their_meaning_with_stable_ties()
    {
        $users = $this->accounts(4);
        foreach (['Zulu', 'Alpha', 'Bravo', 'Charlie'] as $i => $name) $users[$i]->update(['first_name' => $name]);
        $this->actingAs(create(Admin::class), 'admin');
        $ascending = ['date' => [3, 2, 1, 0], 'id' => [0, 1, 2, 3], 'name' => [1, 2, 3, 0], 'origin' => [2, 0, 3, 1]];
        $descending = ['date' => [0, 1, 2, 3], 'id' => [3, 2, 1, 0], 'name' => [0, 3, 2, 1], 'origin' => [3, 1, 2, 0]];
        foreach (['asc' => $ascending, 'desc' => $descending] as $direction => $sorts) {
            foreach ($sorts as $column => $indices) {
                $page = $this->table(['sort' => $column.'_'.$direction])->assertOk()->viewData('users');
                $this->assertSame(array_map(function ($i) use ($users) { return $users[$i]->id; }, $indices), $page->pluck('id')->all());
            }
        }
        User::whereIn('id', $users->pluck('id'))->update(['created_at' => now()]);
        $this->assertSame($users->pluck('id')->reverse()->values()->all(), $this->table()->assertOk()->viewData('users')->pluck('id')->all());
    }

    public function test_account_cells_escape_content_and_preserve_status_flags_and_actions()
    {
        $users = $this->accounts(4);
        $users[0]->update(['first_name' => '<script>unsafe</script>', 'super_user' => true]);
        $users[1]->membership->source->update(['status' => 'trialing']);
        $users[2]->membership->source->update(['renews_at' => now()->subDay()]);
        $this->actingAs(create(Admin::class), 'admin');
        $response = $this->table()->assertOk();
        $response->assertSee('&lt;script&gt;', false)->assertDontSee('<script>unsafe</script>', false)
            ->assertSee('flag-icon-us')->assertSee('Super user')->assertSee('On trial')->assertSee('Expired')->assertSee('Member (Monthly)');
        $xpath = $this->xpath($response);
        $actions = $xpath->query('//table[@id="users-table"]/tbody/tr[1]/td[6]/div/a');
        $this->assertCount(2, $actions);
        $this->assertSame(route('admin.users.show', $users[0]), $actions[0]->getAttribute('href'));
        $this->assertSame(route('admin.analytics.users.profile', ['user' => $users[0]->id, 'tab' => 'visits']), $actions[1]->getAttribute('href'));
        $this->assertSame('Logs & analytics', $actions[1]->getAttribute('aria-label'));
        foreach (['unused-account-secret', 'unused-receipt-secret', 'unused-apple-secret', 'unused-customer-secret', 'latest_receipt', 'logs_count', 'favorites_count'] as $unused) $response->assertDontSee($unused);
    }

    public function test_queries_and_rendering_are_bounded_without_unused_records_or_activity_counts()
    {
        $this->accounts();
        $counts = [];
        foreach ([12, 24] as $length) {
            DB::enableQueryLog(); DB::flushQueryLog();
            $data = (new UsersTable)->get(Request::create('/users', 'GET', ['per_page' => $length]));
            view('admin.pages.users.table.rows', $data)->render();
            $queries = DB::getQueryLog(); DB::disableQueryLog();
            $this->assertCount($length, $data['users']); $counts[] = count($queries);
            $sql = implode("\n", array_column($queries, 'query'));
            foreach (['activity_events', 'favorites', 'latest_receipt', 'password', 'stripe_id', 'card_last_four'] as $unused) $this->assertStringNotContainsString($unused, $sql);
            $this->assertStringContainsString('limit '.$length, $sql);
        }
        $this->assertSame([6, 6], $counts);
    }

    public function test_admin_authorization_and_allowlisted_query_parameters()
    {
        $this->withExceptionHandling();
        $this->getJson(route('admin.users.index'))->assertUnauthorized();
        $user = Model::withoutEvents(function () { return create(User::class, ['email' => 'arthurvillar@gmail.com']); });
        $this->actingAs($user, 'web')->getJson(route('admin.users.index'))->assertUnauthorized();
        $this->actingAs(create(Admin::class), 'admin');
        $this->table(['columns' => [['name' => 'users.password']]])->assertOk()->assertDontSee($user->password);
        foreach ([['per_page' => -1], ['per_page' => 1000], ['per_page' => 13], ['page' => -1], ['page' => ['2']],
            ['search' => ['bad']], ['search' => str_repeat('a', 256)], ['sort' => ['name_asc']], ['sort' => 'password_asc'], ['sort' => 'date_desc; DELETE FROM users']] as $bad) {
            $this->getJson(route('admin.users.index', $bad))->assertUnprocessable();
        }
        $this->assertDatabaseCount('users', 1);
    }

    public function test_super_user_overrides_do_not_fetch_billing_and_empty_results_have_no_fake_rows()
    {
        $users = $this->accounts(4);
        User::whereIn('id', $users->pluck('id'))->update(['super_user' => true]);
        Stripe::query()->update(['renews_at' => now()->subYear(), 'ended_at' => now()->subYear()]);
        Apple::query()->update(['renews_at' => now()->subYear()]);
        DB::enableQueryLog(); DB::flushQueryLog();
        $data = (new UsersTable)->get(Request::create('/users'));
        $html = view('admin.pages.users.table.rows', $data)->render();
        $queries = DB::getQueryLog(); DB::disableQueryLog();
        $this->assertCount(3, $queries);
        $this->assertStringNotContainsString('memberships', implode("\n", array_column($queries, 'query')));
        $this->assertSame(4, substr_count($html, 'Super user'));
        DB::table('users')->delete();
        $this->actingAs(create(Admin::class), 'admin');
        $response = $this->table()->assertOk()->assertSee('No users yet.')->assertDontSee('name="per_page"', false);
        $this->assertSame(0, $response->viewData('users')->total());
        $this->assertCount(1, $this->xpath($response)->query('//table[@id="users-table"]/tbody/tr/td[@colspan="6"]'));
        $this->assertCount(0, $this->xpath($response)->query('//ul[contains(@class,"pagination")]'));
    }

    public function test_paused_and_grace_period_labels_keep_the_existing_status_rules()
    {
        $users = $this->accounts(4);
        $users[1]->membership->source->update(['paused_at' => now()]);
        $users[3]->membership->source->update(['membership_ends_at' => now()->addDays(3)]);
        $this->actingAs(create(Admin::class), 'admin');
        $this->table()->assertOk()->assertSee('Paused')->assertSee('Grace period');
    }

    public function test_directory_indexes_remove_the_default_sort_and_membership_scan_and_can_roll_back()
    {
        $this->accounts(4);
        $plan = function ($sql) {
            return implode(' ', array_map(function ($row) { return $row->detail; }, DB::select('EXPLAIN QUERY PLAN '.$sql)));
        };
        $usersSql = 'SELECT id FROM users ORDER BY created_at DESC, id DESC LIMIT 12';
        $membershipSql = 'SELECT id FROM memberships WHERE user_id IN (1, 2, 3, 4)';
        $this->assertStringContainsString('users_admin_created_at_id_index', $plan($usersSql));
        $this->assertStringNotContainsString('TEMP B-TREE', $plan($usersSql));
        $this->assertStringContainsString('memberships_user_id_index', $plan($membershipSql));
        $migration = new \AddAdminUserDirectoryIndexes;
        $migration->down();
        $this->assertStringContainsString('TEMP B-TREE', $plan($usersSql));
        $this->assertStringNotContainsString('memberships_user_id_index', $plan($membershipSql));
        $this->assertDatabaseCount('users', 4);
        $this->assertDatabaseCount('memberships', 4);
        $migration->up();
        $this->assertStringContainsString('users_admin_created_at_id_index', $plan($usersSql));
        $this->assertStringContainsString('memberships_user_id_index', $plan($membershipSql));
    }
}
