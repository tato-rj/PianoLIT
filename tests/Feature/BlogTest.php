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
    	 
    	$this->assertFalse($post->is_published);

    	$this->patch(route('admin.posts.update-status', $post->slug));

    	$this->assertTrue($post->fresh()->is_published);
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
}
