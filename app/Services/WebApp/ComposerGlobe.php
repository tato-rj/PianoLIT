<?php

namespace App\Services\WebApp;

use App\{Composer, Country};
use Illuminate\Support\Facades\DB;

/** Public globe data without serializing models or piece media. */
class ComposerGlobe
{
    const CONTINENTS = ['Africa', 'Antarctica', 'Asia', 'Europe', 'North America', 'Oceania', 'South America'];

    public static function catalogue(): array
    {
        $eligible = Composer::atLeast(1)->select('composers.id', 'composers.country_id')->toBase();
        $rows = DB::query()->fromSub($eligible, 'catalogue_composers')
            ->join('pieces', 'pieces.composer_id', '=', 'catalogue_composers.id')
            ->leftJoin('countries', 'countries.id', '=', 'catalogue_composers.country_id')
            ->select('countries.id', 'countries.name', 'countries.flag_code', 'countries.continent')
            ->selectRaw('COUNT(DISTINCT catalogue_composers.id) AS composers, COUNT(pieces.id) AS pieces')
            ->groupBy('countries.id', 'countries.name', 'countries.flag_code', 'countries.continent')
            ->orderBy('countries.name')->get();

        $totals = ['composers' => 0, 'pieces' => 0];
        $unmapped = $totals;
        $countries = [];
        $continents = [];
        foreach (self::CONTINENTS as $name) {
            $continents[$name] = ['name' => $name, 'composers' => 0, 'pieces' => 0, 'countries' => 0,
                'url' => route('webapp.composers.index', ['continent' => $name])];
        }

        foreach ($rows as $row) {
            $counts = ['composers' => (int) $row->composers, 'pieces' => (int) $row->pieces];
            foreach ($counts as $key => $count) $totals[$key] += $count;
            $continent = in_array($row->continent, self::CONTINENTS, true) ? $row->continent : null;
            if (! $continent) {
                foreach ($counts as $key => $count) $unmapped[$key] += $count;
            } else {
                foreach ($counts as $key => $count) $continents[$continent][$key] += $count;
                $continents[$continent]['countries']++;
            }
            if ($row->id) {
                $countries[] = array_merge($counts, [
                    'id' => (int) $row->id, 'name' => $row->name,
                    'code' => strtoupper(trim((string) $row->flag_code)), 'continent' => $continent,
                    'url' => route('webapp.composers.index', ['country' => $row->id]),
                    'portraits_url' => route('webapp.composers.globe.country', ['country' => $row->id]),
                ]);
            }
        }

        return compact('totals', 'unmapped', 'countries') + ['continents' => array_values($continents)];
    }

    public static function portraits(Country $country): array
    {
        // Use the directory's eligibility and a flat projection, avoiding the
        // Person model's default country eager load and appended attributes.
        $composers = Composer::atLeast(1)->where('country_id', $country->id)
            ->select('id', 'name', 'cover_path')->withCount('pieces')
            ->orderByDesc('pieces_count')->orderBy('name')->orderBy('id')->toBase()->get();

        return ['country_id' => $country->id, 'composers' => $composers->map(function ($composer) {
            return [
                'id' => (int) $composer->id, 'name' => $composer->name,
                'image' => $composer->cover_path ? storage($composer->cover_path) : null,
                'pieces' => (int) $composer->pieces_count,
                'url' => route('webapp.search.results', ['search' => $composer->name]),
            ];
        })->values()->all()];
    }
}
