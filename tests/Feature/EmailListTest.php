<?php

namespace Tests\Feature;

use Tests\AppTest;
use App\{EmailList, Subscription, Piece};
use App\Mail\{FreePickEmail, NewsletterEmail};
use App\Notifications\Emails\EmailListSentNotification;

class EmailListTest extends AppTest
{
    public function setUp() : void
    {
        parent::setUp();
        
        $freePick = create(Piece::class, ['is_free' => true]);
        $freePick->tags()->attach($this->tag);
        $freePick->tags()->attach($this->level);
        $freePick->update(['videos' => serialize([['title' => 'Foo', 'description' => 'bar', 'filename' => 'video']])]);
        
        $this->subscriber1 = create(Subscription::class);
        $this->subscriber2 = create(Subscription::class);
        $this->subscriber3 = create(Subscription::class);
        $this->subscriber4 = create(Subscription::class);

    	$this->freePickList = create(EmailList::class, ['name' => 'Free Pick']);
    	$this->freePickList->subscribers()->attach($this->subscriber1);
    	$this->freePickList->subscribers()->attach($this->subscriber2);

        $this->newsletterList = create(EmailList::class, ['name' => 'Newsletter']);
        $this->newsletterList->subscribers()->attach($this->subscriber3);
        $this->newsletterList->subscribers()->attach($this->subscriber4);
    }

    /** @test */
    public function admins_can_send_out_the_free_pick_email_to_all_subscribers()
    {
    	\Mail::fake();

        $this->signIn();

    	$this->get(route('admin.subscriptions.lists.send', $this->freePickList));

        \Mail::assertQueued(FreePickEmail::class, 2);

        $this->assertNotNull($this->freePickList->fresh()->last_sent_at);
    }

    /** @test */
    public function admins_are_notified_when_an_email_list_is_sent()
    {
        $this->signIn();

        \Notification::fake();
        
        $this->get(route('admin.subscriptions.lists.send', $this->freePickList));

        \Notification::assertSentTo($this->admin, EmailListSentNotification::class);
    }

    /** @test */
    public function admins_can_send_out_a_free_pick_email_preview()
    {
    	$this->signIn();

    	\Mail::fake();

    	$this->get(route('admin.subscriptions.lists.send-to', ['list' => $this->freePickList, 'email' => 'test@email.com']));

        \Mail::assertQueued(FreePickEmail::class, 1);
    }

    /** @test */
    public function admins_can_send_out_a_newsletter_with_a_custom_subject_line()
    {
        $this->signIn();

        \Mail::fake();

        $this->get(route('admin.subscriptions.lists.send-to', ['list' => $this->newsletterList, 'email' => 'test@email.com', 'subject' => 'foo']));
         
        \Mail::assertQueued(NewsletterEmail::class, function($mail) {
            return $mail->subject == 'foo';
        });

        $this->get(route('admin.subscriptions.lists.send', ['list' => $this->newsletterList, 'subject' => 'bar']));
         
        \Mail::assertQueued(NewsletterEmail::class, function($mail) {
            return $mail->subject == 'bar';
        });
    }

    /** @test */
    public function admins_can_add_and_remove_emails_from_the_lists()
    {
        $this->signIn();

        $this->assertDatabaseHas('email_list_subscription', ['subscription_id' => $this->subscriber1->id]);

        $this->patch(route('admin.subscriptions.lists.status', $this->freePickList), ['subscriberId' => $this->subscriber1->id]);

        $this->assertDatabaseMissing('email_list_subscription', ['subscription_id' => $this->subscriber1->id]);
        
        $this->patch(route('admin.subscriptions.lists.status', $this->freePickList), ['subscriberId' => $this->subscriber1->id]);

        $this->assertDatabaseHas('email_list_subscription', ['subscription_id' => $this->subscriber1->id]);
    }

    /** @test */
    public function admins_can_delete_lists()
    {
        $this->signIn();

        $listId = $this->freePickList->id;

        $this->assertDatabaseHas('email_list_subscription', ['subscription_id' => $this->subscriber1->id]);

        $this->delete(route('admin.subscriptions.lists.destroy', $this->freePickList));

        $this->assertDatabaseMissing('email_list_subscription', ['subscription_id' => $this->subscriber1->id]);
        
        $this->assertDatabaseMissing('email_lists', ['id' => $listId]);
    }
}
