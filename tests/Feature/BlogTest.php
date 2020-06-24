<?php

namespace Tests\Feature;

use App\Blog\Post;
use Tests\AppTest;
use Tests\Traits\AdminEvents;
use Illuminate\Support\Facades\Storage;
use Illuminate\Http\UploadedFile;

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

        $cover = $post->cover_path;
        $thumbnail = $post->thumbnail_path;

        $update = make(Post::class, ['cover_image' => UploadedFile::fake()->image('cover.jpg')]);

        $update->cropped_width = '200';
        $update->cropped_height = '200';
        $update->cropped_x = '0';
        $update->cropped_y = '0';

        $this->patch(route('admin.posts.update', $post->slug), $update->toArray());

        $this->assertNotEquals($title, $post->fresh()->title);

        $this->assertFalse(Storage::disk('public')->exists($cover));
        $this->assertFalse(Storage::disk('public')->exists($thumbnail));

        $this->assertTrue(Storage::disk('public')->exists($post->fresh()->cover_path));
        $this->assertTrue(Storage::disk('public')->exists($post->fresh()->thumbnail_path));
    }

    /** @test */
    public function an_admin_can_remove_a_blog_post()
    {
    	$this->signIn();

        $post = $this->storeBlogPost();

        $this->delete(route('admin.posts.destroy', $post->slug));

        $this->assertDatabaseMissing('posts', ['title' => $post->title]);

        Storage::disk('public')->assertMissing($post->cover_path);
        Storage::disk('public')->assertMissing($post->thumbnail_path);
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
