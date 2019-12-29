<?php

namespace Tests\Feature;

use App\Timeline;
use Tests\AppTest;

class TimelineTest extends AppTest
{
    /** @test */
    public function an_admin_can_add_an_event_on_the_timeline()
    {
        $this->signIn();

        $timeline = make(Timeline::class);

        $this->post(route('admin.timelines.store'), $timeline->toArray());

        $this->assertDatabaseHas('timelines', ['year' => $timeline->year]);
    }

    /** @test */
    public function the_admin_cannot_add_the_same_event_twice()
    {
        $this->signIn();
        
        $timeline = make(Timeline::class);

        $this->expectException('Illuminate\Database\QueryException');

        $this->post(route('admin.timelines.store'), $timeline->toArray());

        $this->post(route('admin.timelines.store'), $timeline->toArray());
    }

    /** @test */
    public function an_admin_can_edit_a_timeline_event()
    {
        $this->signIn();

        $oldYear = $this->timeline->year;

        $this->patch(route('admin.timelines.update', $this->timeline->id), make(Timeline::class, ['year' => 1000])->toArray());

        $this->assertNotEquals($oldYear, $this->timeline->fresh()->year);
    }

    /** @test */
    public function an_admin_can_remove_an_event_from_the_timeline()
    {
        $this->signIn();
        
        $oldId = $this->timeline->id;

        $this->delete(route('admin.timelines.destroy', $this->timeline->id));

        $this->assertDatabaseMissing('timelines', ['id' => $oldId]);
    }
}
