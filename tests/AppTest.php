<?php

namespace Tests;

use App\Blog\{Post, Topic};
use App\Quiz\Quiz;
use App\Quiz\Topic as QuizTopic;
use App\{Composer, Piece, Admin, Country, Tag, User, Membership, Playlist, Subscription, Timeline, Pianist};

class AppTest extends TestCase
{
	public function setUp() : void
	{
		parent::setUp();

        $this->admin = create(Admin::class);

        $this->user = create(User::class);

        $this->timeline = create(Timeline::class, ['creator_id' => $this->admin->id]);

        $this->post = create(Post::class, ['creator_id' => $this->admin->id]);

        $this->topic = create(Topic::class, ['creator_id' => $this->admin->id]);

        $this->quiz = create(Quiz::class, ['creator_id' => $this->admin->id]);

        $this->quiz_topic = create(QuizTopic::class, ['creator_id' => $this->admin->id]);

        $this->membership = create(Membership::class);

        $this->user->membership()->save($this->membership);

        $this->country = create(Country::class);

        $this->playlist = create(Playlist::class);

        $this->tag = create(Tag::class, ['creator_id' => $this->admin->id]);

        $this->pianist = create(Pianist::class, [
            'creator_id' => $this->admin->id,
            'country_id' => $this->country->id
        ]);

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

        $this->piece->views()->create(['user_id' => $this->user->id]);

        $this->post->topics()->attach($this->topic);

        $this->quiz->topics()->attach($this->quiz_topic);

        $this->playlist->pieces()->attach($this->piece);
	}

    protected function signIn($admin = null, $guard = 'admin')
    {
        $admin = ($admin) ?: $this->admin;
        return $this->actingAs($admin, $guard);
    }

    public function subscribe($email = null, $bot = null, $wait = true)
    {
        return $this->post(route('subscriptions.store'), [
            'email' => $email ?? make(Subscription::class)->email,
            'subscription_name' => $bot ?? null,
            'started_at' => $wait ? now()->subSeconds(5) : now()]);
    }

    public function unsubscribe($email)
    {
        return $this->post(route('api.subscriptions.unsubscribe', ['email' => $email]));
    }
}
