<?php

namespace Tests\Review;

use App\{Admin, User, Piece, Subscription};
use App\Blog\Post;
use App\Services\Admin\Dashboard;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class AdminDashboardTest extends ReviewTestCase
{
    public function setUp(): void
    {
        parent::setUp();
        $this->travelTo(Carbon::parse('2026-10-12 14:00:00'));
        $this->actingAs(create(Admin::class, ['name' => 'PianoLIT Manager']), 'admin');
    }

    protected function user($firstName = 'Active')
    {
        return Model::withoutEvents(function () use ($firstName) {
            return create(User::class, ['first_name' => $firstName, 'last_name' => 'Pianist']);
        });
    }

    protected function event(array $attributes = [])
    {
        DB::table('activity_events')->insert(array_merge([
            'event_id' => (string) Str::uuid(), 'platform' => 'webapp', 'type' => 'page_view',
            'source' => 'server', 'occurred_at' => '2026-10-12 12:00:00', 'received_at' => now(),
        ], $attributes));
    }

    public function test_dashboard_counts_real_activity_with_zero_days_and_verified_web_rankings()
    {
        $first = $this->user('First'); $second = $this->user('Second');
        $this->event(['user_id' => $first->id]);
        $this->event(['user_id' => $first->id, 'type' => 'media_play', 'source' => 'browser']);
        $this->event(['user_id' => $second->id, 'platform' => 'web']);
        $this->event(['user_id' => 987654]); // Deleted accounts do not become active accounts.
        $this->event(['platform' => 'web']); // Anonymous activity contributes to totals.
        $this->event(['user_id' => $second->id, 'platform' => 'app', 'source' => 'app', 'type' => 'request']);
        $this->event(['user_id' => $second->id, 'platform' => 'webapp', 'source' => 'legacy', 'type' => 'legacy_request']);
        $this->event(['user_id' => $second->id, 'type' => 'heartbeat']);
        $this->event(['user_id' => $second->id, 'type' => 'page_hidden']);
        $this->event(['user_id' => $second->id, 'type' => 'page_checkpoint']);
        $this->event(['user_id' => $second->id, 'type' => 'media_progress']);
        $this->event(['occurred_at' => '2026-10-09 23:59:59']);
        $this->event(['occurred_at' => '2026-10-13 00:00:00']); // Future timestamps excluded.
        $this->event(['occurred_at' => '2026-10-06 00:00:00']); // Before previous period.
        $data = app(Dashboard::class)->data(3, 'all');
        $this->assertSame(7, $data['total']);
        $this->assertSame(1, $data['previous']);
        $this->assertEquals(600, $data['change']);
        $this->assertSame([0, 0, 7], array_column($data['trend'], 'total'));
        $this->assertSame(2, $data['activeUsers']);
        $this->assertSame([$first->id, $second->id], $data['engagedUsers']->pluck('id')->all());
        $this->assertSame([2, 1], $data['engagedUsers']->pluck('events_count')->all());
        $this->assertSame(1, $data['platforms']['app']['total']);
    }

    public function test_date_and_platform_filters_are_bounded_and_keep_other_selection()
    {
        $user = $this->user();
        foreach (['web', 'webapp', 'app'] as $platform) $this->event(['user_id' => $user->id, 'platform' => $platform]);
        $response = $this->get(route('admin.home', ['days' => 3, 'platform' => 'web']))->assertOk();
        $data = $response->viewData('dashboard');
        $this->assertSame(1, $data['total']);
        $this->assertSame(3, $data['days']);
        $this->assertSame('web', $data['platform']);
        $response->assertSee(e(route('admin.home', ['days' => 30, 'platform' => 'web'])), false)
            ->assertSee(e(route('admin.home', ['days' => 3, 'platform' => 'webapp'])), false);
        $this->assertSame(1, app(Dashboard::class)->data(7, 'app')['total']);
        $this->assertCount(0, app(Dashboard::class)->data(7, 'app')['engagedUsers']);
        $this->withExceptionHandling();
        foreach ([['days' => 1000], ['days' => ['bad']], ['platform' => 'unknown'], ['platform' => ['bad']]] as $filters) {
            $this->getJson(route('admin.home', $filters))->assertStatus(422);
        }
    }

    public function test_metrics_use_real_totals_and_publication_dates_without_model_serialization_queries()
    {
        $user = $this->user();
        $old = $this->user('Old'); $old->update(['created_at' => now()->subMonths(2)]);
        Model::withoutEvents(function () {
            create(Piece::class);
            create(Subscription::class);
            create(Post::class, ['created_at' => now()->subMonths(2), 'published_at' => now()->subDay()]);
            create(Post::class, ['published_at' => null]);
        });
        DB::enableQueryLog(); DB::flushQueryLog();
        $data = app(Dashboard::class)->data(7, 'all');
        $queries = DB::getQueryLog(); DB::disableQueryLog();
        $this->assertSame([2, 1, 1, 1], array_column($data['metrics'], 'total'));
        $this->assertSame([1, 1, 1, 1], array_column($data['metrics'], 'added'));
        $this->assertEquals(50, $data['metrics'][0]['share']);
        $this->assertCount(12, $queries);
        $this->assertStringNotContainsString('post_topic', implode(' ', array_column($queries, 'query')));
    }

    public function test_empty_manager_dashboard_and_nested_menu_render_without_external_chart_requests()
    {
        $response = $this->get(route('admin.home'))->assertOk();
        $response->assertSee('Most engaged users')->assertSee('Meet your next regulars')
            ->assertSee('Activity will appear here')->assertSee('Coming soon')
            ->assertSee('disabled aria-label="Dark mode (coming soon)"', false)
            ->assertSee('action="'.route('admin.logout').'"', false)
            ->assertDontSee('www.gstatic.com')->assertDontSee('PullToRefresh');
        foreach (['api', 'statistics', 'users', 'subscriptions', 'repertoire', 'shop', 'metaverse', 'blog', 'quizzes', 'infographics', 'crash-courses', 'media'] as $id) {
            $response->assertSee('id="'.$id.'" data-bs-parent="#navbarResponsive"', false);
        }
        foreach (['admin.pieces.index', 'admin.timeline-events.index', 'admin.stats.memberships', 'admin.users.logs', 'admin.memberships.logs', 'admin.subscriptions.lists.index', 'admin.escores.topics.index', 'admin.posts.gifts.index', 'admin.quizzes.media.images', 'admin.crashcourses.subscriptions.index', 'admin.timelines.index'] as $route) {
            $response->assertSee(route($route), false);
        }
        $this->assertDatabaseCount('activity_events', 0);
    }

    public function test_editor_only_sees_their_contribution_dashboard_and_active_menu_expands_on_other_pages()
    {
        $editor = create(Admin::class, ['name' => 'Editor Example', 'role' => 'editor']);
        $this->actingAs($editor, 'admin');
        $this->get(route('admin.home'))->assertOk()->assertSee('Welcome, Editor.')
            ->assertSee('My profile')->assertDontSee('Most engaged users')->assertDontSee('Total users');
        $this->actingAs(create(Admin::class), 'admin');
        $this->get(route('admin.pieces.create'))->assertOk()
            ->assertSee('admin-submenu collapse show" id="repertoire"', false)
            ->assertSee('aria-current="page"', false);
    }
}
