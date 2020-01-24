<?php

namespace Tests\Unit;

use App\{Subscription, EmailList};
use Tests\AppTest;

class SubscriptionTest extends AppTest
{
    public function setUp() : void
    {
        parent::setUp();

		$this->subscription = create(Subscription::class);
		create(EmailList::class, ['name' => 'Newsletter']);
		create(EmailList::class, ['name' => 'Free Pick']);
    }

	/** @test */
	public function it_belongs_to_many_lists()
	{
		$this->subscription->join(EmailList::newsletter());

		$this->assertInstanceOf(EmailList::class, $this->subscription->lists()->first());
	}

	/** @test */
	public function it_knows_how_to_join_or_leave_an_email_list()
	{
		$this->subscription->join(EmailList::newsletter());

		$this->assertCount(1, $this->subscription->lists()->get());
		
		$this->subscription->join(EmailList::freepick());

		$this->assertCount(2, $this->subscription->lists()->get());

		$this->subscription->leave(EmailList::newsletter());
		
		$this->assertCount(1, $this->subscription->lists()->get());
		
		$this->subscription->leave(EmailList::freepick());

		$this->assertCount(0, $this->subscription->lists()->get());
	}

	/** @test */
	public function it_knows_if_it_is_in_a_given_list()
	{
		$this->assertFalse($this->subscription->in(EmailList::newsletter()));
	
		$this->subscription->join(EmailList::newsletter());

		$this->assertTrue($this->subscription->in(EmailList::newsletter()));		 
	}
}
