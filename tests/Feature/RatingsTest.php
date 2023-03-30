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
}
