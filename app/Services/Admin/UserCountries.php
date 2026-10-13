<?php

namespace App\Services\Admin;

use Illuminate\Support\Facades\DB;

class UserCountries
{
    public function data(): array
    {
        $world = json_decode(file_get_contents(resource_path('data/admin-world-map.json')), true, 512, JSON_THROW_ON_ERROR);
        $names = json_decode(file_get_contents(resource_path('data/country-continents.json')), true, 512, JSON_THROW_ON_ERROR);
        foreach ($world['countries'] as $shape) $names[$shape['code']]['name'] = $shape['name'];

        // locations.user_id is unique. Start from users to exclude orphan locations
        // and retain accounts whose country has not been recorded.
        $rows = DB::table('users')->leftJoin('locations', 'locations.user_id', '=', 'users.id')
            ->selectRaw('UPPER(TRIM(locations.countryCode)) as country, COUNT(*) as total')
            ->groupByRaw('UPPER(TRIM(locations.countryCode))')->get();
        $countries = [];
        $total = (int) $rows->sum('total');
        foreach ($rows as $row) {
            if (!isset($names[$row->country])) continue;
            $countries[] = ['code' => $row->country, 'name' => $names[$row->country]['name'], 'total' => (int) $row->total];
        }
        usort($countries, function ($a, $b) { return ($b['total'] <=> $a['total']) ?: strcmp($a['name'], $b['name']); });
        $located = array_sum(array_column($countries, 'total'));
        foreach ($countries as $index => &$country) {
            $country['share'] = $located ? round($country['total'] / $located * 100, 1) : 0;
            $country['color'] = $index < 6 ? (string) $index : 'other';
        }
        unset($country);

        return ['total' => $total, 'located' => $located, 'unlocated' => $total - $located,
            'countries' => $countries, 'byCode' => array_column($countries, null, 'code'),
            'top' => $countries[0] ?? null, 'world' => $world];
    }
}
