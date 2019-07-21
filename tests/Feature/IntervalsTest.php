<?php

namespace Tests\Feature;

use Tests\AppTest;
use App\Resources\ChordFinder\ChordFinder;

class IntervalsTest extends AppTest
{
    public function setUp() : void
    {
        parent::setUp();

        $this->finder = new ChordFinder;
    }

    /** @test */
    public function it_can_identify_any_interval()
    {
        $intervals = [
            ['name' => 'minor 2', 'notes' => ['a', 'b-']],
            ['name' => 'major 2', 'notes' => ['b', 'c+']],
            ['name' => 'minor 3', 'notes' => ['f', 'a-']],
            ['name' => 'major 3', 'notes' => ['f-', 'a-']],
            ['name' => 'diminished 4', 'notes' => ['g-', 'c--']],
            ['name' => 'perfect 4', 'notes' => ['d+', 'g+']],
            ['name' => 'augmented 4', 'notes' => ['e', 'a+']],
            ['name' => 'diminished 5', 'notes' => ['f-', 'c--']],
            ['name' => 'perfect 5', 'notes' => ['b', 'f+']],
            ['name' => 'augmented 5', 'notes' => ['a', 'e+']],
            ['name' => 'minor 6', 'notes' => ['c', 'a-']],
            ['name' => 'major 6', 'notes' => ['g', 'e']],
            ['name' => 'minor 7', 'notes' => ['f', 'e-']],
            ['name' => 'major 7', 'notes' => ['c', 'b']],
            ['name' => 'minor 9', 'notes' => ['c', 'd-2']],
            ['name' => 'major 9', 'notes' => ['e', 'f+2']]
        ];

        foreach ($intervals as $interval) {
            $this->assertEquals(
                $this->finder->worker()->interval($interval['notes'][0], $interval['notes'][1])->analyse()['full'], 
                $interval['name']
            );            
        }
    }
}
