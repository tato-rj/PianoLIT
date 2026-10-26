# Automatic email delivery

Settings → Emails lists automatic emails and their **Sending**
switches. These switches apply globally, independently of newsletter subscriptions.
Browser and test-send previews remain available when automatic sending is off.
The **Weekly admin snapshot** appears under Administration with Browser and Email
preview actions. Its Sending switch defaults on, preserves the existing Sunday
22:30 America/New_York delivery to managers, and suppresses new dispatches and
already-queued reports when turned off. Its previews use fictional, labeled
figures, existing preview artwork and inert report/piece links; rendering never
queries customer analytics or sends to the scheduled manager list. Stripe/Apple
provider receipts remain outside this catalog.

Existing email types default to on. **Website welcome**, **Web app welcome** and
**iOS app welcome** have independent switches and default to off. The web app welcome keeps the existing
`account-welcome` setting and `AccountWelcome` class, preserving its saved state.
`account-welcome-ios` controls `IosAccountWelcome` separately. The new
`account-welcome-website` setting controls `WebsiteAccountWelcome` and starts off.
All three share the same minimal layout, floating brand icon and actual screenshot
mockups. Website signup highlights both apps, web app signup highlights iOS, and
iOS signup highlights the web app, always explaining that the same account works.
See [WELCOME_EMAILS.md](WELCOME_EMAILS.md) for assets, editing and local previews.

The welcome listener uses the new account's saved signup `origin`: `web` selects
the website email, `webapp` the web app email, and `ios` the iOS email. Other/unknown origins do not
receive either welcome. Browser/device headers and the current request's fields
do not override that choice. Welcomes follow future `Registered` events only;
they do not send retrospectively, join a newsletter or replace verification.

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
Keep its editable copy and buttons in a purpose-based Blade folder under
`resources/views/emails/`, following `welcome/`, `memberships/`, `shop/` and
`tutorials/`. See `resources/views/emails/README.md` for the editing convention.

## Local Mailtrap images

When the application environment is `local` and `APP_URL` uses a `.test` host,
`EmbedLocalImages` runs after the automatic-sending check on `MessageSending`.
It replaces local `<img src>` URLs with CID references and attaches the matching
public bitmap files inline. This covers sent admin previews, normal mailables,
notifications and queued delivery without making the developer's machine public.
Repeated references to one file share one inline part. Plain text, navigation
links and existing attachments are preserved. Browser previews retain image URLs.

Only existing PNG/JPEG/GIF/WebP files whose resolved paths remain inside
`public/images` qualify. Other hosts, missing files, non-image content, traversal
and symlink escapes are left untouched; the listener makes no HTTP requests.
Production and other environments/hosts retain their existing delivery behavior.
New test emails include the images; existing messages in Mailtrap cannot change.
Long-running local workers must load the updated listener through their normal
restart process. There is no migration, credential/configuration change or build.

## Verification

Use `phpunit-review.xml` with fake or explicitly constructed in-memory mail
transports. `AutomaticEmailSettingsTest` exercises persistence, desired-state
retries, authorization/CSRF, all delivery mappings, independent notification
variants, queued-copy suppression, preview bypass, dispatch suppression and the
default-off welcome. `AdminEmailsTest` checks the catalog and previews, and
`StripeMembershipBillingTest` covers disabled confirmation mail after checkout.
SQLite checks do not establish MySQL behavior or actual inbox delivery.
`LocalEmailImagesTest` verifies real array-transport MIME output for all welcome
variants, serialized welcome delivery, shared catalog images, plaintext/attachment
preservation, local-only guards, deduplication and filesystem boundaries.
