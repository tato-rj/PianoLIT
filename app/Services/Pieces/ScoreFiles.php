<?php

namespace App\Services\Pieces;

use App\Piece;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class ScoreFiles
{
    public function forPiece(Piece $piece): string
    {
        abort_unless($piece->isPublicDomain, 404);
        $version = request()->query('version');
        abort_if($version !== null && $version !== hash('sha256', (string) $piece->score_path), 404);

        return $this->saved($piece->score_path);
    }

    public function saved(?string $path): string
    {
        abort_unless($path, 404);
        // Legacy upload() stored the local-disk prefix in some piece records.
        $path = preg_replace('~^public/~', '', $path);

        return $this->resolve('', $path);
    }

    public function adminFile(string $filename): string
    {
        return $this->resolve('app/score', $filename);
    }

    private function resolve(string $directory, string $path): string
    {
        $root = realpath(Storage::disk('public')->path($directory));
        $file = $root ? realpath($root.DIRECTORY_SEPARATOR.$path) : false;
        abort_unless($file && str_starts_with($file, $root.DIRECTORY_SEPARATOR)
            && strtolower(pathinfo($file, PATHINFO_EXTENSION)) === 'pdf'
            && is_file($file) && is_readable($file), 404);

        return $file;
    }

    public function response(string $file, string $disposition = 'inline'): BinaryFileResponse
    {
        return new BinaryFileResponse($file, 200, [
            'Content-Type' => 'application/pdf',
            'Cache-Control' => 'private, no-store',
            'X-Content-Type-Options' => 'nosniff',
        ], false, $disposition);
    }
}
