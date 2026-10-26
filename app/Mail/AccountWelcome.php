<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use App\Mail\Traits\EditorialLayout;

class AccountWelcome extends Mailable implements ShouldQueue
{
    use Queueable, EditorialLayout;

    public $firstName;

    public $theme = 'welcome';

    // Resolve once so HTML and plain text use the same scalar cards.
    public $welcomePieces;

    protected $signupOrigin = 'webapp';

    public function __construct(string $firstName)
    {
        $this->firstName = $firstName;
    }

    public function build()
    {
        $website = $this->websiteUrl();
        $views = [
            'web' => 'emails.welcome.website',
            'webapp' => 'emails.welcome.webapp',
            'ios' => 'emails.welcome.ios',
        ];

        if ($this->welcomePieces === null) {
            $this->welcomePieces = app(\App\Services\WelcomePieces::class)->random($website);
        }
        $this->welcomePieces = array_map(function ($piece) use ($website) {
            if (strpos($piece['image'], '/') === 0) $piece['image'] = $website.$piece['image'];
            return $piece;
        }, $this->welcomePieces);

        return $this->subject('Welcome to PianoLIT!')->markdown($views[$this->signupOrigin], [
            'websiteUrl' => $website,
            'webappUrl' => route('webapp.discover'),
            'iosUrl' => config('app.stores.ios'),
            'premiumUrl' => route('webapp.membership.pricing'),
            'repertoireUrl' => route('webapp.explore'),
            'unsubscribeUrl' => $website.route('users.profile', [], false).'?tab=email-preferences',
        ]);
    }
}
