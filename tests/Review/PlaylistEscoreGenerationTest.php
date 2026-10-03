<?php

namespace Tests\Review;

use App\PDF\PDFGenerator;
use Illuminate\Support\Facades\Storage;
use Webklex\PDFMerger\Facades\PDFMergerFacade as PDFMerger;

class PlaylistEscoreGenerationTest extends ReviewTestCase
{
    private function cover()
    {
        Storage::fake('public');
    }

    private function generator()
    {
        return new class extends PDFGenerator {
            protected function frontMatter($entries)
            {
                $fixture = new \FPDF();
                $fixture->AddPage();
                return $fixture->Output('S');
            }
        };
    }

    public function test_each_escore_has_its_own_temporary_cover_and_cleans_up()
    {
        $this->cover();
        $paths = [];
        $merger = \Mockery::mock();
        $merger->shouldReceive('addPDF')->twice()->withArgs(function ($path, $pages) use (&$paths) {
            $this->assertFileExists($path);
            $paths[] = $path;
            return $pages === 'all';
        });
        $merger->shouldReceive('merge')->twice();
        $merger->shouldReceive('setFileName')->twice()->with('escore.pdf')->andReturnSelf();
        PDFMerger::shouldReceive('init')->twice()->andReturn($merger);
        for ($i = 0; $i < 2; $i++) $this->generator()->pieces(collect())->request([])->generate();
        $this->assertNotSame($paths[0], $paths[1]);
        $this->assertSame([], Storage::disk('public')->allFiles('pdf'));
    }

    public function test_a_failed_merge_removes_its_temporary_cover()
    {
        $this->cover();
        $merger = \Mockery::mock();
        $merger->shouldReceive('addPDF')->once();
        $merger->shouldReceive('merge')->once()->andThrow(new \RuntimeException('Broken score'));
        PDFMerger::shouldReceive('init')->once()->andReturn($merger);
        try {
            $this->generator()->pieces(collect())->request([])->generate();
            $this->fail('The merge should fail.');
        } catch (\RuntimeException $exception) {
            $this->assertSame('Broken score', $exception->getMessage());
            $this->assertSame([], Storage::disk('public')->allFiles('pdf'));
        }
    }
}
