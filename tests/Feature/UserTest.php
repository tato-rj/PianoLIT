<?php

namespace Tests\Feature;

use Tests\AppTest;
use Tests\Traits\ManageDatabase;

class UserTest extends AppTest
{
    use ManageDatabase;
    
    /** @test */
    public function users_can_signup_from_the_app()
    {
        $this->register()->assertSuccessful();

        $this->assertDatabaseHas('users', ['first_name' => 'John']); 
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
