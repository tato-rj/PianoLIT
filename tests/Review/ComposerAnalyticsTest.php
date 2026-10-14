<?php

namespace Tests\Review;

use App\{Admin, Piece, User};
use App\Analytics\ComposerReport;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ComposerAnalyticsTest extends ReviewTestCase
{
    public function setUp(): void
    {
        parent::setUp();
        $this->travelTo(carbon('2026-10-14 14:00:00'));
    }

    protected function composer(array $attributes = [])
    {
        return DB::table('composers')->insertGetId(array_merge(['name' => 'Composer', 'biography' => 'Biography', 'cover_path' => 'portrait.jpg',
            'gender' => 'female', 'period' => 'romantic', 'created_at' => now(), 'updated_at' => now()], $attributes));
    }

    protected function piece($composer)
    {
        return Model::withoutEvents(function () use ($composer) { return create(Piece::class, ['composer_id' => $composer]); });
    }

    protected function account(array $attributes = [])
    {
        return Model::withoutEvents(function () use ($attributes) { return create(User::class, $attributes); });
    }

    protected function report(array $filters = [])
    {
        return app(ComposerReport::class)->get(Request::create('/', 'GET', $filters));
    }

    public function test_admin_guard_and_empty_charts()
    {
        $this->withExceptionHandling();
        $this->get(route('admin.analytics.composers'))->assertRedirect(route('admin.login.show'));
        $this->actingAs(create(Admin::class), 'admin');
        $response = $this->get(route('admin.analytics.composers'))->assertOk()
            ->assertSee('No composers yet')->assertSee('Repertoire & profile coverage', false)
            ->assertSee('Composers added by month')->assertSee('Composers by period')->assertSee('Composers by country')
            ->assertSee('No piece views have been recorded')->assertSee('No current saves have been recorded')
            ->assertDontSee('www.gstatic.com')->assertDontSee('datatables.min')->assertDontSee('mapsApiKey');
        $this->assertSame(0, $response->viewData('summary')['total']);
        $this->assertSame(0.0, $response->viewData('summary')['coverage']);
        $this->assertSame(array_fill(0, 12, 0), array_column($response->viewData('growth'), 'total'));
        $this->assertNull($response->viewData('countries')['top']);
    }

    public function test_depth_period_gender_and_profile_counts_share_the_same_denominator()
    {
        foreach ([0, 1, 3, 4, 10, 11] as $index => $count) {
            $id = $this->composer(['biography' => $index ? '   ' : 'Text', 'cover_path' => $index ? '' : 'image.jpg',
                'period' => $index ? ' Romantic ' : '', 'gender' => $index ? ' FEMALE ' : '']);
            for ($i = 0; $i < $count; $i++) $this->piece($id);
        }
        $this->piece(999999); // An orphan piece cannot inflate composer catalogue size.
        $report = $this->report();
        $this->assertSame(6, $report['summary']['total']);
        $this->assertSame(29, $report['summary']['pieces']);
        $this->assertSame(5, $report['summary']['represented']);
        $this->assertSame(2, $report['summary']['small']);
        $this->assertSame(1, $report['summary']['biography']);
        $this->assertSame(1, $report['summary']['portrait']);
        $this->assertSame(83.3, $report['summary']['coverage']);
        $this->assertSame([1, 2, 2, 1], array_column($report['depths'], 'total'));
        $this->assertSame(6, array_sum(array_column($report['periods'], 'total')));
        $this->assertSame(5, array_sum(array_column($report['periods'], 'represented')));
        $this->assertSame(6, array_sum(array_column($report['genders'], 'total')));
        $periods = array_column($report['periods'], null, 'key');
        $this->assertSame(5, $periods['romantic']['represented']);
        $this->assertSame(1, $periods['']['total']);
        foreach (['biography', 'portrait'] as $profile) $this->assertSame(5, $this->report(compact('profile'))['directory']->total());
    }

    public function test_saves_are_unique_account_piece_pairs_and_exclude_optouts_and_missing_records()
    {
        $id = $this->composer(); $a = $this->piece($id); $b = $this->piece($id);
        $normal = $this->account(); $other = $this->account();
        $excluded = [];
        foreach ([' ARTHURVILLAR@gmail.com ', 'elena.dare@gmail.com', 'ARTHURVILAR@gmail.com'] as $email) $excluded[] = $this->account(compact('email'));
        foreach (array_merge([$normal, $other], $excluded) as $user) {
            foreach ([$a, $b] as $piece) {
                for ($copy = 0; $copy < 2; $copy++) {
                    DB::table('favorites')->insert(['piece_id' => $piece->id, 'user_id' => $user->id, 'favorite_folder_id' => $copy + 1]);
                    DB::table('piece_views')->insert(['piece_id' => $piece->id, 'user_id' => $user->id]);
                }
            }
        }
        DB::table('favorites')->insert(['piece_id' => $a->id, 'user_id' => 999999]);
        foreach (['favorites', 'piece_views'] as $table) DB::table($table)->insert(['piece_id' => 999999, 'user_id' => $normal->id]);
        $before = DB::table('favorites')->count();
        $report = $this->report();
        $this->assertSame(4, $report['rankings']['saves'][0]['saves']);
        $this->assertSame(2, $report['rankings']['saves'][0]['savers']);
        $this->assertSame(8, $report['rankings']['views'][0]['views']);
        $this->assertSame(2.0, $report['opportunities'][0]['saves_per_piece']);
        $this->assertSame($before, DB::table('favorites')->count());
    }

    public function test_country_counts_weight_profiles_and_keep_missing_and_unmapped_territories_explicit()
    {
        $us = DB::table('countries')->insertGetId(['name' => 'USA', 'nationality' => 'American', 'flag_code' => ' us ']);
        $gf = DB::table('countries')->insertGetId(['name' => 'French Guiana', 'nationality' => 'French Guianese', 'flag_code' => 'GF']);
        $invalid = DB::table('countries')->insertGetId(['name' => 'Bad country', 'nationality' => 'Bad', 'flag_code' => 'QQ']);
        $first = $this->composer(['country_id' => $us]);
        $this->composer(['country_id' => $us]); $this->composer(['country_id' => $gf]);
        $this->composer(['country_id' => $invalid]); $this->composer(['country_id' => 999999]);
        for ($i = 0; $i < 3; $i++) $this->piece($first);
        $map = $this->report()['countries'];
        $this->assertSame(5, $map['total']); $this->assertSame(3, $map['located']); $this->assertSame(2, $map['unlocated']);
        $this->assertSame(2, $map['byCode']['US']['total']);
        $this->assertSame(66.7, $map['byCode']['US']['share']);
        $this->assertSame(1, $map['byCode']['GF']['total']);
        $this->assertSame(2, $this->report(['profile' => 'country'])['directory']->total());
    }

    public function test_monthly_additions_include_zero_months_and_current_time_boundary()
    {
        foreach (['2025-10-31 23:59:59', '2025-11-01 00:00:00', '2026-01-31 23:59:59', '2026-10-14 14:00:00', '2026-10-14 14:00:01', null] as $created_at) $this->composer(compact('created_at'));
        $report = $this->report();
        $this->assertSame(6, $report['summary']['total']);
        $this->assertSame('2025-11', $report['growth'][0]['month']);
        $this->assertSame([1, 0, 1, 0, 0, 0, 0, 0, 0, 0, 0, 1], array_column($report['growth'], 'total'));
    }

    public function test_calendar_dates_cover_today_year_rollover_and_approximate_invalid_dates()
    {
        $today = $this->composer(['name' => 'Today', 'date_of_birth' => '1800-10-14']);
        $last = $this->composer(['name' => 'Last day', 'date_of_death' => '1900-11-12']);
        $this->composer(['name' => 'Outside', 'date_of_birth' => '1800-11-13']);
        $this->composer(['id' => 128, 'name' => 'Approximate birth', 'date_of_birth' => '1729-10-14']);
        $invalid = $this->composer(['name' => 'Bad chronology', 'date_of_birth' => '1900-10-15', 'date_of_death' => '1800-10-16']);
        $this->composer(['name' => 'Future', 'date_of_birth' => '2027-10-16']);
        $report = $this->report();
        $this->assertSame([$today, $invalid, $last], array_column($report['anniversaries'], 'id'));
        $this->assertSame(226, $report['anniversaries'][0]['years']);
        $this->assertSame('c. 1729', $report['directory']->firstWhere('id', 128)['birth_label']);
        $this->assertNull($report['directory']->firstWhere('id', $invalid)['age']);
        $this->travelTo(carbon('2026-12-20 14:00:00'));
        $newYear = $this->composer(['date_of_birth' => '1800-01-01']);
        $events = $this->report()['anniversaries'];
        $this->assertSame($newYear, $events[0]['id']); $this->assertSame('2027-01-01', $events[0]['date']->format('Y-m-d'));
        $this->travelTo(carbon('2027-02-01')); $this->composer(['date_of_birth' => '1804-02-29']);
        $this->assertSame([], $this->report()['anniversaries']);
        $this->travelTo(carbon('2028-02-01'));
        $this->assertSame('2028-02-29', $this->report()['anniversaries'][0]['date']->format('Y-m-d'));
    }

    public function test_directory_filters_literal_search_and_stable_pagination_do_not_change_charts()
    {
        $match = $this->composer(['name' => 'Clara 100%_ Schumann', 'period' => 'classical']);
        $this->piece($match);
        for ($i = 0; $i < 14; $i++) $this->composer(['name' => 'Other '.sprintf('%02d', $i)]);
        $all = $this->report();
        $this->assertSame(12, $all['directory']->perPage());
        $this->assertCount(12, $all['directory']->items());
        $this->assertCount(3, $this->report(['page' => 2])['directory']->items());
        $this->assertSame(1, $this->report(['search' => '100%_'])['directory']->total());
        $this->assertSame(0, $this->report(['search' => 'Clara%Schumann'])['directory']->total());
        $filtered = $this->report(['depth' => 'small', 'period' => 'classical', 'sort' => 'saves']);
        $this->assertSame($match, $filtered['directory']->first()['id']);
        foreach (['summary', 'growth', 'periods', 'rankings', 'genders'] as $key) $this->assertSame($all[$key], $filtered[$key]);
        $this->assertSame(14, $this->report(['depth' => 'empty'])['directory']->total());
        $this->assertSame(0, $this->report(['period' => '__missing'])['directory']->total());
        $url = $this->report(['depth' => 'empty', 'sort' => 'name'])['directory']->url(2);
        parse_str(parse_url($url, PHP_URL_QUERY), $query);
        $this->assertSame('empty', $query['depth']); $this->assertSame('name', $query['sort']);
    }

    public function test_query_count_is_constant_rankings_are_bounded_and_html_is_escaped()
    {
        $this->actingAs(create(Admin::class), 'admin');
        $bad = $this->composer(['name' => '<script>unsafe</script>', 'period' => '<img src=x onerror=alert(1)>']);
        $this->piece($bad);
        DB::enableQueryLog(); DB::flushQueryLog(); $this->report(); $small = DB::getQueryLog(); DB::disableQueryLog();
        for ($i = 0; $i < 15; $i++) $this->piece($this->composer());
        DB::enableQueryLog(); DB::flushQueryLog(); $report = $this->report(); $large = DB::getQueryLog(); DB::disableQueryLog();
        $this->assertCount(3, $small); $this->assertCount(3, $large);
        $this->assertCount(6, $report['rankings']['pieces']);
        $this->get(route('admin.analytics.composers'))->assertOk()->assertSee('&lt;script&gt;unsafe&lt;/script&gt;', false)->assertDontSee('<script>unsafe', false);
        $this->get(route('admin.analytics.composers', ['page' => 999]))->assertOk()->assertSee('No composers on this page')->assertSee('First page');
        $this->get(route('admin.analytics.composers', ['search' => 'NoSuchComposer']))->assertOk()->assertSee('No composers match these filters');
    }

    public function test_invalid_filters_are_rejected()
    {
        $this->actingAs(create(Admin::class), 'admin'); $this->withExceptionHandling();
        foreach ([['search' => ['x']], ['depth' => ['small']], ['depth' => 'other'], ['period' => ['romantic']], ['profile' => 'email'], ['sort' => ['pieces']], ['sort' => 'id desc'], ['page' => 0], ['page' => 100001], ['search' => str_repeat('x', 161)]] as $filter) $this->getJson(route('admin.analytics.composers', $filter))->assertStatus(422);
    }
}
