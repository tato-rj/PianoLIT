<?php

namespace Tests\Feature;

use Tests\AppTest;
use App\Subscription;
use App\CrashCourse\{CrashCourse, CrashCourseLesson};
use App\Mail\Newsletter\Welcome as NewsletterWelcomeEmail;
use App\Mail\CrashCourseEmail;

class CrashCourseSubscriptionTest extends AppTest
{
    public function setUp() : void
    {
        parent::setUp();
		
		$this->crashcourse = create(CrashCourse::class);
		$this->crashcourse->lessons()->save(create(CrashCourseLesson::class, [
            'subject' => 'You\'re awesome [first_name]!',
            'body' => 'First email'
        ]));
		$this->crashcourse->lessons()->save(create(CrashCourseLesson::class, ['body' => 'Second email']));
		$this->crashcourse->lessons()->save(create(CrashCourseLesson::class, ['body' => 'Final email']));
    }

    /** @test */
    public function a_visitor_is_subscribed_upon_signing_up_for_a_crash_course()
    {
    	Subscription::truncate();

        $this->assertCount(0, Subscription::all());

        $this->postCrashCourse($this->crashcourse);

        $this->assertCount(1, Subscription::all());
    }

    /** @test */
    public function a_subscriber_does_not_receive_the_automatic_welcome_email_upon_signing_up_for_a_crash_course()
    {
    	\Mail::fake();

        $this->postCrashCourse($this->crashcourse);

        \Mail::assertNotQueued(NewsletterWelcomeEmail::class);
    }

    /** @test */
    public function a_subscriber_is_not_subscribed_to_others_email_lists_upon_signing_up_for_a_crash_course()
    {
        $this->postCrashCourse($this->crashcourse);

        $this->assertEmpty($this->crashcourse->subscriptions->first()->subscriber->lists()->get());
    }

    /** @test */
    public function a_current_subscriber_can_signup_for_a_crash_course()
    {
        $this->assertEmpty($this->crashcourse->subscriptions()->get());

        $this->subscribe(make(Subscription::class)->email);

        $this->postCrashCourse($this->crashcourse, ['first_name' => 'Jane', 'email' => Subscription::first()->email]);

        $this->assertNotEmpty($this->crashcourse->subscriptions()->get());
    }

    /** @test */
    public function a_new_subscriber_receives_the_first_lesson_right_away_upon_signing_up_for_a_crash_course()
    {
    	\Mail::fake();

        $this->postCrashCourse($this->crashcourse);

        \Mail::assertQueued(CrashCourseEmail::class, function($mail) {
            $this->assertEquals($mail->lesson->body, 'First email');
            return true;
        });
    }

    /** @test */
    public function after_the_first_email_all_subsequent_are_sent_in_the_correct_order()
    {
        \Mail::fake();

        $this->postCrashCourse($this->crashcourse);
        
        \Mail::assertQueued(CrashCourseEmail::class, function($mail) {
            $this->assertEquals($mail->lesson->body, 'First email');
            return true;
        });

        $this->crashcourse->subscriptions->first()->continue();

        \Mail::assertQueued(CrashCourseEmail::class, function($mail) {
            $this->assertEquals($mail->lesson->body, 'Second email');
            return true;
        });
    }
}
