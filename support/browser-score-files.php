<?php

// The PHP development servers serve existing files before Laravel boots.
function isBrowserScoreFile(string $host, string $file): bool
{
    if (!preg_match('/^(admin|my|shop|learn|games|blog|metronome)\./i', $host)) return false;

    $root = realpath(__DIR__.'/../storage/app/public/app/score');
    $path = realpath($file);

    // Valet also resolves aliases on case-insensitive macOS volumes.
    return $root && $path && stripos($path, $root.DIRECTORY_SEPARATOR) === 0;
}
