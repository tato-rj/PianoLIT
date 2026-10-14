<?php

namespace Tests\Review;

use App\{Admin, Composer, FavoriteFolder, Piece, User};
use App\Analytics\FolderReport;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class FolderAnalyticsTest extends ReviewTestCase
{
    public function setUp(): void
    {
        parent::setUp();
        $this->travelTo(carbon('2026-10-13 23:00:00'));
    }

    protected function account(array $attributes = [])
    {
        return Model::withoutEvents(function () use ($attributes) { return create(User::class, $attributes); });
    }

    protected function piece(array $attributes = [])
    {
        return Model::withoutEvents(function () use ($attributes) { return create(Piece::class, $attributes); });
    }

    protected function folder($user, array $attributes = [])
    {
        return FavoriteFolder::create(array_merge(['user_id' => $user->id, 'name' => 'Practice'], $attributes));
    }

    protected function save($user, $folder, $piece, $order = 0, $date = '2026-10-01 12:00:00')
    {
        DB::table('favorites')->insert(['user_id' => $user->id, 'favorite_folder_id' => $folder ? $folder->id : null,
            'piece_id' => $piece->id, 'order' => $order, 'created_at' => $date, 'updated_at' => $date]);
    }

    protected function report(array $filters = [])
    {
        return app(FolderReport::class)->get(Request::create('/', 'GET', $filters));
    }

    public function test_empty_page_and_admin_guard()
    {
        $this->withExceptionHandling();
        $this->get(route('admin.analytics.favorites'))->assertRedirect();
        $this->actingAs(create(Admin::class), 'admin');
        $response = $this->get(route('admin.analytics.favorites'))->assertOk()->assertSee('No custom folders yet')
            ->assertSee('New folders by month')->assertSee('How full are the folders?')->assertDontSee('datatables.min');
        $this->assertSame(0, $response->viewData('summary')['total']);
        $this->assertSame(0.0, $response->viewData('summary')['adoption']);
        $this->assertSame(array_fill(0, 12, 0), array_column($response->viewData('growth'), 'total'));
    }

    public function test_counts_exclude_default_team_optout_orphan_and_foreign_owned_records()
    {
        $owner = $this->account(); $other = $this->account();
        $one = $this->folder($owner); $two = $this->folder($owner); $empty = $this->folder($other);
        $default = $this->folder($owner, ['is_default' => true]);
        $composer = Model::withoutEvents(function () { return create(Composer::class); });
        $piece = $this->piece(['composer_id' => $composer->id]);
        $missingComposer = $this->piece(['composer_id' => 987654]);
        $this->save($owner, $one, $piece); $this->save($owner, $one, $missingComposer);
        $this->save($owner, $two, $piece); $this->save($owner, $default, $piece); $this->save($owner, null, $piece);
        $this->save($other, $one, $piece); // A bad legacy ownership row must not be attributed to this folder.
        DB::table('favorites')->insert(['user_id' => $owner->id, 'favorite_folder_id' => $one->id, 'piece_id' => 987654]);
        foreach ([' ARTHURVILLAR@gmail.com ', ' ELENA.DARE@gmail.com ', 'arthurvilar@gmail.com'] as $email) {
            $excluded = $this->account(['email' => $email]); $folder = $this->folder($excluded); $this->save($excluded, $folder, $piece);
        }
        $team = $this->account(['id' => 284]); $folder = $this->folder($team); $this->save($team, $folder, $piece);
        DB::table('favorite_folders')->insert(['user_id' => 987654, 'name' => 'Orphan', 'is_default' => false]);
        $before = DB::table('favorites')->count();
        $report = $this->report();
        $this->assertSame(3, $report['summary']['total']);
        $this->assertSame(2, $report['summary']['owners']);
        $this->assertSame(2, $report['summary']['eligible_users']);
        $this->assertSame(100.0, $report['summary']['adoption']);
        $this->assertSame(3, $report['summary']['placements']);
        $this->assertSame(2, $report['summary']['filled']);
        $this->assertSame(66.7, $report['summary']['filled_share']);
        $this->assertEquals(1, $report['summary']['average']);
        $this->assertSame([1, 1, 1, 0, 0], array_column($report['sizes'], 'total'));
        $this->assertEquals(1, $report['folders']->firstWhere('id', $one->id)->composers_count);
        $this->assertEquals(2, $report['popular'][0]->total);
        $this->assertEquals(1, $report['popular'][0]->owners);
        $this->assertEquals(2, $report['owners'][0]->total);
        $this->assertSame($before, DB::table('favorites')->count());
    }

    public function test_growth_zero_months_boundaries_and_snapshot_independent_of_filters()
    {
        $user = $this->account();
        foreach (['2025-10-31 23:59:59', '2025-11-01 00:00:00', '2026-01-31 23:59:59', '2026-10-13 23:00:00', '2026-10-13 23:00:01'] as $date) {
            $this->folder($user, ['created_at' => $date]);
        }
        DB::table('favorite_folders')->insert(['user_id' => $user->id, 'name' => 'Unknown date', 'is_default' => false]);
        $report = $this->report(['search' => 'No such folder']);
        $this->assertSame(6, $report['summary']['total']);
        $this->assertSame(0, $report['folders']->total());
        $this->assertSame([1, 0, 1, 0, 0, 0, 0, 0, 0, 0, 0, 1], array_column($report['growth'], 'total'));
        $this->assertSame('2025-11', $report['growth'][0]['month']);
        $this->assertSame('2026-10', $report['growth'][11]['month']);
    }

    public function test_search_is_literal_and_matches_full_names_email_and_folder_names()
    {
        $user = $this->account(['first_name' => 'Liv', 'last_name' => 'Benson', 'email' => 'liv@example.test']);
        $named = $this->folder($user, ['name' => '100%_Practice!']);
        $this->folder($user, ['name' => 'Other']);
        foreach (['Liv Benson' => 2, 'liv@example.test' => 2, '100%_' => 1, 'Practice!' => 1, '%' => 1, 'Missing' => 0] as $term => $count) {
            $this->assertSame($count, $this->report(['search' => $term])['folders']->total());
        }
        $this->assertEquals($named->id, $this->report(['search' => '100%_'])['folders']->first()->id);
    }

    public function test_sizes_sorting_and_stable_twelve_folder_pagination()
    {
        $user = $this->account();
        $pieces = [];
        for ($i = 0; $i < 11; $i++) $pieces[] = $this->piece();
        $ids = [];
        foreach ([0, 1, 2, 5, 6, 10, 11] as $count) {
            $folder = $this->folder($user, ['name' => 'Size '.$count]); $ids[$count] = $folder->id;
            foreach (array_slice($pieces, 0, $count) as $piece) $this->save($user, $folder, $piece);
        }
        foreach (['empty' => 1, 'one' => 1, 'small' => 2, 'medium' => 2, 'large' => 1] as $size => $count) {
            $this->assertSame($count, $this->report(['size' => $size])['folders']->total());
        }
        $this->assertEquals($ids[11], $this->report(['sort' => 'largest'])['folders']->first()->id);
        for ($i = 0; $i < 10; $i++) $this->folder($user, ['name' => 'Extra '.$i]);
        $this->actingAs(create(Admin::class), 'admin');
        $first = $this->get(route('admin.analytics.favorites', ['search' => 'Extra', 'sort' => 'name']))->assertOk()->viewData('folders');
        $this->assertSame(12, $first->perPage());
        $this->assertSame(10, $first->total());
        $first = $this->get(route('admin.analytics.favorites'))->assertOk()->viewData('folders');
        $second = $this->get(route('admin.analytics.favorites', ['page' => 2]))->assertOk()->viewData('folders');
        $this->assertCount(12, $first); $this->assertCount(5, $second);
        $this->assertSame([], array_intersect($first->pluck('id')->all(), $second->pluck('id')->all()));
        $this->assertEquals($ids[0], $second->last()->id);
    }

    public function test_detail_is_bounded_keeps_folder_order_and_independent_pages_and_escapes_text()
    {
        $user = $this->account(['first_name' => '<script>Liv</script>']);
        $folder = $this->folder($user, ['name' => '<img src=x onerror=alert(1)>', 'description' => '<b>Description</b>']);
        $ordered = [];
        for ($i = 0; $i < 14; $i++) {
            $piece = $this->piece(['name' => '<b>Piece '.$i.'</b>']); $ordered[] = $piece->id;
            $this->save($user, $folder, $piece, 14 - $i);
        }
        for ($i = 0; $i < 13; $i++) $this->folder($user);
        $other = $this->account(); $foreign = $this->piece(['name' => 'Foreign contents']); $this->save($other, $folder, $foreign);
        $this->actingAs(create(Admin::class), 'admin');
        $response = $this->get(route('admin.analytics.favorites', ['folder' => $folder->id, 'page' => 2, 'sort' => 'largest']))->assertOk()
            ->assertSee('&lt;img src=x onerror=alert(1)&gt;', false)->assertSee('&lt;script&gt;Liv&lt;/script&gt;', false)
            ->assertSee('&lt;b&gt;Description&lt;/b&gt;', false)->assertDontSee('Foreign contents');
        $this->assertCount(12, $response->viewData('pieces'));
        $this->assertEquals(array_slice(array_reverse($ordered), 0, 12), $response->viewData('pieces')->pluck('id')->all());
        $next = $response->viewData('pieces')->nextPageUrl();
        $this->assertStringContainsString('page=2', $next);
        $this->assertStringContainsString('pieces_page=2', $next);
        $this->assertStringContainsString('sort=largest', $next);
        $this->assertStringEndsWith('#folder-detail', $next);
        $response = $this->get($next)->assertOk();
        $this->assertCount(2, $response->viewData('pieces'));
        $this->assertSame(2, $response->viewData('folders')->currentPage());
    }

    public function test_excluded_and_default_folder_details_are_not_reported()
    {
        $this->withExceptionHandling();
        $user = $this->account(['email' => ' ELENA.DARE@gmail.com ']); $excluded = $this->folder($user);
        $other = $this->account(); $default = $this->folder($other, ['is_default' => true]);
        $this->actingAs(create(Admin::class), 'admin');
        foreach ([$excluded->id, $default->id, 987654] as $id) $this->get(route('admin.analytics.favorites', ['folder' => $id]))->assertNotFound();
    }

    public function test_query_count_is_constant_and_rankings_are_bounded()
    {
        $user = $this->account(); $this->folder($user);
        DB::enableQueryLog(); DB::flushQueryLog();
        $this->report(); $small = count(DB::getQueryLog());
        DB::disableQueryLog();
        for ($i = 0; $i < 16; $i++) {
            $owner = $this->account(); $folder = $this->folder($owner); $this->save($owner, $folder, $this->piece());
        }
        DB::enableQueryLog(); DB::flushQueryLog();
        $report = $this->report();
        $this->assertSame($small, count(DB::getQueryLog()));
        $this->assertSame(8, $small);
        $this->assertCount(5, $report['owners']); $this->assertCount(5, $report['popular']);
        DB::disableQueryLog();
    }

    public function test_invalid_filter_shapes_and_sort_keys_are_rejected()
    {
        $this->withExceptionHandling();
        $this->actingAs(create(Admin::class), 'admin');
        foreach ([['sort' => 'email desc'], ['size' => 'unknown'], ['search' => ['x']], ['folder' => -1], ['page' => ['x']], ['pieces_page' => 0]] as $filters) {
            $this->getJson(route('admin.analytics.favorites', $filters))->assertStatus(422);
        }
    }
}
