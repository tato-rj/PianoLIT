<?php

namespace Tests\Feature;

use Tests\AppTest;
use App\Mail\Newsletter\Welcome as WelcomeToNewsletter;

class EmailTest extends AppTest
{
    /** @test */
    public function guests_receive_an_email_upon_subscription()
    {
        \Mail::fake();

        $this->subscribe();
        
        \Mail::assertQueued(WelcomeToNewsletter::class);
    }
}
