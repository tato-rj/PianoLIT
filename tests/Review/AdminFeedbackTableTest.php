<?php

namespace Tests\Review;

use App\{Admin, FeedbackSubmission, User};
use App\Services\Admin\FeedbackTable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AdminFeedbackTableTest extends ReviewTestCase
{
    protected function table(array $parameters = [])
    {
        return $this->get(route('admin.feedback.index', $parameters));
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

    public function test_filtered_pages_render_complete_rows_without_datatables_and_empty_results_have_no_pagination()
    {
        $this->admin();
        for ($i = 0; $i < 14; $i++) FeedbackSubmission::create(['rating' => 'good', 'message' => 'Good note '.$i]);
        FeedbackSubmission::create(['rating' => 'bad', 'message' => 'Other rating']);
        $response = $this->table(['rating' => 'good', 'page' => 2])->assertOk();
        $response->assertDontSee('Other rating')->assertDontSee('cdn.datatables.net')->assertDontSee('data-admin-datatable')
            ->assertDontSee('admin-table-loading.js')->assertDontSee('deferLoading')->assertSee('Showing 13–14 of 14 results');
        $xpath = $this->xpath($response);
        $this->assertCount(0, $xpath->query('//section[contains(@class,"admin-table")]//h2'));
        $this->assertCount(2, $xpath->query('//table[@id="feedback-table"]/tbody/tr'));
        $this->assertCount(1, $xpath->query('//table[@id="feedback-table"]/ancestor::section[contains(@class,"admin-table")]'));
        $this->assertCount(0, $xpath->query('//form[contains(@class,"admin-table__filters")]//input[@name="page"]'));
        $empty = $this->table(['rating' => 'not_bad'])->assertOk()->assertSee('No feedback with this rating yet.');
        $this->assertCount(1, $this->xpath($empty)->query('//table[@id="feedback-table"]/tbody/tr/td[@colspan="5"]'));
        $this->assertCount(0, $this->xpath($empty)->query('//ul[contains(@class,"pagination")]'));
    }

    public function test_search_and_pagination_cover_the_full_filtered_list()
    {
        $this->admin();
        $user = Model::withoutEvents(function () {
            return create(User::class, ['first_name' => 'Jamie', 'last_name' => 'Parker', 'email' => 'jamie@example.test']);
        });
        for ($i = 0; $i < 25; $i++) FeedbackSubmission::create(['rating' => 'good', 'message' => sprintf('Note %02d', $i), 'user_id' => $i === 0 ? $user->id : null]);
        FeedbackSubmission::create(['rating' => 'not_bad', 'message' => 'A different rating']);
        $first = $this->table(['rating' => 'good'])->assertOk()->viewData('submissions');
        $second = $this->table(['rating' => 'good', 'page' => 2])->assertOk()->viewData('submissions');
        $this->assertSame(25, $first->total()); $this->assertCount(12, $first); $this->assertCount(12, $second);
        $this->assertCount(0, $first->pluck('id')->intersect($second->pluck('id')));
        $this->assertCount(1, $this->table(['rating' => 'good', 'page' => 3])->assertOk()->viewData('submissions'));
        $this->assertCount(24, $this->table(['per_page' => 24])->assertOk()->viewData('submissions'));
        foreach (['Note 00', 'Jamie Parker', 'JAMIE@EXAMPLE.TEST'] as $search) {
            $page = $this->table(['rating' => 'good', 'search' => $search])->assertOk()->viewData('submissions');
            $this->assertSame(1, $page->total(), $search); $this->assertSame($user->id, $page->first()->user_id);
        }
        $this->assertSame(1, $this->table(['search' => 'Not bad'])->assertOk()->viewData('submissions')->total());
        $this->assertSame(24, $this->table(['rating' => 'good', 'search' => 'Guest'])->assertOk()->viewData('submissions')->total());
        $this->assertSame(0, $this->table(['rating' => 'good', 'search' => 'different'])->assertOk()->viewData('submissions')->total());
        $page = $this->table(['rating' => 'good', 'search' => 'Note', 'sort' => 'message_asc', 'per_page' => 24])->assertOk()->viewData('submissions');
        parse_str(parse_url($page->url(2), PHP_URL_QUERY), $query);
        $this->assertSame(['search' => 'Note', 'sort' => 'message_asc', 'per_page' => '24', 'rating' => 'good', 'page' => '2'], $query);
    }

    public function test_sorting_keeps_fixed_columns_and_stable_ties()
    {
        $this->admin();
        $users = Model::withoutEvents(function () {
            return [create(User::class, ['first_name' => 'Zara']), create(User::class, ['first_name' => 'Anna'])];
        });
        $older = FeedbackSubmission::create(['rating' => 'good', 'message' => 'Alpha', 'user_id' => $users[0]->id]);
        $newer = FeedbackSubmission::create(['rating' => 'bad', 'message' => 'Zulu', 'user_id' => $users[1]->id]);
        $older->forceFill(['created_at' => '2026-10-01 12:00:00'])->save();
        $newer->forceFill(['created_at' => '2026-10-02 12:00:00'])->save();
        foreach (['date' => ['Zulu', 'Alpha'], 'rating' => ['Alpha', 'Zulu'], 'message' => ['Zulu', 'Alpha'], 'sender' => ['Alpha', 'Zulu']] as $column => $descending) {
            foreach (['asc', 'desc'] as $direction) {
                $page = $this->table(['sort' => $column.'_'.$direction])->assertOk()->viewData('submissions');
                $this->assertSame($direction === 'desc' ? $descending : array_reverse($descending), $page->pluck('message')->all());
            }
        }
        $newer->forceFill(['created_at' => $older->created_at])->save();
        $this->assertSame('Zulu', $this->table()->assertOk()->viewData('submissions')->first()->message);
    }

    public function test_cells_escape_content_and_keep_context_and_csrf_delete_forms()
    {
        $this->admin();
        FeedbackSubmission::create(['rating' => 'good', 'message' => '<script>alert(1)</script> 100% _unique',
            'context' => ['url' => route('webapp.settings'), 'route' => 'webapp.settings', 'browser' => '<img src=x>', 'os' => 'iOS', 'device_type' => 'mobile']]);
        $response = $this->table(['columns' => [['name' => 'users.password']]])->assertOk();
        foreach (['&lt;script&gt;', '&lt;img', 'Context', 'webapp.settings', 'noopener noreferrer', 'name="_token"', 'name="_method"', 'value="DELETE"', 'aria-label="Delete feedback"', 'icon-trash-2'] as $text) $response->assertSee($text, false);
        $response->assertDontSee('<script>alert(1)</script>', false);
        foreach (['%' => 1, '_unique' => 1, '_absent' => 0, "' OR 1=1 --" => 0] as $search => $count) {
            $this->assertSame($count, $this->table(compact('search'))->assertOk()->viewData('submissions')->total());
        }
    }

    public function test_validation_and_admin_authorization()
    {
        $this->withExceptionHandling();
        $this->getJson(route('admin.feedback.index'))->assertUnauthorized();
        $user = Model::withoutEvents(function () { return create(User::class); });
        $this->actingAs($user, 'web')->getJson(route('admin.feedback.index'))->assertUnauthorized();
        $this->admin();
        foreach ([['page' => -1], ['page' => ['2']], ['per_page' => -1], ['per_page' => 1000], ['rating' => ['good']], ['rating' => 'unknown'],
            ['search' => ['bad']], ['search' => str_repeat('a', 256)], ['sort' => ['date_desc']], ['sort' => 'password_asc'], ['sort' => 'date_desc; DROP TABLE users']] as $invalid) {
            $this->getJson(route('admin.feedback.index', $invalid))->assertUnprocessable();
        }
        $this->table()->assertOk()->assertSee('No feedback yet.');
    }

    public function test_deletion_keeps_search_sort_and_page_size_and_returns_to_the_last_matching_page()
    {
        $this->admin();
        for ($i = 0; $i < 25; $i++) FeedbackSubmission::create(['rating' => 'good', 'message' => sprintf('Note %02d', $i)]);
        FeedbackSubmission::create(['rating' => 'good', 'message' => 'Unrelated']);
        $filters = ['rating' => 'good', 'search' => 'Note', 'sort' => 'message_asc', 'per_page' => 24, 'page' => 2];
        $response = $this->table($filters)->assertOk();
        $target = $response->viewData('submissions')->first();
        $action = $this->xpath($response)->query('//table[@id="feedback-table"]/tbody/tr/td[last()]/form')[0]->getAttribute('action');
        parse_str(parse_url($action, PHP_URL_QUERY), $query);
        $this->assertEquals($filters, $query);
        $deleted = $this->delete($action, ['feedback' => 999999])->assertSessionHas('status', 'Feedback deleted.');
        parse_str(parse_url($deleted->headers->get('Location'), PHP_URL_QUERY), $query);
        unset($filters['page']);
        $this->assertEquals($filters, $query);
        $this->assertDatabaseMissing('feedback_submissions', ['id' => $target->id]);
        $this->assertDatabaseHas('feedback_submissions', ['message' => 'Unrelated']);
        $this->get($deleted->headers->get('Location'))->assertOk()->assertSee('Showing 1–24 of 24 results');
    }

    public function test_invalid_return_parameters_do_not_delete_the_feedback_and_legacy_ajax_delete_still_works()
    {
        $this->admin(); $this->withExceptionHandling();
        $target = FeedbackSubmission::create(['rating' => 'good']);
        foreach ([['sort' => 'password_asc'], ['search' => ['bad']], ['per_page' => 1000]] as $invalid) {
            $this->deleteJson(route('admin.feedback.destroy', $target), $invalid)->assertUnprocessable();
            $this->assertDatabaseHas('feedback_submissions', ['id' => $target->id]);
        }
        $other = FeedbackSubmission::create(['rating' => 'bad']);
        $this->postJson(route('admin.feedback.destroy', $target), ['_method' => 'DELETE', 'feedback' => $other->id],
            ['X-Requested-With' => 'XMLHttpRequest'])->assertOk()->assertExactJson(['deleted' => true]);
        $this->assertDatabaseMissing('feedback_submissions', ['id' => $target->id]);
        $this->assertDatabaseHas('feedback_submissions', ['id' => $other->id]);
    }

    public function test_queries_and_rendering_do_not_run_unused_activity_queries_per_row()
    {
        $user = Model::withoutEvents(function () { return create(User::class); });
        for ($i = 0; $i < 24; $i++) FeedbackSubmission::create(['rating' => 'good', 'user_id' => $user->id]);
        $counts = [];
        foreach ([12, 24] as $length) {
            DB::enableQueryLog(); DB::flushQueryLog();
            $data = (new FeedbackTable)->get(Request::create('/feedback', 'GET', ['per_page' => $length]));
            foreach ($data['submissions'] as $submission) view('admin.pages.feedback.table.sender', compact('submission'))->render();
            $queries = DB::getQueryLog(); DB::disableQueryLog(); $counts[] = count($queries);
            $this->assertCount($length, $data['submissions']);
            $this->assertStringNotContainsString('activity_events', implode("\n", array_column($queries, 'query')));
        }
        $this->assertSame([3, 3], $counts);
    }
}
