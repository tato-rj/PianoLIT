<?php

namespace Tests\Review;

use App\{Admin, Country, Pianist};
use App\Services\Admin\PianistsTable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\{Request, UploadedFile};
use Illuminate\Support\Facades\{DB, Storage};

class AdminPianistsTest extends ReviewTestCase
{
    protected $admin, $country, $otherCountry, $pianists;

    public function setUp(): void
    {
        parent::setUp();
        Storage::fake('public');
        $this->admin = create(Admin::class, ['role' => 'manager']);
        $this->actingAs($this->admin, 'admin');
        Model::withoutEvents(function () {
            $this->country = create(Country::class, ['name' => 'France', 'nationality' => 'French']);
            $this->otherCountry = create(Country::class, ['name' => 'Canada', 'nationality' => 'Canadian']);
            $this->pianists = collect();
            for ($i = 0; $i < 15; $i++) {
                $this->pianists->push(create(Pianist::class, [
                    'name' => sprintf('Pianist %02d', $i), 'slug' => 'pianist-'.$i,
                    'country_id' => $i % 2 ? $this->otherCountry->id : $this->country->id,
                    'creator_id' => $this->admin->id, 'date_of_birth' => (1940 + $i).'-01-01',
                    'date_of_death' => null, 'itunes_id' => $i % 2 ? null : '123456',
                    'updated_at' => now()->subDays($i),
                ]));
            }
        });
    }

    public function test_directory_filters_sorts_paginates_and_keeps_navigation_context()
    {
        $response = $this->get(route('admin.pianists.index'))->assertOk()->assertSee('Find a pianist')->assertSee('Linked')->assertSee('Not linked');
        $page = $response->viewData('pianists');
        $this->assertSame(12, $page->perPage());
        $this->assertSame(15, $page->total());
        $this->assertSame($this->pianists->take(12)->pluck('id')->all(), $page->pluck('id')->all());
        $response->assertDontSee('cdn.datatables.net')->assertDontSee('new DataTable');
        foreach (['name_asc', 'born_asc', 'updated_desc'] as $sort) {
            $this->assertSame($this->pianists->take(12)->pluck('id')->all(), $this->get(route('admin.pianists.index', compact('sort')))->assertOk()->viewData('pianists')->pluck('id')->all());
        }
        foreach (['name_desc', 'born_desc', 'updated_asc'] as $sort) {
            $this->assertSame($this->pianists->reverse()->take(12)->pluck('id')->values()->all(), $this->get(route('admin.pianists.index', compact('sort')))->assertOk()->viewData('pianists')->pluck('id')->all());
        }
        $this->assertSame(7, $this->get(route('admin.pianists.index', ['search' => 'Canadian']))->assertOk()->viewData('pianists')->total());
        $this->assertSame(8, $this->get(route('admin.pianists.index', ['country_id' => $this->country->id]))->assertOk()->viewData('pianists')->total());
        $context = ['search' => 'Pianist', 'sort' => 'name_desc', 'page' => 2];
        $page = $this->get(route('admin.pianists.index', $context))->assertOk()->viewData('pianists');
        $this->assertSame('Pianist 02', $page->first()->name);
        $this->assertStringContainsString('sort=name_desc', $page->url(1));
        $this->get(route('admin.pianists.edit', ['pianist' => $page->first()] + $context))->assertOk()->assertSee(e(route('admin.pianists.index', $context)), false)->assertSee(route('resources.pianists.show', $page->first()), false);
        $this->pianists[0]->updateQuietly(['name' => '100% _unique! <unsafe>']);
        $this->get(route('admin.pianists.index', ['search' => '% _unique!']))->assertOk()->assertSee('&lt;unsafe&gt;', false)->assertDontSee('<unsafe>', false);
        $this->get(route('admin.pianists.index', ['search' => 'absent']))->assertOk()->assertSee('No matching pianists');
        $this->get(route('admin.pianists.index', ['page' => 100]))->assertOk()->assertSee('No pianists on this page');
    }

    public function test_directory_validates_request_shapes_and_bulk_loads_country_rows()
    {
        $this->withExceptionHandling();
        foreach ([['sort' => 'password_asc'], ['search' => ['bad']], ['per_page' => 1000], ['page' => -1], ['country_id' => ['bad']], ['country_id' => 999999]] as $data) {
            $this->getJson(route('admin.pianists.index', $data))->assertUnprocessable();
        }
        $counts = [];
        foreach ([12, 24] as $size) {
            DB::enableQueryLog(); DB::flushQueryLog();
            $data = (new PianistsTable)->get(Request::create('/pianists', 'GET', ['per_page' => $size]));
            foreach ($data['pianists'] as $item) view('admin.pages.pianists.row', ['item' => $item, 'returnFilters' => $data['returnFilters']])->render();
            $counts[] = count(DB::getQueryLog()); DB::disableQueryLog();
        }
        $this->assertSame([3, 3], $counts);
        $this->pianists[0]->updateQuietly(['country_id' => null, 'creator_id' => null, 'date_of_birth' => null]);
        $this->get(route('admin.pianists.index'))->assertOk()->assertSee('Not recorded')->assertSee('Life dates not recorded');
        $this->get(route('admin.pianists.edit', $this->pianists[0]))->assertOk()->assertSee('an administrator');
    }

    protected function payload()
    {
        return ['name' => 'A new pianist', 'biography' => 'A short biography.', 'country_id' => $this->country->id,
            'itunes_id' => '987654321', 'date_of_birth' => '1985-04-23', 'date_of_death' => null];
    }

    public function test_create_and_edit_save_the_artist_id_dates_and_portrait_without_changing_model_json()
    {
        $original = $this->pianists[0]->fresh()->toArray();
        $response = $this->post(route('admin.pianists.store'), $this->payload() + ['cover' => UploadedFile::fake()->image('portrait.jpg')]);
        $created = Pianist::where('name', 'A new pianist')->firstOrFail();
        $response->assertRedirect(route('admin.pianists.edit', $created));
        $this->assertSame('987654321', $created->itunes_id);
        $this->assertSame('1985-04-23', $created->date_of_birth->format('Y-m-d'));
        $this->assertNull($created->date_of_death);
        $this->assertSame($this->admin->id, $created->creator_id);
        Storage::disk('public')->assertExists($created->cover_path);
        $context = ['country_id' => $this->otherCountry->id, 'search' => 'Pianist', 'sort' => 'name_desc'];
        $updated = array_merge($this->payload(), ['name' => 'A renamed pianist', 'date_of_birth' => '04/23/1985']);
        $cover = $created->cover_path;
        $this->patch(route('admin.pianists.update', ['pianist' => $created] + $context), $updated)
            ->assertRedirect(route('admin.pianists.edit', ['pianist' => 'a-renamed-pianist'] + $context));
        $this->assertSame($cover, $created->fresh()->cover_path);
        $this->assertSame($this->country->id, $created->fresh()->country_id);
        $this->assertSame(array_keys($original), array_keys($created->fresh()->toArray()));
        $this->assertSame($original, $this->pianists[0]->fresh()->toArray());
    }

    public function test_validation_restores_the_add_dialog_and_rejects_invalid_updates_without_mutation()
    {
        $this->withExceptionHandling();
        $this->from(route('admin.pianists.index'))->post(route('admin.pianists.store'), ['name' => 'Restored pianist'])
            ->assertRedirect()->assertSessionHasErrors(['cover', 'biography', 'date_of_birth'], null, 'pianistCreate');
        $this->get(route('admin.pianists.index'))->assertOk()->assertSee('autoshow')->assertSee('value="Restored pianist"', false)->assertSee('name="itunes_id"', false)->assertDontSee('iteuns_id');
        $this->flushSession();
        $target = $this->pianists[0];
        foreach ([['date_of_birth' => 'invalid'], ['date_of_death' => '1900-01-01'], ['country_id' => 999999], ['name' => $this->pianists[1]->name], ['itunes_id' => ['bad']]] as $invalid) {
            $this->patchJson(route('admin.pianists.update', $target), array_merge($this->payload(), $invalid))->assertUnprocessable();
            $this->assertSame('Pianist 00', $target->fresh()->name);
        }
    }

    public function test_creator_permissions_admin_sessions_and_delete_return_page_are_preserved()
    {
        $target = $this->pianists[0];
        $this->actingAs(create(Admin::class, ['role' => 'unauthorized']), 'admin');
        $this->withExceptionHandling();
        $this->get(route('admin.pianists.edit', $target))->assertOk()->assertSee('Only its creator or a manager')->assertDontSee('Save changes</button>', false);
        $this->patchJson(route('admin.pianists.update', $target), $this->payload())->assertForbidden();
        $this->deleteJson(route('admin.pianists.destroy', $target))->assertForbidden();
        $this->actingAs($this->admin->fresh()->forceFill(['role' => 'editor']), 'admin');
        $this->patch(route('admin.pianists.update', $target), array_merge($this->payload(), ['name' => $target->name]))->assertRedirect();
        $this->actingAs($this->admin->fresh()->forceFill(['role' => 'manager']), 'admin');
        $this->pianists->take(2)->each->delete();
        $this->delete(route('admin.pianists.destroy', ['pianist' => $this->pianists->last(), 'page' => 2]))->assertRedirect(route('admin.pianists.index'));
        auth('admin')->logout();
        $this->get(route('admin.pianists.index'))->assertRedirect(route('admin.login.show'));
        $this->post(route('admin.pianists.store'), [])->assertRedirect(route('admin.login.show'));
    }

    public function test_render_preview_when_requested()
    {
        if (!$directory = getenv('ADMIN_PIANISTS_PREVIEW_DIR')) { $this->assertTrue(true); return; }
        $names = ['Alexandre Moreau', 'Amelia Laurent', 'Benjamin Clarke', 'Clara Beaumont', 'Daniel Tremblay', 'Elise Bernard', 'Gabriel Martin', 'Isabelle Roy', 'Julien Dubois', 'Lucie Fontaine', 'Nathan Wilson', 'Olivia Mercier', 'Raphael Blanc', 'Sophie Girard', 'Theo Pelletier'];
        foreach ($this->pianists as $i => $pianist) $pianist->updateQuietly(['name' => $names[$i], 'biography' => 'A pianist and educator known for thoughtful interpretations and a wide-ranging repertoire.', 'cover_path' => 'fixture-portrait.jpg']);
        $this->pianists->last()->updateQuietly(['itunes_id' => null]);
        foreach (['index' => route('admin.pianists.index'), 'empty' => route('admin.pianists.index', ['search' => 'absent']), 'edit' => route('admin.pianists.edit', $this->pianists->last())] as $name => $url) {
            $html = $this->get($url)->assertOk()->getContent();
            $html = preg_replace('~https?://(?:admin\.)?localhost(?=/(?:css|js|images|fonts|storage)/)~', '/assets', $html);
            file_put_contents($directory.'/'.$name.'.html', $html);
        }
        $this->withExceptionHandling()->from(route('admin.pianists.index'))->post(route('admin.pianists.store'), ['name' => 'Restored pianist'])->assertRedirect();
        $html = $this->get(route('admin.pianists.index'))->assertOk()->getContent();
        $html = preg_replace('~https?://(?:admin\.)?localhost(?=/(?:css|js|images|fonts|storage)/)~', '/assets', $html);
        file_put_contents($directory.'/invalid.html', $html);
    }
}
