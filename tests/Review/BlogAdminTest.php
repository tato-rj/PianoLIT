<?php

namespace Tests\Review;

use App\Admin;
use App\Blog\{Post, Topic};
use App\Services\Admin\{BlogAudio, PostsTable};
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\{DB, Http, Mail, Storage};

class BlogAdminTest extends ReviewTestCase
{
    public function setUp(): void
    {
        parent::setUp();
        Storage::fake('public');
        $this->actingAs(create(Admin::class, ['role' => 'manager']), 'admin');
    }

    private function postRecord(array $data = [])
    {
        return Model::withoutEvents(fn () => create(Post::class, $data + [
            'title' => 'Piano practice', 'content' => '<p>Saved article.</p>', 'creator_id' => auth('admin')->id(), 'cover_path' => null,
        ]));
    }

    private function topic(string $name = 'Technique')
    {
        return Topic::create(['name' => $name, 'slug' => str_slug($name), 'creator_id' => auth('admin')->id()]);
    }

    private function wav(string $name = 'sample.wav', int $samples = 100): UploadedFile
    {
        $pcm = str_repeat("\0", $samples * 2);
        $bytes = 'RIFF'.pack('V', 36 + strlen($pcm)).'WAVEfmt '.pack('VvvVVvv', 16, 1, 1, 8000, 16000, 2, 16).'data'.pack('V', strlen($pcm)).$pcm;
        return UploadedFile::fake()->createWithContent($name, $bytes);
    }

    private function xpath($response): \DOMXPath
    {
        $dom = new \DOMDocument; @$dom->loadHTML($response->getContent());
        return new \DOMXPath($dom);
    }

    public function test_directory_uses_shared_table_modals_and_native_pagination()
    {
        $topic = $this->topic();
        for ($i = 0; $i < 13; $i++) {
            $post = $this->postRecord(['title' => 'Practice '.$i, 'created_at' => now()->subMinutes($i)]);
            $post->topics()->attach($topic);
        }
        $response = $this->get(route('admin.posts.index'))->assertOk()->assertSee('Showing 1–12 of 13')
            ->assertSee('Manage audio')->assertSee('Manage topics')->assertDontSee('datatables.min')->assertDontSee('Gift path here');
        $xpath = $this->xpath($response);
        $this->assertCount(12, $xpath->query('//table[@id="posts-table"]/tbody/tr'));
        $this->assertCount(0, $xpath->query('//form//form'));
        $this->assertCount(0, $xpath->query('//form[@method="POST" and not(.//input[@name="_token"])]'));
        $this->assertCount(0, $xpath->query('//*[@id="blog"]//a[contains(@href,"gifts") or contains(@href,"audio") or contains(@href,"topics")]'));
        $this->assertCount(0, $xpath->query('//table[@id="posts-table"]//a[contains(@href,"blog.localhost")]'));
        $this->assertCount(12, $xpath->query('//table[@id="posts-table"]//button[@disabled and @aria-disabled="true"]'));
        $this->get(route('admin.posts.index', ['page' => 2]))->assertOk()->assertSee('Showing 13–13 of 13');
        $this->get(route('admin.posts.index', ['search' => 'no match']))->assertOk()->assertSee('No matching posts');
        $this->get(route('admin.posts.index', ['page' => 99]))->assertOk()->assertSee('No posts on this page');

        if ($directory = getenv('BLOG_ADMIN_PREVIEW_DIR')) {
            Storage::disk('public')->put('blog/audio/piano-example.wav', $this->wav()->get());
            foreach (['posts' => [], 'topics' => ['manage' => 'topics'], 'audio' => ['manage' => 'audio']] as $name => $query) {
                $html = $this->get(route('admin.posts.index', $query))->assertOk()->getContent();
                $html = str_replace(['http://admin.localhost', 'http://localhost'], 'http://127.0.0.1:8769', $html);
                file_put_contents($directory.'/'.$name.'.html', $html);
            }
        }
        Http::assertNothingSent();
    }

    public function test_sql_search_filters_sorts_are_literal_and_validated()
    {
        $topic = $this->topic();
        $first = $this->postRecord(['title' => 'A 100% practice', 'published_at' => now(), 'reading_time' => 2]);
        $first->topics()->attach($topic);
        $second = $this->postRecord(['title' => 'Z 1000 practice', 'reading_time' => 9]);
        $table = app(PostsTable::class);
        foreach ([['search' => '%'], ['topic' => $topic->id], ['status' => 'published']] as $state) {
            $filters = $table->filters(\Illuminate\Http\Request::create('/', 'GET', $state));
            $this->assertSame([$first->id], $table->query($filters)->pluck('posts.id')->all());
        }
        $filters = $table->filters(\Illuminate\Http\Request::create('/', 'GET', ['sort' => 'reading_desc']));
        $this->assertSame([$second->id, $first->id], $table->query($filters)->pluck('posts.id')->all());
        $this->withExceptionHandling();
        foreach ([['sort' => 'title;drop'], ['per_page' => 1000], ['topic' => ['bad']], ['manage' => ['bad']], ['search' => ['bad']]] as $bad) {
            $this->getJson(route('admin.posts.index', $bad))->assertUnprocessable();
        }
    }

    public function test_draft_public_navigation_is_disabled_and_returns_when_published()
    {
        $draft = $this->postRecord(['title' => 'Draft article', 'slug' => 'draft-article']);
        $published = $this->postRecord(['title' => 'Published article', 'slug' => 'published-article', 'published_at' => now(), 'created_at' => now()->subDay()]);
        $index = $this->get(route('admin.posts.index'))->assertOk();
        $xpath = $this->xpath($index);
        $this->assertCount(0, $xpath->query('//a[@href="'.route('posts.show', $draft).'"]'));
        $this->assertCount(1, $xpath->query('//span[contains(@class,"admin-table__primary") and text()="Draft article"]'));
        $this->assertCount(1, $xpath->query('//button[@aria-label="Preview Draft article" and @disabled and @aria-disabled="true"]'));
        $this->assertCount(1, $xpath->query('//a[@href="'.route('admin.posts.edit', $draft).'"]'));
        $this->assertCount(1, $xpath->query('//input[@aria-label="Published: Draft article" and not(@disabled)]'));
        $this->assertCount(2, $xpath->query('//a[@href="'.route('posts.show', $published).'" and @target="_blank" and @rel="noopener noreferrer"]'));

        $editor = $this->get(route('admin.posts.edit', $draft))->assertOk();
        $this->assertCount(1, $this->xpath($editor)->query('//*[@id="page-title"]//button[@disabled and @aria-disabled="true"]'));
        $legacy = view('admin.pages.blog.table.actions', ['item' => $draft])->render();
        $this->assertStringNotContainsString(route('posts.show', $draft), $legacy);
        $this->assertStringContainsString('aria-disabled="true"', $legacy);

        if ($directory = getenv('BLOG_DRAFT_PREVIEW_DIR')) {
            foreach (['index' => $index, 'editor' => $editor] as $name => $page) {
                file_put_contents($directory.'/'.$name.'.html', str_replace(['http://admin.localhost', 'http://localhost'], 'http://127.0.0.1:8775', $page->getContent()));
            }
        }
        $statusUrl = route('admin.posts.update-status', ['post' => $draft, 'return_to' => 'directory']);
        $this->patch($statusUrl, ['published' => '1'])->assertRedirect(route('admin.posts.index'));
        $index = $this->get(route('admin.posts.index'))->assertOk();
        $this->assertCount(2, $this->xpath($index)->query('//a[@href="'.route('posts.show', $draft).'"]'));
        $editor = $this->get(route('admin.posts.edit', $draft))->assertOk();
        $this->assertCount(1, $this->xpath($editor)->query('//*[@id="page-title"]//a[@href="'.route('posts.show', $draft).'" and @rel="noopener noreferrer"]'));
        $this->patch($statusUrl, ['published' => '0'])->assertRedirect(route('admin.posts.index'));
        $index = $this->get(route('admin.posts.index'))->assertOk();
        $this->assertCount(0, $this->xpath($index)->query('//a[@href="'.route('posts.show', $draft).'"]'));
    }

    public function test_topics_save_delete_and_validation_return_to_the_correct_modal()
    {
        $topic = $this->topic();
        $post = $this->postRecord(); $post->topics()->attach($topic);
        $filters = ['search' => 'piano', 'status' => 'draft', 'page' => 2];
        $url = route('admin.posts.index', $filters + ['manage' => 'topics']);
        $this->post(route('admin.topics.store', $filters), ['name' => '  Theory  '])->assertRedirect($url)->assertSessionHas('status', 'Topic created.');
        $created = Topic::where('name', 'Theory')->firstOrFail();
        $this->patch(route('admin.topics.update', ['topic' => $created] + $filters), ['name' => 'Reading'])->assertRedirect($url);
        $this->assertSame('reading', $created->fresh()->slug);
        $this->withExceptionHandling();
        $this->patch(route('admin.topics.update', ['topic' => $created->fresh()] + $filters), ['name' => 'Technique!'])
            ->assertRedirect($url)->assertSessionHasErrors('name', null, 'blogTopic'.$created->id);
        $response = $this->get($url)->assertOk();
        $this->assertCount(1, $this->xpath($response)->query('//input[contains(@class,"is-invalid")]'));
        foreach (['', [], '!!!', 'Technique', str_repeat('x', 256)] as $name) {
            $this->postJson(route('admin.topics.store'), compact('name'))->assertUnprocessable()->assertJsonValidationErrors('name');
        }
        $this->delete(route('admin.topics.destroy', ['topic' => $topic] + $filters))->assertRedirect($url);
        $this->assertNull($topic->fresh());
        $this->assertSame(0, $post->topics()->count());
        $this->assertNotNull($post->fresh());
    }

    public function test_topic_detachment_rolls_back_when_deletion_fails()
    {
        $topic = $this->topic(); $post = $this->postRecord(); $post->topics()->attach($topic);
        // Fail after the model's pivot-detachment callback.
        Topic::deleting(function () { throw new \RuntimeException('Simulated failure'); });
        try {
            app(\App\Services\Admin\BlogTopics::class)->delete(\Illuminate\Http\Request::create('/'), $topic);
            $this->fail('Expected deletion failure');
        } catch (\RuntimeException $exception) {
            $this->assertSame('Simulated failure', $exception->getMessage());
        }
        $this->assertNotNull($topic->fresh());
        $this->assertSame(1, DB::table('post_topic')->where('topic_id', $topic->id)->count());
    }

    public function test_audio_upload_replace_delete_preserve_urls_and_directory_state()
    {
        $filters = ['search' => 'piano', 'page' => 2];
        $url = route('admin.posts.index', $filters + ['manage' => 'audio']);
        $this->post(route('admin.posts.audio.store', $filters), ['file' => $this->wav()])->assertRedirect($url);
        Storage::disk('public')->assertExists('blog/audio/sample.wav');
        $this->patch(route('admin.posts.audio.update', $filters), ['path' => 'blog/audio/sample.wav', 'file' => $this->wav('new.wav', 200)])->assertRedirect($url);
        $this->assertSame(444, strlen(Storage::disk('public')->get('blog/audio/sample.wav')));
        Storage::disk('public')->assertMissing('blog/audio/new.wav');
        $this->assertSame([], Storage::disk('public')->allFiles('blog/audio-staging'));
        $this->delete(route('admin.posts.audio.destroy', $filters), ['path' => 'blog/audio/sample.wav'])->assertRedirect($url);
        Storage::disk('public')->assertMissing('blog/audio/sample.wav');
    }

    public function test_audio_rejects_duplicates_non_audio_oversize_traversal_and_other_media()
    {
        $this->withExceptionHandling();
        $disk = Storage::disk('public');
        $disk->put('other/keep.wav', 'preserved');
        $disk->put('blog/audio/sample.wav', 'preserved');
        foreach ([$this->wav(), $this->wav('unsafe.php'), UploadedFile::fake()->createWithContent('fake.mp3', '<?php echo 1;')->mimeType('text/x-php'), UploadedFile::fake()->create('large.wav', 2049, 'audio/wav')] as $file) {
            $this->post(route('admin.posts.audio.store'), compact('file'))->assertRedirect(route('admin.posts.index', ['manage' => 'audio']))
                ->assertSessionHasErrors('file', null, 'blogAudioCreate');
        }
        foreach (['../other/keep.wav', 'other/keep.wav', 'blog/audio/../../other/keep.wav', ['bad']] as $path) {
            $this->deleteJson(route('admin.posts.audio.destroy'), compact('path'))->assertNotFound();
            $this->patchJson(route('admin.posts.audio.update'), ['path' => $path, 'file' => $this->wav()])->assertNotFound();
        }
        symlink($disk->path('other/keep.wav'), $disk->path('blog/audio/link.wav'));
        $this->deleteJson(route('admin.posts.audio.destroy'), ['path' => 'blog/audio/link.wav'])->assertNotFound();
        $this->assertSame('preserved', $disk->get('other/keep.wav'));
        $this->assertSame('preserved', $disk->get('blog/audio/sample.wav'));
    }

    public function test_publication_saves_desired_state_and_clamps_filtered_returns()
    {
        $post = $this->postRecord();
        $url = route('admin.posts.update-status', ['post' => $post, 'return_to' => 'directory', 'status' => 'draft', 'page' => 2]);
        $this->patch($url, ['published' => '1'])->assertRedirect(route('admin.posts.index', ['status' => 'draft']));
        $published = $post->fresh()->published_at;
        $this->patch($url, ['published' => '1'])->assertSessionHas('status', 'Publication saved.');
        $this->assertEquals($published, $post->fresh()->published_at);
        $this->patch($url, ['published' => '0'])->assertRedirect(route('admin.posts.index', ['status' => 'draft']));
        $this->assertNull($post->fresh()->published_at);
    }

    public function test_editor_saves_topics_and_keeps_historical_gift_data_out_of_new_writes()
    {
        $topic = $this->topic();
        $post = $this->postRecord(['gift_path' => 'gifts/historical.jpg']);
        $fields = ['title' => 'Updated article', 'description' => 'Article description.', 'content' => '<p>Updated content.</p>', 'topics' => [$topic->id], 'gift_path' => 'gifts/new.jpg'];
        $this->patch(route('admin.posts.update', ['post' => $post, 'search' => 'piano']), $fields)
            ->assertRedirect(route('admin.posts.edit', ['post' => 'updated-article', 'search' => 'piano']));
        $this->assertSame('gifts/historical.jpg', $post->fresh()->gift_path);
        $this->assertSame([$topic->id], $post->topics()->pluck('topics.id')->all());
        $fields['title'] = 'New article';
        $this->post(route('admin.posts.store'), $fields + ['cover_credits' => ''])
            ->assertRedirect(route('admin.posts.index'));
        $this->assertNull(Post::orderByDesc('id')->first()->gift_path);
        $this->withExceptionHandling();
        $fields['topics'] = [999999];
        $this->patchJson(route('admin.posts.update', $post->fresh()), $fields)->assertUnprocessable()->assertJsonValidationErrors('topics.0');
        $this->assertSame([$topic->id], $post->topics()->pluck('topics.id')->all());
    }

    public function test_old_pages_redirect_and_gift_writes_are_retired_without_deleting_files()
    {
        $this->get(route('admin.topics.index', ['search' => 'piano']))->assertRedirect(route('admin.posts.index', ['search' => 'piano', 'manage' => 'topics']));
        $this->get(route('admin.posts.audio.index'))->assertRedirect(route('admin.posts.index', ['manage' => 'audio']));
        $this->get(route('admin.posts.gifts.index'))->assertRedirect(route('admin.posts.index'));
        $post = $this->postRecord(['gift_path' => 'gifts/keep.jpg', 'published_at' => now()]);
        Storage::disk('public')->put('gifts/keep.jpg', 'keep');
        foreach (['create' => [], 'edit' => ['post' => $post]] as $action => $parameters) {
            $this->get(route('admin.posts.'.$action, $parameters))->assertOk()->assertDontSee('name="gift_path"', false);
        }
        auth()->shouldUse('web');
        $this->get(route('posts.show', $post))->assertOk()->assertDontSee('gift-container')->assertDontSee('id="popup-container"', false)
            ->assertSee('Visit Shop')->assertSee('Visit Games')->assertSee('Visit Learn');
        $this->assertSame('gifts/keep.jpg', $post->fresh()->toArray()['gift_path']);
        $this->withExceptionHandling();
        $this->get(route('posts.gift', $post))->assertStatus(410);
        $this->post(route('admin.posts.gifts.store'), ['file' => UploadedFile::fake()->image('new.jpg')])->assertStatus(410);
        $this->delete(route('admin.posts.gifts.destroy'), ['path' => 'gifts/keep.jpg'])->assertStatus(410);
        auth()->shouldUse('web');
        $this->post('http://blog.localhost/subscriptions', ['email' => 'reader@example.test', 'gift_url' => '/storage/gifts/keep.jpg', 'started_at' => now()->subSeconds(10)->toDateTimeString()])->assertStatus(410);
        Storage::disk('public')->assertExists('gifts/keep.jpg');
        Mail::assertNothingSent(); Mail::assertNothingQueued();
    }

    public function test_admin_auth_and_topic_permissions_remain_required()
    {
        $this->actingAs(create(Admin::class, ['role' => 'editor']), 'admin');
        $this->withExceptionHandling();
        $this->post(route('admin.topics.store'), ['name' => 'Blocked'])->assertForbidden();
        $this->get(route('admin.posts.index'))->assertOk()->assertDontSee('Manage topics');
        auth('admin')->logout();
        $this->get(route('admin.posts.index'))->assertRedirect(route('admin.login.show'));
        $this->post(route('admin.posts.audio.store'), ['file' => $this->wav()])->assertRedirect(route('admin.login.show'));
        foreach (['admin.posts.audio.store', 'admin.posts.audio.update', 'admin.posts.audio.destroy', 'admin.topics.store'] as $name) {
            $middleware = app('router')->getRoutes()->getByName($name)->gatherMiddleware();
            $this->assertContains('web', $middleware); $this->assertContains('auth:admin', $middleware);
        }
    }
}
