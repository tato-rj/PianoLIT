<?php

namespace Tests\Feature;

use Tests\AppTest;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Traits\AdminEvents;

class PurchaseTest extends AppTest
{
    use AdminEvents;

    /** @test */
    public function a_purchase_is_saved_when_an_infograph_is_downloaded()
    {
        $this->signIn();

        $infograph = $this->storeInfograph();

        $this->logout();
        $this->signIn($this->user);

        $this->get(route('infographs.download', $infograph->slug));

        $this->assertEquals(1, $infograph->fresh()->purchases->count());
    }

    /** @test */
    public function the_same_infograph_purchase_is_not_saved_twice_in_the_same_instant()
    {
        $this->signIn();

        $infograph = $this->storeInfograph();

        $this->logout();
        $this->signIn($this->user);

        $this->get(route('infographs.download', $infograph->slug));
        $this->get(route('infographs.download', $infograph->slug));

        $this->assertEquals(1, $infograph->fresh()->purchases->count());
        
    }
}
