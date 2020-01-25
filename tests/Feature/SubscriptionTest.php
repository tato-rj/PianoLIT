<?php

namespace Tests\Feature;

use App\{Subscription, EmailList};
use Tests\Traits\ManageDatabase;
use Tests\AppTest;
use App\Mail\Newsletter\Welcome as WelcomeToNewsletter;
use App\Notifications\Emails\UnsubscribedNotification;

class SubscriptionTest extends AppTest
{
    use ManageDatabase;

    /** @test */
    public function a_guest_can_subscribe_to_the_newsletter()
    {
        $email = make(Subscription::class)->email;

        $this->subscribe($email);

        $this->assertDatabaseHas('subscriptions', ['email' => $email]);

        $this->assertTrue(EmailList::newsletter()->subscribers()->byEmail($email)->exists());
    }

    /** @test */
    public function guests_receive_an_email_upon_subscription()
    {
        \Mail::fake();

        $this->subscribe();
        
        \Mail::assertQueued(WelcomeToNewsletter::class);
    }

    /** @test */
    public function upon_subscription_an_email_is_automatically_reactivated_if_it_exists_or_created_new_if_it_doesnt()
    {
        $unsubscribedSubscription = create(Subscription::class);

        $this->assertFalse($unsubscribedSubscription->in(EmailList::newsletter()));

        $this->subscribe($unsubscribedSubscription->email);

        $this->assertTrue($unsubscribedSubscription->in(EmailList::newsletter()));

        $this->assertCount(1, Subscription::all());

        $this->subscribe();

        $this->assertCount(2, Subscription::all());
    }

    /** @test */
    public function guests_can_unsubscribe_from_the_newsletter()
    {
        $subscription = create(Subscription::class);

        $subscription->join(EmailList::newsletter());

        $this->assertTrue($subscription->in(EmailList::newsletter()));

        $this->get(route('subscriptions.unsubscribe', [$subscription, EmailList::newsletter()]));

        $this->assertDatabaseHas('subscriptions', ['email' => $subscription->email]);

        $this->assertFalse($subscription->in(EmailList::newsletter()));
    }

    /** @test */
    public function admins_are_notified_when_an_email_is_unsubscribed_by_a_guest()
    {
        \Notification::fake();

        $subscription = create(Subscription::class);

        $subscription->join(EmailList::newsletter());

        $this->get(route('subscriptions.unsubscribe', [$subscription, EmailList::newsletter()]));

        \Notification::assertSentTo($this->admin, UnsubscribedNotification::class);
    }

    /** @test */
    public function users_can_toggle_their_own_email_from_the_subscription_lists()
    {
        $this->register();

        $this->assertTrue(EmailList::newsletter()->has(auth()->user()->email));

        $this->patch(route('users.subscriptions.update-list', EmailList::newsletter()));

        $this->assertFalse(EmailList::newsletter()->has(auth()->user()->email));
    }

    /** @test */
    public function admins_are_notified_when_an_email_is_unsubscribed_by_a_user()
    {
        \Notification::fake();

        $this->register();

        $this->patch(route('users.subscriptions.update-list', EmailList::newsletter()));

        \Notification::assertSentTo($this->admin, UnsubscribedNotification::class);
    }

    /** @test */
    public function admins_can_toggle_any_email_from_the_subscription_lists()
    {
        $this->signIn();

        $subscription1 = create(Subscription::class);
        $subscription2 = create(Subscription::class);

        $this->assertFalse(EmailList::newsletter()->has($subscription1->email));
        $this->assertFalse(EmailList::newsletter()->has($subscription2->email));

        $this->patch(route('admin.subscriptions.lists.status', ['list' => EmailList::newsletter()->id, 'subscriberId' => $subscription1->id]));

        $this->assertTrue(EmailList::newsletter()->has($subscription1->email));
        $this->assertFalse(EmailList::newsletter()->has($subscription2->email));

        $this->patch(route('admin.subscriptions.lists.status', ['list' => EmailList::newsletter()->id, 'subscriberId' => $subscription1->id]));
        $this->patch(route('admin.subscriptions.lists.status', ['list' => EmailList::newsletter()->id, 'subscriberId' => $subscription2->id]));

        $this->assertFalse(EmailList::newsletter()->has($subscription1->email));
        $this->assertTrue(EmailList::newsletter()->has($subscription2->email));
    }

    /** @test */
    public function admins_can_delete_an_email_from_the_subscribers_list()
    {
        $subscription = create(Subscription::class);

        $subscription->join(EmailList::newsletter());

        $this->assertDatabaseHas('subscriptions', ['email' => $subscription->email]);

        $this->assertTrue(EmailList::newsletter()->has($subscription->email));

        $this->signIn();

        $this->delete(route('subscriptions.destroy', $subscription->email));

        $this->assertDatabaseMissing('subscriptions', ['email' => $subscription->email]);

        $this->assertFalse(EmailList::newsletter()->has($subscription->email));
    }
}
