<?php

namespace Tests\Feature;

use Tests\AppTest;
use App\{TutorialRequest, Piece};

class TutorialRequestTest extends AppTest
{
    /** @test */
    public function a_user_can_request_tutorials()
    {
        $this->post(route('api.users.tutorial-requests.store'), [
            'user_id' => $this->user->id, 
            'piece_id' => $this->piece->id
        ]);

        $this->assertDatabaseHas('tutorial_requests', ['user_id' => $this->user->id, 'piece_id' => $this->piece->id]);
    }

    /** @test */
    public function a_user_cannot_make_a_new_request_while_it_as_one_pending()
    {
        $this->expectException('Illuminate\Auth\Access\AuthorizationException');

        create(TutorialRequest::class, ['user_id' => $this->user->id, 'piece_id' => create(Piece::class)->id]);

        $this->post(route('api.users.tutorial-requests.store'), [
            'user_id' => $this->user->id, 
            'piece_id' => $this->piece->id
        ]);
    }

    /** @test */
    public function a_user_cannot_make_two_requests_for_the_same_piece()
    {
        $this->expectException('Illuminate\Auth\Access\AuthorizationException');

        create(TutorialRequest::class, ['user_id' => $this->user->id, 'piece_id' => $this->piece->id, 'published_at' => now()]);

        $this->post(route('api.users.tutorial-requests.store'), [
            'user_id' => $this->user->id, 
            'piece_id' => $this->piece->id
        ]);
    }
}
