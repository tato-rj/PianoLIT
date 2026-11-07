<?php

namespace Tests\Review;

use Illuminate\Support\Facades\Redis;
use Stevebauman\Location\Facades\Location;

class ChordFinderOmissionsTest extends ReviewTestCase
{
    public function setUp(): void
    {
        parent::setUp();
        Redis::shouldReceive('get')->andReturn(null);
        Location::shouldReceive('get')->andReturn(false);
    }

    private function analyse(array $notes, array $options = [], string $url = 'http://learn.localhost/chord-finder/analyse'): array
    {
        return $this->getJson($url.'?'.http_build_query(array_merge(['notes' => $notes, 'tool' => 'button', 'dev' => 1], $options)))
            ->assertOk()->json();
    }

    private function candidates(array $result)
    {
        return collect($result['chords'])->flatMap(function ($chord) { return $chord['inversions']; })
            ->keyBy(function ($inversion) { return strip_tags($inversion['label']['full_shorthand']); });
    }

    public function test_missing_third_sevenths_outrank_sparse_extensions_without_inventing_played_notes()
    {
        $result = $this->analyse(['A', 'C', 'D']);
        $chords = $this->candidates($result);
        $this->assertSame($result['most_relevant'], $chords['Dm7']['ranking']);
        $this->assertSame($chords['Dm7']['ranking'], $chords['D7']['ranking']);
        foreach (['Am11', 'Amadd4'] as $name) $this->assertLessThan($result['most_relevant'], $chords[$name]['ranking']);
        foreach (['Dm7' => 'F', 'D7' => 'F#'] as $name => $omitted) {
            $chord = $chords[$name];
            $this->assertSame(['d', 'a', 'c'], $chord['chord']);
            $this->assertSame(['perfect 5', 'minor 7'], array_column($chord['intervals'], 'name'));
            $this->assertSame([['degree' => 3, 'note' => $omitted]], $chord['label']['omissions']);
            $this->assertTrue($chord['label']['quality_ambiguous']);
            $this->assertArrayNotHasKey('inferred_from_key', $chord['label']);
        }
        $ids = $chords->pluck('id')->all();
        $this->assertCount(count(array_unique($ids)), $ids);
        $this->get('http://learn.localhost/chord-finder/analyse?'.http_build_query(['notes' => ['A', 'C', 'D'], 'tool' => 'button']))
            ->assertOk()->assertSee('F (3rd) omitted.')->assertSee('F# (3rd) omitted.')
            ->assertSee('Without the 3rd')->assertDontSee('so we treat it as having a major 3rd');
    }

    public static function signatures(): array
    {
        return [
            'no accidentals is specified context' => [0, 'Dm7', 'D7', 'F'],
            'one sharp' => [1, 'D7', 'Dm7', 'F#'],
            'one flat' => [-1, 'Dm7', 'D7', 'F'],
            'seven sharps' => [7, 'D7', 'Dm7', 'F#'],
        ];
    }

    /** @dataProvider signatures */
    public function test_key_signature_resolves_the_missing_third_only($signature, $preferred, $alternative, $note)
    {
        foreach (['http://learn.localhost/chord-finder/analyse', 'http://localhost/tools/chord-finder/analyse'] as $url) {
            $result = $this->analyse(['A', 'C', 'D'], ['key_signature' => $signature], $url);
            $chords = $this->candidates($result);
            $this->assertSame($result['most_relevant'], $chords[$preferred]['ranking']);
            $this->assertLessThan($chords[$preferred]['ranking'], $chords[$alternative]['ranking']);
            $this->assertSame($note, $chords[$preferred]['label']['omissions'][0]['note']);
            $this->assertNotEmpty($chords[$preferred]['label']['inferred_from_key']);
            $this->assertSame(['d', 'a', 'c'], $chords[$preferred]['chord']);
        }
    }

    public static function shells(): array
    {
        return [
            [['C', 'G', 'B-'], 'Cm7', 'C7', 'Eb', 'E'],
            [['E', 'B', 'D'], 'Em7', 'E7', 'G', 'G#'],
            [['F+', 'C+', 'E'], 'F#m7', 'F#7', 'A', 'A#'],
            [['B-', 'F', 'A-'], 'Bbm7', 'Bb7', 'Db', 'D'],
            [['C-', 'G-', 'B--'], 'Cbm7', 'Cb7', 'Ebb', 'Eb'],
            [['C+', 'G+', 'B'], 'C#m7', 'C#7', 'E', 'E#'],
            [['D', 'A', 'C+'], 'Dm maj7', 'D maj7', 'F', 'F#'],
        ];
    }

    /** @dataProvider shells */
    public function test_shell_recognition_transposes_and_preserves_spelling($notes, $minor, $major, $minorThird, $majorThird)
    {
        $chords = $this->candidates($this->analyse($notes));
        $this->assertSame($minorThird, $chords[$minor]['label']['omissions'][0]['note']);
        $this->assertSame($majorThird, $chords[$major]['label']['omissions'][0]['note']);
        $this->assertSame($chords[$minor]['ranking'], $chords[$major]['ranking']);
    }

    public function test_signature_does_not_override_an_actual_third_or_guess_an_unavailable_third()
    {
        $chords = $this->candidates($this->analyse(['D', 'F+', 'A', 'C'], ['key_signature' => 0]));
        $this->assertArrayHasKey('D7', $chords->all());
        $this->assertArrayNotHasKey('Dm7', $chords->all());
        $chords = $this->candidates($this->analyse(['D', 'A', 'C'], ['key_signature' => -7]));
        $this->assertSame($chords['Dm7']['ranking'], $chords['D7']['ranking'], 'Cb major contains Fb, so neither F nor F# is inferred.');
    }

    public function test_sevenths_missing_the_fifth_are_supported_but_suspended_chords_stay_suspended()
    {
        foreach ([['C', 'E', 'B-'], ['C', 'E-', 'B-']] as $notes) {
            $result = $this->analyse($notes);
            $name = $notes[1] === 'E' ? 'C7' : 'Cm7';
            $chord = $this->candidates($result)[$name];
            $this->assertSame($result['most_relevant'], $chord['ranking']);
            $this->assertSame([['degree' => 5, 'note' => 'G']], $chord['label']['omissions']);
        }
        $chords = $this->candidates($this->analyse(['C', 'F', 'G', 'B-'], ['root' => 'C']));
        $this->assertArrayHasKey('C7sus4', $chords->all());
        $this->assertArrayNotHasKey('Cm7', $chords->all());
    }

    public function test_explicit_root_and_bass_keep_playback_and_root_intervals_separate()
    {
        $chords = $this->candidates($this->analyse(['A', 'C', 'D'], ['root' => 'D', 'tool' => 'keyboard', 'key_signature' => 0]));
        $this->assertArrayHasKey('Dm7/A', $chords->all());
        $this->assertArrayNotHasKey('Am11', $chords->all());
        $this->assertSame(['a', 'c', 'd'], $chords['Dm7/A']['chord']);
        $this->assertSame(['d', 'a', 'c'], $chords['Dm7/A']['analysis_chord']);
        $this->get('http://learn.localhost/chord-finder/analyse?'.http_build_query(['notes' => ['A', 'C', 'D'], 'root' => 'D', 'tool' => 'keyboard', 'key_signature' => 0]))
            ->assertOk()->assertSee('D → A')->assertSee('D → C')->assertSee('belongs to the');
    }

    public function test_key_signature_input_is_bounded()
    {
        $this->withExceptionHandling();
        foreach ([8, -8, 'D major', ['0'], '1.5'] as $key) {
            $this->getJson('http://learn.localhost/chord-finder/analyse?'.http_build_query(['notes' => ['A', 'C', 'D'], 'key_signature' => $key]))->assertStatus(422);
        }
    }
}
