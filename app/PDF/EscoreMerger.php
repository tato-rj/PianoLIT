<?php

namespace App\PDF;

use setasign\Fpdi\PdfParser\StreamReader;

/** Portrait exports resize copies of scores; stored originals remain untouched. */
class EscoreMerger extends \Webklex\PDFMerger\PDFMerger
{
    // Millimetres on the source page, before fitting it to the export paper size.
    // Both corners use the same small inset box; adjust these for other editions.
    public const SOURCE_PAGE_NUMBER_MASK_SIDE_INSET_MM = 12;
    public const SOURCE_PAGE_NUMBER_MASK_TOP_OFFSET_MM = 8;
    public const SOURCE_PAGE_NUMBER_MASK_WIDTH_MM = 20;
    public const SOURCE_PAGE_NUMBER_MASK_HEIGHT_MM = 12;

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
        [$width, $height] = $this->settings['page_size'] === 'a4' ? [210, 297] : [215.9, 279.4];
        $number = 0;
        foreach ($this->aFiles as $fileIndex => $file) {
            $count = $pdf->setSourceFile(StreamReader::createByString(file_get_contents($file['name'])));
            for ($page = 1; $page <= $count; $page++) {
                $template = $pdf->importPage($page);
                $size = $pdf->getTemplateSize($template);
                $pdf->AddPage('P', [$width, $height]);
                $number++;
                if ($number === 1) {
                    $color = $this->settings['color'];
                    $pdf->SetFillColor(hexdec(substr($color, 1, 2)), hexdec(substr($color, 3, 2)), hexdec(substr($color, 5, 2)));
                    $pdf->Rect(0, 0, $width, $height, 'F');
                }
                $margin = $fileIndex === 0 ? 0 : 8;
                $availableHeight = $height - ($fileIndex === 0 ? 0 : 18);
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
                $this->folio($pdf, $number, $width);
            }
            if ($fileIndex > 0 && $fileIndex < $this->aFiles->count() - 1 && $this->settings['blank_pages']) {
                $pdf->AddPage('P', [$width, $height]);
                $this->folio($pdf, ++$number, $width);
            }
        }
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
        if (!$this->settings['page_numbers'] || $number === 1) return;
        $pdf->SetFont('Helvetica', '', 9);
        $pdf->SetTextColor(90, 90, 90);
        $label = (string) $number;
        $pdf->Text($width - self::FOLIO_INSET_MM - $pdf->GetStringWidth($label), self::FOLIO_INSET_MM, $label);
    }
}
