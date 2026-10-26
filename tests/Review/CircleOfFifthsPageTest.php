<?php

namespace Tests\Review;

use Illuminate\Support\Facades\Redis;
use Stevebauman\Location\Facades\Location;

class CircleOfFifthsPageTest extends ReviewTestCase
{
    public function setUp(): void
    {
        parent::setUp();
        Redis::shouldReceive('get')->andReturn(null);
        Location::shouldReceive('get')->andReturn(false);
    }

    private function xpath($html)
    {
        $dom = new \DOMDocument; @$dom->loadHTML($html);
        return new \DOMXPath($dom);
    }

    public function test_circle_renders_the_complete_existing_key_data_with_accessible_controls_and_shared_tabs()
    {
        $response = $this->get(route('tools.circle-of-fifths'))->assertOk()->assertSee('Circle of Fifths')
            ->assertSee('Hear the scale')->assertSee('Explore PianoLIT')
            ->assertSee('<link rel="canonical" href="http://learn.localhost/circle-of-fifths"', false)
            ->assertSee('data-url="http://learn.localhost/subscriptions/modal"', false);
        $xpath = $this->xpath($response->getContent());
        $keys = $xpath->query('//*[@id="letters"]/*[@key-major]');
        $this->assertSame(12, $keys->length);
        $this->assertSame(1, $xpath->query('//*[@id="letters"]/*[@class="key" and @id="key-c"]')->length);
        $this->assertSame(1, $xpath->query('//*[@id="letters"]/*[@aria-pressed="true" and @tabindex="0" and @id="key-c"]')->length);
        foreach ($keys as $key) {
            $this->assertSame('button', $key->getAttribute('role'));
            $this->assertNotEmpty($key->getAttribute('aria-label'));
            foreach (['left', 'right'] as $direction) {
                $id = substr($key->getAttribute('control-'.$direction), 1);
                $this->assertSame(1, $xpath->query('//*[@id="'.$id.'"]')->length);
            }
            foreach (['major', 'minor', 'enharmonic'] as $mode) {
                $this->assertCount(7, json_decode($key->getAttribute('key-'.$mode.'-scale'), true));
                $this->assertCount(7, json_decode($key->getAttribute('key-'.$mode.'-roman'), true));
                foreach (['tonic', 'dominant', 'subdominant'] as $group) {
                    $this->assertCount(3, json_decode($key->getAttribute('key-'.$mode.'-'.$group), true));
                }
            }
        }
        $c = $xpath->query('//*[@id="key-c"]')->item(0);
        $this->assertSame('#key-g', $c->getAttribute('control-left'));
        $this->assertSame('#key-f', $c->getAttribute('control-right'));
        $this->assertSame(['c', 'd', 'e', 'f', 'g', 'a', 'b'], json_decode($c->getAttribute('key-major-scale'), true));
        $this->assertSame(['a', 'b', 'c', 'd', 'e', 'f', 'g'], json_decode($c->getAttribute('key-minor-scale'), true));
        $this->assertSame(['E major*', 'G# dim*', 'C aug*'], json_decode($c->getAttribute('key-minor-dominant'), true));
        $this->assertSame(1, $xpath->query('//*[@id="mode-tabs" and @data-tab-navigation and contains(@class, "tab-navigation--segmented")]')->length);
        foreach (['major', 'minor', 'enharmonic'] as $mode) {
            $this->assertSame(1, $xpath->query('//*[@id="'.$mode.'-tab" and @data-name="'.$mode.'" and @aria-controls="mode-'.$mode.'"]')->length);
            $this->assertSame(1, $xpath->query('//*[@id="mode-'.$mode.'" and @aria-labelledby="'.$mode.'-tab"]')->length);
        }
        $this->assertSame(1, $xpath->query('//button[@direction="left" and @aria-label="Next key"]')->length);
        $this->assertSame(1, $xpath->query('//button[@direction="right" and @aria-label="Previous key"]')->length);
        $this->assertSame(1, $xpath->query('//button[@id="circle-reset" and @type="button"]')->length);
        $this->assertSame(1, $xpath->query('//button[@id="circle-play-scale" and @aria-pressed="false"]')->length);
        $this->assertSame(1, $xpath->query('//nav[@aria-label="Breadcrumb"]//a[@href="'.route('learn.home').'"]')->length);
        foreach (['key', 'key-signature', 'relative-key', 'closely-related-keys', 'functional-harmony'] as $topic) {
            $this->assertSame(1, $xpath->query('//*[@id="modal-'.$topic.'"]')->length);
            $this->assertSame(3, $xpath->query('//button[@data-bs-target="#modal-'.$topic.'" and @aria-label]')->length);
        }
    }

    public function test_responsive_piano_keeps_the_same_note_identity_and_leaves_existing_instances_fixed()
    {
        $octaves = [3 => [], 4 => []];
        $default = $this->xpath(view('components.music.piano.keyboard', ['centered' => true, 'octaves' => $octaves])->render());
        $responsive = $this->xpath(view('components.music.piano.keyboard', ['centered' => true, 'octaves' => $octaves, 'responsive' => true])->render());
        $this->assertSame(0, $default->query('//*[contains(@class, "keyboard-responsive")]')->length);
        $this->assertSame(1, $responsive->query('//*[contains(@class, "keyboard-responsive") and contains(@style, "--piano-key-count: 14")]')->length);
        $oldKeys = $default->query('//*[@data-name and @data-octave and @data-names]');
        $newKeys = $responsive->query('//*[@data-name and @data-octave and @data-names]');
        $this->assertSame(24, $oldKeys->length);
        $this->assertSame($oldKeys->length, $newKeys->length);
        foreach ($oldKeys as $index => $key) {
            foreach (['data-name', 'data-octave', 'data-names'] as $attribute) {
                $this->assertSame($key->getAttribute($attribute), $newKeys->item($index)->getAttribute($attribute));
            }
        }
        $this->assertSame(14, $default->query('//*[contains(@class, "keyboard-white-key") and contains(@style, "width: 40px")]')->length);
        $this->assertSame(10, $default->query('//*[contains(@class, "keyboard-black-key") and contains(@style, "width: 36px") and contains(@style, "right: -21.3px")]')->length);
    }
}
