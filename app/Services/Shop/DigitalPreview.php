<?php

namespace App\Services\Shop;

use Illuminate\Support\Facades\{File, Storage};
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Symfony\Component\Process\{ExecutableFinder, Process};

class DigitalPreview
{
    /** Render only the cover and next six pages. The full PDF is never sent to the preview UI. */
    public function render(string $path): array
    {
        $directory = storage_path('app/digital-preview-tmp/'.Str::uuid());
        $saved = [];
        try {
            if (!Storage::disk('public')->exists($path) || Storage::disk('public')->size($path) > 50 * 1024 * 1024) {
                throw new \RuntimeException('Missing or oversized PDF');
            }
            File::makeDirectory($directory, 0700, true);
            $input = Storage::disk('public')->readStream($path);
            if (!$input) throw new \RuntimeException('PDF cannot be read');
            $output = fopen($directory.'/source.pdf', 'wb');
            try { stream_copy_to_stream($input, $output, 50 * 1024 * 1024 + 1); }
            finally { fclose($input); fclose($output); }
            $executable = (new ExecutableFinder)->find(config('digital-media.pdftoppm'), null, ['/opt/homebrew/bin', '/usr/local/bin']);
            if (!$executable) throw new \RuntimeException('PDF renderer missing');
            $process = new Process([$executable, '-f', '1', '-l', '7', '-scale-to', '1600',
                '-jpeg', '-jpegopt', 'quality=85', $directory.'/source.pdf', $directory.'/page']);
            $process->setTimeout(90);
            $process->mustRun();
            $pages = glob($directory.'/page-*.jpg');
            natsort($pages);
            if (!$pages || count($pages) > 7) throw new \RuntimeException('No preview pages');
            $prefix = 'shop/digital/previews/'.Str::uuid();
            foreach (array_values($pages) as $index => $page) {
                $target = $prefix.'/page-'.($index + 1).'.jpg';
                $saved[] = $target;
                if (!Storage::disk('public')->put($target, file_get_contents($page))) throw new \RuntimeException('Preview storage failed');
            }
            return $saved;
        } catch (\Throwable $exception) {
            Storage::disk('public')->delete($saved);
            throw ValidationException::withMessages(['pdf_file' => 'The PDF preview could not be generated. Use an unlocked PDF up to 50 MB and try again. If it still fails, check that the PDF renderer is installed.']);
        } finally {
            if (is_dir($directory)) File::deleteDirectory($directory);
        }
    }
}
