<?php

namespace Tests\Review;

use App\Admin;
use App\Blog\{Post, Topic};
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\{Http, Storage};

class BlogPostEditorTest extends ReviewTestCase
{
    public function setUp(): void
    {
        parent::setUp();
        Storage::fake('public');
        $this->actingAs(create(Admin::class, ['role' => 'manager']), 'admin');
    }

    private function article(array $attributes = [])
    {
        return Model::withoutEvents(fn () => create(Post::class, $attributes + [
            'title' => 'Reading a piano score', 'description' => 'Notes on reading piano music.',
            'content' => '<h2>Read the score</h2><p>Saved article content.</p>',
            'references' => serialize(['First saved source', 'Second saved source']),
            'cover_path' => null, 'cover_credits' => 'Original image source', 'creator_id' => auth('admin')->id(),
        ]));
    }

    private function topic(string $name)
    {
        return Topic::create(['name' => $name, 'slug' => str_slug($name), 'creator_id' => auth('admin')->id()]);
    }

    private function xpath($response): \DOMXPath
    {
        $document = new \DOMDocument; @$document->loadHTML($response->getContent());
        return new \DOMXPath($document);
    }

    public function test_both_editors_use_one_shared_layout_and_preserve_routes_and_content()
    {
        $post = $this->article();
        $post->topics()->attach($this->topic('Technique')->id);
        $this->topic('Reading');
        $filters = ['search' => 'piano', 'status' => 'draft', 'page' => 2];
        foreach (['create' => [], 'edit' => ['post' => $post]] as $kind => $parameters) {
            $response = $this->get(route('admin.posts.'.$kind, $parameters + $filters))->assertOk()
                ->assertSee('Post details')->assertSee('Article content')->assertSee('Cover image')->assertSee('References')
                ->assertSee('data-table-save-actions', false)->assertDontSee('Gift path here')->assertDontSee('cropperjs/1.4.3');
            $xpath = $this->xpath($response);
            $this->assertCount(6, $xpath->query('//section[contains(@class,"admin-editor-section")]'));
            $this->assertCount(1, $xpath->query('//form[@id="post-editor"]//input[@name="_token"]'));
            $this->assertCount(0, $xpath->query('//form//form'));
            $this->assertCount(1, $xpath->query('//label[@for="editor"]'));
            $this->assertCount(1, $xpath->query('//button[@type="button" and @data-add-reference]'));
            $this->assertCount($kind === 'edit' ? 2 : 1, $xpath->query('//*[@data-reference-rows]/*[@data-reference-row]'));
            $this->assertCount(0, $xpath->query('//input[@name="gift_path"]'));
            $this->assertStringContainsString('search=piano', $xpath->query('//form[@id="post-editor"]')->item(0)->getAttribute('action'));
            if ($kind === 'edit') {
                $this->assertSame($post->content, $xpath->query('//textarea[@name="content"]')->item(0)->textContent);
                $this->assertSame('First saved source', $xpath->query('//input[@id="post-reference-0"]')->item(0)->getAttribute('value'));
                $this->assertCount(0, $xpath->query('//a[@href="'.route('posts.show', $post).'"]'));
                $this->assertCount(1, $xpath->query('//*[@id="page-title"]//button[@disabled and @aria-disabled="true"]'));
            }
            if ($directory = getenv('BLOG_POST_EDITOR_PREVIEW_DIR')) {
                $html = str_replace(['http://admin.localhost', 'http://localhost'], 'http://127.0.0.1:8771', $response->getContent());
                file_put_contents($directory.'/'.$kind.'.html', $html);
            }
        }
        Http::assertNothingSent();
    }

    public function test_validation_keeps_latest_body_references_and_topic_selection()
    {
        $post = $this->article();
        $saved = $this->topic('Technique'); $selected = $this->topic('Reading');
        $post->topics()->attach($saved);
        $url = route('admin.posts.edit', ['post' => $post, 'search' => 'piano']);
        $this->withExceptionHandling()->from($url)->patch(route('admin.posts.update', $post), [
            'title' => 'x', 'description' => 'Edited description', 'content' => '<p>Latest unsaved article.</p>',
            'topics' => [$selected->id], 'references' => [0 => 'Latest source', 2 => str_repeat('x', 2001)], 'cover_credits' => '',
        ])->assertRedirect($url)->assertSessionHasErrors(['title', 'references.2']);
        $response = $this->get($url)->assertOk(); $xpath = $this->xpath($response);
        $this->assertSame('<p>Latest unsaved article.</p>', $xpath->query('//textarea[@name="content"]')->item(0)->textContent);
        $this->assertSame('x', $xpath->query('//input[@name="title"]')->item(0)->getAttribute('value'));
        $this->assertSame('', $xpath->query('//input[@name="cover_credits"]')->item(0)->getAttribute('value'));
        $this->assertCount(1, $xpath->query('//input[@name="topics[]" and @checked and @value="'.$selected->id.'"]'));
        $this->assertCount(0, $xpath->query('//input[@name="topics[]" and @checked and @value="'.$saved->id.'"]'));
        $this->assertCount(1, $xpath->query('//input[@id="post-reference-2" and @aria-invalid="true"]'));
        $this->assertSame('First saved source', $post->fresh()->referencesArray[0]);
    }

    public function test_cleared_fields_are_not_restored_from_the_saved_post_after_validation()
    {
        $post = $this->article(); $post->topics()->attach($this->topic('Technique'));
        $url = route('admin.posts.edit', $post);
        $this->withExceptionHandling()->from($url)->patch(route('admin.posts.update', $post), [
            'title' => '', 'description' => '', 'content' => '', 'cover_credits' => '', 'references' => ['  '],
        ])->assertRedirect($url)->assertSessionHasErrors(['title', 'description', 'content']);
        $xpath = $this->xpath($this->get($url)->assertOk());
        $this->assertSame('', $xpath->query('//input[@name="title"]')->item(0)->getAttribute('value'));
        foreach (['description', 'content'] as $name) $this->assertSame('', $xpath->query('//textarea[@name="'.$name.'"]')->item(0)->textContent);
        $this->assertCount(0, $xpath->query('//input[@name="topics[]" and @checked]'));
        $this->assertSame('', $xpath->query('//input[@id="post-reference-0"]')->item(0)->getAttribute('value'));
    }

    public function test_references_save_as_citations_and_can_be_cleared_without_changing_other_data()
    {
        $post = $this->article();
        $data = ['title' => $post->title, 'description' => $post->description, 'content' => $post->content, 'references' => [0 => '  New source  ', 1 => '', 3 => 'Another source']];
        $this->patch(route('admin.posts.update', $post), $data)->assertSessionHas('status', 'Post saved.');
        $this->assertSame([0 => 'New source', 3 => 'Another source'], $post->fresh()->referencesArray);
        $this->assertSame($post->content, $post->fresh()->content);
        unset($data['references']);
        $this->patch(route('admin.posts.update', $post->fresh()), $data)->assertSessionHas('status', 'Post saved.');
        $this->assertNull($post->fresh()->references);
        $this->withExceptionHandling();
        foreach (['bad', [['nested']], [str_repeat('x', 2001)]] as $references) {
            $this->patchJson(route('admin.posts.update', $post->fresh()), $data + compact('references'))->assertUnprocessable();
        }
    }

    public function test_renaming_an_article_keeps_the_existing_public_and_embedded_urls()
    {
        $post = $this->article(['published_at' => now()]);
        $url = route('posts.show', $post);
        $embedUrl = $post->app_url;

        $this->patch(route('admin.posts.update', $post), [
            'title' => 'A clearer article title', 'description' => $post->description, 'content' => $post->content,
        ])->assertSessionHas('status', 'Post saved.');

        $this->assertSame($post->slug, $post->fresh()->slug);
        auth()->shouldUse('web');
        $this->get($url)->assertOk()->assertSee('A clearer article title');
        $this->get($embedUrl)->assertOk()->assertSee('A clearer article title');
    }

    public function test_cropped_cover_upload_still_saves_original_and_thumbnail()
    {
        $post = $this->article();
        $this->patch(route('admin.posts.update', $post), [
            'title' => $post->title, 'description' => $post->description, 'content' => $post->content,
            'cover_image' => UploadedFile::fake()->image('cover.jpg', 320, 240),
            'cropped_width' => 320, 'cropped_height' => 180, 'cropped_x' => 0, 'cropped_y' => 0,
        ])->assertSessionHas('status', 'Post saved.');
        $post = $post->fresh();
        Storage::disk('public')->assertExists($post->cover_path);
        Storage::disk('public')->assertExists($post->thumbnail_path);
        $image = \Image::make(Storage::disk('public')->get($post->cover_path));
        $this->assertSame(320, $image->width()); $this->assertSame(180, $image->height());
    }
}
