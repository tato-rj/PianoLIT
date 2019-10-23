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
        $unsubscribedSubscription = create(Subscription::class, ['newsletter_list' => false]);

        $this->assertFalse($unsubscribedSubscription->getStatusFor('newsletter_list', $boolean = true));

        $this->subscribe($unsubscribedSubscription->email);

        $this->assertTrue($unsubscribedSubscription->fresh()->getStatusFor('newsletter_list', $boolean = true));

        $this->assertCount(1, Subscription::all());

        $this->subscribe();

        $this->assertCount(2, Subscription::all());
    }

    /** @test */
    public function guests_can_unsubscribe_from_the_newsletter()
    {
        $subscription = create(Subscription::class);

        $this->assertTrue($subscription->getStatusFor('newsletter_list', true));

        $this->unsubscribe($subscription->email, 'newsletter_list');

        $this->assertDatabaseHas('subscriptions', ['email' => $subscription->email]);

        $this->assertFalse($subscription->fresh()->getStatusFor('newsletter_list', true));
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
