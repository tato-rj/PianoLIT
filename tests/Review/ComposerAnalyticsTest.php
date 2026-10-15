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

    protected function video($piece, $url = 'https://example.test/video.mp4')
    {
        DB::table('tutorials')->insert(['piece_id' => $piece->id, 'type' => 'Performance', 'category' => 'performance', 'filename' => 'video', 'video_url' => $url]);
    }

    protected function collection(array $composers, $tutorials = 6, array $attributes = [])
    {
        $id = DB::table('playlists')->insertGetId(array_merge(['creator_id' => 1, 'name' => 'Collection', 'subtitle' => 'Subtitle', 'description' => 'Description', 'published_at' => now()], $attributes));
        foreach ($composers as $index => $composer) {
            $piece = $this->piece($composer);
            DB::table('piece_playlist')->insert(['piece_id' => $piece->id, 'playlist_id' => $id]);
            if ($index < $tutorials) $this->video($piece);
        }
        return $id;
    }

    public function test_admin_guard_and_empty_charts()
    {
        $this->withExceptionHandling();
        $this->get(route('admin.analytics.composers'))->assertRedirect(route('admin.login.show'));
        $this->actingAs(create(Admin::class), 'admin');
        $response = $this->get(route('admin.analytics.composers'))->assertOk()
            ->assertSee('No composers yet')->assertSee('Video & collection coverage', false)
            ->assertSee('Composers added by month')->assertSee('Composers by period')->assertSee('Composers by country')
            ->assertSee('No piece views have been recorded')->assertSee('No current saves have been recorded')
            ->assertDontSee('www.gstatic.com')->assertDontSee('datatables.min')->assertDontSee('mapsApiKey');
        $this->assertSame(0, $response->viewData('summary')['total']);
        $this->assertSame(0.0, $response->viewData('summary')['coverage']);
        $this->assertSame(array_fill(0, 12, 0), array_column($response->viewData('growth'), 'total'));
        $this->assertNull($response->viewData('countries')['top']);
        foreach (['videos', 'folders', 'playlists'] as $key) $this->assertSame(0, $response->viewData('summary')[$key]);
        $response->assertDontSee('Biography saved')->assertDontSee('Portrait saved');
    }

    public function test_video_gauge_counts_distinct_composers_with_a_nonblank_video_link()
    {
        $withVideo = $this->composer(['period' => ' Romantic ']); $blank = $this->composer(['period' => 'classical']); $withoutPieces = $this->composer(['period' => '']);
        $piece = $this->piece($withVideo); $this->video($piece); $this->video($piece);
        $this->video($this->piece($withVideo));
        $blankPiece = $this->piece($blank); $this->video($blankPiece, '  '); $this->video($blankPiece, null);
        $this->video($this->piece(999999));
        $report = $this->report();
        $this->assertSame(3, $report['summary']['total']);
        $this->assertSame(1, $report['summary']['videos']);
        $periods = array_column($report['periods'], null, 'key');
        $this->assertSame(1, $periods['romantic']['videos']);
        $this->assertSame(0, $periods['classical']['videos']);
        $this->assertSame(0, $periods['']['videos']);
        $this->assertSame($report['summary']['videos'], array_sum(array_column($report['periods'], 'videos')));
        $this->assertSame([$blank, $withoutPieces], $this->report(['coverage' => 'videos'])['directory']->pluck('id')->all());
        $this->actingAs(create(Admin::class), 'admin');
        $this->get(route('admin.analytics.composers'))->assertOk()->assertSee('33.3%')->assertSee('With videos')
            ->assertSee('In users’ folders')->assertSee('In playlists')->assertDontSee('With pieces');
        $filtered = $this->get(route('admin.analytics.composers', ['coverage' => 'videos']))->assertOk();
        $this->assertSame($report['summary'], $filtered->viewData('summary'));
        $this->assertSame(2, $filtered->viewData('directory')->total());
    }

    public function test_folder_gauge_matches_custom_folder_ownership_and_exclusions()
    {
        $included = $this->composer(); $excludedOnly = $this->composer(); $unused = $this->composer();
        $piece = $this->piece($included); $other = $this->piece($excludedOnly);
        $normal = $this->account(); $foreign = $this->account();
        $optouts = [];
        foreach ([' ARTHURVILLAR@gmail.com ', 'ELENA.DARE@gmail.com', 'arthurvilar@gmail.com'] as $email) $optouts[] = $this->account(compact('email'));
        $team = $this->account(['id' => 944]);
        foreach ([$normal, $foreign] as $user) {
            for ($i = 0; $i < 2; $i++) {
                $folder = DB::table('favorite_folders')->insertGetId(['name' => 'Practice '.$i, 'user_id' => $user->id, 'is_default' => false]);
                DB::table('favorites')->insert(['piece_id' => $piece->id, 'user_id' => $user->id, 'favorite_folder_id' => $folder]);
            }
        }
        foreach (array_merge($optouts, [$team]) as $user) {
            $folder = DB::table('favorite_folders')->insertGetId(['name' => 'Excluded', 'user_id' => $user->id, 'is_default' => false]);
            DB::table('favorites')->insert(['piece_id' => $other->id, 'user_id' => $user->id, 'favorite_folder_id' => $folder]);
        }
        $default = DB::table('favorite_folders')->insertGetId(['name' => 'Default', 'user_id' => $normal->id, 'is_default' => true]);
        $owned = DB::table('favorite_folders')->insertGetId(['name' => 'Owned', 'user_id' => $normal->id, 'is_default' => false]);
        foreach ([[$default, $normal->id], [$owned, $foreign->id], [999999, $normal->id], [null, $normal->id]] as [$folder, $owner]) {
            DB::table('favorites')->insert(['piece_id' => $other->id, 'user_id' => $owner, 'favorite_folder_id' => $folder]);
        }
        $orphan = DB::table('favorite_folders')->insertGetId(['name' => 'Missing owner', 'user_id' => 999999, 'is_default' => false]);
        DB::table('favorites')->insert(['piece_id' => $other->id, 'user_id' => 999999, 'favorite_folder_id' => $orphan]);
        DB::table('favorites')->insert(['piece_id' => 999999, 'user_id' => $normal->id, 'favorite_folder_id' => $owned]);
        $report = $this->report();
        $this->assertSame(1, $report['summary']['folders']);
        $this->assertEqualsCanonicalizing([$excludedOnly, $unused], $this->report(['coverage' => 'folders'])['directory']->pluck('id')->all());
        $this->assertSame($report['summary'], $this->report(['coverage' => 'folders'])['summary']);
    }

    public function test_playlist_gauge_counts_only_composers_in_pieces_shown_on_collections()
    {
        $visible = $this->composer(); $noTutorial = $this->composer();
        $listed = $this->collection(array_merge(array_fill(0, 5, $visible), [$noTutorial]), 5);
        $alsoListed = $this->collection(array_fill(0, 6, $visible));
        $hiddenComposers = [];
        foreach ([['published_at' => null], ['published_at' => now()->addSecond()], ['group' => 'journey']] as $attributes) {
            $hiddenComposers[] = $id = $this->composer();
            $this->collection(array_fill(0, 6, $id), 6, $attributes);
        }
        $hiddenComposers[] = $tooShort = $this->composer(); $this->collection(array_fill(0, 5, $tooShort), 5);
        $hiddenComposers[] = $tooFewVideos = $this->composer(); $this->collection(array_fill(0, 6, $tooFewVideos), 4);
        $report = $this->report();
        $this->assertSame(1, $report['summary']['playlists']);
        $this->assertEqualsCanonicalizing(array_merge([$noTutorial], $hiddenComposers), $this->report(['coverage' => 'playlists'])['directory']->pluck('id')->all());
        $cards = app(\App\Services\WebApp\Collections::class)->data()['playlists'];
        $this->assertEqualsCanonicalizing([$listed, $alsoListed], $cards->pluck('playlist.id')->all());
        $this->assertTrue($report['directory']->firstWhere('id', $visible)['playlists']);
        $this->assertFalse($report['directory']->firstWhere('id', $noTutorial)['playlists']);
        // Analytics reads must not invoke the collection service's weekly-selection cache.
        \Illuminate\Support\Facades\Cache::forget('webapp.collections.weekly-selection');
        $this->report();
        $this->assertFalse(\Illuminate\Support\Facades\Cache::has('webapp.collections.weekly-selection'));
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
        $this->assertSame(0, array_sum(array_column($report['periods'], 'videos')));
        $this->assertSame(6, array_sum(array_column($report['genders'], 'total')));
        $periods = array_column($report['periods'], null, 'key');
        $this->assertSame(0, $periods['romantic']['videos']);
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
        $this->assertSame(5, $map['total']); $this->assertSame(4, $map['located']); $this->assertSame(1, $map['unlocated']);
        $this->assertSame(2, $map['byCode']['US']['total']);
        $this->assertSame(50.0, $map['byCode']['US']['share']);
        $this->assertSame(1, $map['byCode']['GF']['total']);
        $this->assertSame('USA', $map['byCode']['US']['name']);
        $this->assertSame('Bad country', $map['byCode']['country-'.$invalid]['name']);
        $this->assertFalse($map['byCode']['country-'.$invalid]['has_shape']);
        $this->assertGreaterThanOrEqual(1, $map['not_on_map']);
        $this->assertSame(4, $map['mapped'] + $map['not_on_map']);
        $this->assertCount(3, $map['countries']);
        $this->assertSame(1, $this->report(['profile' => 'country'])['directory']->total());
    }

    public function test_saved_country_with_missing_or_unrecognized_map_code_is_still_a_recorded_country()
    {
        foreach (['UK', '', 'gb-eng', 'US'] as $index => $code) {
            $country = DB::table('countries')->insertGetId(['name' => 'Saved country '.$index, 'nationality' => 'Nationality', 'flag_code' => $code]);
            $this->composer(['country_id' => $country]);
        }
        $report = $this->report();
        $this->assertSame(4, $report['summary']['located']);
        $this->assertSame(0, $report['countries']['unlocated']);
        $this->assertSame(3, $report['countries']['not_on_map']);
        $this->assertCount(4, $report['countries']['countries']);
        $this->assertSame(4, array_sum(array_column($report['countries']['countries'], 'total')));
        $this->assertSame(0, $this->report(['profile' => 'country'])['directory']->total());
        $this->actingAs(create(Admin::class), 'admin');
        $this->get(route('admin.analytics.composers'))->assertOk()->assertSee('4 of 4 composers have a country recorded.')
            ->assertSee('3 composers have countries without a matching map shape')->assertSee('Saved country 0')
            ->assertDontSee('no usable country')->assertDontSee('no linked country record.');
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
        for ($i = 0; $i < 15; $i++) {
            $piece = $this->piece($this->composer());
            DB::table('piece_views')->insert(['piece_id' => $piece->id, 'user_id' => 999999]);
        }
        DB::enableQueryLog(); DB::flushQueryLog(); $report = $this->report(); $large = DB::getQueryLog(); DB::disableQueryLog();
        $this->assertCount(3, $small); $this->assertCount(3, $large);
        $this->assertCount(6, $report['rankings']['views']);
        $this->get(route('admin.analytics.composers'))->assertOk()->assertSee('&lt;script&gt;unsafe&lt;/script&gt;', false)->assertDontSee('<script>unsafe', false);
        $this->get(route('admin.analytics.composers', ['page' => 999]))->assertOk()->assertSee('No composers on this page')->assertSee('First page');
        $this->get(route('admin.analytics.composers', ['search' => 'NoSuchComposer']))->assertOk()->assertSee('No composers match these filters');
    }

    public function test_invalid_filters_are_rejected()
    {
        $this->actingAs(create(Admin::class), 'admin'); $this->withExceptionHandling();
        foreach ([['search' => ['x']], ['depth' => ['small']], ['depth' => 'other'], ['period' => ['romantic']], ['profile' => 'email'], ['coverage' => ['videos']], ['coverage' => 'biography'], ['sort' => ['pieces']], ['sort' => 'id desc'], ['page' => 0], ['page' => 100001], ['search' => str_repeat('x', 161)]] as $filter) $this->getJson(route('admin.analytics.composers', $filter))->assertStatus(422);
    }
}
