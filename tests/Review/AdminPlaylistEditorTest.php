<?php

namespace Tests\Review;

use App\{Admin, Composer, Piece, Playlist, Tag};
use App\Services\Admin\PlaylistPieceFinder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\{DB, Storage};

class AdminPlaylistEditorTest extends ReviewTestCase
{
    protected $playlist, $pieces, $composers, $levels;

    public function setUp(): void
    {
        parent::setUp();
        Storage::fake('public');
        $this->actingAs(create(Admin::class, ['role' => 'manager']), 'admin');
        Model::withoutEvents(function () {
            $this->playlist = create(Playlist::class, ['name' => 'Quiet mornings']);
            $this->composers = collect([create(Composer::class, ['name' => 'Clara Schumann']), create(Composer::class, ['name' => 'Johann Sebastian Bach'])]);
            $this->levels = collect([create(Tag::class, ['type' => 'level', 'name' => 'beginner']), create(Tag::class, ['type' => 'level', 'name' => 'intermediate'])]);
            $this->pieces = collect();
            for ($i = 0; $i < 30; $i++) {
                $piece = create(Piece::class, ['name' => sprintf('Study %02d', $i), 'nickname' => null, 'composer_id' => $this->composers[$i % 2]->id, 'updated_at' => now()->subMinutes($i)]);
                $piece->tags()->attach($this->levels[$i % 2]->id);
                $this->pieces->push($piece);
            }
            foreach ([2, 0, 1] as $position => $i) $this->playlist->pieces()->attach($this->pieces[$i]->id, ['order' => $position]);
        });
    }

    private function xpath($html)
    {
        $dom = new \DOMDocument;
        @$dom->loadHTML($html);
        return new \DOMXPath($dom);
    }

    private function candidateIds(array $filters = [])
    {
        $response = $this->getJson(route('admin.playlists.pieces', ['playlist' => $this->playlist] + $filters))->assertOk();
        $ids = [];
        foreach ($this->xpath($response->json('html'))->query('//tr[@data-finder-piece]') as $row) $ids[] = (int) $row->getAttribute('data-finder-piece');
        return [$ids, $response];
    }

    public function test_editor_uses_shared_sections_and_restores_the_saved_piece_order_without_datatables()
    {
        $before = DB::table('piece_playlist')->get()->toArray();
        $response = $this->get(route('admin.playlists.edit', $this->playlist))->assertOk()->assertSee('Playlist details')->assertSee('Add pieces')->assertSee('Save changes');
        $response->assertDontSee('datatables.net')->assertDontSee('new DataTable')->assertDontSee('class="selected-piece"', false);
        $xpath = $this->xpath($response->getContent());
        $rows = $xpath->query('//form[@id="playlist-edit-form"]//tr[@data-selected-piece]');
        $this->assertSame(3, $rows->length);
        $this->assertSame((string) $this->pieces[2]->id, $rows[0]->getAttribute('data-selected-piece'));
        $this->assertSame(1, $xpath->query('//form[@id="playlist-edit-form"]')->length);
        $this->assertSame(0, $xpath->query('//form[@id="playlist-edit-form"]//form')->length);
        $this->assertSame(1, $xpath->query('//div[@data-playlist-finder]//form[@data-admin-filter-form]')->length);
        foreach ($xpath->query('//tr[@data-selected-piece]//a[contains(@class,"admin-table__primary")]') as $link) {
            $this->assertSame('_blank', $link->getAttribute('target'));
            $this->assertSame('noopener noreferrer', $link->getAttribute('rel'));
        }
        $this->assertStringContainsString(route('admin.playlists.pieces', $this->playlist).'?page=2', $response->getContent());
        $this->assertEquals($before, DB::table('piece_playlist')->get()->toArray());
    }

    public function test_finder_search_filters_sorting_and_pagination_apply_to_the_full_repertoire()
    {
        [$ids, $response] = $this->candidateIds();
        $this->assertCount(12, $ids);
        $this->assertSame(30, $response->json('total'));
        $this->assertSame($this->pieces->take(12)->pluck('id')->all(), $ids);
        [$ids, $response] = $this->candidateIds(['composer_id' => $this->composers[1]->id, 'level_id' => $this->levels[1]->id, 'sort' => 'name_desc', 'page' => 2]);
        $this->assertCount(3, $ids); $this->assertSame(15, $response->json('total'));
        $this->assertStringContainsString('composer_id='.$this->composers[1]->id, $response->json('html'));
        foreach (PlaylistPieceFinder::SORTS as $sort => $label) $this->assertCount(12, $this->candidateIds(compact('sort'))[0]);
        $this->assertSame(15, $this->candidateIds(['search' => 'Clara beginner'])[1]->json('total'));
        $this->pieces[0]->updateQuietly(['name' => '100% _unique! <script>']);
        [$ids, $response] = $this->candidateIds(['search' => '% _unique!']);
        $this->assertSame([$this->pieces[0]->id], $ids);
        $this->assertStringContainsString('&lt;script&gt;', $response->json('html'));
        $this->assertStringNotContainsString('<script>', $response->json('html'));
        $this->assertSame(0, $this->candidateIds(['search' => 'absent'])[1]->json('total'));
        $this->assertSame(3, $this->playlist->pieces()->count());
    }

    public function test_finder_validation_admin_guard_and_query_cost_are_bounded()
    {
        $this->withExceptionHandling();
        foreach ([['sort' => 'password_asc'], ['search' => ['bad']], ['composer_id' => 99999], ['level_id' => ['bad']], ['per_page' => 1000], ['page' => -1]] as $filters) {
            $this->getJson(route('admin.playlists.pieces', ['playlist' => $this->playlist] + $filters))->assertUnprocessable();
        }
        $finder = new PlaylistPieceFinder;
        $choices = $finder->choices(); $counts = [];
        foreach ([12, 24] as $size) {
            DB::enableQueryLog(); DB::flushQueryLog();
            $data = $finder->get(Request::create(route('admin.playlists.pieces', $this->playlist), 'GET', ['per_page' => $size]));
            view('admin.pages.playlists.edit.finder.table', $data + $choices + ['playlist' => $this->playlist])->render();
            $queries = DB::getQueryLog(); DB::disableQueryLog();
            $counts[] = count($queries);
            foreach (['activity_events', 'recently_viewed_pieces', 'tutorials', 'favorites'] as $table) $this->assertStringNotContainsString($table, implode(' ', array_column($queries, 'query')));
        }
        $this->assertSame([4, 4], $counts);
        auth('admin')->logout();
        $this->getJson(route('admin.playlists.pieces', $this->playlist))->assertUnauthorized();
        $this->get(route('admin.playlists.edit', $this->playlist))->assertRedirect(route('admin.login.show'));
    }

    public function test_validation_restores_the_draft_selection_including_an_explicitly_empty_playlist()
    {
        $url = route('admin.playlists.edit', $this->playlist);
        $payload = ['_playlist_editor' => $this->playlist->id, 'name' => '', 'subtitle' => 'Keep my subtitle', 'description' => 'Keep my introduction',
            'pieces' => [$this->pieces[9]->id, $this->pieces[2]->id, $this->pieces[0]->id]];
        $this->withExceptionHandling()->from($url)->patch(route('admin.playlists.update', $this->playlist), $payload)->assertSessionHasErrors('name');
        $response = $this->get($url)->assertOk()->assertSee('Keep my subtitle')->assertSee('Unsaved changes');
        $this->assertSame($payload['pieces'], $response->viewData('selectedPieces')->pluck('id')->all());
        unset($payload['pieces']);
        $this->from($url)->patch(route('admin.playlists.update', $this->playlist), $payload)->assertSessionHasErrors('name');
        $this->assertCount(0, $this->get($url)->assertOk()->viewData('selectedPieces'));
        $this->assertSame(3, $this->playlist->pieces()->count());
    }

    public function test_saving_adds_removes_and_reorders_only_the_route_bound_playlists_pieces()
    {
        $other = create(Playlist::class, ['group' => 'journey']);
        $other->pieces()->attach($this->pieces[1]->id, ['order' => 0]);
        $payload = ['name' => 'Changed title', 'subtitle' => 'Changed subtitle', 'description' => 'Changed description', 'pieces' => [99 => $this->pieces[8]->id, 7 => $this->pieces[2]->id]];
        $this->patch(route('admin.playlists.update', $this->playlist), $payload)->assertRedirect();
        $this->assertSame([$this->pieces[8]->id, $this->pieces[2]->id], $this->playlist->fresh()->pieces->pluck('id')->all());
        $this->assertSame([0, 1], DB::table('piece_playlist')->where('playlist_id', $this->playlist->id)->orderBy('order')->pluck('order')->all());
        $this->assertSame([$this->pieces[1]->id], $other->fresh()->pieces->pluck('id')->all());
        $this->withExceptionHandling()->patchJson(route('admin.playlists.update', $this->playlist), array_merge($payload, ['pieces' => [$this->pieces[2]->id, $this->pieces[2]->id]]))->assertUnprocessable();
        $this->assertSame(2, $this->playlist->pieces()->count());
        unset($payload['pieces']);
        $this->patch(route('admin.playlists.update', $this->playlist), $payload)->assertRedirect();
        $this->assertSame(0, $this->playlist->pieces()->count());
    }
}
