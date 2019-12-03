<?php

namespace Tests\Feature;

use Tests\AppTest;
use Tests\Traits\ManageDatabase;
use App\{User, Subscription};

class UserTest extends AppTest
{
    use ManageDatabase;
    
    /** @test */
    public function users_can_signup_from_the_app()
    {
        $this->register();
        
        $this->assertDatabaseHas('users', ['first_name' => 'John']); 
    }

    /** @test */
    public function users_are_subscribed_after_confirming_their_email()
    {
        \Mail::fake();

        $this->register();
        
        $user = User::latest()->first();

        $this->assertDatabaseMissing('subscriptions', ['email' => $user->email]);

        if ($user->markEmailAsVerified()) {
            event(new \Illuminate\Auth\Events\Verified($user));
        }

        $this->assertDatabaseHas('subscriptions', ['email' => $user->email]);
    }

    /** @test */
    public function a_users_favorites_are_removed_when_the_user_is_deleted()
    {
    	$this->signIn();

    	$userId = $this->user->id;

    	$this->delete(route('admin.users.destroy', $userId));

    	$this->assertDatabaseMissing('favorites', ['user_id' => $userId]);
    }

    /** @test */
    public function a_users_views_are_removed_when_the_user_is_deleted()
    {
    	$this->signIn();
    	
    	$userId = $this->user->id;

    	$this->assertDatabaseHas('piece_views', ['user_id' => $userId]);

    	$this->delete(route('admin.users.destroy', $userId));

    	$this->assertDatabaseMissing('piece_views', ['user_id' => $userId]);
    }
}
