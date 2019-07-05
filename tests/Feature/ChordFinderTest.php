<?php

namespace Tests\Feature;

use Tests\AppTest;
use App\Resources\ChordFinder\ChordFinder;

class ChordFinderTest extends AppTest
{
	public function setUp() : void
	{
		parent::setUp();

		$this->finder = new ChordFinder;
	}

	/** @test */
	public function it_does_not_accept_less_than_three_notes()
	{
        $this->expectException('Symfony\Component\HttpKernel\Exception\HttpException');

		$this->finder->take(['c', 'g'])->analyse();		 
	}

	/** @test */
	public function if_knows_how_to_reorder_a_seventh()
	{
        $chord = $this->finder->take(['c', 'g', 'b'])->analyse();

        $this->assertEquals($chord['notes'], ['c', 'g', 'b']);		 
	}

	/** @test */
	public function it_knows_how_to_discard_any_repeated_notes()
	{
        $chord = $this->finder->take(['c', 'c', 'g', 'e'])->analyse();

        $this->assertEquals($chord['notes'], ['c', 'e', 'g']);
	}

    /** @test */
    public function it_can_sort_the_notes()
    {
        $chord = $this->finder->take(['e', 'c+', 'g'])->analyse();

        $this->assertEquals($chord['notes'], ['c+', 'e', 'g']);
    }

    /** @test */
    public function it_can_identify_an_interval()
    {
        $interval = $this->finder->interval()->find('g', 'a', true);

        dd($interval); 
    }

    /** @test */
    public function it_knows_the_name_of_a_simple_triad()
    {
        $CMajorChord = $this->finder->take(['e', 'c', 'g'])->analyse();
        $GMajorChord = $this->finder->take(['b', 'g', 'd'])->analyse();

        $this->assertEquals($CMajorChord['name'], 'C major');
        $this->assertEquals($GMajorChord['name'], 'G major');
    }

    /** @test */
    public function it_knows_the_name_of_a_four_note_chord()
    {
        $GMajorChord = $this->finder->take(['b', 'd', 'g', 'f'])->analyse();
        $FMajorChord = $this->finder->take(['f', 'e', 'c', 'a'])->analyse();

        $this->assertEquals($GMajorChord['name'], 'G major');
        $this->assertEquals($FMajorChord['name'], 'F major');
    }
}
