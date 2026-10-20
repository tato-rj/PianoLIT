<?php

namespace Tests\Review;

use App\{Admin, Piece, Playlist, Tutorial};
use App\Services\Admin\PlaylistsTable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\{DB, Storage};

class AdminPlaylistsTest extends ReviewTestCase
{
    protected $general, $journey;

    public function setUp(): void
    {
        parent::setUp();
        Storage::fake('public');
        $this->actingAs(create(Admin::class, ['role' => 'manager']), 'admin');
        Model::withoutEvents(function () {
            $this->general = collect(); $this->journey = collect();
            for ($i = 0; $i < 15; $i++) {
                $this->general->push(create(Playlist::class, ['name' => sprintf('General %02d', $i), 'subtitle' => 'A thoughtful collection',
                    'description' => 'Music for daily practice', 'featured' => $i === 0 ? 'Weekly' : null, 'order' => $i,
                    'published_at' => $i % 3 === 0 ? now()->subDay() : ($i % 3 === 1 ? null : now()->addDay())]));
            }
            for ($i = 0; $i < 3; $i++) $this->journey->push(create(Playlist::class, ['name' => 'Path '.$i, 'group' => 'journey', 'order' => $i]));
            $piece = create(Piece::class);
            $other = create(Piece::class);
            create(Tutorial::class, ['piece_id' => $piece->id]);
            $this->general[0]->pieces()->attach([$piece->id, $other->id]);
            $this->journey[0]->pieces()->attach($piece->id);
        });
    }

    public function test_directory_filters_sql_sorting_pagination_and_counts_exclude_legacy_journeys()
    {
        $response = $this->get(route('admin.playlists.index'))->assertOk()->assertSee('Arrange order')->assertSee('Draft')->assertSee('Published')->assertSee('Scheduled');
        $page = $response->viewData('playlists');
        $this->assertSame(12, $page->perPage());
        $this->assertSame(15, $page->total());
        $this->assertSame($this->general->take(12)->pluck('id')->all(), $page->pluck('id')->all());
        $this->assertSame(2, $page[0]->pieces_count);
        $this->assertSame(1, $page[0]->playable_pieces_count);
        $response->assertDontSee('Path 0')->assertDontSee('Journey');
        $this->get(route('admin.playlists.index', ['group' => 'journey']))->assertRedirect(route('admin.playlists.index'));
        $this->assertSame(3, Playlist::byGroup('journey')->count());
        foreach (['draft', 'published', 'scheduled'] as $status) {
            $this->assertSame(5, $this->get(route('admin.playlists.index', compact('status')))->assertOk()->viewData('playlists')->total());
        }
        $page = $this->get(route('admin.playlists.index', ['sort' => 'name_desc', 'page' => 2]))->assertOk()->viewData('playlists');
        $this->assertSame('General 02', $page->first()->name);
        $this->assertStringContainsString('sort=name_desc', $page->url(1));
        foreach (PlaylistsTable::SORTS as $sort => $label) {
            $this->assertCount(12, $this->get(route('admin.playlists.index', compact('sort')))->assertOk()->viewData('playlists'));
        }
        $this->general[0]->updateQuietly(['name' => '100% _unique! <script>']);
        $this->get(route('admin.playlists.index', ['search' => '% _unique!']))->assertOk()->assertSee('&lt;script&gt;', false)->assertDontSee('100% _unique! <script>', false);
        $this->assertSame(1, $this->get(route('admin.playlists.index', ['search' => 'Weekly']))->assertOk()->viewData('playlists')->total());
        $this->get(route('admin.playlists.index', ['search' => 'absent']))->assertOk()->assertSee('No matching playlists');
    }

    public function test_directory_validation_and_page_size_query_cost_are_bounded()
    {
        $this->withExceptionHandling();
        foreach ([['sort' => 'password_asc'], ['group' => ['journey']], ['status' => 'bad'], ['search' => ['bad']], ['per_page' => 1000], ['page' => -1]] as $data) {
            $this->getJson(route('admin.playlists.index', $data))->assertUnprocessable();
        }
        $counts = [];
        foreach ([12, 24] as $size) {
            DB::enableQueryLog(); DB::flushQueryLog();
            $data = (new PlaylistsTable)->get(Request::create('/playlists', 'GET', ['per_page' => $size]));
            view('admin.pages.playlists.table.rows', $data)->render();
            $queries = DB::getQueryLog(); DB::disableQueryLog();
            $counts[] = count($queries);
            $this->assertStringNotContainsString('from "composers"', implode(' ', array_column($queries, 'query')));
            $this->assertStringNotContainsString('activity_events', implode(' ', array_column($queries, 'query')));
        }
        $this->assertSame([2, 2], $counts);
    }

    public function test_creation_makes_regular_drafts_and_preserves_legacy_programmatic_group_support()
    {
        $payload = ['name' => 'A new path', 'subtitle' => 'First steps', 'description' => 'A short journey.', 'published_at' => now()];
        $response = $this->post(route('admin.playlists.store'), $payload)->assertRedirect();
        $created = Playlist::where('name', 'A new path')->firstOrFail();
        $response->assertRedirect(route('admin.playlists.edit', $created));
        $this->assertNull($created->group);
        $this->assertSame(15, $created->order);
        $this->assertNull($created->published_at);
        $this->assertSame(auth('admin')->id(), $created->creator_id);
        $payload['name'] = 'Legacy path'; $payload['is_journey'] = 1;
        $this->post(route('admin.playlists.store'), $payload)->assertRedirect();
        $this->assertSame('journey', Playlist::where('name', 'Legacy path')->firstOrFail()->group);
        $this->withExceptionHandling();
        $this->from(route('admin.playlists.index'))->post(route('admin.playlists.store'), ['name' => 'Restored name'])
            ->assertRedirect()->assertSessionHasErrors(['subtitle', 'description'], null, 'playlistCreate');
        $this->get(route('admin.playlists.index'))->assertOk()->assertSee('autoshow')->assertSee('value="Restored name"', false);
        $this->postJson(route('admin.playlists.store'), array_merge($payload, ['group' => 'unknown']))->assertUnprocessable();
    }

    public function test_arrange_order_saves_a_complete_group_preserves_piece_order_and_rejects_stale_or_mixed_lists()
    {
        $original = $this->general->pluck('id')->all();
        $ids = array_reverse($original);
        $pieces = $this->general[0]->pieces->pluck('id')->all();
        $this->get(route('admin.playlists.order'))->assertOk()->assertSee('Save changes')->assertSee('original_ids[]', false);
        $this->post(route('admin.playlists.reorder'), ['_method' => 'PATCH', 'group' => 'general', 'ids' => $ids, 'original_ids' => $original])
            ->assertRedirect(route('admin.playlists.index'));
        $this->assertSame($ids, Playlist::byGroup(null)->sorted()->pluck('id')->all());
        $this->assertTrue(Playlist::find($ids[0])->is_featured);
        $this->assertSame($this->journey->pluck('id')->all(), Playlist::byGroup('journey')->sorted()->pluck('id')->all());
        $this->assertSame($pieces, $this->general[0]->fresh()->pieces->pluck('id')->all());
        $this->withExceptionHandling();
        foreach ([['ids' => $ids, 'original_ids' => $original], ['ids' => array_slice($ids, 0, 3)], ['ids' => [$ids[0], $this->journey[0]->id]], ['ids' => [999999]], ['ids' => [$ids[0], $ids[0]]], ['ids' => 'bad']] as $data) {
            $this->patchJson(route('admin.playlists.reorder'), $data)->assertUnprocessable();
            $this->assertSame($ids, Playlist::byGroup(null)->sorted()->pluck('id')->all());
        }
        $this->patchJson(route('admin.playlists.reorder'), ['ids' => $this->journey->reverse()->pluck('id')->values()->all()])->assertOk();
    }

    public function test_reorder_rolls_back_every_position_on_a_database_failure()
    {
        $original = $this->journey->pluck('id')->all();
        DB::unprepared('CREATE TRIGGER reject_playlist_order BEFORE UPDATE OF "order" ON playlists WHEN NEW.id = '.$original[1]." BEGIN SELECT RAISE(ABORT, 'fixture failure'); END");
        try {
            $this->withExceptionHandling()->patchJson(route('admin.playlists.reorder'), ['ids' => array_reverse($original)])->assertStatus(500);
            $this->assertSame($original, Playlist::byGroup('journey')->sorted()->pluck('id')->all());
        } finally { DB::unprepared('DROP TRIGGER reject_playlist_order'); }
    }

    public function test_row_actions_preserve_filters_and_deleting_the_last_page_clamps_the_return()
    {
        $filters = ['return_to' => 'directory', 'status' => 'draft', 'sort' => 'name_desc'];
        $target = $this->general[1];
        $this->patch(route('admin.playlists.publication', ['playlist' => $target] + $filters))->assertRedirect(route('admin.playlists.index', ['sort' => 'name_desc', 'status' => 'draft']));
        $this->assertNotNull($target->fresh()->published_at);
        $this->withExceptionHandling()->patchJson(route('admin.playlists.publication', $target).'?return_to=directory&sort=bad')->assertUnprocessable();
        $this->assertNotNull($target->fresh()->published_at);
        $this->general->take(2)->each(function ($playlist) { $playlist->delete(); });
        $target = $this->general->last();
        $this->delete(route('admin.playlists.destroy', ['playlist' => $target, 'return_to' => 'directory', 'page' => 2]))
            ->assertRedirect(route('admin.playlists.index'));
        $this->assertDatabaseMissing('playlists', ['id' => $target->id]);
    }

    public function test_every_directory_and_order_action_requires_the_admin_session()
    {
        auth('admin')->logout();
        $this->withExceptionHandling();
        foreach (['admin.playlists.index', 'admin.playlists.order'] as $route) $this->get(route($route))->assertRedirect(route('admin.login.show'));
        $this->patch(route('admin.playlists.reorder'), ['ids' => $this->general->pluck('id')->all()])->assertRedirect(route('admin.login.show'));
        $this->post(route('admin.playlists.store'), [])->assertRedirect(route('admin.login.show'));
    }
}
