<?php

namespace Tests\Feature;

use App\{User, Location, Piece};
use App\Blog\Post;
use Tests\AppTest;

class LocationsTest extends AppTest
{
    /** @test */
    public function users_visiting_the_website_have_their_location_saved_or_updated()
    {
        $this->signIn($this->user);

        $this->assertFalse($this->user->location()->exists());

        $this->get(route('home'));

        $this->assertTrue($this->user->location()->exists());

        // $this->get(route('posts.index'));

        // $this->assertTrue($this->user->location()->exists());
    }

    /** @test */
    public function users_visiting_the_webapp_have_their_location_saved_or_updated()
    {
        create(Post::class, ['published_at' => now()]);
        Piece::first()->update(['is_free' => true]);

        $this->signIn($this->user);

        $this->assertFalse($this->user->location()->exists());

        $this->get(route('webapp.discover'));

        $this->assertTrue($this->user->location()->exists());
    }

    /** @test */
    public function users_visiting_the_ios_app_have_their_location_saved_or_updated()
    {
        $this->assertFalse($this->user->location()->exists());

        $this->get(route('api.pieces.find', ['user_id' => $this->user->id]));

        $this->assertTrue($this->user->location()->exists());
    }
}
