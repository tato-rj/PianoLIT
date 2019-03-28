<?php

namespace Tests\Feature;

use App\Subscription;
use Tests\AppTest;

class SubscriptionTest extends AppTest
{
    /** @test */
    public function a_guest_can_subscribe_to_the_newsletter()
    {
        $subscription = make(Subscription::class);

        $this->post(route('subscriptions.store'), ['email' => $subscription->email]);

        $this->assertDatabaseHas('subscriptions', ['email' => $subscription->email]);
    }

    /** @test */
    public function the_same_guest_cannot_subscribe_more_than_once_twice_minute()
    {
        $this->expectException('Illuminate\Http\Exceptions\ThrottleRequestsException');
        
        $this->post(route('subscriptions.store'), ['email' => 'bart@email.com']);
        
        $this->post(route('subscriptions.store'), ['email' => 'homer@email.com']);

        $this->post(route('subscriptions.store'), ['email' => 'lisa@email.com']); 
    }

    /** @test */
    public function upon_subscription_an_email_is_automatically_reactivated_if_it_exists_or_created_new_if_it_doesnt()
    {
        $unsubscribedSubscription = create(Subscription::class, ['is_active' => false]);

        $this->assertFalse($unsubscribedSubscription->is_active);

        $this->post(route('subscriptions.store'), ['email' => $unsubscribedSubscription->email]);

        $this->assertTrue($unsubscribedSubscription->fresh()->is_active);

        $this->assertCount(1, Subscription::all());

        $this->post(route('subscriptions.store'), ['email' => 'new@email.com']);

        $this->assertCount(2, Subscription::all());
    }

    /** @test */
    public function guests_can_unsubscribe_from_the_newsletter()
    {
        $subscription = create(Subscription::class);

        $this->assertTrue($subscription->is_active);

        $this->post(route('api.subscriptions.unsubscribe', ['email' => $subscription->email]));

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
