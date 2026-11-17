<?php

namespace App\Services\Pieces;

use DOMDocument;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\Process\{ExecutableFinder, Process};

class ScoreSource
{
    public const PIANOLIT = 'pianolit';
    public const EXTERNAL = 'external';
    public const UNKNOWN = 'unknown';

    private const FOOTER = 'PianoLIT eScore';
    private const MAX_BYTES = 50 * 1024 * 1024;

    private $executable;

    public function executable(): ?string
    {
        return $this->executable ??= (new ExecutableFinder)->find('pdftotext', null, [
            '/usr/local/bin', '/usr/bin', '/bin', '/opt/homebrew/bin',
        ]);
    }

    public function inspectSaved(string $path, array $references = []): array
    {
        return $this->inspect(Storage::disk('public')->path($path), $references);
    }

    public function inspect(string $file, array $references = []): array
    {
        if (!is_file($file) || !is_readable($file)) {
            return [null, 'PDF missing or unreadable'];
        }
        $size = @filesize($file);
        if (!$size || $size > self::MAX_BYTES) {
            return [null, 'PDF empty or larger than 50 MB'];
        }
        if ($references && isset($references[@hash_file('sha256', $file)])) {
            return [self::PIANOLIT, 'matches a reference PDF'];
        }
        $executable = $this->executable();
        if (!$executable) {
            return [null, 'pdftotext is unavailable'];
        }

        // Poppler includes text positions and PDF metadata in its HTML output.
        try {
            $process = new Process([$executable, '-f', '1', '-l', '3', '-bbox-layout', $file, '-']);
            $process->setTimeout(8);
            $process->run();
        } catch (\RuntimeException | \LogicException $exception) {
            return [null, 'PDF inspection failed'];
        }
        $output = $process->getOutput();
        if (!$process->isSuccessful() || $output === '' || strlen($output) > 4 * 1024 * 1024) {
            return [null, 'PDF inspection failed'];
        }

        $document = new DOMDocument;
        // Poppler can emit unclosed page elements for pages with no extractable text.
        if (!$document->loadHTML($output, LIBXML_NONET | LIBXML_NOERROR | LIBXML_NOWARNING)) {
            return [null, 'PDF text could not be read'];
        }
        $metadata = [];
        foreach ($document->getElementsByTagName('meta') as $meta) {
            $metadata[$meta->getAttribute('name')] = trim($meta->getAttribute('content'));
        }

        // PianoLIT's collection exporter can add branding to third-party sheet music.
        if (($metadata['Creator'] ?? '') === self::FOOTER || str_starts_with($metadata['Producer'] ?? '', 'FPDF')) {
            return [null, 'possibly a generated collection PDF'];
        }
        foreach ($document->getElementsByTagName('page') as $page) {
            $height = (float) $page->getAttribute('height');
            foreach ($page->getElementsByTagName('line') as $line) {
                if ($height <= 0 || (float) $line->getAttribute('ymin') < $height * 0.85) {
                    continue;
                }
                if (preg_replace('/\s+/', ' ', trim($line->textContent)) === self::FOOTER) {
                    return [self::PIANOLIT, 'PianoLIT eScore footer'];
                }
            }
        }

        return [null, $document->getElementsByTagName('word')->length
            ? 'no PianoLIT eScore footer'
            : 'no extractable text, possibly a scan'];
    }
}
