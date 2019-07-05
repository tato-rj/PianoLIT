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
    public function it_can_identify_a_third()
    {
        $first = $this->finder->interval()->find('a', 'c');
        $second = $this->finder->interval()->find('a', 'c+');
        $third = $this->finder->interval()->find('c', 'e-');

        $this->assertEquals($first['full'], 'minor 3');
        $this->assertEquals($second['full'], 'major 3');
        $this->assertEquals($third['full'], 'minor 3');
    }

    /** @test */
    public function it_can_identify_a_fifth()
    {
        // $first = $this->finder->interval()->find('a', 'e');
        // $second = $this->finder->interval()->find('g', 'd');
        // $third = $this->finder->interval()->find('b', 'f');
        $fourth = $this->finder->interval()->find('g', 'a');
        dd($fourth);

        $this->assertEquals($first['full'], 'perfect 5');
        $this->assertEquals($second['full'], 'perfect 5');
        $this->assertEquals($third['full'], 'diminished 5');
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
