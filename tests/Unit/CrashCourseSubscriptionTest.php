<?php

namespace Tests\Unit;

use Tests\AppTest;
use App\CrashCourse\{CrashCourse, CrashCourseLesson, CrashCourseSubscription};
use App\Subscription;

class CrashCourseSubscriptionTest extends AppTest
{
    public function setUp() : void
    {
        parent::setUp();
		
		$this->crashcourse = create(CrashCourse::class);
		$this->crashcourse->subscriptions()->save(create(CrashCourseSubscription::class));
		$this->crashcourse->lessons()->save(create(CrashCourseLesson::class));
		$this->crashcourse->lessons()->save(create(CrashCourseLesson::class));
		$this->crashcourse->lessons()->save(create(CrashCourseLesson::class));

		$this->course_susbcription = CrashCourseSubscription::first();
		$this->crashcourse = $this->crashcourse->fresh();
    }

    /** @test */
    public function it_belongs_to_a_crash_course()
    {
		$this->assertInstanceOf(CrashCourse::class, $this->course_susbcription->crashcourse);
    }

    /** @test */
    public function it_belongs_to_a_subscriber()
    {
		$this->assertInstanceOf(Subscription::class, $this->course_susbcription->subscriber);
    }

    /** @test */
    public function it_knows_its_course_lessons()
    {
		$this->assertInstanceOf(CrashCourseLesson::class, $this->course_susbcription->lessons->first());
    }

    /** @test */
    public function it_knows_its_last_sent_lesson()
    {
		$this->assertNull($this->course_susbcription->previousLesson);

		$this->course_susbcription->start();

		$this->assertInstanceOf(CrashCourseLesson::class, $this->course_susbcription->previousLesson);
    }

    /** @test */
    public function it_knows_its_upcoming_lesson()
    {
		$this->assertEquals(CrashCourseLesson::first(), $this->course_susbcription->upcomingLesson);

		$this->course_susbcription->start();

		$this->assertEquals(CrashCourseLesson::find(2), $this->course_susbcription->upcomingLesson);

		$this->course_susbcription->continue();

		$this->assertEquals(CrashCourseLesson::find(3), $this->course_susbcription->upcomingLesson);

		$this->course_susbcription->continue();

		$this->assertNull($this->course_susbcription->upcomingLesson);
    }

    /** @test */
    public function it_knows_the_number_of_remaining_lessons()
    {
    	$this->assertEquals(3, $this->course_susbcription->remaining_lessons_count);
		
		$this->course_susbcription->start();

    	$this->assertEquals(2, $this->course_susbcription->remaining_lessons_count);
    }

    /** @test */
    public function it_knows_how_to_start()
    {
    	$this->assertNull($this->course_susbcription->started_at);
    	$this->assertNull($this->course_susbcription->previousLesson);

    	$this->course_susbcription->start();

    	$this->assertNotNull($this->course_susbcription->started_at);
    	$this->assertNotNull($this->course_susbcription->previousLesson);
    }

    /** @test */
    public function it_knows_how_to_continue()
    {
    	$this->course_susbcription->start();

    	$secondLesson = $this->course_susbcription->upcomingLesson;

    	$this->course_susbcription->continue();
    	
    	$this->assertNotEquals($secondLesson, $this->course_susbcription->upcomingLesson);
    }

    /** @test */
    public function it_knows_how_to_finish()
    {
    	$this->course_susbcription->start();

    	$this->course_susbcription->continue();

    	$this->assertNull($this->course_susbcription->completed_at);

    	$this->course_susbcription->finish();

    	$this->assertNotNull($this->course_susbcription->completed_at);
    }

    /** @test */
    public function it_knows_how_to_cancel()
    {
    	$this->course_susbcription->start();

    	$this->course_susbcription->continue();

    	$this->assertNull($this->course_susbcription->cancelled_at);

    	$this->course_susbcription->cancel();

    	$this->assertNull($this->course_susbcription->completed_at);
    	$this->assertNotNull($this->course_susbcription->cancelled_at);    	 
    }

    /** @test */
    public function its_lessons_know_how_to_add_the_susbcriber_name_in_the_title_if_needed()
    {
        $lesson = create(CrashCourseLesson::class, ['subject' => 'Thank you [first_name]!']);

        $this->crashcourse->lessons()->save($lesson);

        $subscriber = $this->crashcourse->subscriptions->first();

        $this->assertEquals('Thank you ' . $subscriber->first_name . '!', $subscriber->subject($lesson));
    }
}
