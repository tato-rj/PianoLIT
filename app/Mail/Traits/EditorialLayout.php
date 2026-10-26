<?php

namespace App\Mail\Traits;

use Illuminate\Mail\Markdown;

trait EditorialLayout
{
    protected function websiteUrl(): string
    {
        $website = rtrim(config('app.url'), '/');
        if (!preg_match('#^https?://#i', $website)) {
            $website = ltrim($website, '/');
            $host = strtolower((string) parse_url('//'.$website, PHP_URL_HOST));
            $scheme = substr($host, -5) === '.test' ? request()->getScheme() : 'https';
            $website = $scheme.'://'.$website;
        }
        return $website;
    }

    protected function buildMarkdownView()
    {
        // Workers send other mail families too; keep this theme off the shared renderer.
        $markdown = clone app(Markdown::class);
        $markdown->theme($this->theme);
        $data = $this->buildViewData();

        return ['html' => $markdown->render($this->markdown, $data), 'text' => $markdown->renderText($this->markdown, $data)];
    }
}
