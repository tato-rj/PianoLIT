<?php

namespace Tests\Review;

use App\{Admin, FeedbackSubmission, User};
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class AdminFeedbackTableTest extends ReviewTestCase
{
    protected function table(array $parameters = [])
    {
        $this->app->forgetInstance('datatables.request');
        return $this->getJson(route('admin.feedback.index', $parameters + ['draw' => 1, 'start' => 0, 'length' => 12]),
            ['X-Requested-With' => 'XMLHttpRequest']);
    }

    protected function admin()
    {
        $this->actingAs(create(Admin::class), 'admin');
    }

    public function test_initial_filtered_pages_keep_their_rows_and_shared_loading_frame()
    {
        $this->admin();
        for ($i = 0; $i < 14; $i++) FeedbackSubmission::create(['rating' => 'good', 'message' => 'Good note '.$i]);
        FeedbackSubmission::create(['rating' => 'bad', 'message' => 'Other rating']);
        $response = $this->get(route('admin.feedback.index', ['rating' => 'good', 'page' => 2]))->assertOk();
        $response->assertSee('deferLoading: 14', false)->assertDontSee('Other rating');
        $dom = new \DOMDocument;
        @$dom->loadHTML($response->getContent());
        $xpath = new \DOMXPath($dom);
        $this->assertCount(2, $xpath->query('//table[@id="feedback-table"]/tbody/tr'));
        $this->assertCount(1, $xpath->query('//table[@id="feedback-table" and @data-page="2"]/ancestor::*[@data-admin-datatable]'));
        $this->assertCount(1, $xpath->query('//*[@data-admin-table-fallback-pagination]/ancestor::*[@data-admin-datatable]'));
        $this->assertCount(1, $xpath->query('//script[contains(@src,"/js/components/admin-table-loading.js")]'));
        $empty = $this->get(route('admin.feedback.index', ['rating' => 'not_bad']))->assertOk();
        $empty->assertSee('deferLoading: 0', false)->assertSee('No feedback with this rating yet.');
    }

    public function test_datatable_paginates_and_searches_the_full_filtered_list()
    {
        $this->admin();
        $user = Model::withoutEvents(function () {
            return create(User::class, ['first_name' => 'Jamie', 'last_name' => 'Parker', 'email' => 'jamie@example.test']);
        });
        for ($i = 0; $i < 25; $i++) FeedbackSubmission::create(['rating' => 'good', 'message' => sprintf('Note %02d', $i), 'user_id' => $i === 0 ? $user->id : null]);
        FeedbackSubmission::create(['rating' => 'not_bad', 'message' => 'A different rating']);
        $this->app->forgetInstance('datatables.request');
        $this->getJson(route('admin.feedback.index'), ['X-Requested-With' => 'XMLHttpRequest'])->assertOk()->assertJsonCount(12, 'data');
        $first = $this->table(['rating' => 'good'])->assertOk()->assertJsonCount(12, 'data')
            ->assertJsonPath('recordsTotal', 25)->assertJsonPath('recordsFiltered', 25);
        $second = $this->table(['rating' => 'good', 'start' => 12])->assertOk()->assertJsonCount(12, 'data');
        $this->assertSame([], array_intersect(array_column($first->json('data'), 'message'), array_column($second->json('data'), 'message')));
        $this->table(['rating' => 'good', 'start' => 24])->assertOk()->assertJsonCount(1, 'data');
        $this->table(['length' => 24])->assertOk()->assertJsonCount(24, 'data');
        foreach (['Note 00', 'Jamie Parker', 'jamie@example.test'] as $search) {
            $this->table(['rating' => 'good', 'search' => ['value' => $search]])->assertOk()
                ->assertJsonPath('recordsTotal', 25)->assertJsonPath('recordsFiltered', 1)->assertJsonCount(1, 'data');
        }
        $this->table(['search' => ['value' => 'Not bad']])->assertOk()->assertJsonPath('recordsFiltered', 1);
        $this->table(['rating' => 'good', 'search' => ['value' => 'Guest']])->assertOk()->assertJsonPath('recordsFiltered', 24);
        $this->table(['rating' => 'good', 'search' => ['value' => 'different']])->assertOk()->assertJsonPath('recordsFiltered', 0);
    }

    public function test_sorting_uses_fixed_columns_and_stable_ties()
    {
        $this->admin();
        $users = Model::withoutEvents(function () {
            return [create(User::class, ['first_name' => 'Zara']), create(User::class, ['first_name' => 'Anna'])];
        });
        $older = FeedbackSubmission::create(['rating' => 'good', 'message' => 'Alpha', 'user_id' => $users[0]->id]);
        $newer = FeedbackSubmission::create(['rating' => 'bad', 'message' => 'Zulu', 'user_id' => $users[1]->id]);
        $older->forceFill(['created_at' => '2026-10-01 12:00:00'])->save();
        $newer->forceFill(['created_at' => '2026-10-02 12:00:00'])->save();
        foreach ([0 => ['Zulu', 'Alpha'], 1 => ['Alpha', 'Zulu'], 2 => ['Zulu', 'Alpha'], 3 => ['Alpha', 'Zulu']] as $column => $descending) {
            foreach (['asc', 'desc'] as $direction) {
                $data = $this->table(['order' => [['column' => $column, 'dir' => $direction]]])->assertOk()->json('data');
                $this->assertSame($direction === 'desc' ? $descending : array_reverse($descending),
                    array_map(function ($row) { return trim(strip_tags($row['message'])); }, $data));
            }
        }
        $newer->forceFill(['created_at' => $older->created_at])->save();
        $this->assertStringContainsString('Zulu', $this->table()->assertOk()->json('data.0.message'));
    }

    public function test_ajax_cells_preserve_escaped_content_context_and_protected_delete_forms()
    {
        $this->admin();
        FeedbackSubmission::create(['rating' => 'good', 'message' => '<script>alert(1)</script> 100% _unique',
            'context' => ['url' => route('webapp.settings'), 'route' => 'webapp.settings', 'browser' => '<img src=x>', 'os' => 'iOS', 'device_type' => 'mobile']]);
        $response = $this->table(['columns' => [['data' => 'password', 'name' => 'users.password', 'searchable' => true,
            'search' => ['value' => 'untrusted']]]])->assertOk();
        $row = $response->json('data.0');
        $this->assertEqualsCanonicalizing(['submitted', 'rating', 'message', 'sender', 'actions'], array_keys($row));
        foreach (['&lt;script&gt;', '&lt;img', 'Context', 'webapp.settings', 'noopener noreferrer'] as $text) $this->assertStringContainsString($text, $row['message']);
        $this->assertStringNotContainsString('<script>', $row['message']);
        foreach (['name="_token"', 'name="_method"', 'value="DELETE"', 'aria-label="Delete feedback"', 'icon-trash-2'] as $text) $this->assertStringContainsString($text, $row['actions']);
        $this->table(['search' => ['value' => '%']])->assertOk()->assertJsonPath('recordsFiltered', 1);
        $this->table(['search' => ['value' => '_unique']])->assertOk()->assertJsonPath('recordsFiltered', 1);
        $this->table(['search' => ['value' => '_absent']])->assertOk()->assertJsonPath('recordsFiltered', 0);
    }

    public function test_ajax_validation_and_admin_authorization()
    {
        $this->withExceptionHandling();
        $this->table()->assertUnauthorized();
        $user = Model::withoutEvents(function () { return create(User::class); });
        $this->actingAs($user, 'web');
        $this->table()->assertUnauthorized();
        $this->admin();
        foreach ([['draw' => '<script>'], ['start' => -1], ['length' => -1], ['length' => 1000], ['rating' => ['good']],
            ['search' => ['value' => ['bad']]], ['order' => [['column' => 4, 'dir' => 'asc']]],
            ['columns' => 'bad'], ['columns' => [['name' => 'missing data']]], ['columns' => [['data' => 'sender', 'name' => ['bad']]]],
            ['columns' => [['data' => 'sender', 'search' => ['value' => ['bad']]]]],
            ['order' => [['column' => 0, 'dir' => 'desc; DROP TABLE users']]]] as $invalid) {
            $this->table($invalid)->assertUnprocessable();
        }
        $this->table()->assertOk()->assertJsonCount(0, 'data');
    }

    public function test_ajax_delete_removes_only_the_route_bound_row_without_redirecting()
    {
        $this->admin();
        $target = FeedbackSubmission::create(['rating' => 'good']);
        $other = FeedbackSubmission::create(['rating' => 'bad']);
        $this->postJson(route('admin.feedback.destroy', $target), ['_method' => 'DELETE', 'feedback' => $other->id],
            ['X-Requested-With' => 'XMLHttpRequest'])->assertOk()->assertExactJson(['deleted' => true]);
        $this->assertDatabaseMissing('feedback_submissions', ['id' => $target->id]);
        $this->assertDatabaseHas('feedback_submissions', ['id' => $other->id]);
    }

    public function test_member_serialization_does_not_run_unused_activity_queries_per_row()
    {
        $this->admin();
        $user = Model::withoutEvents(function () { return create(User::class); });
        for ($i = 0; $i < 24; $i++) FeedbackSubmission::create(['rating' => 'good', 'user_id' => $user->id]);
        $counts = [];
        foreach ([12, 24] as $length) {
            DB::enableQueryLog(); DB::flushQueryLog();
            $this->table(compact('length'))->assertOk()->assertJsonCount($length, 'data');
            $counts[] = count(DB::getQueryLog());
            DB::disableQueryLog();
        }
        $this->assertSame($counts[0], $counts[1]);
        $this->assertLessThanOrEqual(5, $counts[1]);
    }
}
