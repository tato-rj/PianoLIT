<?php

namespace Tests\Feature;

use Tests\AppTest;

class LogsTest extends AppTest
{
    /** @test */
    public function users_visits_are_recorded_with_redis()
    {
        $this->signIn($this->user);

        $this->assertRedisEmpty();

        $this->get(route('home'));

        $this->assertRedisHas('user:'.auth()->user()->id.':web');
    }

    /** @test */
    public function guests_visits_are_not_recorded_with_redis()
    {
        $this->get(route('home'));

        $this->assertRedisEmpty();
    }

    /** @test */
    public function visits_to_user_restricted_pages_are_also_recorded_on_redis()
    {
        $this->signIn($this->user);

        $this->get(route('users.profile'));

        $key = 'user:'.auth()->user()->id.':web';

        $this->assertRedisHas($key);
    }

    /** @test */
    public function visits_to_admin_restricted_pages_are_not_recorded_on_redis()
    {
        $this->signIn($this->admin);

        $this->get(route('admin.notifications.index'));

        $this->assertRedisEmpty();
    }

    /** @test */
    public function visits_on_the_app_are_stored_on_redis()
    {
        $this->get(route('api.discover'));

        $this->assertRedisMissing('user:'.$this->user->id.':app');

        $this->get(route('api.discover', ['user_id' => $this->user->id]));

        $key = 'user:'.$this->user->id.':app';

        $this->assertRedisHas($key);
    }

    /** @test */
    public function searches_and_pieces_views_on_the_app_are_stored_on_redis()
    {
        $this->get(route('api.search', ['user_id' => $this->user->id, 'search' => 'foo bar']));

        $key = 'user:'.$this->user->id.':app';

        $this->assertRedisContains($key, 'search', 'foo bar');

        $this->post(route('api.pieces.find', ['user_id' => $this->user->id, 'search' => $this->piece->id]));

        $this->assertRedisContains($key, 'search', $this->piece->id);
    }

    /** @test */
    public function searches_on_the_admin_page_are_not_stored_on_redis()
    {
        $this->signIn($this->admin);

        $this->get(route('api.search', ['user_id' => $this->user->id, 'search' => 'foo bar']));

        $this->assertRedisEmpty();
    }
}
