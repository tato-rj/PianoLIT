<?php

namespace Tests\Unit;

use Tests\AppTest;
use App\{EmailList, Subscription};

class EmailListTest extends AppTest
{
    public function setUp() : void
    {
        parent::setUp();
		
		$this->list = create(EmailList::class);
		$this->subscription = create(Subscription::class);
    }

	/** @test */
	public function it_has_many_subscribers()
	{
		$this->list->subscribers()->attach($this->subscription);
		$this->assertInstanceOf(Subscription::class, $this->list->subscribers()->first());		 
	}

	/** @test */
	public function it_cannot_have_the_same_subscriber_twice()
	{
		$this->expectException('Illuminate\Database\QueryException');
		$this->list->subscribers()->attach($this->subscription);
		$this->list->subscribers()->attach($this->subscription); 
	}

	/** @test */
	public function it_knows_how_to_add_an_email_to_its_list()
	{
		$this->assertFalse($this->list->has($this->subscription->email));

		$this->list->add($this->subscription);

		$this->assertTrue($this->list->has($this->subscription->email));
	}

	/** @test */
	public function it_knows_how_to_remove_an_email_to_its_list()
	{
		$this->list->add($this->subscription);

		$this->assertTrue($this->list->has($this->subscription->email));

		$this->list->remove($this->subscription);

		$this->assertFalse($this->list->has($this->subscription->email));
	}

	/** @test */
	public function it_knows_how_to_toggle_an_email_to_its_list()
	{
		$this->list->toggle($this->subscription);

		$this->assertTrue($this->list->has($this->subscription->email));

		$this->list->toggle($this->subscription);

		$this->assertFalse($this->list->has($this->subscription->email));
	}

	/** @test */
	public function it_knows_if_it_has_an_email_in_a_given_list()
	{
		$this->assertFalse($this->list->has($this->subscription->email));

		$this->list->toggle($this->subscription);
		
		$this->assertTrue($this->list->has($this->subscription->email));
	}
}
