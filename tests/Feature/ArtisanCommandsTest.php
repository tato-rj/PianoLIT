<?php

namespace Tests\Feature;

use Tests\AppTest;
use App\Mail\Timeline\OnThisDay;
use App\{Composer, EmailList, Subscription};

class ArtisanCommandsTest extends AppTest
{
    /** @test */
    public function birthday_emails_are_sent_it_there_is_a_famous_birthday_email_on_this_day()
    {
        \Mail::fake();

        create(Subscription::class)->join(create(EmailList::class, ['name' => 'Birthdays']));

        $composer = create(Composer::class, ['is_famous' => true, 'date_of_birth' => now()->subYear()]);

        foreach (EmailList::birthdays()->subscribers as $subscriber) {
            \Mail::to($subscriber->email)->send(new OnThisDay($composer, $subscriber));
        }

        \Mail::assertQueued(OnThisDay::class);
    }
}
