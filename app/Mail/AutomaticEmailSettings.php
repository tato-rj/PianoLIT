<?php

namespace App\Mail;

use App\Mail\Memberships\MembershipConfirmed;
use App\Mail\Performances\{PerformanceApprovedEmail, PerformanceSubmittedEmail};
use App\Mail\Shop\ConfirmPurchase;
use App\Mail\Tutorials\{NewRequestEmail, RequestPublishedEmail};
use App\Notifications\User\ConfirmGoogleAccount;
use App\Services\Admin\AutomaticEmails;
use Illuminate\Auth\Notifications\{ResetPassword, VerifyEmail};
use Illuminate\Support\Facades\DB;

class AutomaticEmailSettings
{
    private const MAILABLES = [
        // Each signup subtype has its own switch; match them before the shared base.
        IosAccountWelcome::class => 'account-welcome-ios',
        WebsiteAccountWelcome::class => 'account-welcome-website',
        AccountWelcome::class => 'account-welcome',
        ConfirmPurchase::class => 'purchase-confirmation',
        MembershipConfirmed::class => 'membership-confirmation',
        NewRequestEmail::class => 'tutorial-request',
        RequestPublishedEmail::class => 'tutorial-published',
        PerformanceSubmittedEmail::class => 'performance-submitted',
        PerformanceApprovedEmail::class => 'performance-approved',
        SharePieceEmail::class => 'piece-shared',
    ];

    public function states()
    {
        $saved = DB::table('automatic_email_settings')->pluck('enabled', 'email_key');

        return app(AutomaticEmails::class)->all()->map(function ($email, $key) use ($saved) {
            return $saved->has($key) ? (bool) $saved[$key] : ($email['default_enabled'] ?? true);
        });
    }

    public function enabled($key)
    {
        $email = app(AutomaticEmails::class)->find($key);
        // Read fresh for every delivery, including long-lived queue workers.
        $saved = DB::table('automatic_email_settings')->where('email_key', $key)->value('enabled');

        return $saved === null ? ($email['default_enabled'] ?? true) : (bool) $saved;
    }

    public function setEnabled($key, bool $enabled, $adminId)
    {
        app(AutomaticEmails::class)->find($key);

        // Explicit desired state and the primary key make retries and first writes safe.
        DB::table('automatic_email_settings')->upsert([
            'email_key' => $key, 'enabled' => $enabled, 'updated_by_admin_id' => $adminId,
            'created_at' => now(), 'updated_at' => now(),
        ], ['email_key'], ['enabled', 'updated_by_admin_id', 'updated_at']);
    }

    public function mailableKey($mail)
    {
        foreach (self::MAILABLES as $class => $key) {
            if ($mail instanceof $class) return $key;
        }
    }

    public function notificationKey($notification)
    {
        if ($notification instanceof VerifyEmail) return 'verify-email';
        if ($notification instanceof ResetPassword) return 'reset-password';
        if ($notification instanceof ConfirmGoogleAccount) return $notification->automaticEmailKey();
    }
}
