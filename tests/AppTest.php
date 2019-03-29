<?php

namespace Tests;

use App\Blog\{Post, Topic};
use App\{Composer, Piece, Admin, Country, Tag, User, Membership, Playlist, Subscription};

class AppTest extends TestCase
{
	public function setUp() : void
	{
		parent::setUp();

        $this->admin = create(Admin::class);

        $this->user = create(User::class);

        $this->post = create(Post::class, ['creator_id' => $this->admin->id]);

        $this->topic = create(Topic::class, ['creator_id' => $this->admin->id]);

        $this->membership = create(Membership::class);

        $this->user->membership()->save($this->membership);

        $this->country = create(Country::class);

        $this->playlist = create(Playlist::class);

        $this->tag = create(Tag::class, ['creator_id' => $this->admin->id]);

        $this->composer = create(Composer::class, [
        	'creator_id' => $this->admin->id,
        	'country_id' => $this->country->id
        ]);

        $this->piece = create(Piece::class, [
            'creator_id' => $this->admin->id,
            'composer_id' => $this->composer->id
        ]);
        
        $this->user->favorites()->attach($this->piece);

        $this->piece->tags()->attach($this->tag);

        $this->post->topics()->attach($this->topic);

        $this->playlist->pieces()->attach($this->piece);
	}

    protected function signIn($admin = null, $guard = 'admin')
    {
        $admin = ($admin) ?: $this->admin;
        return $this->actingAs($admin, $guard);
    }

    public function subscribe($email = null)
    {
        return $this->post(route('subscriptions.store'), ['email' => $email ?? make(Subscription::class)->email]);
    }

    public function unsubscribe($email)
    {
        return $this->post(route('api.subscriptions.unsubscribe', ['email' => $email]));
    }
}
