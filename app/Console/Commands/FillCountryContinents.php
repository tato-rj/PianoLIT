<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\{DB, Schema};
use Illuminate\Support\Str;

class FillCountryContinents extends Command
{
    protected $signature = 'countries:fill-continents {--dry-run : Preview all matches without updating countries}';

    protected $description = 'Fill every country continent from the bundled GeoNames mapping';

    public function handle()
    {
        if (! Schema::hasColumn('countries', 'continent')) {
            $this->error('The countries.continent column is missing. Run php artisan migrate first.');
            return 1;
        }

        // Offline snapshot and attribution: docs/COUNTRY_CONTINENTS.md.
        $mapping = json_decode(file_get_contents(resource_path('data/country-continents.json')), true, 512, JSON_THROW_ON_ERROR);
        $names = [];
        foreach ($mapping as $code => $entry) {
            $names[$this->normalizeName($entry['name'])] = $code;
        }
        // Explicit common aliases only; never guess with fuzzy country matching.
        foreach ([
            'UK' => 'GB', 'Great Britain' => 'GB', 'England' => 'GB',
            'Scotland' => 'GB', 'Wales' => 'GB', 'Northern Ireland' => 'GB',
            'United States of America' => 'US', 'USA' => 'US',
            'Russian Federation' => 'RU', 'Czech Republic' => 'CZ',
            'Turkey' => 'TR', 'Türkiye' => 'TR', 'Swaziland' => 'SZ',
            'Macedonia' => 'MK', 'Republic of Korea' => 'KR',
            'Ivory Coast' => 'CI', 'Côte d’Ivoire' => 'CI', 'Cape Verde' => 'CV',
        ] as $name => $code) {
            $names[$this->normalizeName($name)] = $code;
        }
        $flagAliases = ['UK' => 'GB', 'GB-ENG' => 'GB', 'GB-SCT' => 'GB', 'GB-WLS' => 'GB', 'GB-NIR' => 'GB'];

        try {
            return DB::transaction(function () use ($mapping, $names, $flagAliases) {
                $query = DB::table('countries')->orderBy('id');
                if (! $this->option('dry-run')) {
                    $query->lockForUpdate();
                }
                $countries = $query->get(['id', 'name', 'flag_code', 'continent']);
                $updates = $preview = $unmatched = [];
                foreach ($countries as $country) {
                    $code = strtoupper(trim((string) $country->flag_code));
                    $code = $flagAliases[$code] ?? $code;
                    if (! isset($mapping[$code])) {
                        $code = $names[$this->normalizeName($country->name)] ?? null;
                    }
                    if (! isset($mapping[$code])) {
                        $unmatched[] = [$country->id, $country->name, $country->flag_code];
                        continue;
                    }

                    $continent = $mapping[$code]['continent'];
                    $preview[] = [$country->id, $country->name, $country->continent ?? '(empty)', $continent];
                    if ($country->continent !== $continent) {
                        $updates[$country->id] = $continent;
                    }
                }

                if ($this->option('dry-run')) {
                    $this->table(['ID', 'Country', 'Current continent', 'Mapped continent'], $preview);
                }
                if ($unmatched) {
                    $this->table(['ID', 'Unmatched country', 'Flag code'], $unmatched);
                    $this->error('No countries were updated. Correct these flag codes/names or add an explicit mapping, then rerun.');
                    return 1;
                }
                if ($this->option('dry-run')) {
                    $this->info('Dry run: '.count($updates).' of '.$countries->count().' countries would be updated. No changes made.');
                    return 0;
                }

                foreach ($updates as $id => $continent) {
                    // Only change the requested field; leave timestamps and other data intact.
                    DB::table('countries')->where('id', $id)->update(['continent' => $continent]);
                }
                $this->info('Updated '.count($updates).' countries; '.($countries->count() - count($updates)).' already correct.');
                return 0;
            });
        } catch (\Throwable $exception) {
            $this->error('Could not complete the continent update. All changes from this run were rolled back.');
            return 1;
        }
    }

    private function normalizeName(string $name): string
    {
        return preg_replace('/[^a-z0-9]+/', '', strtolower(Str::ascii($name)));
    }
}
