<?php

namespace App\PDF;

use Cpdf;

/** Draw front matter with the installed Unicode PDF backend, without HTML parsing. */
class EscoreDocument
{
    private $pdf;
    private $page = 1;
    private $content, $sections;

    public function render(array $content, $entries)
    {
        $this->pdf = new Cpdf([0, 0, 612, 792], true, sys_get_temp_dir(), sys_get_temp_dir());
        $this->page = 1;
        $this->content = $content;
        $this->sections = ['cover' => 1, 'title' => $content['title_page'] ? 1 : 0, 'index' => 1, 'edition' => 0];
        $this->pdf->addInfo('Title', $content['title']);
        $this->pdf->addInfo('Creator', 'PianoLIT eScore');
        $this->cover($content, true);
        if ($content['title_page']) {
            $this->newPage();
            $this->cover($content, false);
        }
        $this->newPage();
        $this->indexHeader();
        $baseline = $content['cover_style'] === 'modern' ? 280 : 202;
        foreach ($entries as $entryIndex => $entry) {
            if ($content['cover_style'] === 'modern') {
                $this->modernIndexEntry($entry, $entryIndex + 1, $baseline);
                continue;
            }
            $runs = [['text' => $entry['title'].' ', 'size' => 17, 'bold' => false]];
            if ($content['composer_names']) $runs[] = ['text' => 'by '.$entry['composer'], 'size' => 12, 'bold' => false];
            $lines = $this->wrap($runs, 412);
            $height = max(30, count($lines) * 22 + 8);
            if ($baseline + $height > 732) {
                $this->newPage();
                $this->sections['index']++;
                $this->indexHeader();
                $baseline = 202;
            }
            foreach ($lines as $lineIndex => $line) {
                // Also support an exceptionally long catalogue title across pages.
                if ($baseline > 710) {
                    $this->newPage();
                    $this->sections['index']++;
                    $this->indexHeader();
                    $baseline = 202;
                }
                $x = 76;
                foreach ($line as $run) {
                    $this->text($x, $baseline, $run['size'], $run['text'], $run['bold']);
                    $x += $run['width'];
                }
                if ($lineIndex === 0) {
                    $number = (string) $entry['start'];
                    $this->font(false);
                    $this->text(536 - $this->pdf->getTextWidth(14, $number), $baseline, 14, $number);
                }
                $baseline += 22;
            }
            $baseline += max(8, $height - count($lines) * 22);
        }
        if ($content['include_edition']) $this->edition($content['edition_notes'], $entries->count());
        return $this->pdf->output();
    }

    private function font($bold)
    {
        $this->pdf->selectFont(resource_path('fonts/escore/LibreBodoni-'.($bold ? 'Bold' : 'Regular')));
    }

    private function text($x, $baseline, $size, $text, $bold = false)
    {
        $this->font($bold);
        $this->pdf->addText($x, 792 - $baseline, $size, $text);
    }

    private function centered($baseline, $size, $text)
    {
        $this->font(false);
        $this->text((612 - $this->pdf->getTextWidth($size, $text)) / 2, $baseline, $size, $text);
    }

    private function color($hex)
    {
        return array_map(function ($offset) use ($hex) { return hexdec(substr($hex, $offset, 2)) / 255; }, [1, 3, 5]);
    }

    private function newPage()
    {
        $this->pdf->newPage();
        $this->page++;
        $this->pdf->setColor([0, 0, 0]);
        $this->pdf->setStrokeColor([0, 0, 0]);
        if ($this->content['page_numbers'] && !$this->content['page_size']) $this->text(533, 50, 11, (string) $this->page);
        $this->centered(762, 10, $this->content['bottom_text']);
    }

    private function cover($content, $colored)
    {
        if ($colored) {
            $this->pdf->setColor($this->color($content['color']));
            $this->pdf->filledRectangle(0, 0, 612, 792);
            $this->pdf->setColor($this->color($content['textColor']));
            $this->pdf->setStrokeColor($this->color($content['textColor']));
        }
        if ($content['cover_style'] === 'modern') {
            $this->modernCover($content);
            return;
        }
        $x = $colored ? 76 : 49;
        $shift = $colored ? 0 : -17;
        $this->block($x, 130 + $shift, mb_strtoupper($content['title']), 60, 460, 190);
        $subtitle = $this->fit($content['subtitle'], 34, 468, 42, true);
        $subtitleBaseline = 345 + $shift - max(0, count($subtitle['lines']) - 1) * $subtitle['size'] * 1.1;
        $this->drawLines($x, $subtitleBaseline, $subtitle['lines'], $subtitle['size'] * 1.1);
        $this->pdf->setLineStyle(0.7);
        $this->pdf->line($x - 4, 792 - (365 + $shift), $x + 464, 792 - (365 + $shift));
        $this->block($x, 398 + $shift, $content['comment'], 26, 460, 235);
        if ($colored) {
            $this->pdf->setLineStyle(0.5);
            $this->pdf->line(0, 72, 244, 72);
            $this->pdf->line(368, 72, 612, 72);
            $this->centered(720, 20, 'PianoLIT');
            $this->centered(744, 20, 'eScore');
        }
    }

    public function sections()
    {
        return $this->sections;
    }

    private function modernCover($content)
    {
        $layout = $this->fit($content['title'], 54, 460, 140);
        $baseline = 242 - max(0, count($layout['lines']) - 1) * $layout['size'] * 1.2;
        $this->drawLines(76, $baseline, $layout['lines'], $layout['size'] * 1.2);
        $this->pdf->setLineStyle(0.7);
        $this->pdf->line(92, 792 - 282, 520, 792 - 282);
        $subtitle = $this->fit($content['subtitle'], 26, 408, 74);
        $this->drawLines(112, 335, $subtitle['lines'], $subtitle['size'] * 1.2);
        $commentBaseline = 335 + max(1, count($subtitle['lines'])) * $subtitle['size'] * 1.2 + 6;
        $this->block(112, $commentBaseline, $content['comment'], 24, 408, 205);
        $brand = $this->fit($content['bottom_text'], 18, 250, 38);
        $baseline = 704 - max(0, count($brand['lines']) - 1) * $brand['size'] * 1.2;
        foreach ($brand['lines'] as $line) {
            $this->centered($baseline, $brand['size'], implode('', array_column($line, 'text')));
            $baseline += $brand['size'] * 1.2;
        }
        if ($content['creator'] !== '') {
            $creator = $this->fit('created by '.$content['creator'], 12, 460, 20);
            $this->centered(732, $creator['size'], implode('', array_column($creator['lines'][0] ?? [], 'text')));
        }
        $this->pdf->line(52, 792 - 704, 161, 792 - 704);
        $this->pdf->line(451, 792 - 704, 560, 792 - 704);
    }

    private function edition($notes, $pieceCount)
    {
        $this->newPage();
        $this->sections['edition'] = 1;
        $this->text(76, 120, 32, 'About this edition');
        if ($notes === '') $notes = 'This collection brings together '.$pieceCount.' piano pieces selected from your repertoire. Created with PianoLIT.';
        $lines = $this->wrap([['text' => $notes, 'size' => 14, 'bold' => false]], 460);
        $baseline = 170;
        foreach ($lines as $line) {
            if ($baseline > 710) {
                $this->newPage();
                $this->sections['edition']++;
                $baseline = 110;
            }
            $this->drawLines(76, $baseline, [$line], 20);
            $baseline += 20;
        }
    }

    private function block($x, $baseline, $text, $size, $width, $height)
    {
        $layout = $this->fit($text, $size, $width, $height);
        $this->drawLines($x, $baseline, $layout['lines'], $layout['size'] * 1.2);
    }

    private function fit($text, $size, $width, $height, $bold = false)
    {
        $initialSize = $size;
        do {
            $lines = $this->wrap([['text' => $text, 'size' => $size, 'bold' => $bold]], $width);
            if (count($lines) * $size * 1.2 <= $height) break;
            $size--;
        } while ($size > 8);
        if (count($lines) * $size * 1.2 > $height) {
            // Excessive manual line breaks must not push text into the branding.
            $normalized = preg_replace('/\s+/u', ' ', $text);
            if ($normalized !== $text) return $this->fit($normalized, $initialSize, $width, $height, $bold);
        }
        return ['lines' => $lines, 'size' => $size];
    }

    private function drawLines($x, $baseline, $lines, $leading)
    {
        foreach ($lines as $line) {
            $cursor = $x;
            foreach ($line as $run) {
                $this->text($cursor, $baseline, $run['size'], $run['text'], $run['bold']);
                $cursor += $run['width'];
            }
            $baseline += $leading;
        }
    }

    private function indexHeader()
    {
        if ($this->content['cover_style'] === 'modern') {
            $this->centered(70, 11, $this->content['bottom_text']);
            $this->centered(152, 30, 'TABLE OF CONTENTS');
            $this->pdf->setLineStyle(0.5);
            $this->pdf->line(76, 792 - 180, 536, 792 - 180);
            $this->text(76, 238, 12, 'Piece', true);
            $this->text(508, 238, 12, 'Page', true);
            return;
        }
        $this->centered(104, 32, 'TABLE OF CONTENTS');
        $this->text(76, 166, 11, 'Piece');
        $this->text(522, 166, 11, 'Page');
    }

    private function modernIndexEntry($entry, $number, &$baseline)
    {
        $showComposer = $this->content['composer_names'];
        $title = $this->wrap([['text' => $number.'. '.$entry['title'], 'size' => 14, 'bold' => false]], $showComposer ? 280 : 412);
        $composer = $showComposer ? $this->wrap([['text' => $entry['composer'], 'size' => 12, 'bold' => false]], 120) : [];
        $count = max(count($title), count($composer));
        for ($line = 0; $line < $count; $line++) {
            if ($baseline > 710) {
                $this->newPage();
                $this->sections['index']++;
                $this->indexHeader();
                $baseline = 280;
            }
            if (isset($title[$line])) $this->drawLines(76, $baseline, [$title[$line]], 22);
            if (isset($composer[$line])) {
                $this->pdf->setColor([0.42, 0.46, 0.52]);
                $this->drawLines(366, $baseline, [$composer[$line]], 22);
                $this->pdf->setColor([0, 0, 0]);
            }
            if ($line === 0) {
                $this->font(false);
                $page = (string) $entry['start'];
                $this->text(536 - $this->pdf->getTextWidth(12, $page), $baseline, 12, $page);
            }
            $baseline += 22;
        }
        $baseline += 16;
    }

    /** Wrap measured runs while retaining the smaller composer type and explicit newlines. */
    private function wrap($runs, $width)
    {
        $lines = [];
        $line = [];
        $used = 0;
        foreach ($runs as $run) {
            $this->font($run['bold']);
            $tokens = preg_split('/(\R|[^\S\r\n]+)/u', $run['text'], -1, PREG_SPLIT_DELIM_CAPTURE | PREG_SPLIT_NO_EMPTY);
            foreach ($tokens as $token) {
                if (preg_match('/^\R$/u', $token)) {
                    $lines[] = $line;
                    $line = [];
                    $used = 0;
                    continue;
                }
                $tokenWidth = $this->pdf->getTextWidth($run['size'], $token);
                if ($used + $tokenWidth > $width && $line) {
                    $lines[] = $line;
                    $line = [];
                    $used = 0;
                }
                if (!$line && trim($token) === '') continue;
                // Split oversized words at Unicode character boundaries, without losing text.
                $parts = $tokenWidth > $width ? preg_split('//u', $token, -1, PREG_SPLIT_NO_EMPTY) : [$token];
                foreach ($parts as $part) {
                    $partWidth = $this->pdf->getTextWidth($run['size'], $part);
                    if ($used + $partWidth > $width && $line) {
                        $lines[] = $line;
                        $line = [];
                        $used = 0;
                    }
                    $line[] = array_merge($run, ['text' => $part, 'width' => $partWidth]);
                    $used += $partWidth;
                }
            }
        }
        if ($line) $lines[] = $line;
        return $lines;
    }
}
