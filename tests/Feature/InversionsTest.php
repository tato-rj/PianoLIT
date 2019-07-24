<?php

namespace Tests\Feature;

use Tests\AppTest;
use App\Resources\ChordFinder\Inversion;

class InversionsTest extends AppTest
{
    /** @test */
    public function it_knows_how_to_invert_a_chord()
    {
        $this->assertEquals(['b','c','a'], (new Inversion(['a','b','c']))->get());
    }

    /** @test */
    public function it_returns_all_inversions_of_a_chord()
    {
        $chord = ['c', 'e', 'g'];

        $this->assertEquals((new Inversion($chord))->all(),
            [
                ['chord' => ['c', 'e', 'g']],
                ['chord' => ['e', 'g', 'c']],
                ['chord' => ['g', 'c', 'e']]
            ]
        );
    }
}
