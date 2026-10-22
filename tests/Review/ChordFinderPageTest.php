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
            ->assertSee('data-url="http://learn.localhost/subscriptions/modal"', false);
        $xpath = $this->xpath($response->getContent());
        $this->assertSame(24, $xpath->query('//*[@id="chord-input-piano"]//*[@data-name and @data-octave]')->length);
        $this->assertSame(7, $xpath->query('//button[contains(@class,"chord-note-toggle") and @aria-pressed="false"]')->length);
        $this->assertSame(14, $xpath->query('//button[@data-step and @disabled and @aria-label]')->length);
        $this->assertSame(2, $xpath->query('//*[@id="chord-input-tabs"]//*[@role="tab" and @aria-controls]')->length);
        $this->assertSame(1, $xpath->query('//select[@id="chord-root" and contains(@class,"form-control")]')->length);
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
        }
        foreach ($xpath->query('//button[@data-notes and @aria-controls]') as $control) {
            $this->assertSame('#'.$control->getAttribute('aria-controls'), $control->getAttribute('href'), 'Keep existing open-page result handlers usable.');
            $this->assertSame(1, $xpath->query('//*[@id="'.$control->getAttribute('aria-controls').'" and contains(@class,"chord-info")]')->length);
        }
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
