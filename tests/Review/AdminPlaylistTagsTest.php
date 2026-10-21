<?php

namespace Tests\Review;

use App\{Admin, Piece, Playlist, PlaylistTag, Tag, Tutorial};
use App\Services\Admin\PlaylistTags;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\{Cache, DB, Redis, Schema, Storage};

class AdminPlaylistTagsTest extends ReviewTestCase
{
    public function setUp(): void
    {
        parent::setUp();
        Storage::fake('public');
        $this->actingAs(create(Admin::class, ['role' => 'manager']), 'admin');
        Redis::shouldReceive('get')->andReturn(null);
        $this->withoutMiddleware([\App\Http\Middleware\Logs\RecordAppLog::class, \App\Http\Middleware\Logs\RecordWebAppLog::class, \App\Http\Middleware\UpdateLocation::class]);
    }

    private function taggedPlaylist($tag, $group = null)
    {
        return DB::transaction(function () use ($tag, $group) {
            return create(Playlist::class, ['name' => 'A collection', 'group' => $group] + app(PlaylistTags::class)->assignment($tag->id));
        });
    }

    public function test_migration_imports_exact_existing_names_and_preserves_all_legacy_json_on_upgrade_and_rollback()
    {
        require_once database_path('migrations/2026_10_20_180000_create_playlist_tags_table.php');
        $migration = new \CreatePlaylistTagsTable;
        $migration->down();
        $names = ['Theme', 'Theme', 'theme', ' Theme ', '   ', '', null];
        foreach ($names as $i => $name) create(Playlist::class, ['name' => 'Legacy '.$i, 'featured' => $name, 'group' => $i === 1 ? 'journey' : null]);
        $before = Playlist::orderBy('id')->get()->toArray();
        $migration->up();
        $this->assertSame(3, PlaylistTag::count());
        $this->assertSame($before, Playlist::with('featuredTag')->orderBy('id')->get()->toArray());
        foreach (Playlist::orderBy('id')->get() as $i => $playlist) {
            $this->assertSame($names[$i], $playlist->featured);
            if (trim((string) $names[$i]) !== '') $this->assertSame($names[$i], $playlist->featuredTag->name);
            else $this->assertNull($playlist->featured_tag_id);
        }
        $this->assertSame(Playlist::find(1)->featured_tag_id, Playlist::find(2)->featured_tag_id);
        $migration->down();
        $this->assertFalse(Schema::hasColumn('playlists', 'featured_tag_id'));
        $this->assertSame($before, Playlist::orderBy('id')->get()->toArray());
        $migration->up();
    }

    public function test_ajax_crud_validates_exact_unique_names_and_escapes_inline_fields()
    {
        $this->postJson(route('admin.playlists.tags.store'), ['name' => ' Theme '])->assertCreated()->assertJsonPath('tags.0.name', 'Theme');
        $tag = PlaylistTag::firstOrFail();
        $this->withExceptionHandling();
        foreach (['Theme', '', '   ', ['bad'], str_repeat('x', 256)] as $name) {
            $this->postJson(route('admin.playlists.tags.store'), compact('name'))->assertUnprocessable()->assertJsonValidationErrors('name');
        }
        $this->postJson(route('admin.playlists.tags.store'), ['name' => 'theme'])->assertCreated();
        $response = $this->patchJson(route('admin.playlists.tags.update', $tag), ['name' => '<script>name</script>'])->assertOk();
        $this->assertStringContainsString('&lt;script&gt;name&lt;/script&gt;', $response->json('html'));
        $this->assertStringNotContainsString('<script>name</script>', $response->json('html'));
        $this->assertSame('<script>name</script>', $tag->fresh()->name);
        $this->patchJson(route('admin.playlists.tags.update', $tag), ['name' => 'theme'])->assertUnprocessable();
        try {
            DB::table('playlist_tags')->insert(['name' => 'Theme', 'name_key' => hash('sha256', 'theme')]);
            $this->fail('The database must reject a duplicate name key.');
        } catch (QueryException $exception) { $this->assertSame(2, PlaylistTag::count()); }
        $this->deleteJson(route('admin.playlists.tags.destroy', $tag))->assertOk();
        $this->assertDatabaseMissing('playlist_tags', ['id' => $tag->id]);
        $this->patchJson(route('admin.playlists.tags.update', 99999), ['name' => 'Missing'])->assertNotFound();
    }

    public function test_association_rename_and_delete_update_the_legacy_api_value_without_adding_json_fields()
    {
        $tag = app(PlaylistTags::class)->save('Theme');
        $playlist = $this->taggedPlaylist($tag);
        $journey = $this->taggedPlaylist($tag, 'journey');
        Model::withoutEvents(function () use ($playlist, $journey) {
            for ($i = 0; $i < 6; $i++) {
                $piece = create(Piece::class);
                foreach (['level' => 'beginner', 'period' => 'romantic', 'length' => 'short'] as $type => $name) {
                    $piece->tags()->attach(Tag::firstOrCreate(['type' => $type, 'name' => $name]));
                }
                create(Tutorial::class, ['piece_id' => $piece->id]);
                $playlist->pieces()->attach($piece->id, ['order' => $i]);
                $journey->pieces()->attach($piece->id, ['order' => 5 - $i]);
            }
        });
        Cache::put('app.playlists.order', [$playlist->id], 60);
        $generalUrl = route('api.playlists.index');
        $journeyUrl = route('api.playlists.index', ['group' => 'journey']);
        $before = $this->getJson($generalUrl)->assertOk()->json();
        $journeyBefore = $this->getJson($journeyUrl)->assertOk()->json();
        $pivot = DB::table('piece_playlist')->orderBy('id')->get()->toArray();
        $this->assertSame('Theme', $before[0]['featured']);
        $this->assertArrayNotHasKey('featured_tag_id', $before[0]);
        $this->assertArrayNotHasKey('featured_tag', $playlist->fresh()->load('featuredTag')->toArray());
        $this->patchJson(route('admin.playlists.tags.update', $tag), ['name' => 'Mood'])->assertOk();
        $after = $this->getJson($generalUrl)->assertOk()->json();
        $journeyAfter = $this->getJson($journeyUrl)->assertOk()->json();
        foreach ([[$before, $after], [$journeyBefore, $journeyAfter]] as [$old, $new]) {
            $this->assertSame('Mood', $new[0]['featured']);
            unset($old[0]['featured'], $old[0]['updated_at'], $new[0]['featured'], $new[0]['updated_at']);
            $this->assertSame($old, $new);
        }
        $this->assertEquals($pivot, DB::table('piece_playlist')->orderBy('id')->get()->toArray());
        $this->deleteJson(route('admin.playlists.tags.destroy', $tag))->assertOk();
        foreach ([$playlist, $journey] as $record) {
            $this->assertNull($record->fresh()->featured_tag_id);
            $this->assertNull($record->fresh()->featured);
        }
        $this->assertNull($this->getJson($generalUrl)->assertOk()->json('0.featured'));
    }

    public function test_create_and_edit_select_existing_tags_and_validation_precedes_all_playlist_writes()
    {
        $tag = app(PlaylistTags::class)->save('Theme');
        $other = app(PlaylistTags::class)->save('Mood');
        $payload = ['name' => 'New playlist', 'subtitle' => 'A subtitle', 'description' => 'A description', 'featured_tag_id' => $tag->id, 'featured' => 'Forged legacy text'];
        $this->post(route('admin.playlists.store'), $payload)->assertRedirect();
        $playlist = Playlist::where('name', 'New playlist')->firstOrFail();
        $this->assertSame($tag->id, $playlist->featured_tag_id);
        $this->assertSame('Theme', $playlist->featured);
        $this->get(route('admin.playlists.index'))->assertOk()->assertSee('Manage tags')->assertSee('data-tag-create', false)->assertSee('js-playlist-tag-select');
        $this->get(route('admin.playlists.edit', $playlist))->assertOk()->assertSee('name="featured_tag_id"', false)->assertDontSee('name="featured"', false);
        $this->patch(route('admin.playlists.update', $playlist), array_merge($payload, ['featured_tag_id' => $other->id]))->assertRedirect();
        $this->assertSame('Mood', $playlist->fresh()->featured);
        $this->withExceptionHandling();
        foreach ([999999, ['invalid']] as $id) {
            $this->patchJson(route('admin.playlists.update', $playlist), array_merge($payload, ['name' => 'Bad mutation', 'featured_tag_id' => $id]))->assertUnprocessable();
            $this->assertSame('New playlist', $playlist->fresh()->name);
            $this->assertSame('Mood', $playlist->fresh()->featured);
        }
        $this->patch(route('admin.playlists.update', $playlist), array_merge($payload, ['featured_tag_id' => '']))->assertRedirect();
        $this->assertNull($playlist->fresh()->featured);
        $this->assertNull($playlist->fresh()->featured_tag_id);
    }

    public function test_rename_and_delete_roll_back_tag_and_mirror_changes_on_failure()
    {
        $tag = app(PlaylistTags::class)->save('Theme');
        $first = $this->taggedPlaylist($tag); $second = $this->taggedPlaylist($tag);
        DB::unprepared('CREATE TRIGGER reject_playlist_tag BEFORE UPDATE OF featured ON playlists WHEN NEW.id = '.$second->id." BEGIN SELECT RAISE(ABORT, 'fixture failure'); END");
        try {
            $this->withExceptionHandling()->patchJson(route('admin.playlists.tags.update', $tag), ['name' => 'Mood'])->assertStatus(500);
            $this->deleteJson(route('admin.playlists.tags.destroy', $tag))->assertStatus(500);
            $this->assertSame('Theme', $tag->fresh()->name);
            foreach ([$first, $second] as $record) {
                $this->assertSame('Theme', $record->fresh()->featured);
                $this->assertSame($tag->id, $record->fresh()->featured_tag_id);
            }
        } finally { DB::unprepared('DROP TRIGGER reject_playlist_tag'); }
    }

    public function test_playlist_save_rolls_back_details_tag_and_piece_order_together()
    {
        $old = app(PlaylistTags::class)->save('Theme'); $new = app(PlaylistTags::class)->save('Mood');
        $playlist = $this->taggedPlaylist($old);
        $pieces = Model::withoutEvents(function () { return collect([create(Piece::class), create(Piece::class), create(Piece::class)]); });
        $playlist->pieces()->attach($pieces[0]->id, ['order' => 0]);
        $playlist->pieces()->attach($pieces[1]->id, ['order' => 1]);
        $before = DB::table('piece_playlist')->orderBy('id')->get()->toArray();
        DB::unprepared('CREATE TRIGGER reject_playlist_piece BEFORE INSERT ON piece_playlist WHEN NEW.piece_id = '.$pieces[2]->id." BEGIN SELECT RAISE(ABORT, 'fixture failure'); END");
        try {
            $this->withExceptionHandling()->patchJson(route('admin.playlists.update', $playlist), [
                'name' => 'Changed', 'subtitle' => 'Changed', 'description' => 'Changed', 'featured_tag_id' => $new->id,
                'pieces' => [$pieces[2]->id, $pieces[0]->id],
            ])->assertStatus(500);
            $this->assertSame('A collection', $playlist->fresh()->name);
            $this->assertSame('Theme', $playlist->fresh()->featured);
            $this->assertSame($old->id, $playlist->fresh()->featured_tag_id);
            $this->assertEquals($before, DB::table('piece_playlist')->orderBy('id')->get()->toArray());
        } finally { DB::unprepared('DROP TRIGGER reject_playlist_piece'); }
    }

    public function test_ajax_writes_require_admin_session_and_csrf()
    {
        $tag = app(PlaylistTags::class)->save('Theme');
        $this->withExceptionHandling();
        auth('admin')->logout();
        $this->postJson(route('admin.playlists.tags.store'), ['name' => 'Mood'])->assertUnauthorized();
        $this->patchJson(route('admin.playlists.tags.update', $tag), ['name' => 'Mood'])->assertUnauthorized();
        $this->deleteJson(route('admin.playlists.tags.destroy', $tag))->assertUnauthorized();
        $this->actingAs(create(Admin::class), 'admin');
        $this->app['env'] = 'review-csrf';
        $this->withSession(['_token' => 'tag-csrf'])->postJson(route('admin.playlists.tags.store'), ['name' => 'Mood'])->assertStatus(419);
        $this->withSession(['_token' => 'tag-csrf'])->patchJson(route('admin.playlists.tags.update', $tag), ['name' => 'Mood'])->assertStatus(419);
        $this->withSession(['_token' => 'tag-csrf'])->deleteJson(route('admin.playlists.tags.destroy', $tag))->assertStatus(419);
        $this->withHeader('X-CSRF-TOKEN', 'tag-csrf')->postJson(route('admin.playlists.tags.store'), ['name' => 'Mood'])->assertCreated();
        $this->assertSame('Theme', $tag->fresh()->name);
        $this->app['env'] = 'testing';
    }
}
