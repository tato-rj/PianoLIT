<?php

namespace Tests\Review;

use App\{Admin, User};
use App\Services\Admin\{Dashboard, UserCountries};
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class UserCountriesTest extends ReviewTestCase
{
    private function account($country = null)
    {
        $user = Model::withoutEvents(function () { return create(User::class); });
        if ($country !== null) DB::table('locations')->insert(['user_id' => $user->id, 'countryCode' => $country, 'countryName' => '<script>untrusted name</script>', 'ip' => '192.0.2.1']);
        return $user;
    }

    public function test_counts_current_accounts_once_and_keeps_unknown_locations_separate()
    {
        foreach ([' us ', 'US', 'GB', 'XX', '', null] as $code) $this->account($code);
        DB::table('locations')->insert(['user_id' => 987654, 'countryCode' => 'US']);
        DB::enableQueryLog(); DB::flushQueryLog();
        $data = (new UserCountries)->data();
        $queries = DB::getQueryLog(); DB::disableQueryLog();
        $this->assertCount(1, $queries);
        $this->assertSame(6, $data['total']);
        $this->assertSame(3, $data['located']);
        $this->assertSame(3, $data['unlocated']);
        $this->assertSame(['US', 'GB'], array_column($data['countries'], 'code'));
        $this->assertSame([2, 1], array_column($data['countries'], 'total'));
        $this->assertSame(66.7, $data['top']['share']);
        $this->assertSame('United States', $data['top']['name']);
        $this->assertStringNotContainsString('users.*', $queries[0]['query']);
        $this->assertStringNotContainsString('favorites', $queries[0]['query']);
        $this->assertStringContainsString('group by UPPER(TRIM(locations.countryCode))', $queries[0]['query']);
    }

    public function test_all_time_country_counts_do_not_change_with_activity_filters()
    {
        $user = $this->account('BR');
        DB::table('users')->where('id', $user->id)->update(['created_at' => '2020-01-01 00:00:00']);
        $first = app(Dashboard::class)->data(3, 'web')['userCountries'];
        $second = app(Dashboard::class)->data(30, 'app')['userCountries'];
        $this->assertSame($first, $second);
        $this->assertSame(1, $first['byCode']['BR']['total']);
    }

    public function test_missing_geometry_does_not_discard_or_reassign_a_country_count()
    {
        $this->account('GF');
        $data = (new UserCountries)->data();
        $this->assertSame(1, $data['located']);
        $this->assertSame('French Guiana', $data['top']['name']);
        $this->assertArrayNotHasKey('FR', $data['byCode']);
        $this->assertSame(0, $data['unlocated']);
    }

    public function test_empty_and_unlocated_accounts_have_real_zero_totals()
    {
        $data = (new UserCountries)->data();
        $this->assertSame(0, $data['total']);
        $this->assertSame(0, $data['located']);
        $this->assertNull($data['top']);
        $this->account();
        $data = (new UserCountries)->data();
        $this->assertSame(1, $data['unlocated']);
        $this->assertSame([], $data['countries']);
    }

    public function test_manager_dashboard_renders_the_map_after_metrics_with_aggregate_data_only()
    {
        $this->account('US');
        $this->actingAs(create(Admin::class), 'admin');
        $response = $this->get(route('admin.home'))->assertOk()->assertSee('Users around the world')->assertSee('Registered accounts · all time');
        $html = $response->getContent();
        $start = strpos($html, '<section class="admin-country-section"');
        $end = strpos($html, '<section class="admin-library-section"');
        $this->assertGreaterThan(strpos($html, '<section class="admin-metrics-section"'), $start);
        $map = substr($html, $start, $end - $start);
        $this->assertStringContainsString('data-country="US"', $map);
        $this->assertStringContainsString('United States · 1 user', $map);
        $this->assertStringNotContainsString('192.0.2.1', $map);
        $this->assertStringNotContainsString('untrusted name', $map);
        $this->assertStringNotContainsString('www.gstatic.com', $map);
        $this->assertStringContainsString('/js/views/admin-user-map.js?', $html);
        $this->assertStringContainsString('/css/vendor/flag-icon/flag-icon.min.css', $html);
        $this->actingAs(create(Admin::class, ['role' => 'editor']), 'admin');
        $this->get(route('admin.home'))->assertOk()->assertDontSee('data-user-country-map', false)->assertDontSee('admin-user-map.js')->assertDontSee('flag-icon.min.css');
    }
}
