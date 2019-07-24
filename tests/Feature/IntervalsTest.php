<?php

namespace Tests\Feature;

use Tests\AppTest;
use App\Resources\ChordFinder\Interval;

class IntervalsTest extends AppTest
{
    /** @test */
    public function it_knows_how_to_locate_the_distance_between_two_notes_in_letters_and_steps()
    {
        $this->assertEquals(3, (new Interval('c', 'e'))->countLetters());
        $this->assertEquals(4, (new Interval('c', 'e'))->countSteps());
        $this->assertEquals(3, (new Interval('c+', 'e'))->countSteps());

        $this->assertEquals(4, (new Interval('a', 'd'))->countLetters());
        $this->assertEquals(5, (new Interval('a', 'd'))->countSteps());
        $this->assertEquals(4, (new Interval('a', 'd-'))->countSteps());

        $this->assertEquals(6, (new Interval('d', 'b'))->countLetters());
        $this->assertEquals(9, (new Interval('d', 'b'))->countSteps());
        $this->assertEquals(7, (new Interval('d++', 'b'))->countSteps());
    }

    /** @test */
    public function it_knows_if_an_interval_is_valid_to_a_chord()
    {
        $this->assertCount(5, (new Interval('d', 'b'))->read());

        $this->expectException('Symfony\Component\HttpKernel\Exception\HttpException');

        (new Interval('c', 'f+'))->read();
    }

    /** @test */
    public function it_can_identify_the_type()
    {
        $intervals = [
            ['name' => 'minor', 'notes' => ['a', 'b-'], 'absolute' => false],
            ['name' => 'major', 'notes' => ['b', 'c+'], 'absolute' => false],
            ['name' => 'minor', 'notes' => ['f', 'a-'], 'absolute' => false],
            ['name' => 'major', 'notes' => ['f-', 'a-'], 'absolute' => false],
            ['name' => null, 'notes' => ['g-', 'c--'], 'absolute' => false],
            ['name' => 'diminished', 'notes' => ['g-', 'c--'], 'absolute' => true],
            ['name' => 'perfect', 'notes' => ['d+', 'g+'], 'absolute' => false],
            ['name' => null, 'notes' => ['e', 'a+'], 'absolute' => false],
            ['name' => 'augmented', 'notes' => ['e', 'a+'], 'absolute' => true],
            ['name' => 'diminished', 'notes' => ['f-', 'c--'], 'absolute' => false],
            ['name' => 'perfect', 'notes' => ['b', 'f+'], 'absolute' => false],
            ['name' => 'augmented', 'notes' => ['a', 'e+'], 'absolute' => false],
            ['name' => 'minor', 'notes' => ['c', 'a-'], 'absolute' => false],
            ['name' => 'major', 'notes' => ['g', 'e'], 'absolute' => false],
            ['name' => 'minor', 'notes' => ['f', 'e-'], 'absolute' => false],
            ['name' => 'major', 'notes' => ['c', 'b'], 'absolute' => false],
            ['name' => 'minor', 'notes' => ['c', 'd-2'], 'absolute' => false],
            ['name' => 'major', 'notes' => ['e', 'f+2'], 'absolute' => false]
        ];

        foreach ($intervals as $interval) {
            $this->assertEquals(
                (new Interval($interval['notes'][0], $interval['notes'][1], $interval['absolute']))->type(), 
                $interval['name']
            );
        }
    }

    /** @test */
    public function it_considers_the_octave_only_for_the_interval_of_a_second()
    {
        $this->assertEquals(3, (new Interval('d', 'f2'))->countLetters());
        $this->assertEquals(9, (new Interval('d', 'e2'))->countLetters());
    }

    /** @test */
    public function it_give_a_complete_report_for_an_interval()
    {
        $expectedReport = [
            'interval' => 3,
            'steps' => 4,
            'name' => 'major 3',
            'type' => 'major',
            'shorthand' => 'M'
        ];

        $this->assertEquals($expectedReport, (new Interval('d', 'f+'))->read());
    }
}
