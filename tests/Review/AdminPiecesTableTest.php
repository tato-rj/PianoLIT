<?php

namespace Tests\Review;

use App\{Admin, Composer, Favorite, Piece, Tag, Tutorial, User};
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class AdminPiecesTableTest extends ReviewTestCase
{
    protected $pieces;

    public function setUp(): void
    {
        parent::setUp();
        $this->actingAs(create(Admin::class, ['role' => 'manager']), 'admin');
        Model::withoutEvents(function () {
            $composer = create(Composer::class, ['name' => 'Aaron Adams']);
            $other = create(Composer::class, ['name' => 'Zara Zed']);
            $period = create(Tag::class, ['type' => 'period', 'name' => 'baroque']);
            $length = create(Tag::class, ['type' => 'length', 'name' => 'short']);
            $levels = ['elementary', 'beginner', 'intermediate', 'advanced'];
            $names = ['Zulu', 'Alpha', 'Bravo', 'Charlie'];
            $dates = [null, '2020-01-01', '2022-01-01', '2021-01-01'];
            $favorites = [0, 2, 1, 3];
            $this->pieces = collect();
            foreach ($names as $i => $name) {
                $piece = create(Piece::class, ['name' => $name, 'composer_id' => $i < 2 ? $composer->id : $other->id,
                    'highlighted_at' => $dates[$i], 'updated_at' => '2026-01-0'.($i + 1),
                    'audio_path' => 'audio/example.mp3', 'cover_path' => 'covers/example.jpg']);
                $piece->tags()->attach([$period->id, $length->id, create(Tag::class, ['type' => 'level', 'name' => $levels[$i]])->id]);
                for ($j = 0; $j < $i; $j++) $piece->tags()->attach(create(Tag::class, ['type' => 'mood', 'name' => 'Mood '.$j])->id);
                for ($j = 0; $j < $favorites[$i]; $j++) create(Favorite::class, ['piece_id' => $piece->id, 'user_id' => create(User::class)->id]);
                $this->pieces->push($piece);
            }
            foreach ([0, 1] as $i) {
                $video = create(Tutorial::class, ['piece_id' => $this->pieces[$i]->id, 'type' => 'Performance', 'category' => 'performance']);
                $video->moments()->create(['start_time' => 0, 'title' => 'Theme', 'comment' => '']);
            }
            foreach ([0, 2] as $i) create(Tutorial::class, ['piece_id' => $this->pieces[$i]->id, 'type' => 'Synthesia', 'category' => 'synthesia']);
        });
    }

    protected function table(array $parameters = [])
    {
        // Yajra holds the Request in a singleton; real HTTP requests have fresh
        // containers, while multiple isolated test requests reuse this app.
        $this->app->forgetInstance('datatables.request');
        return $this->getJson(route('admin.pieces.index').'?'.http_build_query(array_replace_recursive([
            'draw' => 1, 'start' => 0, 'length' => 10,
            'without_videos' => 0, 'without_moments' => 0, 'without_synthesia' => 0,
        ], $parameters)), ['X-Requested-With' => 'XMLHttpRequest']);
    }

    protected function ids(array $parameters = [])
    {
        $response = $this->table($parameters)->assertOk();
        $this->assertArrayNotHasKey('error', $response->json());
        return array_column($response->json('data'), 'id');
    }

    public function test_columns_and_cell_contents_are_preserved_except_the_media_icons()
    {
        $html = $this->get(route('admin.pieces.index'))->assertOk()->getContent();
        preg_match('/<table[^>]*id="pieces-table".*?<\/table>/s', $html, $table);
        preg_match_all('/<th\b[^>]*>(.*?)<\/th>/s', $table[0], $headers);
        foreach (['ID', 'Piece', 'Composer', 'Tags', 'Level', 'Highlighted', 'Favorited', 'Actions'] as $i => $label) {
            $this->assertStringContainsString($label, strip_tags($headers[1][$i]));
        }
        $this->assertStringNotContainsString('cdn.datatables.net', $html);
        $this->assertStringContainsString('class="admin-table__table admin-pieces-table"', $html);
        foreach (['without_videos', 'without_moments', 'without_synthesia'] as $filter) {
            preg_match('/<input[^>]*name="'.$filter.'"[^>]*>/', $html, $input);
            $this->assertNotEmpty($input);
            $this->assertStringNotContainsString('checked', $input[0]);
        }
        foreach (['Missing video', 'Missing moments', 'Missing synthesia'] as $label) {
            $this->assertStringContainsString('>'.$label.'</label>', $html);
        }
        $row = $this->table(['order' => [['column' => 0, 'dir' => 'asc']]])->assertOk()->json('data.0');
        $this->assertEqualsCanonicalizing(['id', 'name', 'composer', 'tags', 'level', 'ranking', 'favorited', 'actions'], array_keys($row));
        $this->assertSame('A. Adams', $row['composer']['short_name']);
        foreach (['name', 'tags', 'level', 'ranking', 'favorited', 'actions'] as $column) {
            $item = Piece::with('tags')->findOrFail($row['id']);
            $this->assertSame(view('admin.pages.pieces.table.'.$column, compact('item'))->render(), $row[$column]);
        }
    }

    public function test_every_missing_filter_combination_and_unfiltered_default()
    {
        $this->pieces[1]->tutorials->first()->moments()->delete();
        for ($mask = 0; $mask < 8; $mask++) {
            $expected = [];
            foreach ([[1, 1, 1], [1, 0, 0], [0, 0, 1], [0, 0, 0]] as $i => $flags) {
                if ((!($mask & 1) || !$flags[0]) && (!($mask & 2) || ($flags[0] && !$flags[1])) && (!($mask & 4) || !$flags[2])) $expected[] = $this->pieces[$i]->id;
            }
            $response = $this->table(['without_videos' => (int) (bool) ($mask & 1), 'without_moments' => (int) (bool) ($mask & 2),
                'without_synthesia' => (int) (bool) ($mask & 4)])->assertOk();
            $this->assertEqualsCanonicalizing($expected, array_column($response->json('data'), 'id'));
            $this->assertSame(4, $response->json('recordsTotal'));
            $this->assertSame(count($expected), $response->json('recordsFiltered'));
        }
        $this->app->forgetInstance('datatables.request');
        $this->getJson(route('admin.pieces.index'), ['X-Requested-With' => 'XMLHttpRequest'])
            ->assertOk()->assertJsonPath('recordsFiltered', 4)->assertJsonCount(4, 'data');
        // Existing data can identify Synthesia by category, as the removed icon did.
        Tutorial::where('piece_id', $this->pieces[2]->id)->update(['type' => 'Tutorial']);
        $this->assertEqualsCanonicalizing([$this->pieces[1]->id, $this->pieces[3]->id], $this->ids(['without_synthesia' => 1]));
    }

    public function test_missing_video_and_moments_only_consider_performance_videos()
    {
        $this->assertEqualsCanonicalizing([$this->pieces[2]->id, $this->pieces[3]->id], $this->ids(['without_videos' => 1]));
        $this->assertSame([], $this->ids(['without_moments' => 1]));

        Model::withoutEvents(function () {
            foreach (['Synthesia', 'Slow performance', 'Harmonic analysis'] as $type) {
                $video = create(Tutorial::class, ['piece_id' => $this->pieces[2]->id, 'type' => $type,
                    'category' => $type === 'Synthesia' ? 'synthesia' : 'other']);
                $video->moments()->create(['start_time' => 0, 'title' => 'Theme', 'comment' => '']);
            }
        });
        $this->assertEqualsCanonicalizing([$this->pieces[2]->id, $this->pieces[3]->id], $this->ids(['without_videos' => 1]));
        $this->assertSame([], $this->ids(['without_moments' => 1]));

        $performance = Model::withoutEvents(function () {
            return create(Tutorial::class, ['piece_id' => $this->pieces[2]->id, 'type' => 'Performance', 'category' => 'performance']);
        });
        $this->assertSame([$this->pieces[3]->id], $this->ids(['without_videos' => 1]));
        $this->assertSame([$this->pieces[2]->id], $this->ids(['without_moments' => 1]));
        $this->assertSame([], $this->ids(['without_moments' => 1, 'without_videos' => 1]));
        $this->assertSame([], $this->ids(['without_moments' => 1, 'without_synthesia' => 1]));

        // Legacy records identified by category still count as a performance.
        $performance->updateQuietly(['type' => 'Tutorial']);
        $this->assertSame([$this->pieces[3]->id], $this->ids(['without_videos' => 1]));
        $this->assertSame([$this->pieces[2]->id], $this->ids(['without_moments' => 1]));
        $performance->moments()->create(['start_time' => 0, 'title' => 'Theme', 'comment' => '']);
        $this->assertSame([], $this->ids(['without_moments' => 1]));
    }

    public function test_each_data_column_sorts_in_both_directions_and_across_pages()
    {
        $orders = [[0, 1, 2, 3], [1, 2, 3, 0], [1, 0, 3, 2], [0, 1, 2, 3], [0, 1, 2, 3], [0, 1, 3, 2], [0, 2, 1, 3]];
        foreach ($orders as $column => $indices) {
            foreach (['asc', 'desc'] as $direction) {
                // Composer ties keep the deterministic descending-ID fallback.
                $sorted = $direction === 'asc' ? $indices : ($column === 2 ? [3, 2, 1, 0] : array_reverse($indices));
                $expected = array_map(function ($i) { return $this->pieces[$i]->id; }, $sorted);
                $parameters = ['order' => [['column' => $column, 'dir' => $direction]]];
                $this->assertSame($expected, $this->ids($parameters), 'Column '.$column.' '.$direction);
                $this->assertSame(array_slice($expected, 1, 2), $this->ids($parameters + ['start' => 1, 'length' => 2]));
            }
        }
        $this->assertSame($this->pieces->pluck('id')->reverse()->values()->all(), $this->ids());
        Model::withoutEvents(function () {
            $this->pieces[1]->tags()->attach(create(Tag::class, ['type' => 'sublevel', 'name' => 'late beginner'])->id);
            $this->pieces[2]->tags()->attach(create(Tag::class, ['type' => 'sublevel', 'name' => 'early beginner'])->id);
        });
        $this->assertSame([$this->pieces[0]->id, $this->pieces[2]->id, $this->pieces[1]->id, $this->pieces[3]->id],
            $this->ids(['order' => [['column' => 4, 'dir' => 'asc']]]));
    }

    public function test_search_validation_escaping_and_authenticated_admin_boundary()
    {
        $this->assertSame([$this->pieces[2]->id], $this->ids(['search' => ['value' => 'Zara Bravo']]));
        $this->assertCount(2, $this->ids(['search' => ['value' => 'Adams']]));
        $this->assertSame([$this->pieces[3]->id], $this->ids(['search' => ['value' => 'advanced']]));
        $this->assertSame([], $this->ids(['search' => ['value' => "' OR 1=1 --"]]));
        $this->pieces[0]->updateQuietly(['name' => '<script>alert(1)</script>']);
        $this->pieces[0]->composer->updateQuietly(['name' => '<img src=x onerror=alert(1)>']);
        $row = $this->table(['order' => [['column' => 0, 'dir' => 'asc']]])->assertOk()->json('data.0');
        $this->assertStringNotContainsString('<script>', $row['name']);
        $this->assertStringNotContainsString('<img', $row['composer']['short_name']);
        $this->withExceptionHandling();
        foreach ([['without_moments' => ['bad']], ['length' => 1000], ['start' => -1], ['search' => ['value' => ['bad']]],
            ['columns' => [['name' => ['bad']]]], ['columns' => [['search' => ['value' => ['bad']]]]],
            ['order' => [['column' => 0, 'dir' => 'desc; DROP TABLE pieces']]]] as $bad) $this->table($bad)->assertUnprocessable();
        $this->assertCount(4, $this->ids(['columns' => [['data' => 'arbitrary', 'name' => 'malicious.column', 'searchable' => true, 'search' => ['value' => 'bad']]]]));
        auth()->guard('admin')->logout();
        $this->table()->assertUnauthorized();
    }

    protected function directory(array $parameters = [])
    {
        return $this->get(route('admin.pieces.index', $parameters));
    }

    public function test_directory_filters_sorts_and_paginates_the_complete_query()
    {
        foreach (['id', 'name', 'composer', 'tags', 'level', 'highlight', 'favorites', 'updated'] as $column) {
            foreach (['asc', 'desc'] as $direction) {
                $parameters = ['sort' => $column.'_'.$direction];
                $page = $this->directory($parameters)->assertOk()->viewData('pieces');
                $this->assertCount(4, $page);
                if ($column === 'updated') {
                    $expected = $this->pieces->pluck('id')->all();
                    if ($direction === 'desc') $expected = array_reverse($expected);
                } else {
                    $legacyColumn = array_search($column, ['id', 'name', 'composer', 'tags', 'level', 'highlight', 'favorites']);
                    $expected = $this->ids(['order' => [['column' => $legacyColumn, 'dir' => $direction]]]);
                }
                $this->assertSame($expected, $page->pluck('id')->all());
            }
        }
        $this->pieces[1]->tutorials->first()->moments()->delete();
        for ($mask = 0; $mask < 8; $mask++) {
            $filters = ['without_videos' => (int) (bool) ($mask & 1), 'without_moments' => (int) (bool) ($mask & 2), 'without_synthesia' => (int) (bool) ($mask & 4)];
            $this->assertSame($this->ids($filters), $this->directory($filters)->assertOk()->viewData('pieces')->pluck('id')->all());
        }
        $this->assertSame([$this->pieces[2]->id], $this->directory(['search' => 'Zara Bravo'])->assertOk()->viewData('pieces')->pluck('id')->all());
        $this->pieces[0]->updateQuietly(['name' => '100% _unique!']);
        foreach (['%' => 1, '_unique!' => 1, '_absent' => 0] as $search => $total) {
            $this->assertSame($total, $this->directory(compact('search'))->assertOk()->viewData('pieces')->total());
        }
        Model::withoutEvents(function () {
            for ($i = 0; $i < 25; $i++) {
                $piece = create(Piece::class, ['name' => sprintf('Directory %02d', $i), 'composer_id' => $this->pieces[0]->composer_id]);
                $piece->tags()->attach($this->pieces[0]->tags->pluck('id'));
            }
        });
        $filters = ['search' => 'Directory', 'sort' => 'name_asc', 'per_page' => 12, 'without_synthesia' => 1, 'page' => 2];
        $response = $this->directory($filters)->assertOk()->assertSee('Showing 13–24 of 25 results');
        $this->assertSame('Directory 12', $response->viewData('pieces')->first()->name);
        $this->assertStringContainsString('without_synthesia=1', $response->viewData('pieces')->url(3));
        $this->assertSame(12, $this->directory()->viewData('pieces')->perPage());
        foreach ([24, 48, 96] as $size) $this->assertSame($size, $this->directory(['per_page' => $size])->viewData('pieces')->perPage());
    }

    public function test_directory_links_empty_states_and_validation()
    {
        $piece = $this->pieces[0];
        $piece->updateQuietly(['name' => '<script>alert(1)</script>']);
        $piece->tags()->detach();
        $response = $this->directory()->assertOk()->assertSee('&lt;script&gt;', false)->assertDontSee('<script>alert(1)</script>', false)->assertSee('Not set');
        $dom = new \DOMDocument;
        @$dom->loadHTML($response->getContent());
        $xpath = new \DOMXPath($dom);
        $link = $xpath->query('//table[@id="pieces-table"]//a[@href="'.route('webapp.pieces.show', $piece).'"]')->item(0);
        $this->assertSame('_blank', $link->getAttribute('target'));
        $this->assertSame('noopener noreferrer', $link->getAttribute('rel'));
        $this->assertSame(1, $xpath->query('//form[@id="pieces-table-filters"]//input[@name="without_moments"]')->length);
        $this->assertSame(1, $xpath->query('//table[@id="pieces-table"]//a[@href="'.route('admin.pieces.edit', $piece).'"]')->length);
        $this->directory(['search' => 'does-not-exist'])->assertOk()->assertSee('No matching pieces');
        $this->withExceptionHandling();
        foreach ([['search' => ['bad']], ['without_videos' => ['bad']], ['sort' => 'password_asc'], ['page' => -1], ['per_page' => 1000]] as $invalid) {
            $this->getJson(route('admin.pieces.index', $invalid))->assertUnprocessable();
        }
        auth()->guard('admin')->logout();
        $this->directory()->assertRedirect(route('admin.login.show'));
    }

    public function test_directory_query_count_stays_constant_without_media_or_activity_serialization()
    {
        Model::withoutEvents(function () {
            for ($i = 0; $i < 25; $i++) {
                $piece = create(Piece::class, ['composer_id' => $this->pieces[0]->composer_id]);
                $piece->tags()->attach($this->pieces[0]->tags->pluck('id'));
            }
        });
        $counts = [];
        foreach ([12, 24] as $size) {
            DB::enableQueryLog(); DB::flushQueryLog();
            $data = (new \App\Services\Admin\PiecesTable)->get(\Illuminate\Http\Request::create('/pieces', 'GET', ['per_page' => $size]));
            view('admin.pages.pieces.table.rows', $data)->render();
            $queries = DB::getQueryLog(); DB::disableQueryLog();
            $counts[] = count($queries);
            $this->assertCount($size, $data['pieces']);
            $this->assertStringNotContainsString('activity_events', implode(' ', array_column($queries, 'query')));
            $this->assertStringNotContainsString('from "tutorials"', implode(' ', array_column($queries, 'query')));
        }
        $this->assertSame([5, 5], $counts);
    }

    public function test_quick_edits_validate_types_preserve_other_tags_and_roll_back_level_failures()
    {
        $piece = $this->pieces[0];
        $old = $piece->level->id;
        $new = $this->pieces[1]->level->id;
        $mood = Model::withoutEvents(function () { return create(Tag::class, ['type' => 'mood', 'name' => 'Serene']); });
        $this->get(route('admin.pieces.load-tags', $piece))->assertOk()->assertDontSee('name="level', false)->assertSee('Serene');
        $this->patchJson(route('admin.pieces.update-level', $piece), ['old_level_id' => $old, 'new_level_id' => $new])->assertOk()->assertJsonPath('level_id', $new);
        $this->assertSame($new, $piece->fresh()->level->id);
        $this->assertTrue($piece->fresh()->tags->contains('type', 'period'));
        $this->withExceptionHandling();
        $this->patchJson(route('admin.pieces.update-level', $piece), ['old_level_id' => $old, 'new_level_id' => $new])->assertStatus(409);
        $this->patchJson(route('admin.pieces.update-level', $piece), ['old_level_id' => $new, 'new_level_id' => $mood->id])->assertUnprocessable();
        $this->patchJson(route('admin.pieces.update-tag', $piece), ['id' => $new])->assertUnprocessable();
        $this->patchJson(route('admin.pieces.update-tag', $piece), ['id' => $mood->id])->assertOk()->assertJsonPath('count', 1);
        $this->patchJson(route('admin.pieces.update-tag', $piece), ['id' => $mood->id])->assertOk()->assertJsonPath('count', 0);
        $trigger = 'CREATE TRIGGER reject_piece_level BEFORE INSERT ON piece_tag WHEN NEW.tag_id = '.$old." BEGIN SELECT RAISE(ABORT, 'fixture failure'); END";
        DB::unprepared($trigger);
        try {
            $this->patchJson(route('admin.pieces.update-level', $piece), ['old_level_id' => $new, 'new_level_id' => $old])->assertStatus(500);
            $this->assertSame($new, $piece->fresh()->level->id, 'Failed replacement restores the original level.');
        } finally { DB::unprepared('DROP TRIGGER reject_piece_level'); }
    }

    public function test_deletion_preserves_directory_filters_and_clamps_the_page()
    {
        \Illuminate\Support\Facades\Storage::fake('public');
        Model::withoutEvents(function () {
            for ($i = 0; $i < 13; $i++) create(Piece::class, ['name' => 'Delete fixture', 'composer_id' => $this->pieces[0]->composer_id]);
        });
        $filters = ['search' => 'Delete fixture', 'sort' => 'id_asc', 'without_videos' => 1, 'page' => 2];
        $target = $this->directory($filters)->viewData('pieces')->first();
        $this->withExceptionHandling()->deleteJson(route('admin.pieces.destroy', $target), ['sort' => 'invalid'])->assertUnprocessable();
        $this->assertDatabaseHas('pieces', ['id' => $target->id]);
        $this->delete(route('admin.pieces.destroy', ['piece' => $target] + $filters))->assertRedirect(route('admin.pieces.index', array_diff_key($filters, ['page' => true])));
        $this->assertDatabaseMissing('pieces', ['id' => $target->id]);
    }

    public function test_query_count_is_bounded_when_page_size_grows()
    {
        Model::withoutEvents(function () {
            for ($i = 0; $i < 26; $i++) {
                $piece = create(Piece::class, ['composer_id' => $this->pieces[0]->composer_id]);
                $piece->tags()->attach($this->pieces[0]->tags->pluck('id'));
            }
        });
        $counts = [];
        foreach ([10, 25] as $length) {
            DB::enableQueryLog(); DB::flushQueryLog();
            $response = $this->table(compact('length'))->assertOk();
            $this->assertArrayNotHasKey('error', $response->json());
            $this->assertCount($length, $response->json('data'));
            $counts[] = count(DB::getQueryLog());
            DB::disableQueryLog();
        }
        $this->assertSame($counts[0], $counts[1]);
        $this->assertLessThanOrEqual(6, $counts[1]);
        $normalPiece = Piece::with('tags')->find($this->pieces[0]->id)->toArray();
        $this->assertArrayHasKey('media', $normalPiece);
        $this->assertArrayHasKey('long_name', $normalPiece);
    }
}
