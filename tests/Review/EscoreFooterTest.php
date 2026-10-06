<?php

namespace Tests\Review;

use App\{Composer, Piece};
use App\PDF\PDFGenerator;
use Illuminate\Support\Facades\Storage;
use setasign\Fpdi\Fpdi;
use setasign\Fpdi\PdfParser\StreamReader;
use setasign\Fpdi\PdfParser\Type\PdfType;

class EscoreFooterTest extends ReviewTestCase
{
    public static function layouts()
    {
        $cases = [];
        foreach (['letter' => [215.9, 279.4], 'a4' => [210, 297], 'landscape' => [279.4, 215.9]] as $source => $dimensions) {
            foreach ([null, 'letter', 'a4'] as $output) {
                foreach ([false, true] as $numbers) $cases[] = [$source, $dimensions, $output, $numbers];
            }
        }
        return $cases;
    }

    /** @dataProvider layouts */
    public function test_original_footers_are_masked_and_export_footer_has_one_position($sourceName, $dimensions, $outputSize, $numbers)
    {
        Storage::fake('public');
        $disk = Storage::disk('public');
        [$sourceWidth, $sourceHeight] = $dimensions;
        $score = new \FPDF($sourceWidth > $sourceHeight ? 'L' : 'P', 'mm', $dimensions);
        foreach (['text', 'vector', 'scan'] as $encoding) {
            $score->AddPage();
            $score->SetFont('Helvetica', '', 10);
            $score->Text(20, 30, 'Score '.$encoding);
            $score->Text(20, $sourceHeight - 22, 'Final staff');
            $score->Line(20, $sourceHeight - 20, $sourceWidth - 20, $sourceHeight - 20);
            if ($encoding === 'text') {
                $score->Text(($sourceWidth - $score->GetStringWidth('PianoLIT eScore')) / 2, $sourceHeight - 10, 'PianoLIT eScore');
            } elseif ($encoding === 'vector') {
                $score->Rect($sourceWidth / 2 - 20, $sourceHeight - 13, 40, 3, 'F');
            } else {
                $image = imagecreatetruecolor(210, 30);
                imagefill($image, 0, 0, imagecolorallocate($image, 255, 255, 255));
                imagestring($image, 5, 30, 8, 'PianoLIT eScore', imagecolorallocate($image, 0, 0, 0));
                imagepng($image, $disk->path('footer.png')); imagedestroy($image);
                $score->Image($disk->path('footer.png'), $sourceWidth / 2 - 25, $sourceHeight - 14, 50, 7);
            }
        }
        $disk->put('score.pdf', $score->Output('S'));
        $sourceHash = hash_file('sha256', $disk->path('score.pdf'));
        $piece = new Piece(['name' => 'Footer test', 'key' => 'Modal', 'score_path' => 'score.pdf', 'score_url' => null]);
        $piece->setRelation('composer', new Composer(['name' => 'Florence Price']));
        $generator = new PDFGenerator;
        $pdf = $generator->pieces(collect([$piece, $piece]))->request([
            'title' => 'Footer consistency', 'cover_style' => 'modern', 'title_page' => false,
            'include_edition' => true, 'page_size' => $outputSize, 'page_numbers' => $numbers,
            'blank_pages' => true, 'bottom_text' => 'Édition PianoLIT',
        ], true, true)->generate()->output();
        $reader = new class extends Fpdi {
            public function pageStream($number) { return $this->getPdfReader($this->currentReaderId)->getPage($number)->getContentStream(); }
            public function objectStream($number, $name)
            {
                $reader = $this->getPdfReader($this->currentReaderId); $parser = $reader->getParser();
                $resources = PdfType::resolve($reader->getPage($number)->getAttribute('Resources'), $parser);
                $objects = PdfType::resolve($resources->value['XObject'], $parser);
                return PdfType::resolve($objects->value[$name], $parser)->getUnfilteredStream();
            }
        };
        $this->assertSame(10, $reader->setSourceFile(StreamReader::createByString($pdf)));
        for ($page = 1; $page <= 10; $page++) {
            $stream = $reader->pageStream($page);
            $size = $reader->getTemplateSize($reader->importPage($page));
            $isScore = $page >= 4 && $page !== 7;
            preg_match_all('/(-?[\d.]+) (-?[\d.]+) (-?[\d.]+) (-?[\d.]+) re f/', $stream, $masks, PREG_SET_ORDER);
            $this->assertCount($page === 1 ? 1 : ($isScore ? ($numbers ? 3 : 1) : 0), $masks);
            if ($isScore) {
                $mask = end($masks);
                $scale = $outputSize ? min(($size['width'] - 16) / $sourceWidth, ($size['height'] - 18) / $sourceHeight) : 1;
                $pageY = ($size['height'] - $sourceHeight * $scale) / 2;
                $x = (float) $mask[1] / (72 / 25.4);
                $top = $size['height'] - (float) $mask[2] / (72 / 25.4);
                $width = (float) $mask[3] / (72 / 25.4);
                $height = -(float) $mask[4] / (72 / 25.4);
                $this->assertEqualsWithDelta($size['width'] / 2, $x + $width / 2, 0.01);
                $this->assertEqualsWithDelta(70 * $scale, $width, 0.01);
                $this->assertEqualsWithDelta(10 * $scale, $height, 0.01);
                $this->assertEqualsWithDelta($pageY + ($sourceHeight - 14) * $scale, $top, 0.01);
                $this->assertGreaterThan($pageY + ($sourceHeight - 20) * $scale, $top, 'Mask leaves the final staff untouched');
                $this->assertGreaterThan(strpos($stream, ' Do'), strrpos($stream, ' re f'), 'Footer mask follows the imported score');
            }
            preg_match_all('/\/(\w+) Do/', $stream, $objects);
            $this->assertCount($page === 1 || $page === 7 ? 1 : 2, $objects[1], 'Exactly one export footer follows each non-cover page');
            if ($page > 1) {
                $footer = $reader->objectStream($page, end($objects[1]));
                $this->assertMatchesRegularExpression('/BT [\d.]+ 30\.000 Td/', $footer, 'Footer baseline is 30 points above the output edge');
                $this->assertMatchesRegularExpression('/\/F\d+ 10\.0+ Tf/', $footer, 'Footer retains the front-matter typography');
            }
        }
        $this->assertSame($sourceHash, hash_file('sha256', $disk->path('score.pdf')));
        if ($directory = getenv('ESCORE_FOOTER_PREVIEW_DIR')) file_put_contents($directory.'/'.$sourceName.'-'.($outputSize ?: 'original').'-'.($numbers ? 'numbered' : 'unnumbered').'.pdf', $pdf);
    }

    public function test_empty_bottom_text_hides_source_footer_and_legacy_requests_cannot_enable_the_change()
    {
        Storage::fake('public');
        $source = new \FPDF(); $source->AddPage(); $source->SetFont('Helvetica', '', 10);
        $source->Text(90, 287, 'PianoLIT eScore');
        Storage::disk('public')->put('score.pdf', $source->Output('S'));
        $piece = new Piece(['name' => 'Solo', 'key' => 'Modal', 'score_path' => 'score.pdf', 'score_url' => null]);
        $piece->setRelation('composer', new Composer(['name' => 'Florence Price']));
        foreach ([false, true] as $web) {
            $generator = new PDFGenerator;
            $pdf = $generator->pieces(collect([$piece]))->request([
                'title' => 'Empty footer', 'page_size' => 'letter', 'bottom_text' => '',
                'page_numbers' => false, 'uniform_footer' => true,
            ], $web, $web)->generate()->output();
            $reader = new class extends Fpdi {
                public function pageStream($page) { return $this->getPdfReader($this->currentReaderId)->getPage($page)->getContentStream(); }
            };
            $reader->setSourceFile(StreamReader::createByString($pdf));
            $stream = $reader->pageStream($generator->metadata()['entries'][0]['start']);
            $this->assertSame(1, substr_count($stream, ' Do'), 'Cleared bottom text adds no replacement footer');
            $this->assertSame($web ? 1 : 0, substr_count($stream, ' re f'), 'Only trusted web exports hide the source footer');
        }
    }
}
