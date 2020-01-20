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
}
