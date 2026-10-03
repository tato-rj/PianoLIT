<?php

namespace App\PDF;

use setasign\Fpdi\Fpdi;
use setasign\Fpdi\PdfParser\StreamReader;
use Webklex\PDFMerger\Facades\PDFMergerFacade as PDFMerger;

class PDFGenerator
{
    protected $pieces, $content;
    protected $entries, $frontPages, $sections = [];

    public function pieces($pieces)
    {
        // The same set must drive the index and the merged scores.
        $this->pieces = $pieces->filter(function ($piece) {
            return $piece && $piece->score_path && $piece->is_public_domain;
        })->values();

        return $this;
    }

    public function request($request)
    {
        $color = $request['color'] ?? EscoreOptions::DEFAULT_COLOR;
        if (!is_string($color) || !preg_match('/^#[0-9a-fA-F]{6}$/', $color)) {
            $color = EscoreOptions::DEFAULT_COLOR;
        }
        $this->content = [
            'title' => $request['title'] ?? 'My eScore',
            'subtitle' => $request['subtitle'] ?? 'A collection of pieces',
            'comment' => $request['comment'] ?? 'for piano',
            'color' => $color,
            'textColor' => EscoreOptions::textColor($color),
            'bottom_text' => $request['bottom_text'] ?? 'PianoLIT eScore',
            'creator' => $request['creator'] ?? '',
            'edition_notes' => $request['edition_notes'] ?? '',
            'cover_style' => $request['cover_style'] ?? 'reference',
            'page_size' => $request['page_size'] ?? null,
            'page_numbers' => !isset($request['page_numbers']) || (bool) $request['page_numbers'],
            'composer_names' => !isset($request['composer_names']) || (bool) $request['composer_names'],
            'include_edition' => !empty($request['include_edition']),
            'blank_pages' => !empty($request['blank_pages']),
            'title_page' => !isset($request['title_page']) || (bool) $request['title_page'],
        ];

        return $this;
    }

    protected function frontMatter($entries)
    {
        $document = new EscoreDocument;
        $pdf = $document->render($this->content, $entries);
        $this->sections = $document->sections();
        return $pdf;
    }

    public function generate()
    {
        $reader = new Fpdi();
        $entries = $this->pieces->map(function ($piece) use ($reader) {
            return [
                'id' => $piece->id,
                'title' => $piece->medium_name,
                'composer' => $piece->composer->name,
                'pages' => $reader->setSourceFile($piece->score_full_path),
                'start' => 1,
            ];
        });

        // Measure actual rendered index pagination, including wrapped titles.
        // Regenerate with final offsets until the front-matter page count settles.
        $frontPages = 0;
        for ($pass = 0; $pass < 5; $pass++) {
            $nextPage = $frontPages + 1;
            $entries = $entries->map(function ($entry, $index) use (&$nextPage, $entries) {
                $entry['start'] = $nextPage;
                $nextPage += $entry['pages'] + ($this->content['blank_pages'] && $index < $entries->count() - 1 ? 1 : 0);
                return $entry;
            });
            $pdf = $this->frontMatter($entries);
            $actualPages = $reader->setSourceFile(StreamReader::createByString($pdf));
            if ($frontPages === $actualPages) {
                break;
            }
            $frontPages = $actualPages;
        }
        if ($pass === 5) {
            throw new \RuntimeException('The eScore index pagination could not be resolved.');
        }

        $this->entries = $entries;
        $this->frontPages = $frontPages;
        $temporaryPath = 'pdf/'.\Illuminate\Support\Str::uuid().'.pdf';
        try {
            if (!\Storage::disk('public')->put($temporaryPath, $pdf)) {
                throw new \RuntimeException('The eScore cover could not be saved.');
            }
            return $this->merge(\Storage::disk('public')->path($temporaryPath))->setFileName('escore.pdf');
        } finally {
            \Storage::disk('public')->delete($temporaryPath);
        }
    }

    public function metadata()
    {
        $blank = $this->content['blank_pages'] ? max(0, $this->entries->count() - 1) : 0;
        return [
            'entries' => $this->entries->values()->all(),
            'sections' => array_merge($this->sections, ['blank' => $blank]),
            'pages' => $this->frontPages + $this->entries->sum('pages') + $blank,
        ];
    }

    public function merge($pdfpath)
    {
        $merger = $this->content['page_size']
            ? (new EscoreMerger(app(\Illuminate\Filesystem\Filesystem::class)))->settings($this->content)
            : PDFMerger::init();
        $merger->addPDF($pdfpath, 'all');
        foreach ($this->pieces as $piece) {
            $merger->addPDF($piece->score_full_path, 'all');
        }
        $merger->merge();

        return $merger;
    }
}
