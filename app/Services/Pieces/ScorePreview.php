<?php

namespace App\Services\Pieces;

use App\PDF\Poppler;
use Illuminate\Support\Facades\Cache;
use Symfony\Component\Process\Exception\ProcessTimedOutException;
use Symfony\Component\Process\Process;

class ScorePreview
{
    public function image(string $file, bool $excerpt)
    {
        $size = filesize($file);
        abort_unless($size > 0 && $size <= 50 * 1024 * 1024, 404);

        $key = 'web-score-image-v1:'.sha1(implode('|', [$file, filemtime($file), $size, (int) $excerpt]));
        $image = Cache::remember($key, now()->addDay(), function () use ($file, $excerpt) {
            $renderer = Poppler::renderer();
            abort_unless($renderer, 503);

            // Without a destination prefix, pdftoppm writes the one image to stdout.
            $process = new Process([$renderer, '-f', '1', '-l', '1', '-singlefile', '-scale-to',
                $excerpt ? '1600' : '64', '-jpeg', $file]);
            $process->setTimeout(12);
            try {
                $process->run();
            } catch (ProcessTimedOutException $exception) {
                abort(503);
            }
            abort_unless($process->isSuccessful() && str_starts_with($process->getOutput(), "\xff\xd8"), 503);
            $jpeg = $process->getOutput();
            if (!$excerpt) return $jpeg;

            // Publish just the same center strip the web sight-reading question displays.
            $page = imagecreatefromstring($jpeg);
            abort_unless($page, 503);
            $width = imagesx($page);
            $height = max(1, min(imagesy($page), (int) round($width * 0.4)));
            $strip = imagecrop($page, ['x' => 0, 'y' => intdiv(imagesy($page) - $height, 2),
                'width' => $width, 'height' => $height]);
            abort_unless($strip, 503);
            ob_start();
            try {
                abort_unless(imagejpeg($strip, null, 90), 503);
                return ob_get_contents();
            } finally {
                ob_end_clean();
            }
        });

        return response($image, 200, ['Content-Type' => 'image/jpeg', 'X-Content-Type-Options' => 'nosniff',
            'Cache-Control' => 'private, max-age=300']);
    }
}
