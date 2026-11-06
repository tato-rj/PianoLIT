<?php

namespace App\Services\Shop;

use Illuminate\Support\Facades\{File, Log, Storage};
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
        $stage = 'source';
        $process = null;
        try {
            if (!Storage::disk('public')->exists($path)) throw new \RuntimeException('Missing PDF');
            $stage = 'size';
            if (Storage::disk('public')->size($path) > 50 * 1024 * 1024) throw new \RuntimeException('Oversized PDF');
            $stage = 'renderer';
            $executable = $this->renderer();
            if (!$executable) throw new \RuntimeException('PDF renderer missing');
            $stage = 'workspace';
            File::makeDirectory($directory, 0700, true);
            $stage = 'source';
            $input = Storage::disk('public')->readStream($path);
            if (!$input) throw new \RuntimeException('PDF cannot be read');
            $stage = 'workspace';
            $output = null;
            try {
                $output = fopen($directory.'/source.pdf', 'wb');
                if (!$output || stream_copy_to_stream($input, $output, 50 * 1024 * 1024 + 1) === false) {
                    throw new \RuntimeException('PDF copy failed');
                }
            } finally {
                fclose($input);
                if (is_resource($output)) fclose($output);
            }
            $stage = 'render';
            $process = new Process([$executable, '-f', '1', '-l', '7', '-scale-to', '1600',
                '-jpeg', '-jpegopt', 'quality=85', $directory.'/source.pdf', $directory.'/page']);
            $process->setTimeout(90);
            $process->mustRun();
            $pages = glob($directory.'/page-*.jpg');
            natsort($pages);
            if (!$pages || count($pages) > 7) throw new \RuntimeException('No preview pages');
            $stage = 'storage';
            $prefix = 'shop/digital/previews/'.Str::uuid();
            foreach (array_values($pages) as $index => $page) {
                $target = $prefix.'/page-'.($index + 1).'.jpg';
                $saved[] = $target;
                if (!Storage::disk('public')->put($target, file_get_contents($page))) throw new \RuntimeException('Preview storage failed');
            }
            return $saved;
        } catch (\Throwable $exception) {
            Storage::disk('public')->delete($saved);
            // Keep diagnostics without logging original file paths, bytes or process arguments.
            Log::warning('Digital PDF preview failed.', ['stage' => $stage, 'exception' => get_class($exception),
                'exit_code' => $process ? $process->getExitCode() : null]);
            $messages = [
                'source' => 'The saved PDF could not be read. Upload the PDF again and save the product.',
                'size' => 'Use a PDF up to 50 MB to generate the preview.',
                'renderer' => 'The PDF preview renderer is unavailable on the server. Check the renderer installation and configuration.',
                'workspace' => 'The server could not prepare the PDF preview. Check its temporary storage permissions.',
                'render' => 'The PDF could not be rendered. Check that it opens without a password, then try again.',
                'storage' => 'The preview pages could not be saved. Check the server storage and try again.',
            ];
            throw ValidationException::withMessages(['pdf_file' => $messages[$stage]]);
        } finally {
            if (is_dir($directory)) File::deleteDirectory($directory);
        }
    }

    private function renderer(): ?string
    {
        // A cache built before this feature has no digital-media configuration yet.
        $name = config('digital-media.pdftoppm') ?: 'pdftoppm';
        // Symfony's legacy finder accepts command names, not absolute paths.
        if (strpos($name, '/') !== false) return is_file($name) && is_executable($name) ? $name : null;
        // PHP-FPM commonly clears PATH. Include standard Linux locations explicitly.
        return app(ExecutableFinder::class)->find($name, null, ['/usr/local/bin', '/usr/bin', '/bin', '/opt/homebrew/bin']);
    }
}
