<?php

namespace App\Notifications\User;

use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Messages\MailMessage;

class ConfirmGoogleAccount extends Notification
{
    public $url;
    private $purpose;

    public function __construct($url, $purpose)
    {
        $this->url = $url;
        $this->purpose = $purpose;
    }

    public function via($notifiable) { return ['mail']; }

    public function toMail($notifiable)
    {
        return (new MailMessage)->subject('Confirm your PianoLIT account')
            ->line($this->purpose === 'link' ? 'Confirm ownership of your existing PianoLIT account to connect Google.' : 'Confirm your PianoLIT identity before updating your sign-in options.')
            ->line('Open this link in the same browser where you started. It expires in 15 minutes and can be used only once.')
            ->action('Confirm my account', $this->url)
            ->line('If you did not request this, you can ignore this email.');
    }
}
