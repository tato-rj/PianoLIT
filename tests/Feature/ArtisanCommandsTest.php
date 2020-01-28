<?php

namespace Tests\Feature;

use Tests\AppTest;
use App\Mail\BirthdaysEmail;
use App\{Composer, EmailList, Subscription, EmailLog};

class ArtisanCommandsTest extends AppTest
{
    /** @test */
    public function birthday_emails_are_sent_it_there_is_a_famous_birthday_email_on_this_day()
    {
        \Mail::fake();

        create(Subscription::class)->join(create(EmailList::class, ['name' => 'Birthdays']));

        $composer = create(Composer::class, ['is_famous' => true, 'date_of_birth' => now()->subYear()]);

        EmailList::birthdays()->send();

        \Mail::assertQueued(BirthdaysEmail::class);
    }

    /** @test */
    public function birthday_emails_create_logs_for_tracking()
    {
        $this->assertFalse(EmailLog::exists());

        create(Subscription::class)->join(create(EmailList::class, ['name' => 'Birthdays']));

        $composer = create(Composer::class, ['is_famous' => true, 'date_of_birth' => now()->subYear()]);

        EmailList::birthdays()->send();
         
        $this->assertTrue(EmailLog::exists());
    }
}
