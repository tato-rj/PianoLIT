<?php

namespace App\Services\Admin;

use App\{Composer, Piece, TutorialRequest, User};
use App\Mail\Performances\{PerformanceApprovedEmail, PerformanceSubmittedEmail};
use App\Mail\Shop\ConfirmPurchase;
use App\Mail\Memberships\MembershipConfirmed;
use App\Mail\SharePieceEmail;
use App\Mail\{AccountWelcome, IosAccountWelcome, WebsiteAccountWelcome};
use App\Mail\Admin\AdminReport;
use App\Mail\Tutorials\{NewRequestEmail, RequestPublishedEmail};
use App\Notifications\User\ConfirmGoogleAccount;
use Illuminate\Auth\Notifications\{ResetPassword, VerifyEmail};
use Illuminate\Http\Request;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Markdown;
use Illuminate\Support\Facades\URL;

class AutomaticEmails
{
    /** Fixed automatic messages; never resolve submitted class/view names. */
    public function all()
    {
        return collect([
            'account-welcome-website' => ['name' => 'Website welcome', 'category' => 'Account', 'trigger' => 'After a new account is created on pianolit.com.', 'default_enabled' => false],
            'account-welcome' => ['name' => 'Web app welcome', 'category' => 'Account', 'trigger' => 'After a new account is created on the web app.', 'default_enabled' => false],
            'account-welcome-ios' => ['name' => 'iOS app welcome', 'category' => 'Account', 'trigger' => 'After a new account is created from the iOS app.', 'default_enabled' => false],
            'verify-email' => ['name' => 'Signup · verify email', 'category' => 'Account', 'trigger' => 'After password signup, or when email verification is requested again.'],
            'reset-password' => ['name' => 'Reset password', 'category' => 'Account', 'trigger' => 'When someone requests a password reset.'],
            'google-link' => ['name' => 'Connect Google · confirm account', 'category' => 'Account', 'trigger' => 'When confirming an existing account before connecting Google.'],
            'google-reauth' => ['name' => 'Google · confirm sign-in changes', 'category' => 'Account', 'trigger' => 'When email confirmation is used to update Google sign-in options.'],
            'apple-link' => ['name' => 'Connect Apple · confirm account', 'category' => 'Account', 'trigger' => 'When confirming an existing account before connecting Apple.'],
            'apple-reauth' => ['name' => 'Apple · confirm sign-in changes', 'category' => 'Account', 'trigger' => 'When email confirmation is used to update Apple sign-in options.'],
            'apple-signup' => ['name' => 'Apple signup · verify email', 'category' => 'Account', 'trigger' => 'When an email address is supplied to finish Apple signup.'],
            'purchase-confirmation' => ['name' => 'Purchase confirmation', 'category' => 'Shop', 'trigger' => 'After a signed-in customer purchases an eBook or eScore.'],
            'merchandise-confirmation' => ['name' => 'Merchandise purchase confirmation', 'category' => 'Shop', 'trigger' => 'After Stripe verifies a merchandise payment. Includes a private order link.'],
            'merchandise-shipped' => ['name' => 'Merchandise shipped', 'category' => 'Shop', 'trigger' => 'After a verified Printful shipment update, once per package. Includes carrier and tracking details.'],
            'merchandise-delivered' => ['name' => 'Merchandise delivered', 'category' => 'Shop', 'trigger' => 'When all packages are reported delivered, once per order. Subject: Your order arrived!'],
            'membership-confirmation' => ['name' => 'Membership confirmation', 'category' => 'Membership', 'trigger' => 'After a web membership checkout is successfully completed, including a free trial.'],
            'tutorial-request' => ['name' => 'Tutorial request received', 'category' => 'Tutorials', 'trigger' => 'After a tutorial request is submitted.'],
            'tutorial-published' => ['name' => 'Requested tutorial ready', 'category' => 'Tutorials', 'trigger' => 'When a requested tutorial is published.'],
            'performance-submitted' => ['name' => 'Performance submitted', 'category' => 'Performances', 'trigger' => 'After a performance is submitted for review.'],
            'performance-approved' => ['name' => 'Performance approved', 'category' => 'Performances', 'trigger' => 'When a submitted performance is approved.'],
            'piece-shared' => ['name' => 'A piece shared with you', 'category' => 'Sharing', 'trigger' => 'When someone sends a piece to an email address.'],
            'admin-weekly-snapshot' => ['name' => 'Weekly admin snapshot', 'category' => 'Administration', 'trigger' => 'Sundays at 10:30 PM New York time, sent to admin managers.'],
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
        $root = config('app.url');
        if (!preg_match('#^https?://#i', $root)
            && substr(strtolower((string) parse_url('//'.ltrim($root, '/'), PHP_URL_HOST)), -5) === '.test') {
            $root = request()->getScheme().'://'.ltrim($root, '/');
        }
        $publicUrls->setRequest(Request::create($root));
        $publicUrls->forceRootUrl($root);
        $publicUrls->forceScheme(parse_url($root, PHP_URL_SCHEME));
        URL::swap($publicUrls);

        try {
            $mail = $this->sample($key);
            app()->call([$mail, 'build']);
            if ($key === 'admin-weekly-snapshot') {
                $mail->with(['analyticsUrl' => '#email-preview', 'membershipsUrl' => '#email-preview', 'campaignsUrl' => '#email-preview']);
            }
            $markdown = clone app(Markdown::class);
            $markdown->theme($mail->theme ?: config('mail.markdown.theme', 'default'));
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
            case 'account-welcome': return $this->welcomeSample(new AccountWelcome('Jamie'));
            case 'account-welcome-website': return $this->welcomeSample(new WebsiteAccountWelcome('Jamie'));
            case 'account-welcome-ios': return $this->welcomeSample(new IosAccountWelcome('Jamie'));
            case 'admin-weekly-snapshot':
                return new AdminReport((new WeeklySnapshotPreview)->data(), new \App\Admin(['name' => 'Preview Admin']));
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
            case 'merchandise-confirmation':
            case 'merchandise-shipped':
            case 'merchandise-delivered':
                $order = new \App\Shop\ShopOrder(['id' => '00000000-0000-4000-8000-000000000001', 'mode' => 'demo',
                    'recipient' => ['name' => 'Jamie Parker', 'email' => 'jamie@example.test'],
                    'items' => [['name' => 'Piano rainbow T-shirt', 'option' => 'Mint / M', 'quantity' => 1, 'unit_price' => 2800]],
                    'subtotal' => 2800, 'shipping' => 500, 'tax' => 0, 'total' => 3300, 'currency' => 'usd']);
                if ($key === 'merchandise-confirmation') return new \App\Mail\Shop\MerchandiseOrderConfirmed($order, '#email-preview');
                $shipment = new \App\Shop\ShopShipment(['carrier' => 'Example carrier', 'service' => 'Standard delivery',
                    'tracking_number' => 'PREVIEW123456', 'tracking_url' => '#email-preview', 'shipped_at' => '2026-10-28',
                    'estimated_from' => '2026-11-01', 'estimated_to' => '2026-11-03',
                    'items' => [['name' => 'Piano rainbow T-shirt', 'option' => 'Mint / M', 'quantity' => 1]]]);
                if ($key === 'merchandise-delivered') {
                    $shipment->status = 'delivered';
                    $shipment->delivered_at = '2026-10-28';
                    $order->setRelation('shipments', collect([$shipment]));
                    return new \App\Mail\Shop\MerchandiseOrderDelivered($order, '#email-preview');
                }
                return new \App\Mail\Shop\MerchandiseOrderShipped($order, $shipment, '#email-preview');
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

    private function welcomeSample(AccountWelcome $mail)
    {
        // Stable fictional cards keep preview/test sends independent of live data.
        $mail->welcomePieces = [
            ['title' => 'Prelude in E minor', 'composer' => 'Frédéric Chopin', 'image' => (string) mix('images/emails/welcome/preview-prelude.jpg'), 'url' => '#email-preview'],
            ['title' => 'Arabesque No. 1', 'composer' => 'Claude Debussy', 'image' => (string) mix('images/emails/welcome/preview-arabesque.jpg'), 'url' => '#email-preview'],
            ['title' => 'Bagatelle in A minor', 'composer' => 'Ludwig van Beethoven', 'image' => (string) mix('images/emails/welcome/preview-bagatelle.jpg'), 'url' => '#email-preview'],
        ];

        return $mail;
    }
}
