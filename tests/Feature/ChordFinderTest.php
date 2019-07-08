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
    public function it_can_identify_any_interval()
    {
        $intervals = [
            ['name' => 'minor 2', 'notes' => ['a', 'b-'], 'octaveUp' => false],
            ['name' => 'major 2', 'notes' => ['b', 'c+'], 'octaveUp' => false],
            ['name' => 'minor 3', 'notes' => ['f', 'a-'], 'octaveUp' => false],
            ['name' => 'major 3', 'notes' => ['f-', 'a-'], 'octaveUp' => false],
            ['name' => 'diminished 4', 'notes' => ['g-', 'c--'], 'octaveUp' => false],
            ['name' => 'perfect 4', 'notes' => ['d+', 'g+'], 'octaveUp' => false],
            ['name' => 'augmented 4', 'notes' => ['e', 'a+'], 'octaveUp' => false],
            ['name' => 'diminished 5', 'notes' => ['f-', 'c--'], 'octaveUp' => false],
            ['name' => 'perfect 5', 'notes' => ['b', 'f+'], 'octaveUp' => false],
            ['name' => 'augmented 5', 'notes' => ['a', 'e+'], 'octaveUp' => false],
            ['name' => 'minor 6', 'notes' => ['c', 'a-'], 'octaveUp' => false],
            ['name' => 'major 6', 'notes' => ['g', 'e'], 'octaveUp' => false],
            ['name' => 'augmented 6', 'notes' => ['g', 'e+'], 'octaveUp' => false],
            ['name' => 'minor 7', 'notes' => ['f', 'e-'], 'octaveUp' => false],
            ['name' => 'major 7', 'notes' => ['c', 'b'], 'octaveUp' => false],
            ['name' => 'minor 9', 'notes' => ['c', 'd-'], 'octaveUp' => true],
            ['name' => 'major 9', 'notes' => ['e', 'f+'], 'octaveUp' => true]
        ];

        foreach ($intervals as $interval) {
            $this->assertEquals(
                $this->finder->interval($interval['notes'][0], $interval['notes'][1], $interval['octaveUp'])->analyse()['full'], 
                $interval['name']
            );            
        }
    }

    /** @test */
    public function it_knows_the_name_of_a_simple_triad()
    {
        $chords = [
            'C major' => ['e', 'c', 'g'],
            'G major' => ['b', 'g', 'd'],
            'F minor' => ['c', 'a-', 'f'],
            'E diminished' => ['e', 'b-', 'g'],
            'C# diminished' => ['e', 'c+', 'g'],
            'Ab augmented' => ['e', 'a-', 'c']
        ];

        foreach ($chords as $chord => $notes) {
            $this->assertEquals(
                $this->finder->take($notes)->analyse()['chords'][0]['info']['full_name'], 
                $chord
            );            
        }
    }

    /** @test */
    public function it_knows_how_to_order_the_dissonances_on_a_multi_note_chord()
    {
        dd($this->finder->take(['e', 'c', 'g'])->analyse());
        $this->assertEquals(
            $this->finder->take(['d', 'c', 'g', 'f'])->analyse()['chords'][2]['info']['full_name'], 
            'G sus4 b7'
        );

        $this->assertEquals(
            $this->finder->take(['a', 'c', 'g', 'f'])->analyse()['chords'][2]['info']['full_name'], 
            'G sus4 b7'
        );
    }
}
