<?php

namespace Tests\Review;

use App\{Admin, EmailCampaignReport, EmailList, User};
use App\Services\Admin\{EmailReportsTable, EmailReportRecipientsTable};
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AdminEmailReportsTest extends ReviewTestCase
{
    private function admin()
    {
        $this->actingAs(create(Admin::class), 'admin');
    }

    private function campaigns($count = 30)
    {
        return collect(range(1, $count))->map(function ($i) {
            return EmailCampaignReport::create(['list_id' => 'free-pick.'.(1700000000 + $i), 'name' => sprintf('Free Pick %02d', $i),
                'sent_at' => now()->subDays($i), 'emails_count' => 100 + $i, 'delivered_count' => 90 - $i,
                'failed_count' => $i % 3, 'opens_count' => $i % 10, 'clicks_count' => $i % 4]);
        });
    }

    private function logs($campaign, $count = 30)
    {
        foreach (range(1, $count) as $i) {
            $delivered = $i % 3 === 1;
            $failed = $i % 3 === 2;
            DB::table('email_logs')->insert(['list_id' => $campaign->list_id, 'message_id' => 'message-'.$i,
                'sender_type' => EmailList::class, 'sender_id' => 1, 'recipient' => sprintf('reader%02d@example.test', $i),
                'created_at' => now()->subMinutes($i), 'updated_at' => now(),
                'delivered_at' => $delivered ? now()->subMinutes($i) : null, 'unique_delivered' => $delivered ? 1 : 0,
                'failed_at' => $failed ? now()->subMinutes($i) : null, 'unique_failed' => $failed ? 1 : 0,
                'unique_opened' => $delivered ? 1 : 0, 'opened' => $delivered ? $i + 1 : 0,
                'unique_clicked' => $delivered && $i % 2 ? 1 : 0, 'clicked' => $delivered && $i % 2 ? 2 : 0]);
        }
    }

    private function xpath($response)
    {
        $dom = new \DOMDocument;
        @$dom->loadHTML($response->getContent());
        return new \DOMXPath($dom);
    }

    public function test_directory_renders_shared_table_with_real_first_page_metrics_actions_and_page_selection()
    {
        $this->admin(); $campaigns = $this->campaigns();
        $page = $this->get(route('admin.subscriptions.reports.index'))->assertOk()->assertSee('Showing 1–12 of 30 results');
        foreach (['cdn.datatables.net', 'new DataTable', 'selectedReportIds', 'check-datatable'] as $legacy) $page->assertDontSee($legacy);
        $xpath = $this->xpath($page);
        $this->assertCount(12, $xpath->query('//table[@id="reports-table"]/tbody/tr'));
        $this->assertCount(0, $xpath->query('//section[contains(@class,"admin-table")]//h2'));
        $this->assertCount(12, $xpath->query('//input[@name="ids[]" and @form="reports-selection-form"]'));
        $this->assertCount(1, $xpath->query('//form[@id="reports-selection-form"]/input[@name="_token"]'));
        $this->assertCount(1, $xpath->query('//form[@id="reports-selection-form"]/input[@name="_method" and @value="DELETE"]'));
        $this->assertCount(1, $xpath->query('//nav[@aria-label="Breadcrumb"]'));
        $this->assertSame(route('admin.subscriptions.reports.show', $campaigns[0]->list_id), $xpath->query('//table/tbody/tr[1]/td[3]/a')[0]->getAttribute('href'));
        $page->assertSee('88%')->assertSee('delivery and engagement history');
        $this->assertSame('89', $xpath->query('//table/tbody/tr[1]/td[5]/small')[0]->textContent);
        $page->assertDontSee('This also removes them from email lists.');
    }

    public function test_directory_search_page_sizes_pagination_literal_wildcards_and_filter_state()
    {
        $this->admin(); $this->campaigns(); $route = route('admin.subscriptions.reports.index');
        $first = $this->get($route)->assertOk()->viewData('reports');
        $second = $this->get($route.'?page=2')->assertOk()->viewData('reports');
        $this->assertCount(0, $first->pluck('id')->intersect($second->pluck('id')));
        $this->assertCount(12, $second); $this->assertCount(6, $this->get($route.'?page=3')->viewData('reports'));
        $filtered = $this->get($route.'?search=FREE+PICK&sort=emails_desc&per_page=24')->assertOk()->viewData('reports');
        $this->assertCount(24, $filtered);
        parse_str(parse_url($filtered->url(2), PHP_URL_QUERY), $query);
        $this->assertEquals(['search' => 'FREE PICK', 'sort' => 'emails_desc', 'per_page' => 24, 'page' => 2], $query);
        $this->assertSame(1, $this->get($route.'?search=1700000030')->viewData('reports')->total());
        EmailCampaignReport::where('id', $first[0]->id)->update(['name' => '100% _special !']);
        foreach (['%' => 1, '_special' => 1, '_absent' => 0, '!' => 1, "' OR 1=1 --" => 0] as $search => $total) {
            $this->assertSame($total, $this->get(route('admin.subscriptions.reports.index', compact('search')))->assertOk()->viewData('reports')->total());
        }
    }

    public function test_directory_sorts_use_the_existing_count_columns_and_stable_id_ties()
    {
        $this->admin(); $items = $this->campaigns(4);
        $columns = ['date' => 'sent_at', 'name' => 'name', 'emails' => 'emails_count', 'delivered' => 'delivered_count', 'failed' => 'failed_count', 'opened' => 'opens_count', 'clicked' => 'clicks_count'];
        foreach (array_keys(EmailReportsTable::SORTS) as $sort) {
            [$key, $direction] = explode('_', $sort); $column = $columns[$key];
            $expected = $items->sort(function ($a, $b) use ($column, $direction) {
                $comparison = $a->$column <=> $b->$column;
                return ($direction === 'asc' ? $comparison : -$comparison) ?: ($b->id <=> $a->id);
            })->pluck('id')->values()->all();
            $this->assertSame($expected, $this->get(route('admin.subscriptions.reports.index', compact('sort')))->assertOk()->viewData('reports')->pluck('id')->all());
        }
    }

    public function test_report_detail_uses_filtered_sql_recipients_and_independent_unfiltered_unique_totals()
    {
        $this->admin(); $campaign = $this->campaigns(1)[0]; $this->logs($campaign);
        // A legacy excluded row must be hidden even without a matching User record.
        DB::table('email_logs')->insert(['list_id' => $campaign->list_id, 'message_id' => 'excluded', 'sender_type' => EmailList::class, 'sender_id' => 1,
            'recipient' => ' ARTHURVILLAR@GMAIL.COM ', 'unique_delivered' => 1, 'unique_opened' => 1, 'created_at' => now()]);
        $route = route('admin.subscriptions.reports.show', $campaign->list_id);
        $page = $this->get($route)->assertOk()->assertSee('Showing 1–12 of 30 results')->assertDontSee('cdn.datatables.net')->assertDontSee('ARTHURVILLAR');
        $this->assertCount(12, $this->xpath($page)->query('//table[@id="report-table"]/tbody/tr'));
        $this->assertCount(1, $this->xpath($page)->query('//nav[@aria-label="Breadcrumb"]//a[@href="'.route('admin.subscriptions.reports.index').'"]'));
        $this->assertSame(30, (int) $page->viewData('event')->emails_count);
        $this->assertSame(10, (int) $page->viewData('event')->delivered_count);
        $this->assertSame(10, (int) $page->viewData('event')->opens_count);
        $this->assertCount(1, $this->xpath($page)->query('//header//button[@data-url="'.route('admin.subscriptions.reports.destroy', $campaign->list_id).'"]'));
        foreach (['delivered', 'failed', 'unknown'] as $status) {
            $filtered = $this->get($route.'?status='.$status)->assertOk();
            $this->assertSame(10, $filtered->viewData('recipients')->total());
            $this->assertSame(30, (int) $filtered->viewData('event')->emails_count);
        }
        $this->assertSame(1, $this->get($route.'?search=READER30')->viewData('recipients')->total());
        $filtered = $this->get($route.'?search=reader&sort=recipient_asc&per_page=24')->viewData('recipients');
        parse_str(parse_url($filtered->url(2), PHP_URL_QUERY), $query);
        $this->assertEquals(['search' => 'reader', 'sort' => 'recipient_asc', 'per_page' => 24, 'status' => 'all', 'page' => 2], $query);
        foreach (array_keys(EmailReportRecipientsTable::SORTS) as $sort) $this->get($route.'?sort='.$sort)->assertOk();
    }

    public function test_empty_reports_zero_totals_and_missing_or_legacy_details_are_safe()
    {
        $this->admin(); $this->withExceptionHandling();
        $page = $this->get(route('admin.subscriptions.reports.index'))->assertOk()->assertSee('No email reports yet')->assertDontSee('data-table-bulk-delete');
        $this->assertCount(1, $this->xpath($page)->query('//table/tbody/tr/td[@colspan="9"]'));
        $this->get(route('admin.subscriptions.reports.show', 'missing'))->assertNotFound();
        $campaign = $this->campaigns(1)[0];
        $this->get(route('admin.subscriptions.reports.show', $campaign->list_id))->assertOk()->assertSee('No matching recipients')->assertSee('0%');
        $this->logs($campaign, 1); $campaign->delete();
        $this->get(route('admin.subscriptions.reports.show', $campaign->list_id))->assertOk()->assertSee('reader01@example.test');
        DB::table('email_logs')->update(['list_id' => 'old-import-without-timestamp']);
        $this->get(route('admin.subscriptions.reports.show', 'old-import-without-timestamp'))->assertOk();
        DB::table('email_logs')->update(['created_at' => null]);
        $this->get(route('admin.subscriptions.reports.show', 'old-import-without-timestamp'))->assertOk()->assertSee('Date unknown');
        $this->get(route('admin.subscriptions.reports.index', ['search' => 'absent']))->assertOk()->assertSee('No matching reports');
    }

    public function test_admin_authentication_allowlisted_shapes_and_csrf_prevent_invalid_deletion()
    {
        $campaign = $this->campaigns(1)[0]; $this->withExceptionHandling();
        $this->getJson(route('admin.subscriptions.reports.index'))->assertUnauthorized();
        $this->getJson(route('admin.subscriptions.reports.show', $campaign->list_id))->assertUnauthorized();
        $user = Model::withoutEvents(function () { return create(User::class); });
        $this->actingAs($user, 'web')->deleteJson(route('admin.subscriptions.reports.destroy', $campaign->list_id))->assertUnauthorized();
        $this->admin();
        foreach ([['search' => ['bad']], ['sort' => 'password_asc'], ['sort' => ['date_desc']], ['page' => -1], ['per_page' => 13]] as $bad) {
            $this->getJson(route('admin.subscriptions.reports.index', $bad))->assertUnprocessable();
            $this->getJson(route('admin.subscriptions.reports.show', ['list' => $campaign->list_id] + $bad))->assertUnprocessable();
        }
        $this->getJson(route('admin.subscriptions.reports.show', ['list' => $campaign->list_id, 'status' => ['all']]))->assertUnprocessable();
        foreach ([[], ['ids' => []], ['ids' => 'one'], ['ids' => [$campaign->list_id, $campaign->list_id]],
            ['ids' => [$campaign->list_id, 'missing']], ['ids' => [['bad']]], ['ids' => [str_repeat('a', 256)]]] as $bad) {
            $this->deleteJson(route('admin.subscriptions.reports.destroy-many'), $bad)->assertUnprocessable();
            $this->assertDatabaseCount('email_campaign_reports', 1);
        }
        $this->deleteJson(route('admin.subscriptions.reports.destroy-many', ['sort' => 'password_asc']), ['ids' => [$campaign->list_id]])->assertUnprocessable();
        $this->app['env'] = 'review-csrf';
        $this->withSession(['_token' => 'report-csrf'])->deleteJson(route('admin.subscriptions.reports.destroy', $campaign->list_id))->assertStatus(419);
        $this->app['env'] = 'testing';
    }

    public function test_bulk_deletion_keeps_filters_clamps_page_and_deletes_only_selected_history()
    {
        $this->admin(); $items = $this->campaigns(25); $this->logs($items[24], 2);
        $filters = ['search' => 'Free Pick', 'sort' => 'name_asc', 'per_page' => 24, 'page' => 2];
        $page = $this->get(route('admin.subscriptions.reports.index', $filters))->assertOk();
        $this->assertCount(1, $this->xpath($page)->query('//table//input[@name="ids[]"]'));
        $action = $this->xpath($page)->query('//form[@id="reports-selection-form"]')[0]->getAttribute('action');
        unset($filters['page']);
        $this->delete($action, ['ids' => [$items[24]->list_id]])->assertRedirect(route('admin.subscriptions.reports.index', $filters));
        $this->assertDatabaseCount('email_campaign_reports', 24); $this->assertDatabaseCount('email_logs', 0);
        $this->assertDatabaseHas('email_campaign_reports', ['list_id' => $items[0]->list_id]);
        $this->logs($items[0], 2);
        $this->delete(route('admin.subscriptions.reports.destroy', $items[0]->list_id), ['ids' => [$items[1]->list_id]])->assertRedirect();
        $this->assertDatabaseMissing('email_campaign_reports', ['list_id' => $items[0]->list_id]);
        $this->assertDatabaseHas('email_campaign_reports', ['list_id' => $items[1]->list_id]);
        $this->assertDatabaseCount('email_logs', 0);
    }

    public function test_database_failure_rolls_back_summaries_and_all_delivery_history()
    {
        $this->admin(); $items = $this->campaigns(2); $this->logs($items[0], 2);
        DB::unprepared("CREATE TRIGGER fail_report_delete BEFORE DELETE ON email_campaign_reports WHEN OLD.id = ".$items[1]->id." BEGIN SELECT RAISE(ABORT, 'forced report failure'); END");
        try {
            $this->delete(route('admin.subscriptions.reports.destroy-many'), ['ids' => $items->pluck('list_id')->all()]);
            $this->fail('Expected report deletion to fail.');
        } catch (\Illuminate\Database\QueryException $exception) {
            $this->assertStringContainsString('forced report failure', $exception->getMessage());
        }
        $this->assertDatabaseCount('email_campaign_reports', 2); $this->assertDatabaseCount('email_logs', 2);
    }

    public function test_queries_are_bounded_and_cells_escape_stored_text()
    {
        $this->admin(); $items = $this->campaigns(); $this->logs($items[0]);
        foreach ([12, 24] as $size) {
            DB::enableQueryLog(); DB::flushQueryLog();
            $data = (new EmailReportsTable)->get(Request::create('/', 'GET', ['per_page' => $size]));
            $this->assertCount(2, DB::getQueryLog()); DB::flushQueryLog();
            $recipients = (new EmailReportRecipientsTable)->get(Request::create('/', 'GET', ['per_page' => $size]), $items[0]->list_id);
            $this->assertCount(2, DB::getQueryLog());
            $summary = (new EmailReportRecipientsTable)->summary($items[0]->list_id);
            $this->assertCount(4, DB::getQueryLog()); DB::disableQueryLog();
            $this->assertSame(30, (int) $summary->emails_count);
            $this->assertCount($size, $data['reports']); $this->assertCount($size, $recipients['recipients']);
        }
        $items[0]->update(['name' => '<script>alert(1)</script>']);
        $this->get(route('admin.subscriptions.reports.index'))->assertOk()->assertSee('&lt;script&gt;', false)->assertDontSee('<script>alert(1)</script>', false);
    }
}
