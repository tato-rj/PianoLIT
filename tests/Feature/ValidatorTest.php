<?php

namespace Tests\Feature;

use Tests\AppTest;
use App\Resources\ChordFinder\{ChordFinder, Validator};

class ValidatorTest extends AppTest
{
    public function setUp() : void
    {
        parent::setUp();

        $this->finder = new ChordFinder;
    }

    /** @test */
    public function it_knows_how_to_remove_impossible_chords()
    {
        $raw = $this->finder->take(['e','f-','c','g','a+','b-'])->validate()->get();
        $cleaned = (new Validator($raw))->removeImpossible();

        $this->assertNotEquals(count($raw), count($cleaned));    
    }
}
