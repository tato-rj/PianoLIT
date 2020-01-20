<?php

namespace Tests\Feature;

use Tests\AppTest;
use App\{EmailList, Subscription, Piece};
use App\Mail\FreePickEmail;
use App\Mail\Newsletter\Welcome as WelcomeToNewsletter;

class EmailTest extends AppTest
{
    public function setUp() : void
    {
        parent::setUp();
        
        create(Piece::class, ['is_free' => true]);
        
    	$this->freePickList = create(EmailList::class, ['name' => 'Free Pick']);
    	$this->freePickList->subscribers()->attach(create(Subscription::class));
    	$this->freePickList->subscribers()->attach(create(Subscription::class));
    }

    /** @test */
    public function guests_receive_an_email_upon_subscription()
    {
        \Mail::fake();

        $this->subscribe();
        
        \Mail::assertQueued(WelcomeToNewsletter::class);
    }

    /** @test */
    public function admins_can_send_out_the_free_pick_email_to_all_subscribers()
    {
    	$this->signIn();

    	\Mail::fake();

    	$this->get(route('admin.subscriptions.lists.send', $this->freePickList));

        \Mail::assertQueued(FreePickEmail::class, 2);
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
    public function admins_can_see_a_preview_on_the_browser()
    {
    	$this->signIn();

    	$this->get(route('admin.subscriptions.lists.preview', $this->freePickList))->assertSee(Piece::free()->first()->name);    	 
    }
}
