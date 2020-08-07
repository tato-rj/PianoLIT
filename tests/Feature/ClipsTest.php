<?php

namespace Tests\Feature;

use Tests\AppTest;
use App\Clip;

class ClipsTest extends AppTest
{
    /** @test */
    public function an_admin_can_create_a_clip()
    {
        $this->signIn();

        $clip = make(Clip::class);

        $this->post(route('admin.clips.store', $clip->toArray()));

        $this->assertDatabaseHas('clips', ['url' => $clip->url]);
    }

    /** @test */
    public function an_admin_can_update_a_clip()
    {
        $this->signIn();
        
        $old = create(Clip::class);
        $new = make(Clip::class);

        $this->patch(route('admin.clips.update', [
            'clip' => $old, 
            'name' => $new->name,
            'url' => $new->url]));

        $this->assertDatabaseHas('clips', ['url' => $new->url]);
        $this->assertDatabaseMissing('clips', ['url' => $old->url]);
    }

    /** @test */
    public function an_admin_can_delete_a_clip()
    {
        $this->signIn();

        $clip = create(Clip::class);

        $this->delete(route('admin.clips.destroy', $clip));

        $this->assertDatabaseMissing('clips', ['url' => $clip->url]);
    }
}
