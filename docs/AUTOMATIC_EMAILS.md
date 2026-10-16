# Automatic email delivery

Settings → Emails lists the user-facing transactional emails and their **Sending**
switches. These switches apply globally, independently of newsletter subscriptions.
Browser and test-send previews remain available when automatic sending is off.
Stripe/Apple provider receipts and internal admin reports are outside this catalog.

Existing email types default to on. The separate account welcome starts off;
enabling it sends `AccountWelcome` after future `Registered` events. It does not
send retrospectively, join a newsletter or replace email verification.

## Deployment

Before serving this code, run the settings migration against the intended database:

```sh
php artisan migrate --path=database/migrations/2026_10_15_230000_create_automatic_email_settings_table.php
```

Restart the application's long-running queue workers through the normal deployment
process so they load the delivery listeners and mailable metadata callback. The
migration creates only the settings table; no email or billing action is executed.
Missing setting rows retain the defaults above. Do not drop the table while the
feature is in use. Database failures do not silently bypass disabled settings.

## Delivery and compatibility

`App\Mail\AutomaticEmailSettings` reads SQL without a shared cache, so web requests
and workers see saved changes. PATCH requests save an explicit desired state with
an atomic upsert keyed by the fixed email identifier. The latest administrator
and timestamps are recorded; deleting an administrator retains their settings.

Existing dispatch sites skip disabled messages before queuing. Laravel's
`MessageSending` event checks each mapped mailable again at delivery using metadata
from `Mailable::buildViewDataUsing`. `NotificationSending` checks verification,
password reset and each Apple/Google confirmation variant separately, only on the
mail channel. Other notification channels, marketing mail and `EmailPreview` are
unaffected. Previously queued copies are checked when processed; suppressed jobs
finish without sending or replaying later. Delivery already handed to the email
provider cannot be recalled.

Turning off account messages intentionally stops their verification/reset emails;
it does not bypass account verification or change authentication requirements.
Existing routes, mobile response formats, subscriptions, account data, super-user
silence and media delivery are preserved. Membership checkout remains successful
when its email is disabled and does not claim that a confirmation email was sent.

When adding a transactional email, add its catalog definition/sample, fixed
mailable or notification mapping, and dispatch check together. Do not classify
messages by their subject, recipient or a submitted class/view name. The rendering
callback adds only internal mail-view data, with no new model or mobile JSON fields.

## Verification

Use `phpunit-review.xml` with fake or explicitly constructed in-memory mail
transports. `AutomaticEmailSettingsTest` exercises persistence, desired-state
retries, authorization/CSRF, all delivery mappings, independent notification
variants, queued-copy suppression, preview bypass, dispatch suppression and the
default-off welcome. `AdminEmailsTest` checks the catalog and previews, and
`StripeMembershipBillingTest` covers disabled confirmation mail after checkout.
SQLite checks do not establish MySQL behavior or actual inbox delivery.
