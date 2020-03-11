<?php

namespace Tests\Unit;

use Tests\AppTest;
use App\Subscription;
use App\CrashCourse\{CrashCourse, CrashCourseLesson, CrashCourseSubscription};

class CrashCourseTest extends AppTest
{
    public function setUp() : void
    {
        parent::setUp();
		
		$this->crashcourse = create(CrashCourse::class);
		$this->crashcourse->subscriptions()->save(create(CrashCourseSubscription::class));
		$this->crashcourse->lessons()->save(create(CrashCourseLesson::class));
    }

	/** @test */
	public function it_has_many_subscriptions()
	{
		$this->assertInstanceOf(CrashCourseSubscription::class, $this->crashcourse->subscriptions->first());
	}

	/** @test */
	public function it_has_many_lessons()
	{
		$this->assertInstanceOf(CrashCourseLesson::class, $this->crashcourse->lessons->first());
	}

	/** @test */
	public function it_knows_how_to_signup_a_subscriber()
	{
		$this->assertEquals(1, $this->crashcourse->fresh()->subscriptions_count);

		$this->crashcourse->signup(create(Subscription::class));

		$this->assertEquals(2, $this->crashcourse->fresh()->subscriptions_count);
	}

    /** @test */
    public function it_knows_the_status_of_its_subscriptions()
    {
    	$this->assertCount(1, $this->crashcourse->activeSubscriptions()->get());
    	$this->assertCount(0, $this->crashcourse->cancelledSubscriptions()->get());
    	$this->assertCount(0, $this->crashcourse->completedSubscriptions()->get());

    	$this->crashcourse->subscriptions->first()->cancel();

    	$this->assertCount(0, $this->crashcourse->activeSubscriptions()->get());
    	$this->assertCount(1, $this->crashcourse->cancelledSubscriptions()->get());
    	$this->assertCount(0, $this->crashcourse->completedSubscriptions()->get());

    	$this->crashcourse->subscriptions->first()->update(['cancelled_at' => null]);
    	
    	$this->assertCount(1, $this->crashcourse->activeSubscriptions()->get());
    	$this->assertCount(0, $this->crashcourse->cancelledSubscriptions()->get());
    	$this->assertCount(0, $this->crashcourse->completedSubscriptions()->get());
    	
    	$this->crashcourse->subscriptions->first()->finish();

    	$this->assertCount(0, $this->crashcourse->activeSubscriptions()->get());
    	$this->assertCount(0, $this->crashcourse->cancelledSubscriptions()->get());
    	$this->assertCount(1, $this->crashcourse->completedSubscriptions()->get());
    }
}
