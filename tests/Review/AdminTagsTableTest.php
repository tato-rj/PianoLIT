<?php

namespace Tests\Review;

use App\{Admin, Piece, Tag};
use App\Services\Admin\TagsTable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AdminTagsTableTest extends ReviewTestCase
{
    protected $admin;

    public function setUp(): void
    {
        parent::setUp();
        $this->admin = create(Admin::class, ['role' => 'manager', 'name' => 'Alex']);
        $this->actingAs($this->admin, 'admin');
    }

    protected function tag(array $attributes = [])
    {
        return create(Tag::class, $attributes + ['type' => 'mood', 'creator_id' => $this->admin->id]);
    }

    protected function page(array $filters = [])
    {
        return $this->get(route('admin.tags.index', $filters))->assertOk();
    }

    public function test_directory_search_type_sort_and_pagination_cover_all_tags()
    {
        for ($i = 0; $i < 25; $i++) $this->tag(['name' => sprintf('Mood %02d', $i)]);
        $this->tag(['name' => 'Baroque', 'type' => 'period', 'ordering' => 2]);
        $first = $this->page()->assertSee('Showing 1–12 of 26 results')->assertDontSee('cdn.datatables.net')->viewData('tags');
        $second = $this->page(['page' => 2])->viewData('tags');
        $this->assertCount(12, $first);
        $this->assertCount(0, $first->pluck('id')->intersect($second->pluck('id')));
        $this->assertSame(25, $this->page(['tag_type' => 'mood'])->viewData('tags')->total());
        $this->assertSame('Baroque', $this->page(['search' => 'BAROQUE period'])->viewData('tags')->first()->name);
        $page = $this->page(['tag_type' => 'mood', 'search' => 'Mood', 'sort' => 'name_desc', 'per_page' => 24, 'page' => 2])->viewData('tags');
        $this->assertSame('Mood 00', $page->first()->name);
        foreach (['tag_type=mood', 'search=Mood', 'sort=name_desc', 'per_page=24'] as $query) $this->assertStringContainsString($query, $page->url(1));
        $this->page(['search' => 'absent'])->assertSee('No matching tags');
    }

    public function test_counts_ordering_and_missing_creators_render_without_loading_pieces()
    {
        $first = $this->tag(['name' => 'First', 'ordering' => 1]);
        $last = $this->tag(['name' => 'Last', 'ordering' => 8]);
        $unset = $this->tag(['name' => 'Unset', 'creator_id' => null]);
        $piece = Model::withoutEvents(function () { return create(Piece::class); });
        $first->pieces()->attach($piece);
        foreach (['asc' => [$first->id, $last->id, $unset->id], 'desc' => [$last->id, $first->id, $unset->id]] as $direction => $ids) {
            $this->assertSame($ids, $this->page(['sort' => 'ordering_'.$direction])->viewData('tags')->modelKeys());
        }
        $page = $this->page(['sort' => 'pieces_desc'])->assertSee('Not recorded')->assertSee('Not set')->viewData('tags');
        $this->assertSame($first->id, $page->first()->id);
        $this->assertSame(1, $page->first()->pieces_count);
        $this->assertFalse($page->first()->relationLoaded('pieces'));
        foreach (TagsTable::SORTS as $sort => $label) $this->assertCount(3, $this->page(compact('sort'))->viewData('tags'));
    }

    public function test_search_is_literal_cells_are_escaped_and_forms_keep_accessible_actions()
    {
        $tag = $this->tag(['name' => '100% _unique! <script>']);
        $this->tag(['name' => 'Unrelated']);
        foreach (['%' => 1, '_unique!' => 1, '_absent' => 0, "' OR 1=1 --" => 0] as $search => $count) {
            $this->assertSame($count, $this->page(compact('search'))->viewData('tags')->total());
        }
        $response = $this->page()->assertSee('&lt;script&gt;', false)->assertDontSee('100% _unique! <script>', false);
        foreach (['Edit tag 100%', 'Delete tag 100%', 'name="_token"', 'value="DELETE"', 'tag-ordering-help', 'The pieces themselves are kept.'] as $text) $response->assertSee($text, false);
        $this->assertArrayNotHasKey('ordering', $tag->fresh()->toArray());
        $this->assertArrayHasKey('order', $tag->fresh()->toArray());
    }

    public function test_validation_and_manager_permissions_apply_before_mutations()
    {
        $tag = $this->tag(['name' => 'Original']);
        $this->withExceptionHandling();
        foreach ([['search' => ['bad']], ['tag_type' => 'unknown'], ['sort' => 'password_asc'], ['per_page' => 1000], ['page' => -1]] as $invalid) {
            $this->getJson(route('admin.tags.index', $invalid))->assertUnprocessable();
            $this->deleteJson(route('admin.tags.destroy', ['tag' => $tag->id] + $invalid))->assertUnprocessable();
            $this->assertNotNull($tag->fresh());
        }
        foreach ([['name' => ['bad'], 'type' => 'mood'], ['name' => 'Bad', 'type' => 'unknown'], ['name' => 'Bad', 'type' => ['mood']]] as $invalid) {
            $this->postJson(route('admin.tags.store'), $invalid)->assertUnprocessable();
            $this->patchJson(route('admin.tags.update', $tag), $invalid)->assertUnprocessable();
        }
        $this->actingAs(create(Admin::class, ['role' => 'editor']), 'admin');
        $this->getJson(route('admin.tags.index'))->assertForbidden();
        $this->postJson(route('admin.tags.store'), ['name' => 'New', 'type' => 'mood'])->assertForbidden();
        $this->patchJson(route('admin.tags.update', $tag), ['name' => 'Changed'])->assertForbidden();
        $this->deleteJson(route('admin.tags.destroy', $tag))->assertForbidden();
        $this->assertSame('Original', $tag->fresh()->name);
    }

    public function test_writes_keep_directory_filters_separate_from_the_tags_type_and_clamp_pages()
    {
        for ($i = 0; $i < 13; $i++) $this->tag(['name' => sprintf('Mood %02d', $i)]);
        $filters = ['tag_type' => 'mood', 'search' => 'Mood', 'sort' => 'name_asc', 'page' => 2];
        $target = $this->page($filters)->viewData('tags')->first();
        $this->patch(route('admin.tags.update', ['tag' => $target->id] + $filters), ['name' => 'Renamed', 'type' => 'genre'])
            ->assertRedirect(route('admin.tags.index', ['search' => 'Mood', 'sort' => 'name_asc', 'tag_type' => 'mood']));
        $this->assertSame('genre', $target->fresh()->type);
        $this->post(route('admin.tags.store', $filters), ['name' => 'New tag', 'type' => 'period', 'ordering' => 3])
            ->assertRedirect(route('admin.tags.index', ['search' => 'Mood', 'sort' => 'name_asc', 'tag_type' => 'mood']));
        $created = Tag::where('name', 'New tag')->firstOrFail();
        $this->assertSame('period', $created->type);
        $this->assertSame(3, $created->ordering);
        $this->assertSame($this->admin->id, $created->creator_id);
        $this->patch(route('admin.tags.update', $created), ['name' => 'Still a period'])->assertRedirect();
        $this->assertSame('period', $created->fresh()->type);
        $this->delete(route('admin.tags.destroy', ['tag' => $created->id] + $filters))
            ->assertRedirect(route('admin.tags.index', ['search' => 'Mood', 'sort' => 'name_asc', 'tag_type' => 'mood']));
    }

    public function test_invalid_edits_reopen_the_route_bound_tag_with_the_draft_even_off_page()
    {
        $tag = $this->tag(['name' => 'Original']);
        $url = route('admin.tags.index', ['search' => 'absent']);
        $this->withExceptionHandling()->from($url)->patch(route('admin.tags.update', $tag), [
            'name' => 'Unsaved draft', 'type' => 'genre', 'ordering' => 0, 'editing_tag_id' => 999999,
        ])->assertRedirect($url)->assertSessionHasErrors('ordering', null, 'tagForm');
        $response = $this->get($url)->assertOk()->assertSee('autoshow')->assertSee('value="Unsaved draft"', false);
        $this->assertSame($tag->id, $response->viewData('editingTag')->id);
        $response->assertSee('action="'.route('admin.tags.update', ['tag' => $tag->id, 'search' => 'absent']).'"', false);
        $this->assertSame('Original', $tag->fresh()->name);
    }

    public function test_failed_creation_restores_the_single_form_without_an_edit_identity()
    {
        $this->withExceptionHandling()->from(route('admin.tags.index'))->post(route('admin.tags.store'), ['name' => 'Draft', 'type' => 'mood', 'ordering' => -1])
            ->assertSessionHasErrors('ordering', null, 'tagForm');
        $response = $this->page()->assertSee('autoshow')->assertSee('value="Draft"', false);
        $this->assertNull($response->viewData('editingTag'));
        $this->assertSame(0, Tag::count());
    }

    public function test_failed_edits_preserve_cleared_fields_instead_of_restoring_stored_values()
    {
        $tag = $this->tag(['name' => 'Original', 'ordering' => 4]);
        $this->withExceptionHandling()->from(route('admin.tags.index'))->patch(route('admin.tags.update', $tag), [
            'name' => '', 'type' => '', 'ordering' => '',
        ])->assertSessionHasErrors(['name', 'type'], null, 'tagForm');
        $response = $this->page();
        $dom = new \DOMDocument;
        @$dom->loadHTML($response->getContent());
        $xpath = new \DOMXPath($dom);
        foreach (['tag-name', 'tag-ordering'] as $id) {
            $this->assertSame('', $xpath->query('//input[@id="'.$id.'"]')->item(0)->getAttribute('value'));
        }
        $this->assertSame('', $xpath->query('//select[@id="tag-type"]/option[@selected]')->item(0)->getAttribute('value'));
        $this->assertSame('Original', $tag->fresh()->name);
        $this->assertSame(4, $tag->fresh()->ordering);
    }

    public function test_deletion_detaches_only_the_target_and_rolls_back_detach_on_failure()
    {
        $tag = $this->tag();
        $other = $this->tag();
        $piece = Model::withoutEvents(function () { return create(Piece::class); });
        $piece->tags()->attach([$tag->id, $other->id]);
        DB::unprepared('CREATE TRIGGER reject_tag_delete BEFORE DELETE ON tags WHEN OLD.id = '.$tag->id." BEGIN SELECT RAISE(ABORT, 'fixture failure'); END");
        try {
            $this->withExceptionHandling()->deleteJson(route('admin.tags.destroy', $tag))->assertStatus(500);
            $this->assertDatabaseHas('piece_tag', ['tag_id' => $tag->id, 'piece_id' => $piece->id]);
            $this->assertNotNull($tag->fresh());
        } finally { DB::unprepared('DROP TRIGGER reject_tag_delete'); }
        $this->delete(route('admin.tags.destroy', $tag))->assertRedirect();
        $this->assertDatabaseMissing('piece_tag', ['tag_id' => $tag->id]);
        $this->assertDatabaseHas('piece_tag', ['tag_id' => $other->id, 'piece_id' => $piece->id]);
        $this->assertNotNull($piece->fresh());
    }

    public function test_directory_query_cost_stays_constant_as_the_page_size_grows()
    {
        for ($i = 0; $i < 24; $i++) $this->tag();
        $counts = [];
        foreach ([12, 24] as $size) {
            DB::enableQueryLog(); DB::flushQueryLog();
            $data = (new TagsTable)->get(Request::create('/tags', 'GET', ['per_page' => $size]));
            foreach ($data['tags'] as $tag) view('admin.pages.tags.tag', ['tag' => $tag] + $data)->render();
            $queries = DB::getQueryLog(); DB::disableQueryLog();
            $counts[] = count($queries);
            $sql = implode(' ', array_column($queries, 'query'));
            $this->assertStringNotContainsString('select * from "pieces"', $sql);
        }
        $this->assertSame([3, 3], $counts);
    }
}
