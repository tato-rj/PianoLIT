<?php

namespace Tests\Review;

use App\{Admin, EmailList, Subscription, User};
use App\Services\Admin\SubscriptionsTable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\{DB, Mail, Notification};

class AdminSubscriptionsTableTest extends ReviewTestCase
{
    protected function subscribers($count = 30)
    {
        return collect(range(1, $count))->map(function ($i) {
            return Subscription::create(['email' => sprintf('subscriber%02d@example.test', $i),
                'origin_url' => $i % 2 ? 'Website' : 'Recital', 'form_id' => $i % 2 ? 'signup' : null,
                'created_at' => now()->subMinutes($i)]);
        });
    }

    protected function table(array $parameters = [])
    {
        return $this->get(route('admin.subscriptions.index', $parameters));
    }

    protected function admin()
    {
        $this->actingAs(create(Admin::class), 'admin');
    }

    protected function xpath($response)
    {
        $dom = new \DOMDocument;
        @$dom->loadHTML($response->getContent());
        return new \DOMXPath($dom);
    }

    public function test_complete_first_page_preserves_actions_and_account_links_without_datatables_or_lookup_requests()
    {
        $items = $this->subscribers(); $this->admin();
        $user = Model::withoutEvents(function () use ($items) {
            return create(User::class, ['email' => $items[0]->email]);
        });
        $response = $this->table()->assertOk()->assertSee('Showing 1–12 of 30 results');
        foreach (['cdn.datatables.net', 'data-admin-datatable', 'deferLoading', 'findUser(', 'searching for user'] as $old) $response->assertDontSee($old);
        $xpath = $this->xpath($response);
        $this->assertCount(12, $xpath->query('//table[@id="subscriptions-table"]/tbody/tr'));
        $this->assertCount(0, $xpath->query('//section[contains(@class,"admin-table")]//h2'));
        $this->assertCount(12, $xpath->query('//table//input[@form="subscriptions-selection-form"]'));
        $this->assertCount(2, $xpath->query('//header//a[contains(@href,"/subscriptions/export?")]'));
        $this->assertCount(1, $xpath->query('//form[@id="subscriptions-selection-form"]//input[@name="_token"]'));
        $this->assertCount(1, $xpath->query('//form[@id="subscriptions-selection-form"]//input[@name="_method" and @value="DELETE"]'));
        $account = $xpath->query('//table//a[contains(@href,"/users/")]')[0];
        $this->assertSame(route('admin.users.show', $user), $account->getAttribute('href'));
        $this->assertSame('noopener noreferrer', $account->getAttribute('rel'));
        $this->assertSame('mailto:'.$items[0]->email, $xpath->query('//table//a[starts-with(@href,"mailto:")]')[0]->getAttribute('href'));
        $this->assertSame(route('admin.subscriptions.destroy', $items[0]), $xpath->query('//table//button[@data-url]')[0]->getAttribute('data-url'));
        $response->assertSee('No user account');
    }

    public function test_search_pagination_and_literal_wildcards_apply_before_pagination_and_keep_state()
    {
        $this->subscribers(); $this->admin();
        $first = $this->table()->assertOk()->viewData('subscriptions');
        $second = $this->table(['page' => 2])->assertOk()->viewData('subscriptions');
        $this->assertCount(12, $first); $this->assertCount(12, $second);
        $this->assertCount(0, $first->pluck('id')->intersect($second->pluck('id')));
        $this->assertCount(6, $this->table(['page' => 3])->assertOk()->viewData('subscriptions'));
        $this->assertCount(24, $this->table(['per_page' => 24])->assertOk()->viewData('subscriptions'));
        $this->assertSame(1, $this->table(['search' => 'SUBSCRIBER30'])->assertOk()->viewData('subscriptions')->total());
        $this->assertSame(15, $this->table(['search' => 'Website signup'])->assertOk()->viewData('subscriptions')->total());
        $page = $this->table(['search' => 'subscriber', 'sort' => 'email_asc', 'per_page' => 24])->assertOk()->viewData('subscriptions');
        parse_str(parse_url($page->url(2), PHP_URL_QUERY), $query);
        $this->assertEquals(['search' => 'subscriber', 'sort' => 'email_asc', 'per_page' => 24, 'page' => 2], $query);
        Subscription::create(['email' => 'literal@example.test', 'origin_url' => '100% _special !']);
        foreach (['%' => 1, '_special' => 1, '_absent' => 0, '!' => 1, "' OR 1=1 --" => 0] as $search => $count) {
            $this->assertSame($count, $this->table(compact('search'))->assertOk()->viewData('subscriptions')->total());
        }
        $empty = $this->table(['search' => 'absent'])->assertOk()->assertSee('No matching subscribers');
        $this->assertCount(1, $this->xpath($empty)->query('//table/tbody/tr/td[@colspan="7"]'));
        $this->assertCount(0, $this->xpath($empty)->query('//nav[contains(@aria-label,"pagination")]'));
    }

    public function test_all_sorts_use_fixed_columns_and_stable_id_ties()
    {
        $items = $this->subscribers(4); $this->admin();
        $ascending = ['date' => [3, 2, 1, 0], 'id' => [0, 1, 2, 3], 'email' => [0, 1, 2, 3],
            'origin' => [3, 1, 2, 0], 'form' => [3, 1, 2, 0]];
        $descending = ['date' => [0, 1, 2, 3], 'id' => [3, 2, 1, 0], 'email' => [3, 2, 1, 0],
            'origin' => [2, 0, 3, 1], 'form' => [2, 0, 3, 1]];
        foreach (['asc' => $ascending, 'desc' => $descending] as $direction => $sorts) {
            foreach ($sorts as $sort => $indices) {
                $page = $this->table(['sort' => $sort.'_'.$direction])->assertOk()->viewData('subscriptions');
                $this->assertSame(array_map(function ($i) use ($items) { return $items[$i]->id; }, $indices), $page->pluck('id')->all());
            }
        }
        Subscription::query()->update(['created_at' => now()]);
        $this->assertSame($items->pluck('id')->reverse()->values()->all(), $this->table()->assertOk()->viewData('subscriptions')->pluck('id')->all());
    }

    public function test_directory_and_rendering_use_two_queries_for_twelve_and_twenty_four_rows()
    {
        $items = $this->subscribers();
        Model::withoutEvents(function () use ($items) {
            foreach ($items as $item) create(User::class, ['email' => $item->email]);
        });
        foreach ([12, 24] as $size) {
            DB::enableQueryLog(); DB::flushQueryLog();
            $data = (new SubscriptionsTable)->get(Request::create('/subscriptions', 'GET', ['per_page' => $size]));
            view('admin.pages.subscriptions.rows', $data)->render();
            $queries = DB::getQueryLog(); DB::disableQueryLog();
            $this->assertCount($size, $data['subscriptions']); $this->assertCount(2, $queries);
            $sql = implode("\n", array_column($queries, 'query'));
            foreach (['favorites', 'activity_events', 'memberships', 'password', 'newsletter_list', 'birthday_list'] as $unused) $this->assertStringNotContainsString($unused, $sql);
            $this->assertStringContainsString('limit '.$size, $sql);
            $this->assertSame($size, $data['subscriptions']->filter(function ($item) { return $item->account_id !== null; })->count());
        }
    }

    public function test_cells_escape_untrusted_text_and_empty_lists_have_no_selection_or_fake_rows()
    {
        $this->admin();
        $empty = $this->table()->assertOk()->assertSee('No subscribers yet.')->assertDontSee('data-table-bulk-delete');
        $this->assertCount(1, $this->xpath($empty)->query('//table/tbody/tr/td[@colspan="7"]'));
        Subscription::create(['email' => 'unsafe@example.test', 'origin_url' => '<script>alert(1)</script>', 'form_id' => '<img src=x>']);
        $this->table()->assertOk()->assertSee('&lt;script&gt;', false)->assertSee('&lt;img', false)->assertDontSee('<script>alert(1)</script>', false);
    }

    public function test_admin_guard_and_allowlisted_request_shapes()
    {
        $this->withExceptionHandling();
        $this->getJson(route('admin.subscriptions.index'))->assertUnauthorized();
        $this->deleteJson(route('admin.subscriptions.destroy-many'), ['ids' => [1]])->assertUnauthorized();
        $user = Model::withoutEvents(function () { return create(User::class); });
        $this->actingAs($user, 'web')->getJson(route('admin.subscriptions.index'))->assertUnauthorized();
        $this->admin();
        foreach ([['search' => ['bad']], ['search' => str_repeat('a', 256)], ['sort' => ['email_asc']], ['sort' => 'password_asc'],
            ['sort' => 'date_desc; DROP TABLE subscriptions'], ['page' => -1], ['page' => ['2']], ['per_page' => 13], ['per_page' => 1000]] as $bad) {
            $this->getJson(route('admin.subscriptions.index', $bad))->assertUnprocessable();
        }
        $this->getJson(route('admin.subscriptions.export', ['type' => ['fans']]))->assertUnprocessable();
        $this->getJson(route('admin.subscriptions.export', ['type' => 'unknown']))->assertUnprocessable();
    }

    public function test_single_and_bulk_deletion_preserve_filters_clamp_pages_and_detach_only_selected_emails()
    {
        $items = $this->subscribers(25); $this->admin();
        $list = EmailList::create(['name' => 'Test list', 'description' => 'Fictional list']);
        foreach ($items as $item) $item->lists()->attach($list);
        $filters = ['search' => 'subscriber', 'sort' => 'email_asc', 'per_page' => 24, 'page' => 2];
        $response = $this->table($filters)->assertOk();
        $action = $this->xpath($response)->query('//table//button[@data-url]')[0]->getAttribute('data-url');
        $deleted = $this->delete($action, ['ids' => [$items[0]->id]])->assertSessionHas('status');
        unset($filters['page']);
        $deleted->assertRedirect(route('admin.subscriptions.index', $filters));
        $this->assertDatabaseMissing('subscriptions', ['id' => $items[24]->id]);
        $this->assertDatabaseHas('subscriptions', ['id' => $items[0]->id]);
        $this->assertDatabaseMissing('email_list_subscription', ['subscription_id' => $items[24]->id]);
        $this->delete(route('admin.subscriptions.destroy-many', $filters + ['page' => 2]), ['ids' => [$items[0]->id, $items[1]->id]])
            ->assertRedirect(route('admin.subscriptions.index', $filters));
        $this->assertDatabaseCount('subscriptions', 22); $this->assertDatabaseCount('email_list_subscription', 22);
        $this->assertDatabaseCount('email_lists', 1);
    }

    public function test_invalid_selections_and_return_state_never_partially_delete()
    {
        $items = $this->subscribers(2); $this->admin(); $this->withExceptionHandling();
        foreach ([[], ['ids' => '1'], ['ids' => []], ['ids' => [$items[0]->id, $items[0]->id]],
            ['ids' => [['bad']]], ['ids' => [$items[0]->id, 999999]], ['ids' => range(1, 97)],
            ['ids' => [$items[0]->id], 'sort' => 'password_asc']] as $bad) {
            $this->deleteJson(route('admin.subscriptions.destroy-many'), $bad)->assertUnprocessable();
            $this->assertDatabaseCount('subscriptions', 2);
        }
        $this->deleteJson(route('admin.subscriptions.destroy', $items[0]), ['per_page' => 1000])->assertUnprocessable();
        $this->assertDatabaseCount('subscriptions', 2);
    }

    public function test_failed_bulk_deletion_rolls_back_subscribers_and_list_membership()
    {
        $items = $this->subscribers(2); $this->admin();
        $list = EmailList::create(['name' => 'Test list', 'description' => 'Fictional list']);
        foreach ($items as $item) $item->lists()->attach($list);
        // Fail after the second model's list-detachment event, inside the transaction.
        DB::unprepared('CREATE TRIGGER fail_subscriber_delete BEFORE DELETE ON subscriptions WHEN OLD.id = '.$items[1]->id." BEGIN SELECT RAISE(ABORT, 'forced delete failure'); END");
        try {
            $this->delete(route('admin.subscriptions.destroy-many'), ['ids' => $items->pluck('id')->all()]);
            $this->fail('Expected the database deletion to fail.');
        } catch (\Illuminate\Database\QueryException $exception) {
            $this->assertStringContainsString('forced delete failure', $exception->getMessage());
        }
        $this->assertDatabaseCount('subscriptions', 2); $this->assertDatabaseCount('email_list_subscription', 2);
    }

    public function test_add_and_export_actions_retain_their_contract_and_addition_sends_no_subscriber_email()
    {
        $this->admin(); Notification::fake(); Mail::fake();
        $this->post(route('admin.subscriptions.store'), ['emails' => 'new@example.test, other@example.test', 'origin_url' => 'Admin import'])
            ->assertSessionHas('status');
        $this->assertDatabaseCount('subscriptions', 2);
        $this->post(route('admin.subscriptions.store'), ['emails' => 'new@example.test', 'origin_url' => 'Again'])->assertSessionHas('status');
        $this->assertDatabaseCount('subscriptions', 2); Mail::assertNothingSent();
        Model::withoutEvents(function () { create(User::class, ['email' => 'export@example.test']); });
        $this->get(route('admin.subscriptions.export', ['type' => 'fans']))->assertOk()->assertSee('export@example.test');
        $this->get(route('admin.subscriptions.export', ['type' => 'members']))->assertOk();
        $this->withExceptionHandling();
        $this->postJson(route('admin.subscriptions.store'), ['emails' => ['bad'], 'origin_url' => ['bad']])->assertUnprocessable();
    }

    public function test_default_sort_index_removes_temporary_sort_and_rolls_back_without_data_loss()
    {
        $this->subscribers(2);
        $plan = function () {
            return implode(' ', array_map(function ($row) { return $row->detail; }, DB::select('EXPLAIN QUERY PLAN SELECT id FROM subscriptions ORDER BY created_at DESC, id DESC LIMIT 12')));
        };
        $this->assertStringContainsString('subscriptions_admin_created_at_id_index', $plan());
        $this->assertStringNotContainsString('TEMP B-TREE', $plan());
        $migration = new \AddAdminSubscriberDirectoryIndex;
        $migration->down();
        $this->assertStringContainsString('TEMP B-TREE', $plan());
        $this->assertDatabaseCount('subscriptions', 2);
        $migration->up();
        $this->assertStringContainsString('subscriptions_admin_created_at_id_index', $plan());
    }

    public function test_invalid_add_form_shapes_redisplay_safely_with_associated_labels()
    {
        $this->admin(); $this->withExceptionHandling();
        $this->from(route('admin.subscriptions.index'))->post(route('admin.subscriptions.store'), [
            'emails' => ['malformed'], 'origin_url' => 'Admin import',
        ])->assertRedirect(route('admin.subscriptions.index'))->assertSessionHasErrors('emails');
        $response = $this->table()->assertOk();
        $xpath = $this->xpath($response);
        $this->assertCount(1, $xpath->query('//label[@for="subscriber-emails"]'));
        $this->assertCount(1, $xpath->query('//textarea[@id="subscriber-emails" and @data-text-improver="off"]'));
        $this->assertCount(1, $xpath->query('//label[@for="subscriber-origin"]'));
        $this->assertDatabaseCount('subscriptions', 0);
    }
}
