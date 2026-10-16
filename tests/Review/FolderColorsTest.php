<?php

namespace Tests\Review;

use App\{FavoriteFolder, User};
use App\Support\FolderPalette;
use Illuminate\Support\Facades\DB;

class FolderColorsTest extends ReviewTestCase
{
    public function setUp(): void
    {
        parent::setUp();
        config(['activity.enabled' => false]);
        $this->withoutMiddleware([\App\Http\Middleware\Logs\RecordWebAppLog::class, \App\Http\Middleware\UpdateLocation::class]);
        DB::table('users')->insert(['id' => 1, 'first_name' => 'Folder', 'last_name' => 'Owner', 'email' => 'folders@example.test', 'password' => 'unused']);
        $this->actingAs(User::findOrFail(1), 'web');
    }

    public function test_palette_names_match_the_existing_sass_scheme()
    {
        $sass = file_get_contents(resource_path('sass/shared/_tokens.scss'));
        preg_match('/\$pastel-palette:\s*\((.*?)\);/s', $sass, $map);
        preg_match_all('/([a-z]+):\s*#[a-f0-9]+/', $map[1], $names);
        $this->assertSame($names[1], FolderPalette::COLORS);
    }

    public function test_suggestions_use_unused_colors_then_balance_repetitions()
    {
        $colors = collect();
        foreach (FolderPalette::COLORS as $unused) {
            $next = FolderPalette::suggest($colors);
            $this->assertContains($next, FolderPalette::COLORS);
            $this->assertFalse($colors->contains($next));
            $colors->push($next);
        }
        $colors->push('sky');
        foreach (range(1, 30) as $attempt) {
            $this->assertNotSame('sky', FolderPalette::suggest($colors));
        }
    }

    public function test_web_creation_saves_the_selected_color_for_the_session_account()
    {
        DB::table('users')->insert(['id' => 2, 'first_name' => 'Other', 'last_name' => 'Account', 'email' => 'other@example.test', 'password' => 'unused']);
        $this->post(route('webapp.users.favorites.folders.store'), ['name' => 'Practice', 'color' => 'rose', 'user_id' => 2])->assertRedirect();
        $this->assertDatabaseHas('favorite_folders', ['user_id' => 1, 'name' => 'Practice', 'web_color' => 'rose']);
        $folder = FavoriteFolder::firstOrFail();
        $this->patch(route('webapp.users.favorites.folders.update', $folder), ['name' => 'Practice', 'description' => 'For this week', 'color' => 'mint'])->assertRedirect();
        $this->assertSame('mint', $folder->fresh()->web_color);
        $this->patch(route('webapp.users.favorites.folders.update', $folder), ['name' => 'Renamed', 'description' => 'Updated'])->assertRedirect();
        $this->assertSame('mint', $folder->fresh()->web_color);
        $this->assertSame('Updated', $folder->fresh()->description);
    }

    public function test_browser_creation_and_renaming_preserve_colors_of_new_mobile_folders()
    {
        $one = FavoriteFolder::create(['user_id' => 1, 'name' => 'Middle']);
        $two = FavoriteFolder::create(['user_id' => 1, 'name' => 'Zulu']);
        $other = FavoriteFolder::create(['user_id' => 2, 'name' => 'Private']);
        $time = $one->updated_at->toDateTimeString();
        $this->post(route('webapp.users.favorites.folders.store'), ['name' => 'Alpha'])->assertRedirect();
        $this->assertSame('sky', $one->fresh()->web_color);
        $this->assertSame('cream', $two->fresh()->web_color);
        $this->assertNotContains(FavoriteFolder::where('name', 'Alpha')->firstOrFail()->web_color, ['sky', 'cream']);
        $this->assertSame($time, $one->fresh()->updated_at->toDateTimeString());
        $this->assertNull($other->fresh()->web_color);
        $three = FavoriteFolder::create(['user_id' => 1, 'name' => 'ZZZ']);
        $previousColor = FolderPalette::forUser(1)->get($three->id);
        $this->patch(route('webapp.users.favorites.folders.update', $one), ['name' => 'AAAA'])->assertRedirect();
        $this->assertSame('sky', $one->fresh()->web_color);
        $this->assertSame($previousColor, $three->fresh()->web_color);
    }

    public function test_missing_color_uses_only_this_accounts_remaining_palette()
    {
        foreach (array_slice(FolderPalette::COLORS, 0, 11) as $index => $color) {
            FavoriteFolder::create(['user_id' => 1, 'name' => 'Folder '.$index, 'web_color' => $color]);
        }
        FavoriteFolder::create(['user_id' => 2, 'name' => 'Private folder', 'web_color' => 'terracotta']);
        $this->post(route('webapp.users.favorites.folders.store'), ['name' => 'New folder'])->assertRedirect();
        $this->assertDatabaseHas('favorite_folders', ['user_id' => 1, 'name' => 'New folder', 'web_color' => 'terracotta']);
        $this->post(route('webapp.users.favorites.folders.store'), ['name' => 'Repeat folder'])->assertRedirect();
        $this->assertContains(FavoriteFolder::where('name', 'Repeat folder')->firstOrFail()->web_color, FolderPalette::COLORS);
    }

    public function test_arbitrary_colors_are_rejected_without_changing_the_folder()
    {
        $this->withExceptionHandling();
        $folder = FavoriteFolder::create(['user_id' => 1, 'name' => 'Practice', 'web_color' => 'sky']);
        foreach (['#ff0000', 'blue', '', ['mint']] as $color) {
            $this->postJson(route('webapp.users.favorites.folders.store'), ['name' => 'New folder', 'color' => $color])->assertStatus(422)->assertJsonValidationErrors('color');
            $this->patchJson(route('webapp.users.favorites.folders.update', $folder), ['name' => 'Practice', 'color' => $color])->assertStatus(422)->assertJsonValidationErrors('color');
        }
        $this->assertSame(1, FavoriteFolder::count());
        $this->assertSame('sky', $folder->fresh()->web_color);
    }

    public function test_failed_creation_rolls_back_preserved_fallback_colors()
    {
        $legacy = FavoriteFolder::create(['user_id' => 1, 'name' => 'Practice']);
        $event = 'eloquent.creating: '.FavoriteFolder::class;
        \Illuminate\Support\Facades\Event::listen($event, function () { throw new \RuntimeException('Simulated folder creation failure'); });
        try {
            $this->post(route('webapp.users.favorites.folders.store'), ['name' => 'Failed creation']);
            $this->fail('Expected creation failure.');
        } catch (\RuntimeException $exception) {
            $this->assertSame('Simulated folder creation failure', $exception->getMessage());
        } finally {
            \Illuminate\Support\Facades\Event::forget($event);
        }
        $this->assertNull($legacy->fresh()->web_color);
        $this->assertSame(1, FavoriteFolder::count());
    }

    public function test_foreign_folders_and_guest_writes_remain_rejected()
    {
        $this->withExceptionHandling();
        $folder = FavoriteFolder::create(['user_id' => 2, 'name' => 'Private', 'web_color' => 'sky']);
        $this->patchJson(route('webapp.users.favorites.folders.update', $folder), ['name' => 'Private', 'color' => 'rose', 'folder_id' => $folder->id, 'user_id' => 1])->assertForbidden();
        $this->assertSame('sky', $folder->fresh()->web_color);
        auth('web')->logout();
        $this->postJson(route('webapp.users.favorites.folders.store'), ['name' => 'Guest folder', 'color' => 'rose', 'user_id' => 1])->assertStatus(401);
    }

    public function test_color_controls_show_the_stored_color_with_unique_radio_ids()
    {
        $folder = FavoriteFolder::create(['user_id' => 1, 'name' => 'Practice', 'web_color' => 'mint']);
        $html = view('webapp.user.my-pieces.favorites.index', [
            'folders' => collect([$folder]), 'folderPreviews' => collect(), 'folderSearch' => collect(),
            'errors' => new \Illuminate\Support\ViewErrorBag,
        ])->render();
        $dom = new \DOMDocument;
        @$dom->loadHTML($html);
        $xpath = new \DOMXPath($dom);
        $this->assertSame(1, $xpath->query('//article[@data-folder-color="mint"]')->length);
        $this->assertSame(24, $xpath->query('//input[@name="color"]')->length);
        $this->assertSame('mint', $xpath->query('//*[@id="edit-folder-'.$folder->id.'"]//input[@name="color"][@checked]')->item(0)->getAttribute('value'));
        $this->assertNotSame('mint', $xpath->query('//*[@id="new-folder-modal"]//input[@name="color"][@checked]')->item(0)->getAttribute('value'));
        $ids = [];
        foreach ($xpath->query('//input[@name="color"]') as $radio) $ids[] = $radio->getAttribute('id');
        $this->assertSame(count($ids), count(array_unique($ids)));
        $this->assertSame(0, $xpath->query('//input[@type="color"]')->length);
    }

    public function test_mobile_json_and_legacy_mobile_updates_do_not_expose_or_change_web_colors()
    {
        $folder = FavoriteFolder::create(['user_id' => 1, 'name' => 'Practice', 'web_color' => 'mint']);
        $this->assertArrayNotHasKey('web_color', $folder->toArray());
        $controller = new \App\Http\Controllers\Api\FavoriteFoldersController;
        $folders = $controller->index(new \Illuminate\Http\Request(['user_id' => 1]));
        $this->assertArrayNotHasKey('web_color', $folders->first()->toArray());
        $response = $controller->update(new \Illuminate\Http\Request(['user_id' => 1, 'folder_id' => $folder->id, 'name' => 'Practice', 'description' => 'From iOS', 'color' => 'rose']));
        $this->assertTrue($response->getData()->valid);
        $this->assertSame('mint', $folder->fresh()->web_color);
    }

    public function test_validation_recovery_keeps_the_color_only_in_the_submitted_modal()
    {
        $one = FavoriteFolder::create(['user_id' => 1, 'name' => 'Practice', 'web_color' => 'mint']);
        $two = FavoriteFolder::create(['user_id' => 1, 'name' => 'Recital', 'web_color' => 'sky']);
        app('request')->setLaravelSession(app('session')->driver());
        session()->flashInput(['color_picker' => 'edit-folder-color-'.$one->id, 'color' => 'rose']);
        $html = view('webapp.user.my-pieces.favorites.index', [
            'folders' => collect([$one, $two]), 'folderPreviews' => collect(), 'folderSearch' => collect(),
            'errors' => new \Illuminate\Support\ViewErrorBag,
        ])->render();
        $dom = new \DOMDocument;
        @$dom->loadHTML($html);
        $xpath = new \DOMXPath($dom);
        $this->assertSame('rose', $xpath->query('//*[@id="edit-folder-'.$one->id.'"]//input[@name="color"][@checked]')->item(0)->getAttribute('value'));
        $this->assertSame('sky', $xpath->query('//*[@id="edit-folder-'.$two->id.'"]//input[@name="color"][@checked]')->item(0)->getAttribute('value'));
    }

    public function test_migration_preserves_existing_palette_order_per_account_and_timestamps()
    {
        $migration = new \AddWebColorToFavoriteFolders;
        $migration->down();
        $time = '2026-09-01 12:00:00';
        foreach (range(502, 0) as $index) DB::table('favorite_folders')->insert(['user_id' => 1, 'name' => sprintf('Folder %03d', $index), 'created_at' => $time, 'updated_at' => $time]);
        DB::table('favorite_folders')->insert(['user_id' => 2, 'name' => 'Other', 'created_at' => $time, 'updated_at' => $time]);
        $migration->up();
        $expected = array_map(function ($index) { return FolderPalette::COLORS[$index % 12]; }, range(0, 502));
        $this->assertSame($expected, DB::table('favorite_folders')->where('user_id', 1)->orderBy('name')->pluck('web_color')->all());
        $this->assertDatabaseHas('favorite_folders', ['user_id' => 2, 'web_color' => 'sky', 'updated_at' => $time]);
        $this->assertSame([$time], DB::table('favorite_folders')->distinct()->pluck('updated_at')->all());
    }
}
