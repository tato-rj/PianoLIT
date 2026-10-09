<?php

namespace Tests\Review;

use App\Country;
use Illuminate\Support\Facades\{Artisan, DB, Http, Schema};

class FillCountryContinentsTest extends ReviewTestCase
{
    public $mockConsoleOutput = false;

    private function country(string $name, ?string $flag = null, ?string $continent = null): Country
    {
        return Country::create([
            'name' => $name, 'nationality' => 'Unchanged', 'flag_code' => $flag,
            'continent' => $continent, 'created_at' => '2000-01-01 00:00:00',
            'updated_at' => '2000-01-01 00:00:00',
        ]);
    }

    public function test_migration_is_nullable_and_reversible_without_losing_countries()
    {
        $country = $this->country('France', 'fr');
        $this->assertNull($country->fresh()->continent);
        $migration = new \AddContinentToCountriesTable;
        $migration->down();
        $this->assertFalse(Schema::hasColumn('countries', 'continent'));
        $this->assertSame(1, Artisan::call('countries:fill-continents'));
        $this->assertStringContainsString('Run php artisan migrate first', Artisan::output());
        $migration->up();
        $this->assertNull($country->fresh()->continent);
        $this->assertSame('France', $country->fresh()->name);
    }

    public function test_updates_every_country_and_preserves_other_fields_and_mobile_serialization()
    {
        $examples = [
            ['Italy', ' IT ', null, 'Europe'],
            ['Japan', 'jp', '', 'Asia'],
            ['Egypt', 'eg', 'Wrong', 'Africa'],
            ['United States', 'us', null, 'North America'],
            ['Brazil', 'br', null, 'South America'],
            ['Australia', 'au', null, 'Oceania'],
            ['Antarctica', 'aq', null, 'Antarctica'],
            ['Russia', 'ru', null, 'Europe'],
            ['Turkey', 'tr', null, 'Asia'],
        ];
        $countries = [];
        foreach ($examples as [$name, $flag, $old, $expected]) {
            $country = $this->country($name, $flag, $old);
            $countries[] = [$country, $country->fresh()->toArray(), $expected];
        }
        $this->assertSame(0, Artisan::call('countries:fill-continents'));
        $this->assertStringContainsString('Updated 9 countries; 0 already correct.', Artisan::output());
        foreach ($countries as [$country, $before, $expected]) {
            $fresh = $country->fresh();
            $this->assertSame($expected, $fresh->continent);
            $this->assertSame($before, $fresh->toArray());
            $this->assertArrayNotHasKey('continent', json_decode($fresh->toJson(), true));
        }
        $this->assertSame(0, Artisan::call('countries:fill-continents'));
        $this->assertStringContainsString('Updated 0 countries; 9 already correct.', Artisan::output());
        Http::assertNothingSent();
    }

    public function test_uses_codes_first_then_normalized_names_and_explicit_aliases()
    {
        $examples = [
            ['Localized display name', 'fr', 'Europe'],
            ['  united STATES of america ', null, 'North America'],
            ['Côte d’Ivoire', 'invalid', 'Africa'],
            ['Czech Republic', null, 'Europe'],
            ['England', 'gb-eng', 'Europe'],
            ['Scotland', null, 'Europe'],
            ['United Kingdom', 'uk', 'Europe'],
            ['Türkiye', null, 'Asia'],
        ];
        $countries = [];
        foreach ($examples as [$name, $flag, $expected]) {
            $countries[] = [$this->country($name, $flag), $expected];
        }
        $this->assertSame(0, Artisan::call('countries:fill-continents'));
        foreach ($countries as [$country, $expected]) {
            $this->assertSame($expected, $country->fresh()->continent);
        }
    }

    public function test_dry_run_previews_matches_without_writing()
    {
        $country = $this->country('France', 'fr', 'Wrong');
        $this->assertSame(0, Artisan::call('countries:fill-continents', ['--dry-run' => true]));
        $output = Artisan::output();
        $this->assertStringContainsString('Europe', $output);
        $this->assertStringContainsString('Wrong', $output);
        $this->assertStringContainsString('1 of 1 countries would be updated', $output);
        $this->assertSame('Wrong', $country->fresh()->continent);
    }

    public function test_unmatched_countries_abort_before_any_write_and_report_the_record()
    {
        $known = $this->country('France', 'fr');
        $unknown = $this->country('Unknown place', 'zz');
        foreach ([true, false] as $dryRun) {
            $this->assertSame(1, Artisan::call('countries:fill-continents', ['--dry-run' => $dryRun]));
            $output = Artisan::output();
            $this->assertStringContainsString('Unknown place', $output);
            $this->assertStringContainsString('zz', $output);
            $this->assertStringContainsString('No countries were updated', $output);
            $this->assertNull($known->fresh()->continent);
            $this->assertNull($unknown->fresh()->continent);
        }
    }

    public function test_a_database_write_failure_rolls_back_earlier_updates()
    {
        $first = $this->country('France', 'fr');
        $second = $this->country('Japan', 'jp');
        // ReviewTestCase guarantees in-memory SQLite before any migrations/SQL.
        DB::unprepared("CREATE TRIGGER fail_continent_update BEFORE UPDATE OF continent ON countries
            WHEN OLD.flag_code = 'jp' BEGIN SELECT RAISE(ABORT, 'Test update failure'); END");
        $this->assertSame(1, Artisan::call('countries:fill-continents'));
        $this->assertStringContainsString('rolled back', Artisan::output());
        $this->assertNull($first->fresh()->continent);
        $this->assertNull($second->fresh()->continent);
    }

    public function test_bundled_mapping_covers_all_252_source_entries_with_valid_continents()
    {
        $mapping = json_decode(file_get_contents(resource_path('data/country-continents.json')), true, 512, JSON_THROW_ON_ERROR);
        $this->assertCount(252, $mapping);
        foreach ($mapping as $code => $entry) {
            $this->assertMatchesRegularExpression('/^[A-Z]{2}$/', $code);
            $this->assertContains($entry['continent'], ['Africa', 'Antarctica', 'Asia', 'Europe', 'North America', 'Oceania', 'South America']);
            $this->country($entry['name'], strtolower($code));
        }
        $this->assertSame(0, Artisan::call('countries:fill-continents'));
        $this->assertSame(0, DB::table('countries')->whereNull('continent')->count());
        $this->assertSame(252, DB::table('countries')->count());
        Http::assertNothingSent();
    }

    public function test_empty_table_is_a_successful_no_op()
    {
        $this->assertSame(0, Artisan::call('countries:fill-continents'));
        $this->assertStringContainsString('Updated 0 countries; 0 already correct.', Artisan::output());
    }
}
