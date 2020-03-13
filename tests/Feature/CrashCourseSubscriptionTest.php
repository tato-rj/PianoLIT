<?php

namespace Tests\Feature;

use Tests\AppTest;
use App\Subscription;
use App\CrashCourse\{CrashCourse, CrashCourseLesson, CrashCourseSubscription};
use App\Mail\Newsletter\Welcome as NewsletterWelcomeEmail;
use App\Mail\{CrashCourseEmail, CrashCourseFeedback};
use App\Notifications\CrashCourse\{CrashCourseSignUpNotification, CrashCourseCancelledNotification};

class CrashCourseSubscriptionTest extends AppTest
{
    public function setUp() : void
    {
        parent::setUp();
		
		$this->crashcourse = create(CrashCourse::class);
		$this->crashcourse->lessons()->save(create(CrashCourseLesson::class, ['subject' => 'You\'re awesome [first_name]!', 'body' => 'First email']));
		$this->crashcourse->lessons()->save(create(CrashCourseLesson::class));
		$this->crashcourse->lessons()->save(create(CrashCourseLesson::class));
    }

    /** @test */
    public function a_visitor_is_subscribed_upon_signing_up_for_a_crash_course()
    {
    	Subscription::truncate();

        $this->assertCount(0, Subscription::all());

        $this->signUpToCrashCourse($this->crashcourse);

        $this->assertCount(1, Subscription::all());
    }

    /** @test */
    public function admins_are_notified_on_a_new_crash_course_signup()
    {
        \Notification::fake();

        $this->signUpToCrashCourse($this->crashcourse);
        
        \Notification::assertSentTo(
            [$this->admin], CrashCourseSignUpNotification::class
        );

    }

    /** @test */
    public function a_subscriber_does_not_receive_the_automatic_welcome_email_upon_signing_up_for_a_crash_course()
    {
    	\Mail::fake();

        $this->signUpToCrashCourse($this->crashcourse);

        \Mail::assertNotQueued(NewsletterWelcomeEmail::class);
    }

    /** @test */
    public function a_subscriber_is_not_subscribed_to_others_email_lists_upon_signing_up_for_a_crash_course()
    {
        $this->signUpToCrashCourse($this->crashcourse);

        $this->assertEmpty($this->crashcourse->subscriptions->first()->subscriber->lists()->get());
    }

    /** @test */
    public function a_current_subscriber_can_signup_for_a_crash_course()
    {
        $this->assertEmpty($this->crashcourse->subscriptions()->get());

        $this->subscribe(make(Subscription::class)->email);

        $this->signUpToCrashCourse($this->crashcourse, ['first_name' => 'Jane', 'email' => Subscription::first()->email]);

        $this->assertNotEmpty($this->crashcourse->subscriptions()->get());
    }

    /** @test */
    public function a_subscriber_cannot_signup_for_the_same_course_while_it_is_active()
    {
        $this->signUpToCrashCourse($this->crashcourse);

        $email = $this->crashcourse->subscriptions->first()->subscriber->email;

        $this->signUpToCrashCourse($this->crashcourse, ['first_name' => 'John', 'email' => $email]);

        $this->assertEquals(1, $this->crashcourse->subscriptions()->count());
    }

    /** @test */
    public function a_new_subscriber_receives_the_first_lesson_right_away_upon_signing_up_for_a_crash_course()
    {
    	\Mail::fake();

        $this->signUpToCrashCourse($this->crashcourse);

        \Mail::assertQueued(CrashCourseEmail::class, function($mail) {
            $this->assertEquals($mail->lesson->body, 'First email');
            return true;
        });
    }

    /** @test */
    public function after_the_first_email_all_subsequent_are_sent_in_the_correct_order()
    {
        $this->signUpToCrashCourse($this->crashcourse);

        $subscription = $this->crashcourse->subscriptions->first();
        
        $this->assertEquals($subscription->upcomingLesson, $this->crashcourse->lessons->get(1));

        $subscription->continue();
        
        $this->assertEquals($subscription->upcomingLesson, $this->crashcourse->lessons->get(2));
        
        $subscription->continue();

        $this->assertEquals($subscription->upcomingLesson, null);
    }

    /** @test */
    public function admins_are_notified_when_a_user_cancels_their_crash_course()
    {
        \Notification::fake();

        $this->crashcourse->subscriptions()->save(create(CrashCourseSubscription::class));

        $this->delete(route('crashcourses.cancel', $this->crashcourse->subscriptions->first()));
        
        \Notification::assertSentTo(
            [$this->admin], CrashCourseCancelledNotification::class
        );
    }

    /** @test */
    public function the_app_knows_how_to_automatically_send_the_correct_lesson()
    {
        \Mail::fake();

        // First lesson
        $this->signUpToCrashCourse($this->crashcourse);

        // Second lesson
        $this->crashcourse->subscriptions->first()->continue();

        $this->assertInstanceOf(CrashCourseLesson::class, $this->crashcourse->subscriptions()->first()->upcomingLesson);

        // Final lesson
        $this->artisan('crashcourse:send');

        \Mail::assertQueued(CrashCourseEmail::class);

        $this->assertNull($this->crashcourse->subscriptions()->first()->upcomingLesson);
    }

    /** @test */
    public function a_feedback_email_is_sent_when_any_course_ends()
    {
        \Mail::fake();

        $this->signUpToCrashCourse($this->crashcourse);
        
        $this->artisan('crashcourse:send');
        
        $this->artisan('crashcourse:send');
        
        $this->artisan('crashcourse:send');         
    
        \Mail::assertQueued(CrashCourseFeedback::class);
    }
}
