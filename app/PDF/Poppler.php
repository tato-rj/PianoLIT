<?php

namespace App\PDF;

use Symfony\Component\Process\ExecutableFinder;

class Poppler
{
    public static function renderer(): ?string
    {
        // Older configuration caches lack this key. FPM can also have an empty PATH.
        $name = config('digital-media.pdftoppm') ?: 'pdftoppm';
        if (strpos($name, '/') !== false) return is_file($name) && is_executable($name) ? $name : null;

        return app(ExecutableFinder::class)->find($name, null, ['/usr/local/bin', '/usr/bin', '/bin', '/opt/homebrew/bin']);
    }
}
