<?php

namespace Tests\Review;

use Illuminate\Support\Facades\Redis;
use Stevebauman\Location\Facades\Location;

class ChordFinderPageTest extends ReviewTestCase
{
    public function setUp(): void
    {
        parent::setUp();
        Redis::shouldReceive('get')->andReturn(null);
        Location::shouldReceive('get')->andReturn(false);
    }

    public static function chords()
    {
        return [
            'major' => [['C', 'E', 'G'], ['C'], 2],
            'minor' => [['C', 'E-', 'G'], ['Cm'], 2],
            'dominant seventh' => [['C', 'E', 'G', 'B-'], ['C7', 'Eob6'], 3],
            'major seventh' => [['C', 'E', 'G', 'B'], ['C maj7', 'Emb6'], 3],
            'diminished' => [['B', 'D', 'F'], ['Bo'], 2],
            'half diminished' => [['B', 'D', 'F', 'A'], ['Bø7', 'Dm6'], 3],
            'suspended' => [['C', 'F', 'G'], ['Csus4', 'Fsus2'], 1],
            'augmented' => [['C', 'E', 'G+'], ['Caug'], 2],
            'sharp spelling' => [['F+', 'A+', 'C+'], ['F#'], 2],
            'flat spelling' => [['G-', 'B-', 'D-'], ['Gb'], 2],
            'double sharps' => [['C++', 'E++', 'G++'], ['C##'], 2],
            'double flats' => [['C--', 'E--', 'G--'], ['Cbb'], 2],
            'legacy sharp notation' => [['Cs', 'Es', 'Gs'], ['C#'], 2],
        ];
    }

    /** @dataProvider chords */
    public function test_existing_chord_rankings_and_labels_are_preserved($notes, $expected, $rank)
    {
        $result = $this->getJson(route('tools.chord-finder.analyse', ['notes' => $notes, 'tool' => 'button', 'dev' => 1]))->assertOk()->json();
        $best = collect($result['chords'])->flatMap(function ($chord) { return $chord['inversions']; })
            ->where('ranking', $result['most_relevant'])->map(function ($inversion) { return strip_tags($inversion['label']['full_shorthand']); })->values()->all();
        $this->assertSame($expected, $best);
        $this->assertSame($rank, $result['most_relevant']);
        $this->assertSame('button', $result['tool']);
        $this->assertFalse($result['strict']);
    }

    public function test_page_keeps_canonical_navigation_and_standard_note_entry_controls()
    {
        $response = $this->get(route('tools.chord-finder.index'))->assertOk()->assertSee('Choose your notes')
            ->assertSee('data-url="http://learn.localhost/chord-finder/analyse"', false)
            ->assertSee('href="http://learn.localhost"', false)
            ->assertDontSee('id="chord-newsletter-title"', false);
        $xpath = $this->xpath($response->getContent());
        $this->assertSame(24, $xpath->query('//*[@id="chord-input-piano"]//*[@data-name and @data-octave]')->length);
        $this->assertSame(7, $xpath->query('//button[contains(@class,"chord-note-toggle") and @aria-pressed="false"]')->length);
        $this->assertSame(14, $xpath->query('//button[@data-step and @disabled and @aria-label]')->length);
        $this->assertSame(2, $xpath->query('//*[@id="chord-input-tabs"]//*[@role="tab" and @aria-controls]')->length);
        $this->assertSame(1, $xpath->query('//select[@id="chord-root" and contains(@class,"form-control")]')->length);
        $this->assertSame(1, $xpath->query('//*[@id="chord-results-modal" and @aria-labelledby="chord-results-modal-title"]//*[@id="chord-results"]')->length);
        $this->assertSame(1, $xpath->query('//button[@id="submit-notes" and @aria-haspopup="dialog" and @aria-controls="chord-results-modal"]')->length);
        $this->assertSame(0, $xpath->query('//*[@id="chord-example"]')->length);
    }

    public function test_each_inversion_has_its_own_accessible_explanations()
    {
        $response = $this->get(route('tools.chord-finder.analyse', ['notes' => ['C', 'E', 'G', 'B'], 'tool' => 'button']))->assertOk();
        $xpath = $this->xpath($response->getContent());
        $ids = [];
        foreach ($xpath->query('//*[@id]') as $element) $ids[] = $element->getAttribute('id');
        $this->assertCount(count(array_unique($ids)), $ids, 'Accordion targets must never collide between chord inversions.');
        $controls = $xpath->query('//button[@data-bs-toggle="collapse"]');
        $this->assertGreaterThan(5, $controls->length);
        foreach ($controls as $control) {
            $id = $control->getAttribute('aria-controls');
            $this->assertSame('#'.$id, $control->getAttribute('data-bs-target'));
            $this->assertSame(1, $xpath->query('//*[@id="'.$id.'" and @aria-labelledby]')->length);
            $this->assertSame('false', $control->getAttribute('aria-expanded'), 'Explanations start collapsed.');
        }
        foreach ($xpath->query('//button[@data-notes and @aria-controls]') as $control) {
            $this->assertSame('#'.$control->getAttribute('aria-controls'), $control->getAttribute('href'), 'Keep existing open-page result handlers usable.');
            $this->assertSame(1, $xpath->query('//*[@id="'.$control->getAttribute('aria-controls').'" and @data-chord-explanation]')->length);
            $this->assertTrue($control->hasAttribute('data-chord-play'));
            $this->assertSame(1, $xpath->query('.//*[@data-chord-stop-icon and @hidden]', $control)->length);
            $this->assertSame(1, $xpath->query('.//*[@data-chord-play-icon and not(@hidden)]', $control)->length);
        }
        $this->assertSame(24, $xpath->query('//*[@id="chord-output-piano" and @role="img"]//*[@inert and @aria-hidden="true"]//*[@data-name and @data-octave]')->length);
        $this->assertSame(0, $xpath->query('//*[@id="chord-output-piano"]//*[@tabindex or @role="button"]')->length);
        $this->assertSame(0, $xpath->query('//button[@data-chord-stop]')->length);
        $this->assertSame(0, $xpath->query('//button[@data-chord-play and not(@data-notes)]')->length);
    }

    public function test_missing_intervals_are_explained_without_empty_or_invented_intervals()
    {
        $this->get(route('tools.chord-finder.analyse', ['notes' => ['C', 'E', 'G'], 'root' => 'C', 'tool' => 'button']))
            ->assertOk()->assertSee('has no 7th')->assertSee('neither add nor sus')->assertSee('has no other dissonances')
            ->assertDontSee('a <strong></strong>', false);
        $result = $this->getJson(route('tools.chord-finder.analyse', ['notes' => ['C', 'E', 'G', 'B'], 'root' => 'C', 'tool' => 'button', 'dev' => 1]))->assertOk()->json();
        $this->assertSame('major 7', $result['chords'][0]['inversions'][0]['label']['seventh']);
    }

    public function test_keyboard_root_retains_the_selected_bass_on_both_calculation_hosts()
    {
        foreach (['http://learn.localhost/chord-finder/analyse', 'http://localhost/tools/chord-finder/analyse'] as $url) {
            $query = http_build_query(['notes' => ['E', 'G', 'C'], 'root' => 'C', 'tool' => 'keyboard']);
            $result = $this->getJson($url.'?'.$query.'&dev=1')->assertOk()->json();
            $this->assertTrue($result['strict']);
            $label = $result['chords'][0]['inversions'][0]['label'];
            $this->assertSame('C', $label['root']);
            $this->assertSame('E', $label['bass']);
            $this->assertStringEndsWith('/E', $label['full_shorthand']);
            $this->get($url.'?'.$query)->assertOk()->assertSee('data-bass="E"', false);
        }
    }

    public static function rootPositionVoicings()
    {
        return [
            'G below C and D' => [['G', 'C', 'D'], ['Gsus4', 'Csus2']],
            'C below D and G' => [['C', 'D', 'G'], ['Csus2', 'Gsus4']],
            'sharp bass' => [['F+', 'B', 'C+'], ['F#sus4', 'Bsus2']],
            'legacy sharp bass' => [['Fs', 'B', 'Cs'], ['F#sus4', 'Bsus2']],
            'flat bass' => [['B-', 'E-', 'F'], ['Bbsus4', 'Ebsus2']],
            'double sharp bass' => [['G++', 'C++', 'D++'], ['G##sus4', 'C##sus2']],
            'sixth versus minor seventh' => [['C', 'E', 'G', 'A'], ['C6', 'Am7']],
        ];
    }

    /** @dataProvider rootPositionVoicings */
    public function test_keyboard_best_matches_put_the_root_in_the_bass_first($notes, $expected)
    {
        foreach (['http://learn.localhost/chord-finder/analyse', 'http://localhost/tools/chord-finder/analyse'] as $url) {
            $query = http_build_query(['notes' => $notes, 'tool' => 'keyboard']);
            $result = $this->getJson($url.'?'.$query.'&dev=1')->assertOk()->json();
            $best = collect($result['chords'])->flatMap(function ($chord) { return $chord['inversions']; })
                ->where('ranking', $result['most_relevant'])->map(function ($inversion) { return strip_tags($inversion['label']['full_shorthand']); })->values()->all();
            $this->assertSame($expected, $best, 'The played bass orders equally strong interpretations without removing alternatives.');
            $response = $this->get($url.'?'.$query)->assertOk();
            $buttons = $this->xpath($response->getContent())->query('//button[@data-chord-play]');
            $this->assertSame('Play '.$expected[0].' chord', $buttons->item(0)->getAttribute('aria-label'));
            $this->assertSame('Play '.$expected[1].' chord', $buttons->item(1)->getAttribute('aria-label'));
        }
    }

    public function test_bass_preference_keeps_explicit_roots_unvoiced_input_and_stronger_readings()
    {
        $url = 'http://learn.localhost/chord-finder/analyse?';
        $response = $this->get($url.http_build_query(['notes' => ['G', 'C', 'D'], 'tool' => 'keyboard', 'root' => 'C']))->assertOk();
        $buttons = $this->xpath($response->getContent())->query('//button[@data-chord-play]');
        $this->assertSame('Play Csus2/G chord', $buttons->item(0)->getAttribute('aria-label'));
        $this->assertSame('G', $buttons->item(0)->getAttribute('data-bass'));

        foreach (['button', null] as $tool) {
            $response = $this->get($url.http_build_query(['notes' => ['G', 'C', 'D'], 'tool' => $tool]))->assertOk();
            $buttons = $this->xpath($response->getContent())->query('//button[@data-chord-play]');
            $this->assertSame('Play Csus2 chord', $buttons->item(0)->getAttribute('aria-label'), 'Unvoiced note names must not acquire an assumed bass.');
        }
        foreach ([null => 'Dm7', 0 => 'Dm7', 1 => 'D7'] as $signature => $preferred) {
            $response = $this->get($url.http_build_query(['notes' => ['A', 'C', 'D'], 'tool' => 'keyboard', 'key_signature' => $signature]))->assertOk();
            $buttons = $this->xpath($response->getContent())->query('//button[@data-chord-play]');
            $this->assertSame('Play '.$preferred.' chord', $buttons->item(0)->getAttribute('aria-label'), 'Sparse A-root extensions must not displace the D seventh shell.');
        }
    }

    public function test_ninth_chords_keep_the_extended_note_and_interval()
    {
        $result = $this->getJson(route('tools.chord-finder.analyse', ['notes' => ['C', 'E', 'G', 'B-', 'D'], 'root' => 'C', 'tool' => 'button', 'dev' => 1]))->assertOk()->json();
        $ninth = collect($result['chords'])->flatMap(function ($chord) { return $chord['inversions']; })
            ->first(function ($inversion) { return strip_tags($inversion['label']['full_shorthand']) === 'C9'; });
        $this->assertNotNull($ninth);
        $interval = collect($ninth['intervals'])->firstWhere('interval', 9);
        $this->assertSame('major 9', $interval['name']);
        $this->assertSame(14, $interval['steps']);
        $this->assertSame('d', end($ninth['chord']));
    }

    public static function invalidRequests()
    {
        return [
            'missing' => [[]], 'scalar' => [['notes' => 'CEG']],
            'nested' => [['notes' => [['C'], 'E', 'G']]],
            'associative' => [['notes' => ['first' => 'C', 'second' => 'E', 'third' => 'G'], 'root' => 'C']],
            'invalid pitch' => [['notes' => ['H', 'E', 'G']]],
            'duplicate notes' => [['notes' => ['C', 'c', 'C']]],
            'missing root' => [['notes' => ['C', 'E', 'G'], 'root' => 'D']],
            'invalid tool' => [['notes' => ['C', 'E', 'G'], 'tool' => 'invalid']],
            'array tool' => [['notes' => ['C', 'E', 'G'], 'tool' => ['keyboard']]],
            'too many' => [['notes' => array_fill(0, 13, 'C')]],
        ];
    }

    /** @dataProvider invalidRequests */
    public function test_malformed_requests_return_validation_instead_of_server_errors($parameters)
    {
        $this->withExceptionHandling();
        $this->getJson(route('tools.chord-finder.analyse', $parameters))->assertStatus(422);
    }

    private function xpath($html)
    {
        $dom = new \DOMDocument; @$dom->loadHTML($html);
        return new \DOMXPath($dom);
    }
}
