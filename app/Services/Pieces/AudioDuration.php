<?php

namespace App\Services\Pieces;

use Illuminate\Support\Facades\Storage;
use Symfony\Component\Process\Exception\RuntimeException;
use Symfony\Component\Process\Process;

class AudioDuration
{
    public function seconds(string $path): ?int
    {
        $disk = Storage::disk('public');
        // Legacy paths include the public/ prefix that Laravel strips from storage URLs.
        $relative = str_starts_with($path, 'public/') ? substr($path, 7) : $path;
        $root = realpath($disk->path(''));
        $file = realpath($disk->path($relative));
        if (!$root || !$file || !str_starts_with($file, $root.DIRECTORY_SEPARATOR)
            || !is_file($file) || !is_readable($file)) {
            return null;
        }

        $probe = new Process([
            config('match-tour.ffprobe', 'ffprobe'), '-v', 'error', '-protocol_whitelist', 'file',
            '-select_streams', 'a:0', '-show_entries', 'format=duration:stream=duration',
            '-of', 'json', $file,
        ], null, null, null, 10);
        try {
            $probe->run();
        } catch (RuntimeException $error) {
            return null;
        }

        if (!$probe->isSuccessful()) return null;

        $metadata = json_decode($probe->getOutput(), true);
        if (empty($metadata['streams'])) return null;

        $seconds = $metadata['format']['duration'] ?? $metadata['streams'][0]['duration'] ?? null;
        if (!is_numeric($seconds) || !is_finite((float) $seconds) || $seconds <= 0 || $seconds > 4294967295) {
            return null;
        }

        return (int) floor((float) $seconds);
    }
}
