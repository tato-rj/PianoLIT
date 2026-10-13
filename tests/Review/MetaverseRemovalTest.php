<?php

namespace Tests\Review;

use App\Admin;
use Illuminate\Support\Facades\{DB, Schema};

class MetaverseRemovalTest extends ReviewTestCase
{
    public function test_fresh_schema_and_routes_no_longer_expose_the_feature()
    {
        $this->assertFalse(Schema::hasTable('metaverse_events'));
        $this->assertFalse(Schema::hasTable('metaverse_locations'));
        $this->assertTrue(Schema::hasTable('locations'));

        foreach (app('router')->getRoutes() as $route) {
            $this->assertStringNotContainsString('metaverse', strtolower((string) $route->getName()));
            $this->assertStringNotContainsString('metaverse', strtolower($route->uri()));
            $this->assertStringNotContainsString('metaverse', strtolower($route->getActionName()));
        }

        $this->withExceptionHandling();
        $this->get('http://localhost/metaverse')->assertNotFound();
        $this->actingAs(create(Admin::class), 'admin');
        $adminHost = 'http://admin.'.config('app.short_url');
        $this->get($adminHost.'/metaverse')->assertNotFound();
        $this->get($adminHost.'/metaverse/locations')->assertNotFound();
        $this->get(route('admin.home'))->assertOk()->assertDontSee('Metaverse');
    }

    public function test_upgrade_drops_only_feature_tables_and_rollback_restores_empty_schemas()
    {
        require_once database_path('migrations/2026_10_12_130000_drop_metaverse_tables.php');
        $migration = new \DropMetaverseTables;
        $migration->down(); // Simulate an installation with the original feature tables.
        DB::table('locations')->insert(['user_id' => 123, 'cityName' => 'Keep account location']);
        $location = DB::table('metaverse_locations')->insertGetId([
            'name' => 'Old venue', 'url' => 'https://example.test/venue', 'venue' => 'Retired room',
        ]);
        DB::table('metaverse_events')->insert([
            'location_id' => $location, 'theme' => 'Retired concert',
            'duration' => '30 min', 'time' => '18:00', 'date' => '2026-10-12 18:00:00',
        ]);
        $this->assertDatabaseCount('metaverse_events', 1);
        $this->assertDatabaseCount('metaverse_locations', 1);

        $migration->up();
        $migration->up(); // Also safe when the obsolete tables are already absent.
        $this->assertFalse(Schema::hasTable('metaverse_events'));
        $this->assertFalse(Schema::hasTable('metaverse_locations'));
        $this->assertDatabaseHas('locations', ['user_id' => 123, 'cityName' => 'Keep account location']);

        $migration->down();
        $migration->down();
        $this->assertSame(['id', 'name', 'url', 'venue', 'capacity', 'created_at', 'updated_at'], Schema::getColumnListing('metaverse_locations'));
        $this->assertSame(['id', 'location_id', 'theme', 'duration', 'description', 'time', 'date', 'created_at', 'updated_at'], Schema::getColumnListing('metaverse_events'));
        $this->assertDatabaseCount('metaverse_events', 0);
        $this->assertDatabaseCount('metaverse_locations', 0);
        $this->assertDatabaseHas('locations', ['user_id' => 123, 'cityName' => 'Keep account location']);
        $migration->up();
    }
}
