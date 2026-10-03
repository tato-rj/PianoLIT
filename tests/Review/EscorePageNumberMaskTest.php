<?php

namespace Tests\Review;

use App\PDF\EscoreMerger;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Facades\Storage;
use setasign\Fpdi\Fpdi;
use setasign\Fpdi\PdfParser\StreamReader;

class EscorePageNumberMaskTest extends ReviewTestCase
{
    public static function paperSizes()
    {
        $cases = [];
        foreach (['letter' => [215.9, 279.4], 'a4' => [210, 297], 'landscape' => [279.4, 215.9]] as $source => $dimensions) {
            foreach (['letter', 'a4'] as $output) {
                foreach ([true, false] as $numbered) {
                    $cases[$source.'-'.$output.'-'.($numbered ? 'numbered' : 'original')] = [$source, $dimensions, $output, $numbered];
                }
            }
        }
        return $cases;
    }

    /** @dataProvider paperSizes */
    public function test_masks_overlay_only_score_templates_and_follow_the_imported_page($sourceName, $dimensions, $outputSize, $numbered)
    {
        Storage::fake('public');
        $disk = Storage::disk('public');
        [$sourceWidth, $sourceHeight] = $dimensions;
        $front = new \FPDF('P', 'mm', [215.9, 279.4]);
        foreach (['Cover', 'Table of contents', 'About this edition'] as $title) {
            $front->AddPage();
            $front->SetFont('Helvetica', '', 12);
            $front->Text(22, 16, 'Front matter');
            $front->Text(100, 35, $title);
        }
        $disk->put('front.pdf', $front->Output('S'));

        // The same corner marks are encoded as text, paths, and a full-page scan.
        $score = new \FPDF($sourceWidth > $sourceHeight ? 'L' : 'P', 'mm', $dimensions);
        foreach (['text', 'vector', 'scan'] as $encoding) {
            $score->AddPage();
            if ($encoding === 'scan') {
                $image = imagecreatetruecolor((int) round($sourceWidth * 3), (int) round($sourceHeight * 3));
                $white = imagecolorallocate($image, 255, 255, 255);
                $black = imagecolorallocate($image, 0, 0, 0);
                imagefill($image, 0, 0, $white);
                foreach ([22, $sourceWidth - 26] as $x) {
                    imagestring($image, 5, (int) round($x * 3), 36, '7', $black);
                }
                imagestring($image, 5, (int) round($sourceWidth * 1.5), 84, 'Header', $black);
                imageline($image, 60, 120, (int) round(($sourceWidth - 20) * 3), 120, $black);
                imagepng($image, $disk->path('scan.png'));
                imagedestroy($image);
                $score->Image($disk->path('scan.png'), 0, 0, $sourceWidth, $sourceHeight);
            } else {
                $score->SetFont('Helvetica', '', 12);
                if ($encoding === 'text') {
                    $score->Text(22, 16, '7');
                    $score->Text($sourceWidth - 26, 16, '8');
                } else {
                    $score->SetFillColor(0, 0, 0);
                    $score->Rect(22, 12, 3, 4, 'F');
                    $score->Rect($sourceWidth - 26, 12, 3, 4, 'F');
                }
                $score->Text($sourceWidth / 2, 30, 'Header');
                $score->Line(20, 40, $sourceWidth - 20, 40);
            }
        }
        $disk->put('score.pdf', $score->Output('S'));
        $sourceHashes = [hash_file('sha256', $disk->path('front.pdf')), hash_file('sha256', $disk->path('score.pdf'))];
        $merger = (new EscoreMerger(app(Filesystem::class)))->settings([
            'page_size' => $outputSize, 'color' => '#00a2ff',
            'page_numbers' => $numbered, 'blank_pages' => true,
        ]);
        $merger->addPDF($disk->path('front.pdf'), 'all');
        $merger->addPDF($disk->path('score.pdf'), 'all');
        $merger->addPDF($disk->path('score.pdf'), 'all');
        $merger->merge();
        $output = $merger->output();
        $reader = new class extends Fpdi {
            public function pageStream($page)
            {
                return $this->getPdfReader($this->currentReaderId)->getPage($page)->getContentStream();
            }
        };
        $this->assertSame(10, $reader->setSourceFile(StreamReader::createByString($output)));
        [$width, $height] = $outputSize === 'a4' ? [210, 297] : [215.9, 279.4];
        $scale = min(($width - 16) / $sourceWidth, ($height - 18) / $sourceHeight);
        $pageX = ($width - $sourceWidth * $scale) / 2;
        $pageY = ($height - $sourceHeight * $scale) / 2;
        for ($page = 1; $page <= 10; $page++) {
            $stream = $reader->pageStream($page);
            preg_match_all('/(-?[\d.]+) (-?[\d.]+) (-?[\d.]+) (-?[\d.]+) re f/', $stream, $rectangles, PREG_SET_ORDER);
            $isScore = $page >= 4 && $page !== 7;
            $expected = $page === 1 ? 1 : ($isScore && $numbered ? 2 : 0);
            $this->assertCount($expected, $rectangles, 'Unexpected overlay on page '.$page);
            if ($isScore && $numbered) {
                $this->assertGreaterThan(strpos($stream, ' Do'), strpos($stream, ' re f'), 'Masks must be painted over the imported page');
                $this->assertStringContainsString('1.000 1.000 1.000 rg', $stream);
                foreach ($rectangles as $corner => $rectangle) {
                    // Read the actual PDF rectangles (points, bottom-origin) back
                    // into source mm. The neighboring header starts below 20 mm.
                    $x = ((float) $rectangle[1] / (72 / 25.4) - $pageX) / $scale;
                    $y = ($height - (float) $rectangle[2] / (72 / 25.4) - $pageY) / $scale;
                    $w = (float) $rectangle[3] / (72 / 25.4) / $scale;
                    $h = -(float) $rectangle[4] / (72 / 25.4) / $scale;
                    $markX = $corner === 0 ? 22 : $sourceWidth - 26;
                    $this->assertLessThanOrEqual($markX, $x);
                    $this->assertGreaterThanOrEqual($markX + 3, $x + $w);
                    $this->assertLessThanOrEqual(12, $y);
                    $this->assertGreaterThanOrEqual(16, $y + $h);
                    $this->assertLessThanOrEqual(20.01, $y + $h);
                    $this->assertLessThanOrEqual(20.01, $w);
                }
                $this->assertGreaterThan(strrpos($stream, ' re f'), strpos($stream, '('.$page.') Tj'));
            }
            if ($page > 1) {
                $this->assertSame($numbered, strpos($stream, '('.$page.') Tj') !== false, 'Collection folio on page '.$page);
            }
        }
        $this->assertSame($sourceHashes, [hash_file('sha256', $disk->path('front.pdf')), hash_file('sha256', $disk->path('score.pdf'))]);
        if ($directory = getenv('ESCORE_MASK_PREVIEW_DIR')) {
            file_put_contents($directory.'/'.$sourceName.'-'.$outputSize.'-'.($numbered ? 'numbered' : 'original').'.pdf', $output);
        }
    }
}
