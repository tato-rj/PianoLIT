<?php

namespace App\Mail\Traits;

use Illuminate\Mail\Markdown;

trait EditorialLayout
{
    protected function websiteUrl(): string
    {
        return \App\Support\EmailLayout::websiteUrl();
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
