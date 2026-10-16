<?php

namespace App\Services\Admin;

use App\{Composer, Piece, TutorialRequest, User};
use App\Mail\Performances\{PerformanceApprovedEmail, PerformanceSubmittedEmail};
use App\Mail\Shop\ConfirmPurchase;
use App\Mail\Memberships\MembershipConfirmed;
use App\Mail\SharePieceEmail;
use App\Mail\{AccountWelcome, IosAccountWelcome};
use App\Mail\Tutorials\{NewRequestEmail, RequestPublishedEmail};
use App\Notifications\User\ConfirmGoogleAccount;
use Illuminate\Auth\Notifications\{ResetPassword, VerifyEmail};
use Illuminate\Http\Request;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Markdown;
use Illuminate\Support\Facades\URL;

class AutomaticEmails
{
    /** Fixed, user-facing transactional messages; never resolve submitted class/view names. */
    public function all()
    {
        return collect([
            'account-welcome' => ['name' => 'Web app welcome', 'category' => 'Account', 'trigger' => 'After a new account is created on the website or web app.', 'default_enabled' => false],
            'account-welcome-ios' => ['name' => 'iOS app welcome', 'category' => 'Account', 'trigger' => 'After a new account is created from the iOS app.', 'default_enabled' => false],
            'verify-email' => ['name' => 'Signup · verify email', 'category' => 'Account', 'trigger' => 'After password signup, or when email verification is requested again.'],
            'reset-password' => ['name' => 'Reset password', 'category' => 'Account', 'trigger' => 'When someone requests a password reset.'],
            'google-link' => ['name' => 'Connect Google · confirm account', 'category' => 'Account', 'trigger' => 'When confirming an existing account before connecting Google.'],
            'google-reauth' => ['name' => 'Google · confirm sign-in changes', 'category' => 'Account', 'trigger' => 'When email confirmation is used to update Google sign-in options.'],
            'apple-link' => ['name' => 'Connect Apple · confirm account', 'category' => 'Account', 'trigger' => 'When confirming an existing account before connecting Apple.'],
            'apple-reauth' => ['name' => 'Apple · confirm sign-in changes', 'category' => 'Account', 'trigger' => 'When email confirmation is used to update Apple sign-in options.'],
            'apple-signup' => ['name' => 'Apple signup · verify email', 'category' => 'Account', 'trigger' => 'When an email address is supplied to finish Apple signup.'],
            'purchase-confirmation' => ['name' => 'Purchase confirmation', 'category' => 'Shop', 'trigger' => 'After a signed-in customer purchases an eBook or eScore.'],
            'membership-confirmation' => ['name' => 'Membership confirmation', 'category' => 'Membership', 'trigger' => 'After a web membership checkout is successfully completed, including a free trial.'],
            'tutorial-request' => ['name' => 'Tutorial request received', 'category' => 'Tutorials', 'trigger' => 'After a tutorial request is submitted.'],
            'tutorial-published' => ['name' => 'Requested tutorial ready', 'category' => 'Tutorials', 'trigger' => 'When a requested tutorial is published.'],
            'performance-submitted' => ['name' => 'Performance submitted', 'category' => 'Performances', 'trigger' => 'After a performance is submitted for review.'],
            'performance-approved' => ['name' => 'Performance approved', 'category' => 'Performances', 'trigger' => 'When a submitted performance is approved.'],
            'piece-shared' => ['name' => 'A piece shared with you', 'category' => 'Sharing', 'trigger' => 'When someone sends a piece to an email address.'],
        ]);
    }

    public function find($key)
    {
        abort_unless($this->all()->has($key), 404);

        return $this->all()->get($key);
    }

    public function render($key)
    {
        $this->find($key);

        // Render host-independent links/assets as the public website, even on admin.*.
        // Restore the original generator so subsequent admin navigation stays on admin.*.
        $originalUrls = app('url');
        $publicUrls = clone $originalUrls;
        $publicUrls->setRequest(Request::create(config('app.url')));
        $publicUrls->forceRootUrl(config('app.url'));
        $publicUrls->forceScheme(parse_url(config('app.url'), PHP_URL_SCHEME));
        URL::swap($publicUrls);

        try {
            $mail = $this->sample($key);
            app()->call([$mail, 'build']);
            $markdown = app(Markdown::class);
            $data = $mail->buildViewData();
            $html = (string) $markdown->render($mail->markdown, $data);
            $text = (string) $markdown->renderText($mail->markdown, $data);

            return ['subject' => $mail->subject, 'html' => $html, 'text' => $text];
        } finally {
            URL::swap($originalUrls);
        }
    }

    private function sample($key)
    {
        // Unsaved fixtures avoid real customers, purchases, tokens, events and queues.
        $user = new User(['first_name' => 'Jamie', 'last_name' => 'Parker', 'email' => 'jamie@example.com']);
        $piece = new Piece(['id' => 0, 'name' => 'Prelude', 'key' => 'E minor', 'catalogue_name' => 'Op.', 'catalogue_number' => '28', 'collection_number' => 4]);
        $piece->setRelation('composer', new Composer(['name' => 'Frédéric Chopin']));
        $piece->setRelation('tags', collect());
        $request = (new TutorialRequest)->setRelation('user', $user)->setRelation('piece', $piece);

        switch ($key) {
            case 'account-welcome': return new AccountWelcome('Jamie');
            case 'account-welcome-ios': return new IosAccountWelcome('Jamie');
            case 'verify-email':
                $notification = new class extends VerifyEmail {
                    protected function verificationUrl($notifiable) { return '#email-preview'; }
                };
                break;
            case 'reset-password':
                $notification = new class('preview-only') extends ResetPassword {
                    protected function resetUrl($notifiable) { return '#email-preview'; }
                };
                break;
            case 'google-link':
            case 'google-reauth':
            case 'apple-link':
            case 'apple-reauth':
            case 'apple-signup':
                [$provider, $purpose] = explode('-', $key);
                $notification = new ConfirmGoogleAccount('#email-preview', $purpose, $provider);
                break;
            case 'purchase-confirmation':
                $mail = new ConfirmPurchase((object) ['title' => 'The Pianist’s Practice Companion']);
                $mail->user = $user;
                return $mail;
            case 'membership-confirmation': return new MembershipConfirmed('Jamie', 'yearly', true, 'October 22, 2026');
            case 'tutorial-request': return new NewRequestEmail($request);
            case 'tutorial-published': return new RequestPublishedEmail($request);
            case 'performance-submitted': return new PerformanceSubmittedEmail($piece);
            case 'performance-approved': return new PerformanceApprovedEmail($piece);
            case 'piece-shared':
                $mail = new SharePieceEmail($piece, $user);
                // A fictional piece must never resolve to an unrelated catalogue record.
                $mail->url = '#email-preview';
                return $mail;
        }

        $message = $notification->toMail($user);

        $mail = new class extends Mailable {
            public function build() { return $this; }
        };

        return $mail->subject($message->subject)->markdown($message->markdown, $message->data());
    }
}
