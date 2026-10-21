<?php

namespace Tests\Review;

use App\{Admin, Composer, Country, Piece, Tag};
use App\Services\Admin\ComposersTable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\{Request, UploadedFile};
use Illuminate\Support\Facades\{DB, Storage};

class AdminComposersTest extends ReviewTestCase
{
    protected $composers, $manager;

    public function setUp(): void
    {
        parent::setUp();
        Storage::fake('public');
        $this->manager = create(Admin::class, ['role' => 'manager', 'name' => 'Alex Example']);
        $this->actingAs($this->manager, 'admin');
        Model::withoutEvents(function () {
            $country = create(Country::class, ['name' => 'Germany', 'nationality' => 'German']);
            create(Tag::class, ['type' => 'period', 'name' => 'romantic']);
            create(Tag::class, ['type' => 'period', 'name' => 'baroque']);
            $this->composers = collect();
            for ($i = 0; $i < 15; $i++) {
                $composer = create(Composer::class, ['name' => sprintf('Composer %02d', $i), 'country_id' => $i === 14 ? null : $country->id,
                    'cover_path' => '', 'period' => $i % 2 ? 'romantic' : 'baroque', 'is_famous' => $i < 5,
                    'is_pedagogical' => $i >= 10, 'creator_id' => $this->manager->id, 'gender' => 'female',
                    'date_of_birth' => (1800 + $i).'-01-01', 'date_of_death' => null, 'updated_at' => '2026-10-01']);
                $this->composers->push($composer);
                for ($j = 0; $j < $i; $j++) create(Piece::class, ['composer_id' => $composer->id, 'name' => sprintf('Study %02d', $j)]);
            }
        });
    }

    public function test_directory_filters_sorts_pagination_and_literal_search()
    {
        $response = $this->get(route('admin.composers.index'))->assertOk()->assertSee('Find a composer')->assertSee('Pedagogical')
            ->assertDontSee('cdn.datatables.net')->assertSee('class="admin-table__table ', false);
        $this->savePreview('index', $response);
        $items = $response->viewData('composers');
        $this->assertSame(12, $items->perPage());
        $this->assertSame(15, $items->total());
        $this->assertSame($this->composers->take(12)->pluck('id')->all(), $items->pluck('id')->all());
        $this->assertSame(11, $items->last()->pieces_count);
        foreach (['famous' => ['yes' => 5, 'no' => 10], 'focus' => ['pedagogical' => 5, 'other' => 10], 'period' => ['romantic' => 7, 'baroque' => 8]] as $key => $values) {
            foreach ($values as $value => $total) $this->assertSame($total, $this->get(route('admin.composers.index', [$key => $value]))->assertOk()->viewData('composers')->total());
        }
        $page = $this->get(route('admin.composers.index', ['sort' => 'pieces_desc', 'page' => 2, 'famous' => 'no']))->assertOk()->viewData('composers');
        $this->assertStringContainsString('famous=no', $page->url(1));
        foreach (ComposersTable::SORTS as $sort => $label) {
            $items = $this->get(route('admin.composers.index', compact('sort')))->assertOk()->viewData('composers');
            $this->assertSame(str_ends_with($sort, 'desc') && $sort !== 'updated_desc' ? $this->composers->last()->id : $this->composers->first()->id, $items->first()->id, $sort);
        }
        $this->assertSame(14, $this->get(route('admin.composers.index', ['search' => 'Composer German']))->assertOk()->viewData('composers')->total());
        $this->composers[0]->updateQuietly(['name' => '100% _unique! <script>']);
        $this->get(route('admin.composers.index', ['search' => '% _unique!']))->assertOk()->assertSee('&lt;script&gt;', false)->assertDontSee('100% _unique! <script>', false)
            ->assertViewHas('composers', function ($items) { return $items->total() === 1; });
        $this->get(route('admin.composers.index', ['search' => 'absent']))->assertOk()->assertSee('No matching composers');
        $this->get(route('admin.composers.index', ['page' => 100]))->assertOk()->assertSee('No composers on this page');
    }

    public function test_query_cost_stays_bounded_and_validation_rejects_untrusted_shapes()
    {
        $counts = [];
        foreach ([12, 24] as $perPage) {
            DB::enableQueryLog(); DB::flushQueryLog();
            $data = (new ComposersTable)->get(Request::create('/composers', 'GET', ['per_page' => $perPage]));
            view('admin.pages.composers.table.rows', $data)->render();
            $counts[] = count(DB::getQueryLog()); DB::disableQueryLog();
        }
        $this->assertSame([4, 4], $counts);
        $this->withExceptionHandling();
        $this->getJson(route('admin.composers.edit', ['composer' => $this->composers[0], 'pieces_page' => ['bad']]))->assertUnprocessable();
        foreach ([['search' => ['bad']], ['sort' => 'password_desc'], ['per_page' => 1000], ['page' => -1], ['period' => ['bad']], ['famous' => 'bad'], ['focus' => 'bad']] as $data) {
            $this->getJson(route('admin.composers.index', $data))->assertUnprocessable();
        }
    }

    public function test_editor_keeps_directory_context_paginated_repertoire_and_piece_destinations()
    {
        $composer = $this->composers->last();
        $filters = ['search' => 'Composer', 'sort' => 'pieces_desc', 'focus' => 'pedagogical', 'page' => 2];
        $response = $this->get(route('admin.composers.edit', ['composer' => $composer] + $filters))->assertOk()
            ->assertSee('Biography')->assertSee('Repertoire')->assertSee('Save changes')->assertSee('admin-editor-section', false)
            ->assertSee(e(route('admin.composers.index', $filters)), false);
        $this->savePreview('edit', $response);
        $pieces = $response->viewData('pieces');
        $this->assertSame(14, $pieces->total());
        $this->assertSame(12, $pieces->perPage());
        $this->assertStringContainsString('pieces_page=2', $pieces->url(2));
        $this->assertStringContainsString('focus=pedagogical', $pieces->url(2));
        $this->assertStringEndsWith('#composer-repertoire', $pieces->url(2));
        $response->assertSee('href="'.route('webapp.pieces.show', $pieces->first()).'" class="admin-table__primary" target="_blank" rel="noopener noreferrer"', false);
        $this->assertFalse($composer->relationLoaded('pieces'));
        $this->actingAs(create(Admin::class, ['role' => 'editor']), 'admin');
        $this->get(route('admin.composers.edit', $composer))->assertOk()->assertSee('You can view this profile')->assertDontSee('Save changes')->assertSee(' disabled ', false);
        $this->withExceptionHandling()->patchJson(route('admin.composers.update', $composer), ['name' => 'forged'])->assertForbidden();
        $this->deleteJson(route('admin.composers.destroy', $composer))->assertForbidden();
    }

    public function test_famous_form_is_idempotent_preserves_filters_and_retains_legacy_json()
    {
        $composer = $this->composers[4];
        $filters = ['famous' => 'yes', 'sort' => 'pieces_desc', 'page' => 2];
        $url = route('admin.composers.toggle-famous', ['composer' => $composer, 'return_to' => 'directory'] + $filters);
        for ($i = 0; $i < 2; $i++) $this->patch($url, ['is_famous' => 0])->assertRedirect(route('admin.composers.index', ['sort' => 'pieces_desc', 'famous' => 'yes']));
        $this->assertFalse($composer->fresh()->is_famous);
        $this->patchJson(route('admin.composers.toggle-famous', $composer))->assertOk()->assertExactJson(['status' => 'The composer has been updated.']);
        $this->assertTrue($composer->fresh()->is_famous);
        $this->patch(route('admin.composers.toggle-famous', $composer))->assertOk()->assertExactJson(['status' => 'The composer has been updated.']);
        $this->assertFalse($composer->fresh()->is_famous);
        $this->withExceptionHandling()->patchJson($url, ['is_famous' => ['bad']])->assertUnprocessable();
        $this->assertFalse($composer->fresh()->is_famous);
    }

    public function test_creation_recovers_named_validation_and_opens_the_existing_editor()
    {
        $this->withExceptionHandling();
        $this->from(route('admin.composers.index'))->post(route('admin.composers.store'), ['name' => 'Restored composer', 'gender' => 'female'])
            ->assertRedirect()->assertSessionHasErrors(['biography', 'cover_image'], null, 'composerCreate');
        $this->get(route('admin.composers.index'))->assertOk()->assertSee('autoshow')->assertSee('value="Restored composer"', false);
        $country = Country::first();
        $this->post(route('admin.composers.store'), ['name' => 'New composer', 'gender' => 'female', 'biography' => 'A short biography.',
            'period' => 'romantic', 'country_id' => $country->id, 'date_of_birth' => '01/01/1850', 'cover_image' => UploadedFile::fake()->image('portrait.jpg')])
            ->assertRedirect(route('admin.composers.edit', Composer::where('name', 'New composer')->firstOrFail()));
        $composer = Composer::where('name', 'New composer')->firstOrFail();
        $this->assertSame($this->manager->id, $composer->creator_id);
        Storage::disk('public')->assertExists($composer->cover_path);
    }

    public function test_deletion_clamps_the_directory_and_routes_require_admin_authentication()
    {
        $target = Model::withoutEvents(function () { return create(Composer::class, ['name' => 'Last on page', 'creator_id' => $this->manager->id, 'cover_path' => '']); });
        $this->withExceptionHandling()->deleteJson(route('admin.composers.destroy', ['composer' => $target, 'sort' => 'invalid']))->assertUnprocessable();
        $this->assertDatabaseHas('composers', ['id' => $target->id]);
        $this->delete(route('admin.composers.destroy', ['composer' => $target, 'search' => 'Last on page', 'page' => 2]))
            ->assertRedirect(route('admin.composers.index', ['search' => 'Last on page']));
        $this->assertDatabaseMissing('composers', ['id' => $target->id]);
        auth('admin')->logout();
        $this->get(route('admin.composers.index'))->assertRedirect(route('admin.login.show'));
        $this->get(route('admin.composers.edit', $this->composers[0]))->assertRedirect(route('admin.login.show'));
        $this->patchJson(route('admin.composers.toggle-famous', $this->composers[0]))->assertUnauthorized();
    }

    public function test_profile_save_preserves_portrait_and_uses_existing_field_contracts()
    {
        $composer = $this->composers[0];
        $cover = $composer->cover_path;
        $url = route('admin.composers.edit', ['composer' => $composer, 'search' => 'Composer']);
        $this->from($url)->patch(route('admin.composers.update', $composer), [
            'name' => 'Updated composer', 'biography' => 'Updated biography.', 'curiosity' => 'An interesting fact.',
            'gender' => 'female', 'ethnicity' => 'white', 'country_id' => Country::first()->id,
            'period' => 'romantic', 'mood' => 'gentle', 'date_of_birth' => '02/03/1800', 'date_of_death' => '',
            'is_famous' => 0, 'is_pedagogical' => 1,
        ])->assertRedirect($url);
        $composer->refresh();
        $this->assertSame($cover, $composer->cover_path);
        $this->assertSame('Updated composer', $composer->name);
        $this->assertSame('1800-02-03', $composer->date_of_birth->format('Y-m-d'));
        $this->assertNull($composer->date_of_death);
        $this->assertFalse($composer->is_famous);
        $this->assertTrue($composer->is_pedagogical);
        $this->assertSame('gentle', $composer->mood);
    }

    protected function savePreview($name, $response)
    {
        if ($directory = getenv('ADMIN_COMPOSERS_PREVIEW_DIR')) {
            $html = preg_replace('~https?://(?:admin\\.)?localhost(?=/(?:css|js|images|fonts|storage)/)~', '/assets', $response->getContent());
            $html = str_replace(['href="/css/', 'src="/js/'], ['href="/assets/css/', 'src="/assets/js/'], $html);
            file_put_contents($directory.'/'.$name.'.html', $html);
        }
    }
}
