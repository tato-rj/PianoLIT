<?php

namespace Tests\Review;

use App\{Admin, User};
use App\Services\{CampaignAttributionReport, CampaignParameters};
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\{DB, Schema};
use Illuminate\Support\Str;

class CampaignReportTest extends ReviewTestCase
{
    public function setUp(): void
    {
        parent::setUp();
        // Exercise the retained historical report explicitly. New collection
        // and the production admin entry point are retired by default.
        config(['attribution.enabled' => true]);
    }

    private function visit($source, array $extra = [], $user = null, $created = null, $landing = '')
    {
        $parameters = CampaignParameters::fromQuery(['utm_source' => $source] + $extra);
        $id = (string) Str::uuid();
        DB::table('campaign_visits')->insert($parameters + [
            'id' => $id, 'campaign' => CampaignParameters::key($parameters), 'user_id' => optional($user)->id,
            'landing_url' => $landing,
            'consented_at' => now(), 'created_at' => $created ?: now(), 'expires_at' => now()->addDays(30),
        ]);
        return $id;
    }

    private function paid($visit, $identity, $currency = 'usd', $amount = 999, $paidAt = null)
    {
        DB::table('campaign_subscriptions')->insert([
            'visit_id' => $visit, 'stripe_subscription_id' => 'sub_'.$identity, 'stripe_customer_id' => 'cus_'.$identity,
            'billing_account' => 'us', 'plan' => 'monthly', 'trial_started_at' => now(), 'subscribed_at' => now(),
        ]);
        $this->invoice($identity, $currency, $amount, $paidAt ?: now());
    }

    private function invoice($identity, $currency, $amount, $paidAt, $invoice = null, $customer = null)
    {
        DB::table('stripe_payment_receipts')->insert([
            'kind' => 'invoice', 'stripe_object_id' => $invoice ?: 'in_'.$identity,
            'stripe_subscription_id' => 'sub_'.$identity, 'stripe_customer_id' => $customer ?: 'cus_'.$identity,
            'currency' => $currency, 'amount' => $amount, 'paid_at' => $paidAt, 'created_at' => now(),
        ]);
    }

    private function admin()
    {
        $this->actingAs(create(Admin::class), 'admin');
    }

    public function test_admin_report_requires_admin_guard_and_shows_dynamic_groups_with_currency_separation()
    {
        $this->withExceptionHandling()->get(route('admin.analytics.campaigns'))->assertRedirect();
        $user = Model::withoutEvents(function () { return create(User::class); });
        $this->actingAs($user, 'web')->get(route('admin.analytics.campaigns'))->assertRedirect();
        $youtube = $this->visit('youtube', ['utm_campaign' => 'new_piece', 'utm_content' => 'description']);
        $this->visit('youtube.com', ['utm_campaign' => 'new_piece', 'utm_content' => 'description']);
        $second = $this->visit('youtube.com', ['utm_campaign' => 'new_piece', 'utm_content' => 'description']);
        $this->visit('chatgpt.com');
        $this->visit('reddit.com');
        $this->visit('new-site.example', ['utm_campaign' => 'launch']);
        $this->paid($youtube, 'first');
        $this->paid($second, 'second', 'chf', 1299);
        $this->invoice('first', 'usd', 999, now()->addMonth(), 'in_renewal');
        $this->invoice('first', 'usd', 99999, now()->subDay(), 'in_wrong_customer', 'cus_other');
        $this->admin();
        $page = $this->get(route('admin.analytics.campaigns'))->assertOk()->assertSee('Campaign tracking is enabled')
            ->assertSee('new-site.example')->assertSee('chatgpt.com')->assertSee('reddit.com')->assertSee('CHF 12.99, USD 9.99')
            ->assertDontSee('cdn.datatables.net')->assertDontSee('data-admin-datatable');
        $rows = $page->viewData('campaigns');
        $this->assertSame(4, $rows->total());
        $this->assertSame('youtube.com', $rows[0]->utm_source);
        $this->assertSame(3, $rows[0]->arrivals);
        $this->assertSame(2, $rows[0]->trials);
        $this->assertSame(2, $rows[0]->first_paid_subscriptions);
        $this->assertSame(0, $rows[1]->trials);
        $this->assertSame(0, $rows[1]->first_paid_subscriptions);
        if ($directory = getenv('CAMPAIGN_REPORT_PREVIEW_DIR')) file_put_contents($directory.'/campaigns.html', $page->getContent());
    }

    public function test_sql_pagination_search_and_sorting_cover_every_campaign_and_preserve_filters()
    {
        for ($index = 0; $index < 27; $index++) $this->visit(sprintf('source%02d.example', $index));
        $this->visit('source26.example');
        $this->admin();
        $first = $this->get(route('admin.analytics.campaigns', ['sort' => 'source_asc', 'period' => 'all']))->assertOk();
        $this->assertCount(12, $first->viewData('campaigns'));
        $this->assertSame(27, $first->viewData('campaigns')->total());
        $this->assertStringContainsString('period=all', $first->viewData('campaigns')->nextPageUrl());
        $this->assertStringContainsString('sort=source_asc', $first->viewData('campaigns')->nextPageUrl());
        $last = $this->get(route('admin.analytics.campaigns', ['sort' => 'source_asc', 'period' => 'all', 'page' => 3]))->assertOk();
        $this->assertCount(3, $last->viewData('campaigns'));
        $this->assertSame('source24.example', $last->viewData('campaigns')[0]->utm_source);
        $search = $this->get(route('admin.analytics.campaigns', ['search' => 'SOURCE26']))->assertOk();
        $this->assertSame(1, $search->viewData('campaigns')->total());
        $this->assertSame(2, $search->viewData('campaigns')[0]->arrivals);
        $this->assertSame('source26.example', $this->get(route('admin.analytics.campaigns'))->viewData('campaigns')[0]->utm_source);
        $this->assertSame('source26.example', $this->get(route('admin.analytics.campaigns', ['sort' => 'source_desc']))->viewData('campaigns')[0]->utm_source);
        $this->assertCount(24, $this->get(route('admin.analytics.campaigns', ['per_page' => 24]))->viewData('campaigns'));
        $this->assertSame(0, $this->get(route('admin.analytics.campaigns', ['search' => '%']))->viewData('campaigns')->total());
        foreach (['sort' => 'utm_source desc; drop table users', 'per_page' => 1000, 'period' => 'forever', 'page' => -1, 'search' => ['bad']] as $field => $value) {
            $this->withExceptionHandling()->getJson(route('admin.analytics.campaigns', [$field => $value]))->assertStatus(422);
        }
        if ($directory = getenv('CAMPAIGN_REPORT_PREVIEW_DIR')) file_put_contents($directory.'/campaigns-many.html', $first->getContent());
    }

    public function test_arrival_cohorts_and_placements_stay_distinct_and_excluded_accounts_never_enter_reports()
    {
        $old = $this->visit('youtube.com', ['utm_campaign' => 'older'], null, now()->subDays(45));
        $this->paid($old, 'old');
        $this->visit('youtube.com', ['utm_campaign' => 'current', 'utm_content' => 'description']);
        $this->visit('youtube.com', ['utm_campaign' => 'current', 'utm_content' => 'comment']);
        foreach ([' ArthurVillar@Gmail.Com ', 'elena.dare@gmail.com', 'arthurvilar@gmail.com'] as $index => $email) {
            $excluded = Model::withoutEvents(function () use ($email) { return create(User::class, ['email' => $email]); });
            $visit = $this->visit('excluded.example', [], $excluded);
            $this->paid($visit, 'excluded'.$index);
        }
        $this->admin();
        $this->assertSame(2, $this->get(route('admin.analytics.campaigns'))->viewData('campaigns')->total());
        $all = $this->get(route('admin.analytics.campaigns', ['period' => 'all', 'sort' => 'paid_desc']))->viewData('campaigns');
        $this->assertSame(3, $all->total());
        $this->assertSame('older', $all[0]->utm_campaign);
        $this->assertSame('USD 9.99', $all[0]->first_payment_revenue);
        $report = (new CampaignAttributionReport)->rows(now()->subDays(90));
        $this->assertCount(3, $report);
        $this->assertSame(1, array_sum(array_column($report, 'first_paid_subscriptions')));
        $this->assertSame(1, $this->get(route('admin.analytics.campaigns', ['search' => 'comment']))->viewData('campaigns')->total());
        $this->assertSame(0, $this->get(route('admin.analytics.campaigns', ['search' => '_']))->viewData('campaigns')->total());
    }

    public function test_migration_backfills_the_original_campaign_without_losing_subscription_links()
    {
        $visit = $this->visit('youtube.com');
        DB::table('campaign_visits')->where('id', $visit)->update(['campaign' => 'respighi_notturno']);
        $this->paid($visit, 'legacy');
        $migration = new \ExpandCampaignAttributionSources;
        // Recreate the pre-upgrade schema without SQLite's table rebuild
        // cascading into the already-seeded subscription fixtures.
        Schema::disableForeignKeyConstraints();
        $migration->down();
        Schema::enableForeignKeyConstraints();
        $migration->up();
        $row = (new CampaignAttributionReport)->rows(now()->subDay())[0];
        $this->assertSame('youtube.com', $row['source']);
        $this->assertSame('respighi_notturno', $row['campaign']);
        $this->assertSame('organic_video', $row['medium']);
        $this->assertSame('description', $row['content']);
        $this->assertSame(1, $row['first_paid_subscriptions']);
        $this->assertSame('USD 9.99', $row['first_payment_revenue']);
    }

    public function test_reports_include_campaign_only_and_medium_only_arrivals_without_inventing_missing_fields()
    {
        $campaign = $this->visit('', ['utm_campaign' => 'autumn_repertoire']);
        $this->visit('', ['utm_campaign' => 'autumn_repertoire']);
        $this->visit('', ['utm_medium' => 'newsletter']);
        $this->visit('', ['utm_medium' => 'video', 'utm_campaign' => 'autumn_repertoire']);
        $this->visit('youtube.com', ['utm_medium' => 'video', 'utm_campaign' => 'autumn_repertoire']);
        $this->paid($campaign, 'campaignonly');
        // Historical rows with no attribution labels must still stay out.
        DB::table('campaign_visits')->insert([
            'id' => (string) Str::uuid(), 'campaign' => 'unlabeled', 'consented_at' => now(),
            'created_at' => now(), 'expires_at' => now()->addDays(30),
        ]);
        $this->admin();
        $page = $this->get(route('admin.analytics.campaigns'))->assertOk()
            ->assertSee('No source specified')->assertSee('No campaign specified')->assertSee('newsletter')->assertSee('USD 9.99');
        $rows = $page->viewData('campaigns');
        $this->assertSame(4, $rows->total());
        $this->assertSame('', $rows[0]->utm_source);
        $this->assertSame('', $rows[0]->utm_medium);
        $this->assertSame('autumn_repertoire', $rows[0]->utm_campaign);
        $this->assertSame(2, $rows[0]->arrivals);
        $this->assertSame(1, $rows[0]->trials);
        $this->assertSame(1, $rows[0]->first_paid_subscriptions);
        $this->assertSame('USD 9.99', $rows[0]->first_payment_revenue);
        $this->assertSame(3, $this->get(route('admin.analytics.campaigns', ['search' => 'autumn_repertoire']))->viewData('campaigns')->total());
        $this->assertSame(1, $this->get(route('admin.analytics.campaigns', ['search' => 'newsletter']))->viewData('campaigns')->total());
        $report = (new CampaignAttributionReport)->rows(now()->subDay());
        $this->assertCount(4, $report);
        $this->assertSame('', $report[0]['source']);
        $this->assertSame('autumn_repertoire', $report[0]['campaign']);
        $this->assertSame(2, $report[0]['arrivals']);
        if ($directory = getenv('CAMPAIGN_REPORT_PREVIEW_DIR')) file_put_contents($directory.'/campaigns-partial.html', $page->getContent());
    }

    public function test_empty_and_disabled_reports_remain_useful_and_cli_discovers_new_sources()
    {
        $this->admin();
        $empty = $this->get(route('admin.analytics.campaigns'))->assertOk()->assertSee('No measured campaign arrivals');
        if ($directory = getenv('CAMPAIGN_REPORT_PREVIEW_DIR')) file_put_contents($directory.'/campaigns-empty.html', $empty->getContent());
        $this->visit('future-source.example');
        $this->artisan('attribution:report')->expectsTable(
            ['Campaign', 'Source', 'Medium', 'Placement', 'Arrivals', 'Trials', 'First paid subscriptions', 'First payment revenue'],
            [['(not specified)', 'future-source.example', '', '', 1, 0, 0, '']]
        )->assertExitCode(0);
        config(['attribution.enabled' => false, 'attribution.conversion_links_enabled' => false]);
        $this->get(route('admin.analytics.campaigns'))->assertRedirect('https://analytics.google.com/analytics/web/#/a137232556p399721623/reports/reportinghub');
        $this->assertDatabaseCount('campaign_visits', 1);
    }

    public function test_anonymous_arrival_migration_preserves_existing_visits_and_subscription_links()
    {
        $visit = $this->visit('youtube.com');
        $this->paid($visit, 'beforemigration');
        $before = (array) DB::table('campaign_visits')->sole();
        $subscription = (array) DB::table('campaign_subscriptions')->sole();
        $migration = new \AllowAnonymousCampaignArrivals;
        $migration->down();
        $migration->up();
        $this->assertEquals($before, (array) DB::table('campaign_visits')->sole());
        $this->assertSame($subscription, (array) DB::table('campaign_subscriptions')->sole());
        $this->assertSame(1, (new CampaignAttributionReport)->rows(now()->subDay())[0]['first_paid_subscriptions']);
        $anonymous = $this->visit('reddit.com');
        DB::table('campaign_visits')->where('id', $anonymous)->update(['consented_at' => null]);
        $migration->down(); // A code rollback must neither discard nor invent consent.
        $this->assertDatabaseHas('campaign_visits', ['id' => $anonymous, 'consented_at' => null]);
        $this->assertDatabaseHas('campaign_subscriptions', $subscription);
        $migration->up();
        $this->assertDatabaseHas('campaign_visits', ['id' => $anonymous, 'consented_at' => null]);
        $this->assertSame([], DB::select('PRAGMA foreign_key_check'));
        $this->assertSame(1, (int) DB::select('PRAGMA foreign_keys')[0]->foreign_keys);
    }

    public function test_admin_splits_destinations_without_mixing_their_conversions_or_revenue()
    {
        $first = $this->visit('youtube.com', [], null, null, route('webapp.pieces.show', 174));
        $this->visit('youtube.com', [], null, null, route('webapp.pieces.show', 174));
        $second = $this->visit('youtube.com', [], null, null, route('webapp.pieces.show', 175));
        $this->paid($first, 'landingone');
        $this->paid($second, 'landingtwo', 'chf', 1299);
        $this->admin();
        $page = $this->get(route('admin.analytics.campaigns'))->assertOk()->assertSee('Landing page');
        $rows = $page->viewData('campaigns');
        $this->assertSame(2, $rows->total());
        $this->assertSame(route('webapp.pieces.show', 174), $rows[0]->landing_url);
        $this->assertSame(2, $rows[0]->arrivals);
        $this->assertSame(1, $rows[0]->trials);
        $this->assertSame(1, $rows[0]->first_paid_subscriptions);
        $this->assertSame('USD 9.99', $rows[0]->first_payment_revenue);
        $this->assertSame('CHF 12.99', $rows[1]->first_payment_revenue);
        $filtered = $this->get(route('admin.analytics.campaigns', ['search' => '/pieces/175']))->assertOk()->viewData('campaigns');
        $this->assertSame(1, $filtered->total());
        $this->assertSame('CHF 12.99', $filtered[0]->first_payment_revenue);
        // Keep the existing CLI's UTM-level totals and output fields intact.
        $cli = (new CampaignAttributionReport)->rows(now()->subDay());
        $this->assertCount(1, $cli);
        $this->assertSame(3, $cli[0]['arrivals']);
        $this->assertSame('CHF 12.99, USD 9.99', $cli[0]['first_payment_revenue']);
        if ($directory = getenv('CAMPAIGN_REPORT_PREVIEW_DIR')) file_put_contents($directory.'/campaigns-landings.html', $page->getContent());
    }

    public function test_page_group_pagination_unknown_history_and_untrusted_destinations()
    {
        for ($index = 0; $index < 26; $index++) $this->visit('youtube.com', [], null, null, config('app.url').sprintf('/page/%02d', $index));
        $this->visit('youtube.com');
        $this->visit('youtube.com', [], null, null, 'https://untrusted.example/redirect');
        $this->visit('youtube.com', [], null, null, config('app.url').'/password/reset/[redacted]');
        $this->admin();
        $page = $this->get(route('admin.analytics.campaigns', ['sort' => 'source_asc', 'period' => 'all']))->assertOk();
        $this->assertSame(29, $page->viewData('campaigns')->total());
        $this->assertCount(12, $page->viewData('campaigns'));
        $this->assertStringContainsString('period=all', $page->viewData('campaigns')->nextPageUrl());
        $all = $this->get(route('admin.analytics.campaigns', ['per_page' => 48]))->assertOk()->assertSee('Not recorded')->assertDontSee('untrusted.example');
        foreach ($all->viewData('campaigns') as $row) {
            if ($row->landing_url === '' || strpos($row->landing_url, 'untrusted.example') !== false || strpos($row->landing_url, '[redacted]') !== false) {
                $this->assertNull($row->landing_page_url);
            }
        }
    }

    public function test_landing_migration_preserves_legacy_rows_and_subscription_children()
    {
        $visit = $this->visit('youtube.com');
        $this->paid($visit, 'landingmigration');
        $before = (array) DB::table('campaign_visits')->sole();
        $subscription = (array) DB::table('campaign_subscriptions')->sole();
        $migration = new \AddCampaignLandingUrl;
        $migration->down();
        $migration->up();
        $this->assertEquals($before, (array) DB::table('campaign_visits')->sole());
        $this->assertSame($subscription, (array) DB::table('campaign_subscriptions')->sole());
        $this->assertSame('', DB::table('campaign_visits')->sole()->landing_url);
        $this->assertSame(1, (int) DB::select('PRAGMA foreign_keys')[0]->foreign_keys);
        $this->assertSame([], DB::select('PRAGMA foreign_key_check'));
    }

    public function test_piece_landing_titles_use_one_bulk_query_for_the_whole_admin_page()
    {
        $pieces = Model::withoutEvents(function () { return create(\App\Piece::class, [], 12); });
        foreach ($pieces as $piece) $this->visit('youtube.com', [], null, null, route('webapp.pieces.show', $piece->id));
        $this->admin();
        DB::enableQueryLog();
        $page = $this->get(route('admin.analytics.campaigns'))->assertOk();
        $queries = DB::getQueryLog();
        DB::disableQueryLog();
        $titles = array_filter($queries, function ($query) { return strpos($query['query'], 'from "pieces" as "p"') !== false; });
        $this->assertCount(1, $titles);
        $this->assertCount(12, $page->viewData('campaigns'));
        foreach ($page->viewData('campaigns') as $row) {
            $this->assertNotSame(parse_url($row->landing_url, PHP_URL_PATH), $row->landing_page_label);
            $this->assertSame($row->landing_url, $row->landing_page_url);
        }
    }
}
