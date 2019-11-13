<?php

namespace Tests\Feature;

use App\Blog\Post;
use Tests\AppTest;
use Tests\Traits\AdminEvents;
use Illuminate\Support\Facades\Storage;

class BlogTest extends AppTest
{
	use AdminEvents;

    /** @test */
    public function an_admin_can_create_blog_posts()
    {
        $this->signIn();
        
        $post = $this->storeBlogPost();

        $this->assertDatabaseHas('posts', ['title' => $post->title]);

        Storage::disk('public')->assertExists($post->cover_path);
    }

    /** @test */
    public function a_thumbnail_is_automatically_generated_when_a_cover_image_is_saved()
    {
        $this->signIn();

        $post = $this->storeBlogPost();

        $this->assertNotEquals($post->thumbnail_path, $post->cover_path);
        
        $this->assertTrue(Storage::disk('public')->exists($post->cover_path));

        $this->assertTrue(Storage::disk('public')->exists($post->thumbnail_path));
    }

    /** @test */
    public function an_admin_can_publish_a_blog_post()
    {
    	$this->signIn();

        $post = $this->storeBlogPost();
    	 
    	$this->assertNull($post->published_at);

    	$this->patch(route('admin.posts.update-status', $post->slug));

    	$this->assertNotNull($post->fresh()->published_at);
    }

    /** @test */
    public function an_admin_can_update_a_blog_post()
    {
    	$this->signIn();

        $post = $this->storeBlogPost();

        $title = $post->title;

        $this->patch(route('admin.posts.update', $post->slug), make(Post::class)->toArray());

        $this->assertNotEquals($title, $post->fresh()->title);    	 
    }

    /** @test */
    public function an_admin_can_remove_a_blog_post()
    {
    	$this->signIn();

        $post = $this->storeBlogPost();

        $this->delete(route('admin.posts.destroy', $post->slug));

        $this->assertDatabaseMissing('posts', ['title' => $post->title]);

        Storage::disk('public')->assertMissing($post->cover_path);
    }

    /** @test */
    public function a_user_can_search_the_blog_database()
    {
        $this->post->updateStatus();
        
        $response = $this->json('get', route('api.blog.search'), ['input' => 'xxx']);

        $this->assertCount(0, $response->baseResponse->original['results']);

        $response = $this->json('get', route('api.blog.search'), ['input' => substr($this->post->title, 0, 2)]);

        $this->assertCount(1, $response->baseResponse->original['results']);
    }

    /** @test */
    public function a_post_automatically_increments_its_view_each_time_it_is_viewed()
    {
        $post = create(Post::class, ['published_at' => now()]);

        $views = $post->views;

        $this->get(route('posts.show', $post->slug));

        $this->assertNotEquals($views, $post->fresh()->views);
    }
}
