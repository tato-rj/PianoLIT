<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Markdown;

class AccountWelcome extends Mailable implements ShouldQueue
{
    use Queueable;

    public $firstName;

    public $theme = 'welcome';

    protected $signupOrigin = 'webapp';

    public function __construct(string $firstName)
    {
        $this->firstName = $firstName;
    }

    public function build()
    {
        $website = rtrim(config('app.url'), '/');
        if (!preg_match('#^https?://#i', $website)) {
            $website = ltrim($website, '/');
            $host = strtolower((string) parse_url('//'.$website, PHP_URL_HOST));
            // Local .test sites may serve HTTP only; keep production's HTTPS default.
            $scheme = substr($host, -5) === '.test' ? request()->getScheme() : 'https';
            $website = $scheme.'://'.$website;
        }
        $views = [
            'web' => 'emails.welcome.website',
            'webapp' => 'emails.welcome.webapp',
            'ios' => 'emails.welcome.ios',
        ];

        return $this->subject('Welcome to PianoLIT!')->markdown($views[$this->signupOrigin], [
            'websiteUrl' => $website,
            'webappUrl' => route('webapp.discover'),
            'iosUrl' => config('app.stores.ios'),
        ]);
    }

    protected function buildMarkdownView()
    {
        // A queue worker also sends other emails. Keep this theme off Laravel's
        // shared renderer so the next ordinary message retains its own styling.
        $markdown = clone app(Markdown::class);
        $markdown->theme($this->theme);
        $data = $this->buildViewData();

        return ['html' => $markdown->render($this->markdown, $data), 'text' => $markdown->renderText($this->markdown, $data)];
    }
}
