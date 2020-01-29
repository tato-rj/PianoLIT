<?php

namespace Tests\Feature;

use Tests\AppTest;
use App\Mail\BirthdaysEmail;
use App\Mail\Admin\AdminReport;
use App\{Composer, EmailList, Subscription, EmailLog, User};
use Illuminate\Auth\Notifications\VerifyEmail;

class ArtisanCommandsTest extends AppTest
{
    /** @test */
    public function birthday_emails_are_sent_if_there_is_a_famous_birthday_email_on_this_day()
    {
        \Mail::fake();

        create(Subscription::class)->join(create(EmailList::class, ['name' => 'Birthdays']));

        $composer = create(Composer::class, ['is_famous' => true, 'date_of_birth' => now()->subYear()]);

        $this->artisan('pianolit:timeline-email');

        \Mail::assertQueued(BirthdaysEmail::class);
    }

    /** @test */
    public function birthday_emails_create_logs_for_tracking()
    {
        $this->assertFalse(EmailLog::exists());

        create(Subscription::class)->join(create(EmailList::class, ['name' => 'Birthdays']));

        $composer = create(Composer::class, ['is_famous' => true, 'date_of_birth' => now()->subYear()]);

        $this->artisan('pianolit:timeline-email');
         
        $this->assertTrue(EmailLog::exists());
    }

    /** @test */
    public function admins_receive_weekly_report_emails()
    {
        \Mail::fake();

        $this->artisan('pianolit:admin-report');

        \Mail::assertQueued(AdminReport::class);         
    }

    /** @test */
    public function admin_report_emails_do_not_create_logs_for_tracking()
    {
        $this->assertFalse(EmailLog::exists());

        $this->artisan('pianolit:admin-report');
         
        $this->assertFalse(EmailLog::exists());
    }

    /** @test */
    public function unconfirmed_users_receive_emails_asking_them_to_confirm_their_email()
    {
        \Notification::fake();

        $user = User::unconfirmed()->first();

        $this->artisan('pianolit:unconfirmed-emails');

        \Notification::assertSentTo($user, VerifyEmail::class);
    }
}
