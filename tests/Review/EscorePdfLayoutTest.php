<?php

namespace Tests\Review;

use App\{Composer, Piece};
use App\PDF\{EscoreOptions, PDFGenerator};
use Illuminate\Support\Facades\Storage;
use setasign\Fpdi\Fpdi;
use setasign\Fpdi\PdfParser\StreamReader;

class EscorePdfLayoutTest extends ReviewTestCase
{
    public function setUp(): void
    {
        parent::setUp();
        Storage::fake('public');

    }

    private function score($name, $pages, $index)
    {
        $source = new \FPDF();
        for ($page = 1; $page <= $pages; $page++) {
            $source->AddPage();
            $source->SetFont('Helvetica', '', 16);
            $source->Text(20, 30, 'Score '.$index.' page '.$page);
        }
        Storage::disk('public')->put('score-'.$index.'.pdf', $source->Output('S'));
        $piece = new Piece(['name' => $name, 'key' => 'Modal', 'score_path' => 'score-'.$index.'.pdf', 'score_url' => null]);
        $piece->setRelation('composer', new Composer(['name' => 'Florence Price']));
        return $piece;
    }

    private function generator()
    {
        return new class extends PDFGenerator {
            public $renderedEntries, $frontPdf;
            protected function frontMatter($entries)
            {
                $this->renderedEntries = $entries;
                return $this->frontPdf = parent::frontMatter($entries);
            }
        };
    }

    public function test_real_pdf_has_reference_front_matter_correct_offsets_and_original_page_sizes()
    {
        $first = $this->score('Clover Blossoms', 2, 1);
        $second = $this->score('Melody in C', 1, 2);
        $copyrighted = $this->score('Excluded score', 1, 3);
        $copyrighted->score_url = 'https://example.com';
        $generator = $this->generator();
        $pdf = $generator->pieces(collect([$first, $copyrighted, $second]))->request([
            'title' => 'Pieces for beginners', 'subtitle' => 'A collection of pieces',
            'comment' => "For Late Elementary\nto Beginner levels",
        ])->generate()->output();
        $reader = new Fpdi();
        $this->assertSame(6, $reader->setSourceFile(StreamReader::createByString($pdf)));
        $this->assertSame([4, 6], $generator->renderedEntries->pluck('start')->all());
        $this->assertEqualsWithDelta(215.9, $reader->getTemplateSize($reader->importPage(1))['width'], 0.01);
        $this->assertEqualsWithDelta(210, $reader->getTemplateSize($reader->importPage(4))['width'], 0.01);
        $this->assertSame([], Storage::disk('public')->allFiles('pdf'));
        if ($destination = getenv('ESCORE_PREVIEW_PDF')) file_put_contents($destination, $pdf);
    }

    public function test_large_index_paginates_and_offsets_include_every_index_page()
    {
        $pieces = collect();
        for ($i = 1; $i <= 65; $i++) $pieces->push($this->score('A long piano piece title with several movements number '.$i, 1, $i));
        $generator = $this->generator();
        $pdf = $generator->pieces($pieces)->request(['title' => 'My repertoire'])->generate()->output();
        $reader = new Fpdi();
        $total = $reader->setSourceFile(StreamReader::createByString($pdf));
        $this->assertGreaterThan(68, $total);
        $this->assertSame($total - 64, $generator->renderedEntries->first()['start']);
        $this->assertSame($total, $generator->renderedEntries->last()['start']);
        if ($destination = getenv('ESCORE_LARGE_PREVIEW_PDF')) file_put_contents($destination, $pdf);
        $web = $this->generator();
        $web->pieces($pieces)->request(['title' => 'My repertoire', 'page_size' => 'letter', 'title_page' => false], true, true)->generate();
        $this->assertGreaterThan(1, $web->metadata()['sections']['index']);
        $this->assertSame(1, $web->renderedEntries->first()['folio']);
        $this->assertSame(65, $web->renderedEntries->last()['folio']);
        $this->assertSame($web->metadata()['pages'], $web->metadata()['entries'][64]['start']);
    }

    public function test_cover_treats_markup_as_text_and_colors_have_readable_contrast()
    {
        $this->assertSame('#000000', EscoreOptions::textColor(EscoreOptions::DEFAULT_COLOR));
        $this->assertSame('#ffffff', EscoreOptions::textColor('#111111'));
        $this->assertSame('#000000', EscoreOptions::textColor('#ffffff'));
        $generator = $this->generator();
        $pdf = $generator->pieces(collect())->request(['title' => '<script>bad</script>', 'comment' => '<img src=x>', 'color' => '#000000'])->generate()->output();
        $reader = new Fpdi();
        $this->assertSame(3, $reader->setSourceFile(StreamReader::createByString($pdf)));
    }

    public function test_modern_cover_title_divider_and_supporting_text_share_one_left_edge()
    {
        foreach ([true, false] as $alignCoverText) {
            $generator = $this->generator();
            $generator->pieces(collect())->request([
                'title' => 'Test', 'subtitle' => 'A collection of pieces',
                'comment' => 'for piano', 'cover_style' => 'modern', 'title_page' => false,
                // Submitted options cannot opt the legacy mobile generator in.
                'align_cover_text' => true,
            ], $alignCoverText)->generate();
            $reader = new class extends Fpdi {
                public function firstPageStream()
                {
                    return $this->getPdfReader($this->currentReaderId)->getPage(1)->getContentStream();
                }
            };
            $reader->setSourceFile(StreamReader::createByString($generator->frontPdf));
            $stream = $reader->firstPageStream();
            $this->assertStringContainsString('BT 76.000 550.000 Td', $stream);
            $textX = $alignCoverText ? '76.000' : '112.000';
            foreach (['457.000', '419.800'] as $baseline) {
                $this->assertStringContainsString('BT '.$textX.' '.$baseline.' Td', $stream);
            }
            $line = $alignCoverText ? '76.000 510.000 m 536.000 510.000 l S' : '92.000 510.000 m 520.000 510.000 l S';
            $this->assertStringContainsString($line, $stream);
        }
    }

    public function test_maximum_cover_text_and_manual_line_breaks_fit_without_extra_pages()
    {
        $pdf = $this->generator()->pieces(collect())->request([
            'title' => substr(str_repeat('A very long collection title ', 8), 0, 160),
            'subtitle' => substr(str_repeat('A collection of challenging piano pieces ', 5), 0, 160),
            'comment' => str_repeat("A description line.\n", 30), 'color' => '#111111',
        ])->generate()->output();
        $reader = new Fpdi();
        $this->assertSame(3, $reader->setSourceFile(StreamReader::createByString($pdf)));
        if ($destination = getenv('ESCORE_LONG_COVER_PDF')) file_put_contents($destination, $pdf);
    }
    public function test_modern_portrait_exports_apply_paper_size_blank_pages_and_real_summary_offsets()
    {
        $pieces = collect([$this->score('First piece', 2, 1), $this->score('Second piece', 1, 2)]);
        // Include a landscape source to verify the assembled copy is always portrait.
        $landscape = new \FPDF('L');
        $landscape->AddPage(); $landscape->SetFont('Helvetica', '', 16); $landscape->Text(20, 30, 'Landscape score');
        Storage::disk('public')->put('score-2.pdf', $landscape->Output('S'));
        foreach (['letter' => [215.9, 279.4], 'a4' => [210, 297]] as $size => $dimensions) {
            $generator = $this->generator();
            $pdf = $generator->pieces($pieces)->request(['title' => 'Testing pdfs', 'cover_style' => 'modern', 'title_page' => false, 'page_size' => $size, 'include_edition' => true, 'edition_notes' => 'Personal edition notes.', 'blank_pages' => true])->generate()->output();
            $reader = new Fpdi();
            $this->assertSame(7, $reader->setSourceFile(StreamReader::createByString($pdf)));
            $this->assertSame([4, 7], $generator->metadata()['entries'] ? array_column($generator->metadata()['entries'], 'start') : []);
            $this->assertSame(['cover' => 1, 'title' => 0, 'index' => 1, 'edition' => 1, 'blank' => 1], $generator->metadata()['sections']);
            $this->assertSame(7, $generator->metadata()['pages']);
            for ($page = 1; $page <= 7; $page++) {
                $actual = $reader->getTemplateSize($reader->importPage($page));
                $this->assertEqualsWithDelta($dimensions[0], $actual['width'], 0.01);
                $this->assertEqualsWithDelta($dimensions[1], $actual['height'], 0.01);
            }
            if ($destination = getenv('ESCORE_MODERN_PREVIEW_PDF')) file_put_contents($destination, $pdf);
        }
    }

    public function test_edition_pagination_is_included_in_index_offsets_and_options_can_be_disabled()
    {
        $generator = $this->generator();
        $pdf = $generator->pieces(collect([$this->score('Solo', 1, 1)]))->request(['title' => 'Book', 'cover_style' => 'modern', 'title_page' => false, 'page_size' => 'letter', 'include_edition' => true, 'edition_notes' => str_repeat('An edition note with enough words to wrap. ', 90), 'page_numbers' => false, 'composer_names' => false])->generate()->output();
        $reader = new Fpdi();
        $total = $reader->setSourceFile(StreamReader::createByString($pdf));
        $this->assertGreaterThan(1, $generator->metadata()['sections']['edition']);
        $this->assertSame($total, $generator->metadata()['entries'][0]['start']);
        $this->assertSame($total, $generator->metadata()['pages']);
        if ($destination = getenv('ESCORE_EDITION_PREVIEW_PDF')) file_put_contents($destination, $pdf);
    }

    public function test_web_numbering_starts_at_first_piece_without_changing_preview_positions_or_source_sizes()
    {
        $pieces = collect([$this->score('First piece', 2, 1), $this->score('Second piece', 1, 2)]);
        $landscape = new \FPDF('L');
        $landscape->AddPage(); $landscape->SetFont('Helvetica', '', 16); $landscape->Text(20, 30, 'Landscape score');
        Storage::disk('public')->put('score-2.pdf', $landscape->Output('S'));
        foreach ([null, 'letter', 'a4'] as $size) {
            foreach ([false, true] as $blank) {
                foreach ([false, true] as $numbered) {
                    $generator = $this->generator();
                    $pdf = $generator->pieces($pieces)->request([
                        'title' => 'Piece numbering', 'cover_style' => 'modern', 'page_size' => $size,
                        'title_page' => $blank, 'include_edition' => true,
                        'edition_notes' => str_repeat('Edition notes that continue across several pages. ', 90),
                        'blank_pages' => $blank, 'page_numbers' => $numbered,
                    ], true, true)->generate()->output();
                    $metadata = $generator->metadata();
                    $first = $metadata['entries'][0]['start'];
                    $this->assertGreaterThan(1, $metadata['sections']['edition']);
                    $this->assertSame([1, $blank ? 4 : 3], $generator->renderedEntries->pluck('folio')->all());
                    $this->assertSame([$first, $first + ($blank ? 3 : 2)], array_column($metadata['entries'], 'start'));
                    $this->assertSame(['id', 'title', 'composer', 'pages', 'start'], array_keys($metadata['entries'][0]));
                    $reader = new class extends Fpdi {
                        public function pageStream($page) { return $this->getPdfReader($this->currentReaderId)->getPage($page)->getContentStream(); }
                    };
                    $this->assertSame($first + ($blank ? 3 : 2), $reader->setSourceFile(StreamReader::createByString($pdf)));
                    for ($page = 1; $page <= $metadata['pages']; $page++) {
                        $stream = $reader->pageStream($page);
                        $hasFolio = preg_match('/BT [\d.]+ [\d.]+ Td \((\d+)\) Tj ET/', $stream, $match);
                        $this->assertSame($numbered && $page >= $first, (bool) $hasFolio);
                        if ($hasFolio) $this->assertSame((string) ($page - $first + 1), $match[1]);
                    }
                    if ($size === null) {
                        $firstSize = $reader->getTemplateSize($reader->importPage($first));
                        $lastSize = $reader->getTemplateSize($reader->importPage($metadata['pages']));
                        $this->assertEqualsWithDelta(210, $firstSize['width'], 0.01);
                        $this->assertEqualsWithDelta(297, $firstSize['height'], 0.01);
                        $this->assertEqualsWithDelta(297, $lastSize['width'], 0.01);
                        $this->assertEqualsWithDelta(210, $lastSize['height'], 0.01);
                    }
                    if ($size === 'letter' && $blank && $numbered && ($destination = getenv('ESCORE_PIECE_NUMBERING_PREVIEW_PDF'))) file_put_contents($destination, $pdf);
                }
            }
        }
        // Only the trusted server argument can change legacy mobile numbering.
        $legacy = $this->generator();
        $legacy->pieces($pieces)->request(['title' => 'Legacy', 'page_size' => 'letter', 'number_pieces_from_one' => true])->generate();
        $this->assertArrayNotHasKey('folio', $legacy->renderedEntries->first());
        $this->assertSame(4, $legacy->metadata()['entries'][0]['start']);
    }

}
