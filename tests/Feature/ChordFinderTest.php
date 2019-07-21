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
	public function it_does_not_accept_less_than_two_notes()
	{
        $this->expectException('Symfony\Component\HttpKernel\Exception\HttpException');

		$this->finder->take(['c'])->cleaner()->setMinimum(2);		 
	}

    /** @test */
    public function it_capitalizes_all_letters()
    {
        $array = $this->finder->take(['c', 'g', 'e'])->cleaner()->capitalize()->getNotes();

        $this->assertEquals($array, ['C', 'G', 'E']);
    }

    /** @test */
    public function it_fixes_sharp_signs()
    {
        $array = $this->finder->take(['cs', 'gss', 'e'])->cleaner()->fixSharps()->getNotes();

        $this->assertEquals($array, ['c+', 'g++', 'e']);
    }

	/** @test */
	public function it_knows_how_to_discard_any_repeated_notes()
	{
        $arrayOne = $this->finder->take(['c', 'c', 'g', 'e'])->cleaner()->removeDuplicates()->getNotes();
        $arrayTwo = $this->finder->take(['c', 'c2', 'g', 'e'])->cleaner()->removeDuplicates()->getNotes();

        $this->assertEquals($arrayOne, ['c', 'g', 'e']);
        $this->assertEquals($arrayTwo, ['c', 'g', 'e']);
	}

    /** @test */
    public function it_can_sort_the_notes()
    {
        $array = $this->finder->take(['e', 'c+', 'g'])->cleaner()->sort()->getNotes();

        $this->assertEquals($array, ['c+', 'e', 'g']);
    }

    /** @test */
    public function it_separates_a_request_into_enharmonic_sets_if_needed()
    {
        $array = $this->finder->take(['e', 'f-', 'b+', 'c', 'g'])->cleaner()->getNotes();
        
        dd($array);
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
        dd($this->finder->take(['e', 'c', 'g']))->analyse();

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
        $this->assertEquals(
            $this->finder->take(['d', 'c', 'g', 'f'])->analyse()['chords'][2]['info']['full_name'], 
            'G sus4 b7'
        );
    }
}
