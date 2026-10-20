<?php

namespace Tests\Feature;

use Tests\AppTest;
use App\{EmailCampaignReport, EmailList, EmailLog, Subscription, Piece};
use App\Mail\FreePickEmail;
use App\Notifications\Emails\EmailListSentNotification;
use App\Jobs\{SendMassEmails, SendEmail};

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

    	$this->freePickList = create(EmailList::class, ['name' => 'Free Pick']);
    	$this->freePickList->subscribers()->attach($this->subscriber1);
    	$this->freePickList->subscribers()->attach($this->subscriber2);


    }

    /** @test */
    public function admins_can_send_out_the_free_pick_email_to_all_subscribers()
    {
		\Queue::fake();

        $this->signIn();

        $this->post(route('admin.subscriptions.lists.send', $this->freePickList));

		\Queue::assertPushed(SendMassEmails::class, 1);

        $this->assertNotNull($this->freePickList->fresh()->last_sent_at);
    }

    /** @test */
    public function admins_are_notified_when_an_email_list_is_sent()
    {
        \Notification::fake();

        $this->freePickList->subscribers()->detach();
        (new SendMassEmails($this->freePickList->id, 'free-pick.123'))->handle();

        \Notification::assertSentTo($this->admin, EmailListSentNotification::class);
    }

    /** @test */
    public function mass_emails_are_queued_in_small_background_jobs()
    {
        \Queue::fake();

        (new SendMassEmails($this->freePickList->id, 'free-pick.123'))->handle();

        \Queue::assertPushed(SendEmail::class, 2);
        \Queue::assertPushed(SendMassEmails::class, function ($job) {
            return $job->afterSubscriberId === $this->subscriber2->id;
        });
    }

    /** @test */
    public function a_queued_email_job_sends_to_one_subscriber()
    {
        \Mail::fake();

        (new SendEmail($this->freePickList->id, 'free-pick.123', $this->subscriber1->id))->handle();

        \Mail::assertQueued(FreePickEmail::class, 1);
    }

    /** @test */
    public function admins_can_send_out_a_free_pick_email_preview()
    {
    	$this->signIn();

    	\Mail::fake();

        $this->post(route('admin.subscriptions.lists.send-to', ['list' => $this->freePickList, 'email' => 'test@email.com']));

        \Mail::assertQueued(FreePickEmail::class, 1);
    }

    /** @test */
    public function email_reports_are_paginated_on_the_server()
    {
        $this->freePickList->emailLog()->create([
            'message_id' => 'message-1',
            'list_id' => 'free-pick.100',
            'recipient' => 'one@example.com',
            'unique_delivered' => 1,
        ]);
        $this->freePickList->emailLog()->create([
            'message_id' => 'message-2',
            'list_id' => 'free-pick.100',
            'recipient' => 'two@example.com',
            'unique_opened' => 1,
        ]);
        $this->freePickList->emailLog()->create([
            'message_id' => 'message-3',
            'list_id' => 'free-pick.200',
            'recipient' => 'three@example.com',
        ]);
        $this->signIn();

        $response = $this->get(route('admin.subscriptions.reports.index', ['search' => 'free pick', 'sort' => 'emails_desc']));
        $response->assertOk()->assertSee('Free Pick');
        $reports = $response->viewData('reports');
        $this->assertSame(2, $reports->total());
        $this->assertSame(\App\Support\AdminPagination::PER_PAGE, $reports->perPage());
        $this->assertSame(2, $reports->first()->emails_count);
    }

    /** @test */
    public function email_campaign_summaries_are_updated_incrementally()
    {
        $log = $this->freePickList->emailLog()->create([
            'message_id' => 'message-summary',
            'list_id' => 'free-pick.300',
            'recipient' => 'summary@example.com',
        ]);

        $this->assertDatabaseHas('email_campaign_reports', [
            'list_id' => 'free-pick.300',
            'emails_count' => 1,
            'opens_count' => 0,
        ]);

        $log->update(['unique_opened' => 1]);

        $this->assertSame(1, EmailCampaignReport::where('list_id', 'free-pick.300')->value('opens_count'));
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
