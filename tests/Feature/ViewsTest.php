<?php

namespace Tests\Feature;

use Tests\AppTest;
use App\User;

class ViewsTest extends AppTest
{
    /** @test */
    public function a_view_is_recorded_when_the_user_selects_a_piece_in_the_app()
    {
        $this->piece->views()->delete();

        $this->post(route('api.pieces.views.store'), [
            'user_id' => $this->user->id,
            'piece_id' => $this->piece->id]);

        $this->assertEquals($this->piece->views->count(), 1);
    }

    /** @test */
    public function a_piece_keeps_track_of_both_global_and_unique_views()
    {
    	$this->piece->views()->create(['user_id' => $this->user->id]);

    	$this->assertEquals($this->piece->views->count(), 2);

    	$this->assertEquals($this->piece->views->unique('user_id')->count(), 1);
    }
}
