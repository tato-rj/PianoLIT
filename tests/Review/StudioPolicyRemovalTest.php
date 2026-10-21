<?php

namespace Tests\Review;

use App\{FavoriteFolder, User};
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\{DB, Schema};

class StudioPolicyRemovalTest extends ReviewTestCase
{
    public function test_the_retired_table_and_public_and_account_endpoints_are_gone()
    {
        $this->assertFalse(Schema::hasTable('studio_policies'));
        $registered = collect(app('router')->getRoutes())->map(function ($route) {
            return $route->uri().' '.$route->getName().' '.$route->getActionName();
        })->implode("\n");
        $this->assertStringNotContainsString('studio-policies', $registered);
        $this->assertStringNotContainsString('StudioPoliciesController', $registered);

        $this->withExceptionHandling();
        foreach (['/tools/studio-policies' => 404, '/my-profile/studio-policies' => 405, '/my-profile/studio-policies/create' => 404, '/my-profile/studio-policies/1' => 404, '/my-profile/studio-policies/1/edit' => 404] as $path => $status) {
            // The collection path overlaps the account's existing PATCH/DELETE /{user} route.
            $this->get('http://localhost'.$path)->assertStatus($status);
        }

        $user = Model::withoutEvents(function () { return create(User::class); });
        $this->actingAs($user, 'web');
        $this->postJson('http://localhost/my-profile/studio-policies', ['name' => 'Retired tool'])->assertStatus(405);
        $this->patchJson('http://localhost/my-profile/studio-policies/1', ['name' => 'Retired tool'])->assertNotFound();
        $this->deleteJson('http://localhost/my-profile/studio-policies/1')->assertNotFound();
        $this->assertFalse(method_exists(User::class, 'studioPolicies'));
    }

    public function test_other_tools_and_account_navigation_still_render_without_the_table()
    {
        foreach (['tools.chord-finder.index', 'tools.circle-of-fifths', 'tools.scales.index', 'tools.arpeggios.index'] as $route) {
            $this->get(route($route))->assertOk()->assertDontSee('Staff Generator')->assertDontSee('Studio Policy');
        }

        $user = Model::withoutEvents(function () { return create(User::class); });
        $this->actingAs($user, 'web');
        $this->get(route('users.profile'))->assertOk()->assertSee('My profile information')->assertDontSee('Studio Policy');
        $this->get(route('users.purchases'))->assertOk()->assertDontSee('Studio Policy');
    }

    public function test_upgrade_removes_saved_policies_and_rollback_restores_only_the_empty_schema()
    {
        require_once database_path('migrations/2026_10_12_140000_drop_studio_policies_table.php');
        $migration = new \DropStudioPoliciesTable;
        $migration->down();

        $user = Model::withoutEvents(function () { return create(User::class); });
        $folder = FavoriteFolder::create(['user_id' => $user->id, 'name' => 'Keep favorites', 'is_default' => false]);
        DB::table('studio_policies')->insert([
            'user_id' => $user->id, 'data' => json_encode(['name' => 'Old studio policy']),
            'theme' => 'simple', 'created_at' => now(), 'updated_at' => now(),
        ]);
        $this->assertDatabaseCount('studio_policies', 1);

        $migration->up();
        $migration->up(); // Deployments where the table is already absent are safe.
        $this->assertFalse(Schema::hasTable('studio_policies'));
        $this->assertDatabaseHas('users', ['id' => $user->id, 'email' => $user->email]);
        $this->assertDatabaseHas('favorite_folders', ['id' => $folder->id, 'name' => 'Keep favorites']);

        $migration->down();
        $migration->down();
        $this->assertSame(['id', 'user_id', 'data', 'theme', 'created_at', 'updated_at'], Schema::getColumnListing('studio_policies'));
        $this->assertDatabaseCount('studio_policies', 0);
        DB::table('studio_policies')->insert(['user_id' => $user->id, 'data' => '{}']);
        $this->assertDatabaseHas('studio_policies', ['theme' => 'default']);
        $this->assertDatabaseHas('favorite_folders', ['id' => $folder->id]);
        $migration->up();
    }
}
