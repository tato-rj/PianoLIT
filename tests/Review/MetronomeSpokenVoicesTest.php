<?php

namespace Tests\Review;

use App\Services\Metronome\SpokenVoices;

class MetronomeSpokenVoicesTest extends ReviewTestCase
{
    private $directory;

    public function setUp(): void
    {
        parent::setUp();
        $this->directory = sys_get_temp_dir().'/metronome-voices-'.bin2hex(random_bytes(6));
        mkdir($this->directory);
    }

    protected function tearDown(): void
    {
        foreach (glob($this->directory.'/*') as $path) unlink($path);
        rmdir($this->directory);
        parent::tearDown();
    }

    public function test_missing_incomplete_and_invalid_libraries_are_not_advertised()
    {
        $service = new SpokenVoices;
        $this->assertSame([], $service->available($this->directory));
        foreach (['null', 'true', 'bad json', '{"version":1,"beats":3,"voices":[]}'] as $bad) {
            file_put_contents($this->directory.'/manifest.json', $bad);
            $this->assertSame([], $service->available($this->directory));
        }
        $files = [];
        foreach (range(1, 4) as $beat) {
            $files[] = $file = 'classic-'.$beat.'-1234567890abcdef.wav';
            file_put_contents($this->directory.'/'.$file, str_repeat('x', 100));
        }
        $write = function ($files) {
            file_put_contents($this->directory.'/manifest.json', json_encode(['version' => 1, 'beats' => 4, 'voices' => [['id' => 'classic', 'files' => $files]]]));
        };
        $write($files);
        $voices = $service->available($this->directory);
        $this->assertCount(1, $voices);
        $this->assertSame('Classic voice', $voices[0]['label']);
        $this->assertSame('/audio/metronome/spoken/'.$files[0], $voices[0]['files'][0]);
        $write(array_reverse($files));
        $this->assertSame([], $service->available($this->directory));
        $write(['../1.wav', ...array_slice($files, 1)]);
        $this->assertSame([], $service->available($this->directory));
        $write($files);
        unlink($this->directory.'/'.$files[3]);
        $this->assertSame([], $service->available($this->directory));
    }
    public function test_settings_render_focused_pages_and_six_session_effect_choices()
    {
        \Illuminate\Support\Facades\Redis::shouldReceive('get')->andReturn(null);
        $voices = [['id' => 'classic', 'label' => 'Test tone (QA only)', 'files' => array_map(function ($beat) {
            return '/audio/metronome/spoken/classic-'.$beat.'-1234567890abcdef.wav';
        }, range(1, 4))]];
        $this->app->instance(SpokenVoices::class, \Mockery::mock(SpokenVoices::class, function ($mock) use ($voices) {
            $mock->shouldReceive('available')->andReturn($voices);
        }));
        $html = view('metronome.index', ['errors' => new \Illuminate\Support\ViewErrorBag])->render();
        $dom = new \DOMDocument;
        @$dom->loadHTML($html);
        $xpath = new \DOMXPath($dom);
        $this->assertSame(1, $xpath->query('//*[@data-settings-page="home" and not(@hidden)]')->length);
        $this->assertSame(4, $xpath->query('//*[@data-settings-page and @hidden]')->length);
        $this->assertSame(6, $xpath->query('//*[@data-choice-buttons="voice-effect"]//button')->length);
        $this->assertSame(1, $xpath->query('//*[@data-choice="classic" and @aria-pressed="true"]')->length);
        $this->assertSame(1, $xpath->query('//option[@value="spoken:classic"]')->length);
        $this->assertSame(1, $xpath->query('//*[@data-effect-entry and @hidden]')->length);
        $this->assertSame(1, $xpath->query('//*[@id="metronome-settings" and @data-sheet-draggable]/button[@data-sheet-drag-handle and @data-bs-dismiss="offcanvas"]')->length);
        $this->assertSame(1, $xpath->query('//*[@id="metronome-settings"]//button[contains(@class, "bottom-sheet-handle")]')->length);
        $this->assertStringContainsString('metronome-voice-worker.js?id=', $html);
        if ($directory = getenv('METRONOME_PREVIEW_DIR')) {
            if (!is_dir($directory)) mkdir($directory, 0777, true);
            file_put_contents($directory.'/index.html', $html);
        }
    }

}
