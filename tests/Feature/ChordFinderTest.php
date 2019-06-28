<?php

namespace Tests\Feature;

use Tests\AppTest;
use App\Resources\ChordFinder;

class ChordFinderTest extends AppTest
{
    /** @test */
    public function it_can_name_a_chord_based_on_a_complete_input()
    {
        $finder = new ChordFinder;
        
        $orderedInput = ['c', 'e', 'g'];

        $this->assertEquals($finder->get($orderedInput)['full_name'], 'C major');
    }
}
