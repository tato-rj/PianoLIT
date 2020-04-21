<?php

namespace Tests\Feature;

use Tests\AppTest;

class WebAppTest extends AppTest
{
    /** @test */
    public function unauthenticated_users_are_not_allowed_in_the_webapp_pages()
    {
        $this->expectException('Illuminate\Auth\AuthenticationException');

        $this->get(route('webapp.discover'));
    }
    /** @test */
    public function authenticated_users_are_allowed_in_the_webapp_pages()
    {
        $this->signIn($this->user);

        $this->get(route('webapp.discover'))->assertStatus(200);
    }
}
