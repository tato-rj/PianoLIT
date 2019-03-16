<?php

namespace Tests\Feature;

use Tests\AppTest;

class AdminTest extends AppTest
{
    /** @test */
    public function it_can_log_into_the_admin_page()
    {
        $this->signIn();

        $this->get(route('admin.home'))->assertSuccessful(); 
    }

    /** @test */
    public function unauthorized_users_cannot_log_into_the_admin_section()
    {
        $this->expectException('Illuminate\Auth\AuthenticationException');

        $this->get(route('admin.home')); 
    }
}
