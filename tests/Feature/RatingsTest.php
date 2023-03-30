<?php

namespace Tests\Feature;

use Tests\AppTest;
use App\User;
use Tests\Traits\{BillingResources, InteractsWithStripe};

class RatingsTest extends AppTest
{
    use BillingResources, InteractsWithStripe;

    /** @test */
    public function the_app_requests_reviews_from_members()
    {
        $user = create(User::class);

        $this->assertFalse($user->ratings()->exists());
        
        $response = $this->get(route('api.users.should-review', ['user_id' => $user->id]));

        $this->assertFalse($response->getData()->shouldReview);

        $user->membership()->save($this->membership);

        $this->travel(now()->addDays(7));

        $response = $this->get(route('api.users.should-review', ['user_id' => $user->id]));

        $this->assertTrue($response->getData()->shouldReview);
    }

    /** @test */
    public function the_app_requests_reviews_from_the_guests_with_many_logs()
    {
        $userWhoDoesntLikeUs = create(User::class);
        $userWhoLikesUs = create(User::class);

        $this->signIn($userWhoLikesUs);

        $this->get(route('api.discover', ['user_id' => $userWhoLikesUs->id]));

        $response = $this->get(route('api.users.should-review', ['user_id' => $userWhoLikesUs->id]));

        $this->assertTrue($response->getData()->shouldReview);

        $response = $this->get(route('api.users.should-review', ['user_id' => $userWhoDoesntLikeUs->id]));

        $this->assertFalse($response->getData()->shouldReview);
    }

    /** @test */
    public function the_app_does_not_request_reviews_from_members_on_trial()
    {
        $user = create(User::class);

        $this->assertFalse($user->ratings()->exists());
        
        $response = $this->get(route('api.users.should-review', ['user_id' => $user->id]));

        $this->assertFalse($response->getData()->shouldReview);

        $this->postStripeMembership($user);

        $response = $this->get(route('api.users.should-review', ['user_id' => $user->id]));

        $this->assertFalse($response->getData()->shouldReview);
    }

    /** @test */
    public function when_a_rating_request_is_made_it_is_saved_as_unconfirmed()
    {
        $user = create(User::class);

        $this->assertFalse($user->ratings()->exists());
        
        $this->get(route('api.users.should-review', ['user_id' => $user->id]));

        $this->assertTrue($user->ratings()->exists());
        $this->assertTrue($user->ratings()->unconfirmed()->exists());
        $this->assertFalse($user->ratings()->confirmed()->exists());
    }

    /** @test */
    public function a_user_who_ignores_the_request_does_not_receive_another_one_right_away()
    {
        $user = create(User::class);
        
        $user->membership()->save($this->membership);

        $response = $this->get(route('api.users.should-review', ['user_id' => $user->id]));

        $this->assertTrue($response->getData()->shouldReview);

        $response = $this->get(route('api.users.should-review', ['user_id' => $user->id]));

        $this->assertFalse($response->getData()->shouldReview);
    }

    /** @test */
    public function a_user_who_ignores_the_request_receives_another_one_a_week_later()
    {
        $user = create(User::class);
        
        $user->membership()->save($this->membership);

        $response = $this->get(route('api.users.should-review', ['user_id' => $user->id]));

        $this->assertTrue($response->getData()->shouldReview);

        $response = $this->get(route('api.users.should-review', ['user_id' => $user->id]));

        $this->assertFalse($response->getData()->shouldReview);

        $this->travel(now()->addDays(7));

        $response = $this->get(route('api.users.should-review', ['user_id' => $user->id]));

        $this->assertTrue($response->getData()->shouldReview);
    }

    /** @test */
    public function a_user_who_submited_a_rating_is_not_asked_to_submit_again()
    {
        $user = create(User::class);
        
        $user->membership()->save($this->membership);

        $response = $this->get(route('api.users.should-review', ['user_id' => $user->id]));

        $this->assertTrue($response->getData()->shouldReview);
        
        $user->ratings()->first()->update(['score' => 5]);

        $this->travel(now()->addDays(20));

        $response = $this->get(route('api.users.should-review', ['user_id' => $user->id]));

        $this->assertFalse($response->getData()->shouldReview);
    }

    /** @test */
    public function after_three_attempts_the_user_will_no_longer_receive_rating_requests()
    {
        $user = create(User::class);
        
        $user->membership()->save($this->membership);

        $response = $this->get(route('api.users.should-review', ['user_id' => $user->id]));

        $this->assertTrue($response->getData()->shouldReview);

        $this->travel(now()->addDays(7));

        $response = $this->get(route('api.users.should-review', ['user_id' => $user->id]));

        $this->assertTrue($response->getData()->shouldReview);

        $this->travel(now()->addDays(7));

        $response = $this->get(route('api.users.should-review', ['user_id' => $user->id]));

        $this->assertTrue($response->getData()->shouldReview);

        $this->travel(now()->addDays(7));

        $response = $this->get(route('api.users.should-review', ['user_id' => $user->id]));

        $this->assertTrue($response->getData()->shouldReview);
        
        $this->travel(now()->addDays(7));

        $response = $this->get(route('api.users.should-review', ['user_id' => $user->id]));

        $this->assertFalse($response->getData()->shouldReview);
    }

    /** @test */
    public function a_users_rating_is_saved()
    {
        $user = create(User::class);
        
        $user->membership()->save($this->membership);

        $response = $this->get(route('api.users.should-review', ['user_id' => $user->id]));
        
        $this->assertTrue($response->getData()->shouldReview);

        $this->assertTrue($user->ratings()->unconfirmed()->exists());

        $this->assertFalse($user->ratings()->confirmed()->exists());

        $this->post(route('api.users.should-review', ['user_id' => $user->id, 'score' => 5]));

        $response = $this->get(route('api.users.should-review', ['user_id' => $user->id]));
        
        $this->assertFalse($response->getData()->shouldReview);

        $this->assertFalse($user->ratings()->unconfirmed()->exists());

        $this->assertTrue($user->ratings()->confirmed()->exists());
    }
}
