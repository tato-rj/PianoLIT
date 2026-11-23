<?php

use Valet\Drivers\LaravelValetDriver;

require_once __DIR__.'/support/browser-score-files.php';

class LocalValetDriver extends LaravelValetDriver
{
    public function mutateUri(string $uri): string
    {
        $file = parent::isStaticFile(__DIR__, '', $uri);
        if ($file && isBrowserScoreFile($_SERVER['HTTP_HOST'], $file)) return '/index.php';

        return $uri;
    }
}
