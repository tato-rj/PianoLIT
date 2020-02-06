<?php

namespace Tests;

use App\Blog\{Post, Topic};
use App\Infograph\Infograph;
use App\Infograph\Topic as InfographTopic;
use App\Quiz\Quiz;
use App\Quiz\Topic as QuizTopic;
use App\{Composer, Piece, Admin, Country, Tag, User, Membership, Playlist, Subscription, Timeline, Pianist, EmailList};
use Tests\Traits\CustomAssertions;
use App\Rules\Recaptcha;

class AppTest extends TestCase
{
    use CustomAssertions;
    
	public function setUp() : void
	{
		parent::setUp();

        app()->singleton(Recaptcha::class, function() {
            $mock = \Mockery::mock(Recaptcha::class);
            $mock->shouldReceive('passes')->andReturn(true);
            return $mock;
        });

        $this->redisPrefix = config('database.redis.prefix');

        create(EmailList::class, ['name' => 'Newsletter']);
        create(EmailList::class, ['name' => 'Free Pick']);

        $this->admin = create(Admin::class);

        $this->user = create(User::class);

        $this->infograph = create(Infograph::class, ['creator_id' => $this->admin->id]);

        $this->infograph_topic = create(InfographTopic::class, ['creator_id' => $this->admin->id]);

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

        $this->level = create(Tag::class, ['type' => 'level']);

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
        $this->piece->tags()->attach($this->level);

        $this->piece->views()->create(['user_id' => $this->user->id]);

        $this->post->topics()->attach($this->topic);

        $this->quiz->topics()->attach($this->quiz_topic);

        $this->infograph->topics()->attach($this->infograph_topic);

        $this->playlist->pieces()->attach($this->piece);
        
        $this->beforeApplicationDestroyed(function () {
            $this->artisan('redis:flush ' . $this->redisPrefix);
        });
	}

    protected function signIn($user = null)
    {
        $user = $user ?? $this->admin;
        $guard = get_class($user) == 'App\Admin' ? 'admin' : 'web';
        return $this->actingAs($user, $guard);
    }

    protected function logout()
    {
        \Auth::logout();
    }

    public function subscribe($email = null, $bot = null, $wait = true)
    {
        return $this->post(route('subscriptions.store'), [
            'email' => $email ?? make(Subscription::class)->email,
            'subscription_name' => $bot ?? null,
            'started_at' => $wait ? now()->subSeconds(5) : now()]);
    }

    protected function postMembership($user, $membership)
    {
        return $this->post(route('api.memberships.store'), [
            'user_id' => $user->id,
            'receipt_data' => $membership->withRequest()->receipt_data,
            'password' => $membership->withRequest()->password
        ]);
    }
}
