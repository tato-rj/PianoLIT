<?php

namespace Tests\Feature;

use App\{Subscription, EmailList};
use Tests\Traits\ManageDatabase;
use Tests\AppTest;
use App\Notifications\Emails\UnsubscribedNotification;

class SubscriptionTest extends AppTest
{
    use ManageDatabase;

    /** @test */
    public function a_guest_can_subscribe_to_freepick()
    {
        $email = make(Subscription::class)->email;

        $this->subscribe($email);

        $this->assertDatabaseHas('subscriptions', ['email' => $email]);

        $this->assertTrue(EmailList::freepick()->subscribers()->byEmail($email)->exists());
    }

    /** @test */
    public function a_bot_is_denied_access_to_the_subscription()
    {
        $this->expectException('Illuminate\Auth\Access\AuthorizationException');

        $email = make(Subscription::class)->email;

        $this->subscribe($email, 'bot', false);

        $this->assertDatabaseMissing('subscriptions', ['email' => $email]);

        $this->assertFalse(EmailList::freepick()->subscribers()->byEmail($email)->exists());
    }

    /** @test */
    public function subscribing_does_not_send_the_retired_welcome_email()
    {
        \Mail::fake();

        $this->subscribe();
        
        \Mail::assertNothingSent();
    }

    /** @test */
    public function upon_subscription_an_email_is_automatically_reactivated_if_it_exists_or_created_new_if_it_doesnt()
    {
        $unsubscribedSubscription = create(Subscription::class);

        $this->assertFalse($unsubscribedSubscription->in(EmailList::freepick()));

        $this->subscribe($unsubscribedSubscription->email);

        $this->assertTrue($unsubscribedSubscription->in(EmailList::freepick()));

        $this->assertCount(1, Subscription::all());

        $this->subscribe();

        $this->assertCount(2, Subscription::all());
    }

    /** @test */
    public function guests_can_unsubscribe_from_freepick()
    {
        $subscription = create(Subscription::class);

        $subscription->join(EmailList::freepick());

        $this->assertTrue($subscription->in(EmailList::freepick()));

        $this->get(route('subscriptions.unsubscribe', [$subscription, EmailList::freepick()]));

        $this->assertDatabaseHas('subscriptions', ['email' => $subscription->email]);

        $this->assertFalse($subscription->in(EmailList::freepick()));
    }

    /** @test */
    public function admins_are_notified_when_an_email_is_unsubscribed_by_a_guest()
    {
        \Notification::fake();

        $subscription = create(Subscription::class);

        $subscription->join(EmailList::freepick());

        $this->get(route('subscriptions.unsubscribe', [$subscription, EmailList::freepick()]));

        \Notification::assertSentTo($this->admin, UnsubscribedNotification::class);
    }

    /** @test */
    public function new_users_are_subscribed_to_all_email_lists()
    {
        $this->register();

        foreach (EmailList::all() as $list) {
            $this->assertTrue($list->subscribers()->byEmail(auth()->user()->email)->exists());            
        }         
    }

    /** @test */
    public function users_can_toggle_their_own_email_from_the_subscription_lists()
    {
        $this->register();

        $this->assertTrue(EmailList::freepick()->has(auth()->user()->email));

        $this->patch(route('users.subscriptions.update-list', EmailList::freepick()));

        $this->assertFalse(EmailList::freepick()->has(auth()->user()->email));
    }

    /** @test */
    public function admins_are_notified_when_an_email_is_unsubscribed_by_a_user()
    {
        \Notification::fake();

        $this->register();

        $this->patch(route('users.subscriptions.update-list', EmailList::freepick()));

        \Notification::assertSentTo($this->admin, UnsubscribedNotification::class);
    }

    /** @test */
    public function admins_can_toggle_any_email_from_the_subscription_lists()
    {
        $this->signIn();

        $subscription1 = create(Subscription::class);
        $subscription2 = create(Subscription::class);

        $this->assertFalse(EmailList::freepick()->has($subscription1->email));
        $this->assertFalse(EmailList::freepick()->has($subscription2->email));

        $this->patch(route('admin.subscriptions.lists.status', ['list' => EmailList::freepick()->id, 'subscriberId' => $subscription1->id]));

        $this->assertTrue(EmailList::freepick()->has($subscription1->email));
        $this->assertFalse(EmailList::freepick()->has($subscription2->email));

        $this->patch(route('admin.subscriptions.lists.status', ['list' => EmailList::freepick()->id, 'subscriberId' => $subscription1->id]));
        $this->patch(route('admin.subscriptions.lists.status', ['list' => EmailList::freepick()->id, 'subscriberId' => $subscription2->id]));

        $this->assertFalse(EmailList::freepick()->has($subscription1->email));
        $this->assertTrue(EmailList::freepick()->has($subscription2->email));
    }

    /** @test */
    public function admins_can_delete_an_email_from_the_subscribers_list()
    {
        $subscription = create(Subscription::class);

        $subscription->join(EmailList::freepick());

        $this->assertDatabaseHas('subscriptions', ['email' => $subscription->email]);

        $this->assertTrue(EmailList::freepick()->has($subscription->email));

        $this->signIn();

        $this->delete(route('admin.subscriptions.destroy', $subscription->email));

        $this->assertDatabaseMissing('subscriptions', ['email' => $subscription->email]);

        $this->assertFalse(EmailList::freepick()->has($subscription->email));
    }

    /** @test */
    public function admins_can_delete_multiple_emails_from_the_subscribers_list()
    {
        $subscription1 = create(Subscription::class);
        $subscription2 = create(Subscription::class);

        $subscription1->join(EmailList::freepick());
        $subscription2->join(EmailList::freepick());

        $this->assertDatabaseHas('subscriptions', ['email' => $subscription1->email]);
        $this->assertDatabaseHas('subscriptions', ['email' => $subscription2->email]);

        $this->assertTrue(EmailList::freepick()->has($subscription1->email));
        $this->assertTrue(EmailList::freepick()->has($subscription2->email));

        $this->signIn();

        $this->delete(route('admin.subscriptions.destroy-many', ['ids' => [$subscription1->id, $subscription2->id]]));

        $this->assertDatabaseMissing('subscriptions', ['email' => $subscription1->email]);
        $this->assertDatabaseMissing('subscriptions', ['email' => $subscription2->email]);

        $this->assertFalse(EmailList::freepick()->has($subscription1->email));
        $this->assertFalse(EmailList::freepick()->has($subscription2->email));
    }
}
