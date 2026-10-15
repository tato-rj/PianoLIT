<?php

namespace Tests\Review;

use App\{Admin, Location, User};
use App\Billing\Sources\{Apple, Stripe};
use App\Services\Admin\UsersTable;
use Illuminate\Database\Eloquent\Model;
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
        return $this->getJson(route('admin.users.index', $parameters + ['draw' => 1, 'start' => 0, 'length' => 12]),
            ['X-Requested-With' => 'XMLHttpRequest']);
    }

    public function test_the_initial_page_contains_twelve_ready_rows_in_default_order()
    {
        $users = $this->accounts();
        $this->actingAs(create(Admin::class), 'admin');
        $response = $this->get(route('admin.users.index'))->assertOk();
        $dom = new \DOMDocument;
        @$dom->loadHTML($response->getContent());
        $xpath = new \DOMXPath($dom);
        $rows = $xpath->query('//table[@id="users-table"]/tbody/tr');
        $this->assertCount(1, $xpath->query('//table[@id="users-table"]/ancestor::*[@data-admin-datatable]'));
        $this->assertCount(1, $xpath->query('//script[contains(@src,"/js/components/admin-table-loading.js")]'));
        $this->assertCount(12, $rows);
        $this->assertSame((string) $users[0]->id, trim($xpath->query('./td[2]', $rows[0])[0]->textContent));
        $this->assertSame((string) $users[11]->id, trim($xpath->query('./td[2]', $rows[11])[0]->textContent));
        $this->assertCount(6, $xpath->query('./td', $rows[0]));
        $this->assertCount(0, $xpath->query('//table[@id="users-table"]//input[contains(@class,"status-toggle")]'));
        $this->assertCount(0, $xpath->query('//*[@id="check-all-datatable" or @id="multi-select"]'));
        $response->assertDontSee('Delete selected')->assertDontSee('selectedUserIds')->assertDontSee('check-datatable-');
        $this->assertCount(0, $xpath->query('//table[@id="users-table"]/thead[contains(@class,"invisible")]'));
        $this->assertSame(30, $response->viewData('initialUsers')['recordsTotal']);
        $this->assertSame($response->viewData('initialUsers')['data'], $this->table()->assertOk()->json('data'));
        $response->assertSee('deferLoading: 30', false)->assertDontSee("addClass('table-loading')", false);
    }

    public function test_pagination_and_existing_search_fields_cover_the_full_directory()
    {
        $users = $this->accounts();
        $users[29]->update(['first_name' => 'Distant account']);
        $this->actingAs(create(Admin::class), 'admin');
        $first = $this->table()->assertOk()->assertJsonCount(12, 'data')->assertJsonPath('recordsTotal', 30);
        $second = $this->table(['start' => 12])->assertOk()->assertJsonCount(12, 'data');
        $this->assertSame([], array_intersect(array_column($first->json('data'), 'id'), array_column($second->json('data'), 'id')));
        $this->table(['start' => 24])->assertOk()->assertJsonCount(6, 'data');
        $this->table(['length' => 24])->assertOk()->assertJsonCount(24, 'data');
        $this->table(['search' => ['value' => 'Distant']])->assertOk()->assertJsonPath('recordsFiltered', 1)->assertJsonPath('data.0.id', $users[29]->id);
        $this->table(['search' => ['value' => 'IOS']])->assertOk()->assertJsonPath('recordsFiltered', 15);
        $this->table(['search' => ['value' => 'no-match']])->assertOk()->assertJsonPath('recordsFiltered', 0)->assertJsonCount(0, 'data');
        foreach (['%', '_', "' OR 1=1 --"] as $literal) {
            $this->table(['search' => ['value' => $literal]])->assertOk()->assertJsonPath('recordsFiltered', 0);
        }
        $this->getJson(route('admin.users.index'), ['X-Requested-With' => 'XMLHttpRequest'])->assertOk()->assertJsonCount(12, 'data');
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

    public function test_all_enabled_sort_columns_keep_their_meaning_with_stable_ties()
    {
        $users = $this->accounts(4);
        $users[0]->update(['first_name' => 'Zulu', 'super_user' => true]);
        $users[1]->update(['first_name' => 'Alpha']);
        $users[2]->update(['first_name' => 'Bravo']);
        $users[3]->update(['first_name' => 'Charlie']);
        $this->actingAs(create(Admin::class), 'admin');
        $ascending = [0 => [3, 2, 1, 0], 1 => [0, 1, 2, 3], 2 => [1, 2, 3, 0], 3 => [2, 0, 3, 1]];
        $descending = [0 => [0, 1, 2, 3], 1 => [3, 2, 1, 0], 2 => [0, 3, 2, 1], 3 => [3, 1, 2, 0]];
        foreach (['asc' => $ascending, 'desc' => $descending] as $direction => $sorts) {
            foreach ($sorts as $column => $indices) {
                $data = $this->table(['order' => [['column' => $column, 'dir' => $direction]]])->assertOk()->json('data');
                $this->assertSame(array_map(function ($i) use ($users) { return $users[$i]->id; }, $indices), array_column($data, 'id'));
            }
        }
        User::whereIn('id', $users->pluck('id'))->update(['created_at' => now()]);
        $this->assertSame($users->pluck('id')->reverse()->values()->all(), array_column($this->table()->assertOk()->json('data'), 'id'));
    }

    public function test_response_contains_only_used_cells_and_preserves_status_flags_and_two_actions()
    {
        $users = $this->accounts(4);
        $users[0]->update(['first_name' => '<script>unsafe</script>', 'super_user' => true]);
        $users[1]->membership->source->update(['status' => 'trialing']);
        $users[2]->membership->source->update(['renews_at' => now()->subDay()]);
        $this->actingAs(create(Admin::class), 'admin');
        $response = $this->table()->assertOk();
        $row = $response->json('data.0');
        $this->assertEqualsCanonicalizing(['id', 'created_at', 'name', 'origin_display', 'status', 'actions'], array_keys($row));
        $this->assertStringContainsString('&lt;script&gt;', $row['name']);
        $this->assertStringNotContainsString('<script>', $row['name']);
        $this->assertStringContainsString('flag-icon-us', $row['name']);
        $this->assertStringContainsString('Super user', $row['status']);
        $this->assertStringContainsString('On trial', $response->json('data.1.status'));
        $this->assertStringContainsString('Expired', $response->json('data.2.status'));
        $this->assertStringContainsString('Member (Monthly)', $response->json('data.3.status'));
        foreach (['name', 'origin_display', 'status', 'actions'] as $field) {
            $view = ['origin_display' => 'origin'][$field] ?? $field;
            $item = $users[0]->fresh(['location', 'membership.source']);
            $this->assertSame(view('admin.pages.users.table.'.$view, compact('item'))->render(), $row[$field]);
        }
        $this->assertSame(2, substr_count($row['actions'], '<a '));
        $this->assertStringContainsString(route('admin.users.show', $users[0]), $row['actions']);
        $this->assertStringContainsString(route('impersonate', $users[0]), $row['actions']);
        foreach (['unused-account-secret', 'unused-receipt-secret', 'unused-apple-secret', 'unused-customer-secret', 'latest_receipt', 'logs_count', 'favorites_count'] as $unused) {
            $this->assertStringNotContainsString($unused, $response->getContent());
        }
        $this->get(route('admin.users.index'))->assertOk()->assertSee('&lt;script&gt;', false)->assertDontSee('<script>unsafe</script>', false);
    }

    public function test_table_work_is_bounded_and_does_not_fetch_unused_records_or_run_activity_counts()
    {
        $this->accounts();
        $counts = [];
        foreach ([12, 24] as $length) {
            DB::enableQueryLog(); DB::flushQueryLog();
            $request = \Illuminate\Http\Request::create('/users', 'GET', compact('length'));
            $data = (new UsersTable)->response($request)->getData(true);
            $queries = DB::getQueryLog(); DB::disableQueryLog();
            $this->assertCount($length, $data['data']);
            $counts[] = count($queries);
            $sql = implode("\n", array_column($queries, 'query'));
            foreach (['activity_events', 'favorites', 'latest_receipt', 'password', 'stripe_id', 'card_last_four'] as $unused) $this->assertStringNotContainsString($unused, $sql);
            $this->assertStringContainsString('limit '.$length, $sql);
        }
        $this->assertSame([6, 6], $counts);
        DB::enableQueryLog(); DB::flushQueryLog();
        (new UsersTable)->firstPage();
        $this->assertCount(6, DB::getQueryLog());
        DB::disableQueryLog();
    }

    public function test_authorization_validation_and_client_columns_cannot_change_the_query()
    {
        $this->withExceptionHandling();
        $this->table()->assertUnauthorized();
        $user = Model::withoutEvents(function () { return User::create(['first_name' => 'Directory', 'last_name' => 'Account', 'email' => 'arthurvillar@gmail.com', 'password' => 'unused']); });
        $this->actingAs($user, 'web');
        $this->table()->assertUnauthorized();
        $this->actingAs(create(Admin::class), 'admin');
        $this->table(['columns' => [['data' => 'password', 'name' => 'users.password', 'searchable' => true, 'search' => ['value' => 'arbitrary']]]])->assertOk()->assertJsonPath('data.0.id', $user->id);
        foreach ([['length' => -1], ['length' => 1000], ['start' => -1], ['draw' => '<script>'], ['search' => ['value' => ['bad']]],
            ['order' => [['column' => 5, 'dir' => 'asc']]], ['order' => [['column' => 6, 'dir' => 'asc']]],
            ['columns' => array_fill(0, 7, ['data' => 'id'])], ['order' => [['column' => 0, 'dir' => 'desc; DELETE FROM users']]],
            ['columns' => [['name' => ['bad']]]]] as $bad) $this->table($bad)->assertUnprocessable();
        $this->assertDatabaseCount('users', 1);
    }

    public function test_super_users_keep_their_override_without_fetching_billing_and_empty_pages_are_supported()
    {
        $users = $this->accounts(4);
        User::whereIn('id', $users->pluck('id'))->update(['super_user' => true]);
        Stripe::query()->update(['renews_at' => now()->subYear(), 'ended_at' => now()->subYear()]);
        Apple::query()->update(['renews_at' => now()->subYear()]);
        DB::enableQueryLog(); DB::flushQueryLog();
        $page = (new UsersTable)->firstPage();
        $queries = DB::getQueryLog(); DB::disableQueryLog();
        $this->assertCount(3, $queries);
        $this->assertStringNotContainsString('memberships', implode("\n", array_column($queries, 'query')));
        foreach ($page['data'] as $row) $this->assertStringContainsString('Super user', $row['status']);
        // Removing fixture rows directly avoids invoking account deletion hooks.
        DB::table('users')->delete();
        $this->actingAs(create(Admin::class), 'admin');
        $response = $this->get(route('admin.users.index'))->assertOk();
        $this->assertSame(0, $response->viewData('initialUsers')['recordsTotal']);
        $this->assertSame([], $response->viewData('initialUsers')['data']);
        $response->assertSee('deferLoading: 0', false);
        $this->table()->assertOk()->assertJsonCount(0, 'data');
    }

    public function test_paused_and_grace_period_labels_use_the_existing_status_rules()
    {
        $users = $this->accounts(4);
        $users[1]->membership->source->update(['paused_at' => now()]);
        $users[3]->membership->source->update(['membership_ends_at' => now()->addDays(3)]);
        $this->actingAs(create(Admin::class), 'admin');
        $response = $this->table()->assertOk();
        $this->assertStringContainsString('Paused', $response->json('data.1.status'));
        $this->assertStringContainsString('Grace period', $response->json('data.3.status'));
        foreach ($users as $index => $user) {
            $item = $user->fresh(['location', 'membership.source']);
            $this->assertSame(view('admin.pages.users.table.status', compact('item'))->render(), $response->json('data.'.$index.'.status'));
        }
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
