<?php

namespace Tests\Feature;

use Tests\AppTest;
use App\{SynthesiaRequest, Piece};
use App\Mail\SynthesiaRequests\{NewRequestEmail, RequestPublishedEmail};
use App\Notifications\SynthesiaRequests\{NewRequestNotification, RequestPublishedNotification};

class SynthesiaRequestTest extends AppTest
{
    /** @test */
    public function a_user_can_request_tutorials()
    {
        $this->post(route('api.users.tutorial-requests.store'), [
            'user_id' => $this->user->id, 
            'piece_id' => $this->piece->id
        ]);

        $this->assertDatabaseHas('synthesia_requests', ['user_id' => $this->user->id, 'piece_id' => $this->piece->id]);
    }

    /** @test */
    public function a_user_cannot_make_a_new_request_while_one_is_pending()
    {
        create(SynthesiaRequest::class, ['user_id' => $this->user->id, 'piece_id' => create(Piece::class)->id]);

        $this->post(route('api.users.tutorial-requests.store'), [
            'user_id' => $this->user->id, 
            'piece_id' => $this->piece->id
        ])->assertStatus(403);
    }

    /** @test */
    public function a_user_cannot_make_two_requests_for_the_same_piece()
    {
        create(SynthesiaRequest::class, ['user_id' => $this->user->id, 'piece_id' => $this->piece->id, 'published_at' => now()]);

        $this->post(route('api.users.tutorial-requests.store'), [
            'user_id' => $this->user->id, 
            'piece_id' => $this->piece->id
        ])->assertStatus(403);
    }

    /** @test */
    public function a_user_and_admins_are_notified_when_a_new_request_is_made()
    {
        \Mail::fake();
        \Notification::fake();

        $this->post(route('api.users.tutorial-requests.store'), [
            'user_id' => $this->user->id, 
            'piece_id' => $this->piece->id
        ]);

        \Mail::assertQueued(NewRequestEmail::class);
        \Notification::assertSentTo($this->admin, NewRequestNotification::class);
    }

    /** @test */
    public function an_admin_can_mark_a_request_as_published()
    {
        $this->signIn();

        $request = create(SynthesiaRequest::class);

        $this->assertFalse($request->isPublished());

        $this->patch(route('admin.synthesia-requests.publish', $request->id));

        $this->assertTrue($request->fresh()->isPublished());
    }

    /** @test */
    public function the_admin_and_user_are_notified_when_a_request_is_published()
    {
        \Mail::fake();
        \Notification::fake();

        $this->signIn();
        
        $this->patch(route('admin.synthesia-requests.publish', create(SynthesiaRequest::class)->id));

        \Mail::assertQueued(RequestPublishedEmail::class);
        \Notification::assertSentTo($this->admin, RequestPublishedNotification::class);
    }
}
