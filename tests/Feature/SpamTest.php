<?php

namespace Tests\Feature;

use Tests\AppTest;
use App\{Subscription, User};
use Tests\Traits\ManageDatabase;

class SpamTest extends AppTest
{
    use ManageDatabase;

    /** @test */
    public function if_the_hidden_input_is_filled_it_means_the_guest_is_a_bot_so_the_subscription_is_denied()
    {
        $this->expectException('Illuminate\Auth\Access\AuthorizationException');
        
        $email = make(Subscription::class)->email;

        $this->subscribe($email, $bot = 'is bot');

        $this->assertDatabaseMissing('subscriptions', ['email' => $email]);    
    }

    /** @test */
    public function the_same_guest_cannot_subscribe_more_than_twice_per_minute()
    {
        $this->expectException('Illuminate\Http\Exceptions\ThrottleRequestsException');
        
        $this->subscribe();
        $this->subscribe();
        $this->subscribe();
    }

    /** @test */
    public function the_subscriber_must_take_at_least_3_seconds_to_fill_the_form_and_submit()
    {
        $this->expectException('Illuminate\Auth\Access\AuthorizationException');

        $email = make(Subscription::class)->email;

        $this->subscribe($email, $bot = false, $wait = false);
    }

    /** @test */
    public function users_named_james_smith_cannot_register_on_the_webapp()
    {
        $this->register(make(User::class, ['first_name' => 'James', 'last_name' => 'Smith']))
             ->assertSessionHas('error');
    }
}
