<?php

namespace App\PDF;

use setasign\Fpdi\PdfParser\StreamReader;

/** Export copies may be resized and numbered; stored originals remain untouched. */
class EscoreMerger extends \Webklex\PDFMerger\PDFMerger
{
    // Millimetres on the source page, before fitting it to the export paper size.
    // Both corners use the same small inset box; adjust these for other editions.
    public const SOURCE_PAGE_NUMBER_MASK_SIDE_INSET_MM = 12;
    public const SOURCE_PAGE_NUMBER_MASK_TOP_OFFSET_MM = 8;
    public const SOURCE_PAGE_NUMBER_MASK_WIDTH_MM = 20;
    public const SOURCE_PAGE_NUMBER_MASK_HEIGHT_MM = 12;

    // A centered strip in the source's bottom margin, before page fitting.
    public const SOURCE_FOOTER_MASK_WIDTH_MM = 70;
    public const SOURCE_FOOTER_MASK_HEIGHT_MM = 10;
    public const SOURCE_FOOTER_MASK_BOTTOM_INSET_MM = 4;

    public const FOLIO_INSET_MM = 18;

    private $settings;

    public function settings(array $settings)
    {
        $this->settings = $settings;
        return $this;
    }

    protected function doMerge($orientation, $duplexSafe)
    {
        $pdf = $this->oFPDI;
        $resize = !empty($this->settings['page_size']);
        $fromFirstPiece = !empty($this->settings['number_pieces_from_one']);
        $uniformFooter = !empty($this->settings['uniform_footer']);
        $footers = [];
        [$width, $height] = $this->settings['page_size'] === 'a4' ? [210, 297] : [215.9, 279.4];
        $number = 0; $pieceNumber = 0;
        foreach ($this->aFiles as $fileIndex => $file) {
            $source = StreamReader::createByString(file_get_contents($file['name']));
            $count = $pdf->setSourceFile($source);
            for ($page = 1; $page <= $count; $page++) {
                $template = $pdf->importPage($page);
                $size = $pdf->getTemplateSize($template);
                if (!$resize) { $width = $size['width']; $height = $size['height']; }
                $pdf->AddPage(!$resize && $width > $height ? 'L' : 'P', [$width, $height]);
                $number++;
                if ($number === 1) {
                    $color = $this->settings['color'];
                    $pdf->SetFillColor(hexdec(substr($color, 1, 2)), hexdec(substr($color, 3, 2)), hexdec(substr($color, 5, 2)));
                    $pdf->Rect(0, 0, $width, $height, 'F');
                }
                $margin = !$resize || $fileIndex === 0 ? 0 : 8;
                $availableHeight = $height - (!$resize || $fileIndex === 0 ? 0 : 18);
                $scale = min(($width - $margin * 2) / $size['width'], $availableHeight / $size['height']);
                $pageWidth = $size['width'] * $scale;
                $pageHeight = $size['height'] * $scale;
                $pageX = ($width - $pageWidth) / 2;
                $pageY = ($height - $pageHeight) / 2;
                $pdf->useTemplate($template, $pageX, $pageY, $pageWidth, $pageHeight);
                // The first input contains all generated front matter. Only score
                // templates need their original folios covered, after importing.
                if ($fileIndex > 0 && $this->settings['page_numbers']) {
                    $this->maskSourcePageNumbers($pdf, $pageX, $pageY, $pageWidth, $scale);
                }
                if ($fileIndex > 0 && $uniformFooter) {
                    $this->maskSourceFooter($pdf, $pageX, $pageY, $pageWidth, $pageHeight, $scale);
                }
                $this->folio($pdf, $fromFirstPiece ? ($fileIndex > 0 ? ++$pieceNumber : 0) : $number, $width);
                if ($uniformFooter && $number > 1) $this->footer($pdf, $width, $height, $source, $footers);
            }
            if ($fileIndex > 0 && $fileIndex < $this->aFiles->count() - 1 && $this->settings['blank_pages']) {
                $pdf->AddPage(!$resize && $width > $height ? 'L' : 'P', [$width, $height]);
                $number++;
                $this->folio($pdf, $fromFirstPiece ? ++$pieceNumber : $number, $width);
                if ($uniformFooter) $this->footer($pdf, $width, $height, $source, $footers);
            }
        }
    }

    private function maskSourceFooter($pdf, $pageX, $pageY, $pageWidth, $pageHeight, $scale)
    {
        $width = min($pageWidth, self::SOURCE_FOOTER_MASK_WIDTH_MM * $scale);
        $height = self::SOURCE_FOOTER_MASK_HEIGHT_MM * $scale;
        $top = $pageY + $pageHeight - (self::SOURCE_FOOTER_MASK_BOTTOM_INSET_MM * $scale + $height);
        $pdf->SetFillColor(255, 255, 255);
        $pdf->Rect($pageX + ($pageWidth - $width) / 2, $top, $width, $height, 'F');
    }

    private function footer($pdf, $width, $height, $source, &$footers)
    {
        $text = $this->settings['bottom_text'];
        if ($text === '') return;
        $key = $width.'x'.$height;
        if (!isset($footers[$key])) {
            $bytes = (new EscoreDocument)->renderFooter($text, $width * 72 / 25.4, $height * 72 / 25.4);
            $pdf->setSourceFile(StreamReader::createByString($bytes));
            $footers[$key] = $pdf->importPage(1);
            // Rendering the footer must not change the next score page's reader.
            $pdf->setSourceFile($source);
        }
        $pdf->useTemplate($footers[$key], 0, 0, $width, $height);
    }

    private function maskSourcePageNumbers($pdf, $pageX, $pageY, $pageWidth, $scale)
    {
        $inset = self::SOURCE_PAGE_NUMBER_MASK_SIDE_INSET_MM * $scale;
        $top = $pageY + self::SOURCE_PAGE_NUMBER_MASK_TOP_OFFSET_MM * $scale;
        $width = self::SOURCE_PAGE_NUMBER_MASK_WIDTH_MM * $scale;
        $height = self::SOURCE_PAGE_NUMBER_MASK_HEIGHT_MM * $scale;
        $pdf->SetFillColor(255, 255, 255);
        $pdf->Rect($pageX + $inset, $top, $width, $height, 'F');
        $pdf->Rect($pageX + $pageWidth - $inset - $width, $top, $width, $height, 'F');
    }

    private function folio($pdf, $number, $width)
    {
        if (!$this->settings['page_numbers'] || $number < 1 || ($number === 1 && empty($this->settings['number_pieces_from_one']))) return;
        $pdf->SetFont('Helvetica', '', 9);
        $pdf->SetTextColor(90, 90, 90);
        $label = (string) $number;
        $pdf->Text($width - self::FOLIO_INSET_MM - $pdf->GetStringWidth($label), self::FOLIO_INSET_MM, $label);
    }
}
