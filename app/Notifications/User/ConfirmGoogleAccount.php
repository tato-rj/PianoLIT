<?php

namespace App\Notifications\User;

use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Messages\MailMessage;

class ConfirmGoogleAccount extends Notification
{
    public $url;
    private $purpose;
    public $provider;

    public function __construct($url, $purpose, $provider = 'google')
    {
        $this->url = $url;
        $this->purpose = $purpose;
        $this->provider = $provider;
    }

    public function via($notifiable) { return ['mail']; }

    public function automaticEmailKey()
    {
        return $this->provider.'-'.$this->purpose;
    }

    public function toMail($notifiable)
    {
        return (new MailMessage)->subject('Confirm your PianoLIT account')
            ->line($this->purpose === 'link' ? 'Confirm ownership of your existing PianoLIT account to connect '.(new \App\Auth\Google\Accounts)->provider($this->provider).'.' : ($this->purpose === 'signup' ? 'Verify this email address to finish creating your PianoLIT account with Apple.' : 'Confirm your PianoLIT identity before updating your sign-in options.'))
            ->line('Open this link in the same browser where you started. It expires in 15 minutes and can be used only once.')
            ->action('Confirm my account', $this->url)
            ->line('If you did not request this, you can ignore this email.');
    }
}
