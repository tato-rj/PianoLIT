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
}
