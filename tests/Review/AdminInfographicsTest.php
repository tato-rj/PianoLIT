<?php

namespace Tests\Review;

use App\Admin;
use App\Infograph\{Infograph, Topic};
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\{Http, Storage};

class AdminInfographicsTest extends ReviewTestCase
{
    public function setUp(): void
    {
        parent::setUp();
        $this->actingAs(create(Admin::class, ['role' => 'manager']), 'admin');
        Storage::fake('public');
    }

    private function infographic($name = 'Circle of fifths', array $attributes = [])
    {
        return Model::withoutEvents(fn () => create(Infograph::class, $attributes + [
            'creator_id' => auth('admin')->id(), 'name' => $name, 'slug' => str_slug($name),
            'description' => 'A visual reference for piano practice.', 'cover_path' => 'infograph/reference.jpg',
        ]));
    }

    private function topic($name = 'Theory')
    {
        return Topic::create(['name' => $name, 'slug' => str_slug($name), 'creator_id' => auth('admin')->id()]);
    }

    private function xpath($response)
    {
        $dom = new \DOMDocument; @$dom->loadHTML($response->getContent());
        return new \DOMXPath($dom);
    }

    public function test_index_uses_shared_table_native_pagination_and_compact_topic_forms()
    {
        $topic = $this->topic('Theory <script>');
        for ($i = 1; $i <= 13; $i++) $this->infographic('Piano guide '.$i)->topics()->attach($topic);
        $response = $this->get(route('admin.infographs.index'))->assertOk()->assertSee('Manage topics')->assertSee('Create infographic')
            ->assertSee('Theory &lt;script&gt;', false)->assertDontSee('cdn.datatables.net')->assertDontSee('new DataTable');
        $xpath = $this->xpath($response);
        $this->assertCount(12, $xpath->query('//table[@id="infographs-table"]/tbody/tr'));
        $this->assertCount(1, $xpath->query('//nav[@aria-label="Infographics pagination"]'));
        $this->assertCount(0, $xpath->query('//form//form'));
        $this->assertCount(0, $xpath->query('//form[@method="POST" and not(.//input[@name="_token"])]'));
        $this->assertCount(1, $xpath->query('//*[@id="infographic-topic-'.$topic->id.'"]'));
        $this->assertCount(1, $xpath->query('//a[@href="'.route('admin.infographs.create').'" and contains(.,"Create infographic")]'));
        $this->assertCount(0, $xpath->query('//*[@id="infographic-create-modal"]'));
        $this->assertCount(0, $xpath->query('//a[@href="'.route('admin.infographs.topics.index').'"]'));
        $this->assertCount(1, $this->xpath($this->get(route('admin.infographs.index', ['page' => 2])))->query('//table[@id="infographs-table"]/tbody/tr'));
        Http::assertNothingSent();
        if ($directory = getenv('ADMIN_INFOGRAPHICS_PREVIEW_DIR')) {
            $topic->update(['name' => 'Theory', 'slug' => 'theory']);
            $this->topic('Practice'); $this->topic('Reference');
            $guide = Infograph::firstOrFail();
            $guide->update(['name' => 'Circle of fifths', 'downloads' => 1250, 'published_at' => now()]);
            foreach (['index' => route('admin.infographs.index'), 'topics' => route('admin.infographs.index', ['topics' => '1']),
                'create' => route('admin.infographs.create'), 'edit' => route('admin.infographs.edit', $guide)] as $name => $url) {
                $html = $this->get($url)->assertOk()->getContent();
                $html = preg_replace('#https?://[^/" ]+/storage/infograph/[^" ]+#', '/poster.svg', $html);
                $html = str_replace(['http://admin.localhost', 'http://localhost'], 'http://127.0.0.1:8878', $html);
                file_put_contents($directory.'/'.$name.'.html', $html);
            }
        }
    }

    public function test_search_filters_sorting_and_literal_wildcards_run_across_the_collection()
    {
        $topic = $this->topic();
        for ($i = 1; $i <= 14; $i++) {
            $this->infographic('Guide '.$i, ['downloads' => $i, 'published_at' => now(), 'giftable_at' => now()])->topics()->attach($topic);
        }
        $this->infographic('Draft guide');
        $filters = ['search' => 'theory', 'status' => 'published', 'topic_id' => $topic->id, 'gift' => 'yes', 'sort' => 'downloads_desc'];
        $response = $this->get(route('admin.infographs.index', $filters))->assertOk()->assertDontSee('Draft guide');
        $this->assertSame('Guide 14', trim($this->xpath($response)->query('//table/tbody/tr[1]//a[contains(@class,"admin-table__primary")]')->item(0)->textContent));
        $this->get(route('admin.infographs.index', $filters + ['page' => 2]))->assertOk()->assertSee('Guide 1')->assertDontSee('Guide 14');
        $this->infographic('100% guide');
        $this->get(route('admin.infographs.index', ['search' => '%']))->assertOk()->assertSee('100% guide')->assertDontSee('Draft guide');
        $this->withExceptionHandling();
        foreach (['sort' => 'name;drop', 'per_page' => 1000, 'search' => ['bad'], 'topics' => ['1'], 'topic_id' => 9999, 'gift' => 'invalid'] as $key => $value) {
            $this->getJson(route('admin.infographs.index', [$key => $value]))->assertUnprocessable();
        }
    }

    public function test_create_page_and_edit_forms_retain_directory_state_and_use_one_save_footer()
    {
        $topic = $this->topic();
        $guide = $this->infographic('Theory <guide>'); $guide->topics()->attach($topic);
        $filters = ['search' => 'guide', 'sort' => 'name_asc', 'page' => 2];
        $this->get(route('admin.infographs.index', $filters + ['create' => '1']))
            ->assertRedirect(route('admin.infographs.create', $filters));
        foreach (['create' => route('admin.infographs.create', $filters), 'edit' => route('admin.infographs.edit', ['infograph' => $guide] + $filters)] as $action => $url) {
            $response = $this->get($url)->assertOk(); $xpath = $this->xpath($response);
            $form = $xpath->query('//form[@enctype="multipart/form-data"]')->item(0);
            $this->assertNotNull($form);
            $this->assertSame(route('admin.infographs.'.($action === 'create' ? 'store' : 'update'), ($action === 'create' ? [] : ['infograph' => $guide]) + $filters), $form->getAttribute('action'));
            $this->assertSame(route('admin.infographs.index', $filters), $xpath->query('//nav[@aria-label="Breadcrumb"]//a')->item(1)->getAttribute('href'));
            $this->assertCount(1, $xpath->query('//form//fieldset//section[@aria-labelledby="infographic-details-title"]'));
            $this->assertCount(1, $xpath->query('//form//fieldset//section[@aria-labelledby="infographic-artwork-title"]'));
            $this->assertCount(1, $xpath->query('//form//fieldset//section[@aria-labelledby="infographic-topics-title"]'));
            $this->assertCount(1, $xpath->query('//form/*[@data-table-save-actions]//button[@type="submit"]'));
            $this->assertCount(0, $xpath->query('//form//form'));
            $this->assertSame($action === 'create', $xpath->query('//input[@id="infographic-image"]')->item(0)->hasAttribute('required'));
            $this->assertSame($action === 'edit', $xpath->query('//input[@id="infographic-topic-'.$topic->id.'"]')->item(0)->hasAttribute('checked'));
        }
    }

    public function test_topics_create_rename_delete_keep_assignments_and_return_to_the_modal()
    {
        $topic = $this->topic();
        $infograph = $this->infographic(); $infograph->topics()->attach($topic);
        $filters = ['search' => 'piano', 'status' => 'draft', 'sort' => 'name_asc', 'page' => 2];
        $url = route('admin.infographs.index', ['search' => 'piano', 'sort' => 'name_asc', 'status' => 'draft', 'page' => 2, 'topics' => '1']);
        $this->get(route('admin.infographs.topics.index', $filters))->assertRedirect($url);
        $this->post(route('admin.infographs.topics.store', $filters), ['name' => '  Practice  '])->assertRedirect($url)->assertSessionHas('status', 'Topic created.');
        $this->assertSame(auth('admin')->id(), Topic::where('name', 'Practice')->firstOrFail()->creator_id);
        $this->patch(route('admin.infographs.topics.update', ['topic' => $topic] + $filters), ['name' => 'Harmony'])->assertRedirect($url);
        $this->assertSame('harmony', $topic->fresh()->slug);
        $this->assertSame([$topic->id], $infograph->topics()->pluck('infograph_topics.id')->all());
        $this->patch(route('admin.infographs.topics.update', ['topic' => $topic->fresh(), 'topic_id' => $topic->id]), ['name' => 'Harmony'])
            ->assertRedirect(route('admin.infographs.index', ['topic_id' => $topic->id, 'topics' => '1']));
        $this->delete(route('admin.infographs.topics.destroy', ['topic' => $topic->fresh()] + $filters + ['topic_id' => $topic->id]))->assertRedirect($url);
        $this->assertNull($topic->fresh());
        $this->assertNotNull($infograph->fresh());
        $this->assertSame(0, $infograph->topics()->count());
    }

    public function test_topic_validation_reopens_the_modal_and_marks_only_the_failed_form()
    {
        $this->topic(); $other = $this->topic('Practice');
        $this->withExceptionHandling();
        $this->patch(route('admin.infographs.topics.update', $other), ['name' => 'Theory'])
            ->assertRedirect(route('admin.infographs.index', ['topics' => '1']))->assertSessionHasErrors('name', null, 'infographicTopic'.$other->id);
        $response = $this->get(route('admin.infographs.index', ['topics' => '1']))->assertOk();
        $invalid = $this->xpath($response)->query('//input[contains(@class,"is-invalid")]');
        $this->assertCount(1, $invalid);
        $this->assertSame('infographic-topic-'.$other->id, $invalid->item(0)->getAttribute('id'));
        $create = $this->get(route('admin.infographs.create'))->assertOk();
        $this->assertSame('', $this->xpath($create)->query('//input[@id="infographic-name"]')->item(0)->getAttribute('value'));
        foreach (['', ['bad'], str_repeat('x', 256), '!!!', 'Theory', 'Theory!'] as $name) {
            $this->postJson(route('admin.infographs.topics.store'), compact('name'))->assertUnprocessable()->assertJsonValidationErrors('name');
        }
        $this->assertSame('Practice', $other->fresh()->name);
    }

    public function test_upload_create_and_edit_use_original_images_and_server_dimensions()
    {
        $topic = $this->topic();
        $this->post(route('admin.infographs.store'), [
            'name' => 'New infographic', 'description' => 'A useful piano reference.', 'topics' => [$topic->id],
            'cover_image' => UploadedFile::fake()->image('guide.jpg', 700, 1000), 'width' => 1, 'height' => 1,
        ])->assertRedirect(route('admin.infographs.index'));
        $infograph = Infograph::firstOrFail();
        $this->assertSame([700, 1000], [(int) $infograph->width, (int) $infograph->height]);
        Storage::disk('public')->assertExists([$infograph->cover_path, $infograph->thumbnail_path]);
        $path = $infograph->cover_path;
        $this->patch(route('admin.infographs.update', $infograph), ['name' => 'Renamed guide', 'description' => 'Updated description.'])
            ->assertRedirect(route('admin.infographs.edit', 'renamed-guide'));
        $infograph->refresh();
        $this->assertSame($path, $infograph->cover_path);
        $this->assertSame(700, (int) $infograph->width);
        $this->assertSame(0, $infograph->topics()->count());
        $this->get(route('admin.infographs.edit', $infograph))->assertOk()->assertSee('Replace image')->assertSee('Save changes');
        $this->patch(route('admin.infographs.update', $infograph), ['name' => 'Renamed guide', 'description' => 'Updated description.', 'cover_image' => UploadedFile::fake()->image('replacement.jpg', 900, 1200)]);
        $this->assertSame([900, 1200], [(int) $infograph->fresh()->width, (int) $infograph->fresh()->height]);
    }

    public function test_failed_upload_returns_to_create_with_scoped_fields_and_validates_topic_ids()
    {
        $this->withExceptionHandling();
        $this->post(route('admin.infographs.store', ['search' => 'guide']), ['name' => 'Unfinished guide', 'description' => 'A useful reference.', 'topics' => [99999]])
            ->assertRedirect(route('admin.infographs.create', ['search' => 'guide']))->assertSessionHasErrors(['cover_image', 'topics.0'], null, 'infographic');
        $response = $this->get(route('admin.infographs.create'))->assertOk()->assertSee('Unfinished guide');
        $this->assertCount(1, $this->xpath($response)->query('//span[contains(@class,"invalid-feedback") and contains(.,"topics.0")]'));
        $this->assertCount(0, $this->xpath($response)->query('//*[@id="infographic-topics-modal"]'));
        $this->assertSame(0, Infograph::count());
    }

    public function test_failed_edit_retains_submitted_values_and_an_explicitly_empty_topic_selection()
    {
        $topic = $this->topic(); $guide = $this->infographic(); $guide->topics()->attach($topic);
        $this->withExceptionHandling();
        $filters = ['search' => 'guide', 'sort' => 'name_asc', 'page' => 2];
        $this->patch(route('admin.infographs.update', ['infograph' => $guide] + $filters), ['name' => 'Changed guide', 'description' => 'x'])
            ->assertRedirect(route('admin.infographs.edit', ['infograph' => $guide] + $filters))->assertSessionHasErrors('description', null, 'infographic');
        $response = $this->get(route('admin.infographs.edit', ['infograph' => $guide] + $filters))->assertOk();
        $xpath = $this->xpath($response);
        $this->assertSame('Changed guide', $xpath->query('//input[@id="infographic-name"]')->item(0)->getAttribute('value'));
        $this->assertSame('x', $xpath->query('//textarea[@id="infographic-description"]')->item(0)->textContent);
        $this->assertCount(0, $xpath->query('//input[@name="topics[]" and @checked]'));
        $this->assertSame('Circle of fifths', $guide->fresh()->name);
        $this->assertSame(1, $guide->topics()->count());
    }

    public function test_status_forms_are_idempotent_and_clamp_pages_after_filter_changes()
    {
        $guide = $this->infographic('Published guide', ['published_at' => now()->subDays(2)]);
        $date = $guide->published_at;
        $url = route('admin.infographs.update-status', ['infograph' => $guide, 'return_to' => 'directory', 'status' => 'published', 'page' => 2]);
        $this->patch($url, ['attribute' => 'published_at', 'enabled' => 1])->assertRedirect(route('admin.infographs.index', ['status' => 'published']));
        $this->assertTrue($date->equalTo($guide->fresh()->published_at));
        $this->patch($url, ['attribute' => 'published_at', 'enabled' => 0])->assertRedirect(route('admin.infographs.index', ['status' => 'published']));
        $this->assertNull($guide->fresh()->published_at);
        $this->patch($url, ['attribute' => 'giftable_at', 'enabled' => 1]);
        $this->assertNotNull($guide->fresh()->giftable_at);
        $this->withExceptionHandling();
        $this->patchJson($url, ['attribute' => 'name', 'enabled' => 1])->assertUnprocessable();
        $this->assertSame('Published guide', $guide->fresh()->name);
        $this->delete(route('admin.infographs.destroy', ['infograph' => $guide, 'page' => 2]))->assertRedirect(route('admin.infographs.index'));
        $this->assertNull($guide->fresh());
    }

    public function test_topic_delete_rolls_back_assignments_when_the_operation_fails()
    {
        $topic = $this->topic(); $guide = $this->infographic(); $guide->topics()->attach($topic);
        Topic::deleted(function () { throw new \RuntimeException('Simulated failure'); });
        try {
            $this->delete(route('admin.infographs.topics.destroy', $topic));
            $this->fail('Expected a failed delete');
        } catch (\RuntimeException $exception) {
            $this->assertSame('Simulated failure', $exception->getMessage());
        }
        $this->assertNotNull($topic->fresh());
        $this->assertSame(1, $guide->topics()->count());
    }

    public function test_edit_rolls_back_details_and_assignments_when_topic_sync_fails()
    {
        $topic = $this->topic(); $replacement = $this->topic('Practice');
        $guide = $this->infographic(); $guide->topics()->attach($topic);
        \DB::listen(function ($query) {
            if (strpos($query->sql, 'insert into "infograph_infograph_topic"') === 0) throw new \RuntimeException('Simulated sync failure');
        });
        try {
            $this->patch(route('admin.infographs.update', $guide), ['name' => 'Changed name', 'description' => 'Changed description.', 'topics' => [$replacement->id]]);
            $this->fail('Expected a failed update');
        } catch (\RuntimeException $exception) {
            $this->assertSame('Simulated sync failure', $exception->getMessage());
        }
        $this->assertSame('Circle of fifths', $guide->fresh()->name);
        $this->assertSame([$topic->id], $guide->topics()->pluck('infograph_topics.id')->all());
    }

    public function test_empty_state_and_admin_authentication_are_preserved()
    {
        $this->get(route('admin.infographs.index'))->assertOk()->assertSee('No infographics yet')->assertSee('No topics yet.');
        auth('admin')->logout(); $this->withExceptionHandling();
        $this->get(route('admin.infographs.index'))->assertRedirect(route('admin.login.show'));
        $this->get(route('admin.infographs.create'))->assertRedirect(route('admin.login.show'));
        $this->post(route('admin.infographs.topics.store'), ['name' => 'Private'])->assertRedirect(route('admin.login.show'));
        $this->assertSame(0, Topic::count());
    }
}
