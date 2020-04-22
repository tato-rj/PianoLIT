<?php

namespace Tests\Feature;

use Tests\AppTest;
use App\Piece;

class WebAppTest extends AppTest
{
    public function setUp() : void
    {
        parent::setUp();
        
        create(Piece::class, ['is_free' => true]);
    }

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

    /** @test */
    public function urls_that_are_not_part_of_the_webapp_are_redirected_to_the_discover_page()
    {
        $this->withExceptionHandling();

        $this->signIn($this->user);
        
        $this->get('http://my.pianolit.com/foo')->assertRedirect(route('webapp.discover'));

        $this->get('http://pianolit.com/foo')->assertStatus(404);
    }
}
