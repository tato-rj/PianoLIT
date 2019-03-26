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
        
        $post = $this->postBlogPost();

        $this->assertDatabaseHas('posts', ['title' => $post->title]);

        Storage::disk('public')->assertExists($post->cover_path);
    }

    /** @test */
    public function an_admin_can_publish_a_blog_post()
    {
    	$this->signIn();

        $post = $this->postBlogPost();
    	 
    	$this->assertFalse($post->is_published);

    	$this->patch(route('admin.posts.update-status', $post->slug));

    	$this->assertTrue($post->fresh()->is_published);
    }

    /** @test */
    public function an_admin_can_update_a_blog_post()
    {
    	$this->signIn();

        $post = $this->postBlogPost();

        $title = $post->title;

        $this->patch(route('admin.posts.update', $post->slug), make(Post::class)->toArray());

        $this->assertNotEquals($title, $post->fresh()->title);    	 
    }

    /** @test */
    public function an_admin_can_remove_a_blog_post()
    {
    	$this->signIn();

        $post = $this->postBlogPost();

        $this->delete(route('admin.posts.destroy', $post->slug));

        $this->assertDatabaseMissing('posts', ['title' => $post->title]);

        Storage::disk('public')->assertMissing($post->cover_path);
    }
}
