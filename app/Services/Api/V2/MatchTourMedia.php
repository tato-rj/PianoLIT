<?php

namespace App\Services\Api\V2;

use Illuminate\Support\Facades\{File, Storage, URL};
use Symfony\Component\Process\{ExecutableFinder, Process};
use Symfony\Component\Process\Exception\{ProcessFailedException, ProcessTimedOutException};

/** Private, prebuilt samples only. Never redirects to original audio or scores. */
class MatchTourMedia
{
    const FILES = ['audio' => 'audio.mp3', 'excerpt' => 'excerpt.png', 'sample' => 'sample.png'];

    /** Read-only CLI checks: fail once for deployment prerequisites, before the catalogue. */
    public function preparationErrors(): array
    {
        $errors = [];
        if (!function_exists('proc_open')) $errors[] = 'PHP proc_open is unavailable; enable process execution for the CLI user.';
        if (!extension_loaded('gd') || !function_exists('imagecreatefrompng')) $errors[] = 'PHP GD with PNG support is unavailable; install/enable it for CLI PHP.';
        $finder = new ExecutableFinder;
        foreach (['ffmpeg', 'ffprobe', 'pdftoppm'] as $tool) {
            $configured = config('match-tour.'.$tool);
            if (!is_string($configured) || trim($configured) === '') {
                $errors[] = 'Missing match-tour.'.$tool.' configuration; rebuild Laravel configuration with php artisan config:cache.';
            } elseif (!(strpos($configured, DIRECTORY_SEPARATOR) !== false
                ? is_file($configured) && is_executable($configured) : $finder->find($configured))) {
                $errors[] = $tool.' executable was not found. Install it or set MATCH_TOUR_'.strtoupper($tool).' to its executable path, then rebuild configuration.';
            }
        }
        if (!is_string(config('match-tour.media_root')) || config('match-tour.media_root') === '') {
            $errors[] = 'Missing match-tour.media_root configuration; rebuild Laravel configuration with php artisan config:cache.';
        }
        return $errors;
    }

    public static function https(?string $url): ?string
    {
        if (!$url) return null;
        $parts = parse_url($url);
        if (!$parts || empty($parts['host']) || isset($parts['user']) || isset($parts['pass'])
            || !in_array(strtolower($parts['scheme'] ?? ''), ['http', 'https'], true)) return null;
        return preg_replace('#^http://#i', 'https://', $url);
    }

    public function descriptor(string $path, string $type): array
    {
        $source = $this->source($path);
        $seconds = min(60, max(1, (int) config('webapp.match_tour_audio_seconds', 60)));
        $key = hash('sha256', json_encode(['v1', $type, $path, filemtime($source), filesize($source), $seconds]));
        return ['key' => $key, 'source' => $source, 'seconds' => $seconds];
    }

    private function source(string $path): string
    {
        // Only local public-disk files selected by the shared tour. No remote inputs.
        // Older upload() records are relative to the local disk and start with
        // public/. Laravel's existing storage URL helper removes that prefix.
        // Resolve the same file here without rewriting stored/legacy paths.
        $relative = strpos($path, 'public/') === 0 ? substr($path, 7) : $path;
        $root = realpath(Storage::disk('public')->path(''));
        $source = realpath(Storage::disk('public')->path($relative));
        if (!$root || !$source || strpos($source, $root.DIRECTORY_SEPARATOR) !== 0 || !is_file($source)) {
            throw new MatchTourFailure('media_unavailable', 'A tour sample is unavailable. Please try again later.', 503);
        }
        return $source;
    }

    public function asset(string $path, string $type): array
    {
        $descriptor = $this->descriptor($path, $type);
        $directory = config('match-tour.media_root').'/'.$descriptor['key'];
        $manifest = is_file($directory.'/manifest.json') ? json_decode(file_get_contents($directory.'/manifest.json'), true) : null;
        foreach ($type === 'audio' ? ['audio'] : ['excerpt', 'sample'] as $kind) {
            if (!$manifest || !is_file($directory.'/'.self::FILES[$kind])) {
                throw new MatchTourFailure('media_unavailable', 'A tour sample is unavailable. Please try again later.', 503);
            }
        }
        if ($type === 'audio' && (!is_numeric($manifest['duration_seconds'] ?? null)
            || $manifest['duration_seconds'] <= 0 || $manifest['duration_seconds'] > $descriptor['seconds'] + 0.15)) {
            throw new MatchTourFailure('media_unavailable', 'A tour sample is unavailable. Please try again later.', 503);
        }
        return ['key' => $descriptor['key'], 'duration_seconds' => $manifest['duration_seconds'] ?? null];
    }

    public function urls(array $asset, string $type, $tourExpires): array
    {
        $expires = now()->addMinutes(15)->min(carbon($tourExpires));
        $base = self::https(\App\Support\ProfileUrl::base());
        if (!$base) throw new MatchTourFailure('media_unavailable', 'Tour media is not configured.', 503);
        $result = ['expires_at' => $expires->copy()->utc()->format('Y-m-d\TH:i:s\Z')];
        foreach ($type === 'audio' ? ['audio'] : ['excerpt', 'sample'] as $kind) {
            $result[$kind.'_url'] = $base.URL::temporarySignedRoute('api.v2.match-tour-media', $expires,
                ['asset' => $asset['key'], 'kind' => $kind], false);
        }
        if ($type === 'audio') $result['duration_seconds'] = $asset['duration_seconds'];
        return $result;
    }

    public function file(string $key, string $kind): string
    {
        $file = config('match-tour.media_root').'/'.$key.'/'.self::FILES[$kind];
        if (!is_file($file)) throw new MatchTourFailure('media_unavailable', 'This tour sample is unavailable.', 503);
        return $file;
    }

    /** Explicit offline command only; never invoked by an HTTP request. */
    public function prepare(string $path, string $type): void
    {
        try { $descriptor = $this->descriptor($path, $type); }
        catch (MatchTourFailure $error) {
            throw new MatchTourPreparationFailure('Source file is missing or outside the configured public disk. Check the saved media file and its permissions.', 0, $error);
        }
        if (!is_readable($descriptor['source'])) throw new MatchTourPreparationFailure('The deploy user cannot read the source file. Check its permissions.');
        $destination = config('match-tour.media_root').'/'.$descriptor['key'];
        if (is_file($destination.'/manifest.json')) {
            try { $this->asset($path, $type); return; }
            catch (MatchTourFailure $error) { /* Rebuild missing or incomplete derivatives. */ }
        }
        File::ensureDirectoryExists(config('match-tour.media_root'), 0750);
        $temporary = config('match-tour.media_root').'/.build-'.bin2hex(random_bytes(12));
        File::makeDirectory($temporary, 0750);
        try {
            $duration = null;
            if ($type === 'audio') {
                $probe = $this->run([config('match-tour.ffprobe'), '-v', 'error', '-show_entries', 'format=duration', '-of', 'default=noprint_wrappers=1:nokey=1', $descriptor['source']], 30, 'Source audio probe', [$descriptor['source'], $temporary]);
                $length = (float) trim($probe->getOutput());
                if (!is_finite($length) || $length <= 0) throw new MatchTourPreparationFailure('Source audio has no readable positive duration. Check its encoding.');
                $this->run([config('match-tour.ffmpeg'), '-nostdin', '-v', 'error', '-ss', (string) ($length * 0.4), '-i', $descriptor['source'],
                    '-t', (string) $descriptor['seconds'], '-map', '0:a:0', '-vn', '-map_metadata', '-1', '-codec:a', 'libmp3lame', '-b:a', '128k', $temporary.'/audio.mp3'], 120, 'Audio encoding', [$descriptor['source'], $temporary]);
                // Reprobe the encoded sample; duration includes at most MP3 frame padding.
                $probe = $this->run([config('match-tour.ffprobe'), '-v', 'error', '-show_entries', 'format=duration', '-of', 'default=noprint_wrappers=1:nokey=1', $temporary.'/audio.mp3'], 30, 'Encoded audio probe', [$descriptor['source'], $temporary]);
                $duration = round((float) trim($probe->getOutput()), 3);
                if ($duration <= 0 || $duration > $descriptor['seconds'] + 0.15) throw new MatchTourPreparationFailure('Encoded preview duration is outside its permitted bounds.');
            } else {
                // The same center strip of page one as the website, at two resolutions.
                $this->run([config('match-tour.pdftoppm'), '-f', '1', '-singlefile', '-scale-to', '2400', '-png', $descriptor['source'], $temporary.'/page'], 60, 'Score rendering', [$descriptor['source'], $temporary]);
                $page = imagecreatefrompng($temporary.'/page.png');
                if (!$page) throw new MatchTourPreparationFailure('Rendered score image could not be read.');
                try {
                    $width = imagesx($page); $height = min(imagesy($page), (int) round($width * 0.4));
                    $top = (int) floor((imagesy($page) - $height) / 2);
                    foreach (['excerpt' => 900, 'sample' => 1800] as $kind => $size) {
                        $crop = imagecreatetruecolor($size, (int) round($height * $size / $width));
                        imagecopyresampled($crop, $page, 0, 0, 0, $top, imagesx($crop), imagesy($crop), $width, $height);
                        imagepng($crop, $temporary.'/'.self::FILES[$kind]); imagedestroy($crop);
                    }
                } finally { imagedestroy($page); }
                unlink($temporary.'/page.png');
            }
            file_put_contents($temporary.'/manifest.json', json_encode(['duration_seconds' => $duration]));
            if (!@rename($temporary, $destination)) {
                // Concurrent preparation or repair of an incomplete generation.
                // Replace each bounded file atomically and publish metadata last.
                File::ensureDirectoryExists($destination, 0750);
                foreach ($type === 'audio' ? ['audio.mp3', 'manifest.json'] : ['excerpt.png', 'sample.png', 'manifest.json'] as $file) {
                    if (!rename($temporary.'/'.$file, $destination.'/'.$file)) throw new MatchTourPreparationFailure('Could not publish preview files. Check free disk space and private sample-directory permissions.');
                }
            }
        } finally { if (is_dir($temporary)) File::deleteDirectory($temporary); }
    }

    private function run(array $command, int $timeout, string $stage, array $privatePaths): Process
    {
        $process = new Process($command);
        try { $process->setTimeout($timeout)->mustRun(); }
        catch (ProcessTimedOutException $error) {
            throw new MatchTourPreparationFailure($stage.' timed out after '.$timeout.' seconds.', 0, $error);
        } catch (ProcessFailedException $error) {
            $detail = str_replace($privatePaths, '[file]', $process->getErrorOutput());
            // Tool output can include paths or URLs embedded in malformed input.
            // Keep useful codec/format errors without publishing those locations.
            $detail = preg_replace('#https?://[^\s<>]+#i', '[URL]', $detail);
            $detail = preg_replace('#(?:/[A-Za-z0-9_.-]+){2,}#', '[path]', $detail);
            $detail = preg_replace('/[\x00-\x1f\x7f]+/', ' ', $detail);
            $detail = substr(trim($detail), 0, 700);
            throw new MatchTourPreparationFailure($stage.' failed (exit '.$process->getExitCode().').'.($detail ? ' '.$detail : ''), 0, $error);
        }
        return $process;
    }
}
