<?php

namespace Tests\Feature;

use App\Subscription;
use Tests\AppTest;

class SubscriptionTest extends AppTest
{
    /** @test */
    public function a_guest_can_subscribe_to_the_newsletter()
    {
        $email = make(Subscription::class)->email;

        $this->subscribe($email);

        $this->assertDatabaseHas('subscriptions', ['email' => $email]);
    }

    /** @test */
    public function upon_subscription_an_email_is_automatically_reactivated_if_it_exists_or_created_new_if_it_doesnt()
    {
        $unsubscribedSubscription = create(Subscription::class, ['is_active' => false]);

        $this->assertFalse($unsubscribedSubscription->is_active);

        $this->subscribe($unsubscribedSubscription->email);

        $this->assertTrue($unsubscribedSubscription->fresh()->is_active);

        $this->assertCount(1, Subscription::all());

        $this->subscribe();

        $this->assertCount(2, Subscription::all());
    }

    /** @test */
    public function guests_can_unsubscribe_from_the_newsletter()
    {
        $subscription = create(Subscription::class);

        $this->assertTrue($subscription->is_active);

        $this->unsubscribe($subscription->email);

        $this->assertDatabaseHas('subscriptions', ['email' => $subscription->email]);

        $this->assertFalse($subscription->fresh()->is_active);
    }

    /** @test */
    public function admins_can_delete_an_email_from_the_susbcription_list()
    {
        $email = create(Subscription::class)->email;

        $this->assertDatabaseHas('subscriptions', ['email' => $email]);

        $this->signIn();

        $this->delete(route('subscriptions.destroy', $email));

        $this->assertDatabaseMissing('subscriptions', ['email' => $email]);
    }
}
