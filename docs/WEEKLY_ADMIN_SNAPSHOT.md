# Weekly admin snapshot

`pianolit:admin-report` queues one aggregate snapshot for each existing
`Admin::managers()` recipient on Sundays at **22:30 America/New_York**. It keeps
that command and recipient scope, with an explicit scheduler timezone and overlap
lock. Settings → Emails includes **Weekly admin snapshot** with Browser and Email
preview actions and a default-on Sending switch. Disabling it suppresses new
dispatches and already-queued messages; previews remain available through the
shared recipient form. The report covers the last completed **Sunday–Saturday** week; the comparison
covers the preceding seven complete days. Sunday activity appears in the next
report. Boundaries are inclusive at the start and exclusive at the end.

## Metrics

`App\Services\Admin\WeeklySnapshot` reads SQL and freezes the same scalar payload
for every recipient before queueing. It never calls billing providers, changes
accounts, reads Redis activity, or adds campaign tracking. No recipient/customer
email lists appear in the body.

| Metric | Definition |
| --- | --- |
| New accounts | Existing user records created within the week, across all signup origins, minus `ExcludedAccounts` |
| Signup sources | Saved `users.origin`, with other/missing origins labeled rather than inferred |
| Active accounts | Distinct existing accounts with recorded web-app browser activity or an iOS request in the week; IDs are deduplicated across platforms |
| Came back | Accounts active in both this week and the preceding week; the rate uses the preceding week's active accounts as its denominator |
| New users exploring | New accounts with at least one web piece opening or iOS piece-detail request for an existing piece during the week; divided by new accounts |
| Daily activity | Distinct active accounts per New York calendar day; daily totals are not added to obtain weekly uniques |
| Web app behavior | Distinct accounts opening a piece, starting audio/video, opening a score, or successfully adding a favorite/folder piece; repeat events count once per action |
| iOS behavior | Reported existing accounts and recorded piece-detail, search and total request counts; unnamed legacy POSTs/imports are recognized by their endpoint |
| Members using PianoLIT | Accounts with a currently active, billing-grace, trial or ending Stripe/Apple membership that had activity in the reporting week |
| Membership status | Current status from the same read-only query as the admin membership directory, with Active (including billing grace), Trials and Ending separated; unknown/stale/payment-issue records appear under review |
| Music of the week | Up to three existing pieces ranked by distinct accounts opening them on the web or requesting their details on iOS; one account using both platforms counts once in the total |

Activity queries prefer recorded UTC instants, falling back to application-local
occurrence dates for older records. They use event time rather than import time
or the start of a possibly older visit. Tests cover the 25-hour DST day.
Verified anonymous-to-account visit links are resolved through the existing
activity association. Pure guest traffic, website-only activity, missing/deleted
accounts and excluded accounts do not enter active-account totals. Accountless
billing callbacks do not establish product usage. Newsletter subscribers are not
web-app accounts or paid members.

The report is a snapshot of **surviving accounts and recorded activity**, not an
immutable registration ledger: subsequent account removal/purge or late imported
history can change a rerun. Collection gaps are not estimated. iOS identities
remain legacy client-reported/unverified, and its pre-action requests establish
neither successful viewing nor playback duration. These limitations are briefly
labeled in the email. No API payloads, logging endpoints, media URLs, model
serialization, access rules or provider behavior change.

Membership totals are explicitly current as of generation, not historical
week-end balances. Complimentary-only super users are not paid memberships; a
super user with an actual billing record retains that record's normal reporting.
Sandbox, expired, paused and revoked memberships do not count as current access.
Current statuses cannot establish historical paid growth or churn, so the email
does not invent those figures or imply trial starts are payments. Apple status
still depends on the existing receipt monitor's saved verification/freshness.

## Editing and preview

- Body composition, primary labels and CTA: `resources/views/emails/admin/report/index.blade.php`
- Section layouts/captions: `resources/views/emails/components/{html,text}/snapshot/`
- Shared logo, outer shell and footer: `components/{html,text}/editorial/`
- Styles: `resources/sass/emails/welcome.scss`, compiled into the tracked `components/html/themes/welcome.css`
- Aggregate query: `app/Services/Admin/WeeklySnapshot.php`
- Fictional preview data: `app/Services/Admin/WeeklySnapshotPreview.php`

The outlined logo, neutral background, Poppins headings, rounded white/pastel
surfaces and primary button match the welcome/Free Pick family. The shared footer
has an opt-in internal-report mode with a concise delivery reason instead of
public newsletter unsubscribe controls. Other email footers remain unchanged.
Charts use presentation tables and visible numbers, with no JavaScript, external
chart service or image-only data. Piece covers use existing public artwork, with
the shared score icon when artwork is absent. HTML and plain text contain the same
numbers and destinations; piece links open the user-facing web-app page.

Build and export locally without loading `.env` or using real accounts/mail:

```sh
npm run emails:styles
php -d error_reporting=8191 scripts/preview-weekly-snapshot.php /tmp/pianolit-weekly-snapshot-preview
php -d error_reporting=8191 vendor/bin/phpunit -c phpunit-review.xml --filter WeeklySnapshotTest
```

The exporter reuses the welcome exporter's isolated in-memory SQLite boot and
also writes its welcome samples. Open `weekly-snapshot.html` or `.txt`. Every
snapshot sample is labeled **Sample data** and its report/piece links are inert.
The catalog and exporter share the same fixture service. The scheduled command
never reads this fixture. The Browser action renders current Blade edits directly;
the Email action sends the rendered sample only to the chosen preview address,
with the existing `[Preview]` subject prefix.

Deploy the service, mail class, views and compiled theme together; restart
long-running queue workers through the normal release process. No new migration
or public asset build is required. Existing SQL activity/UTC/link and membership
reporting tables must already be deployed. Old queued report payloads retain their
legacy view so changing the template does not break pending jobs. Do not run the
normal report command as a preview: it queues email to actual managers.

Local review covers HTML/text, a 600px email canvas and 390/320px phone widths,
image loading, no horizontal overflow and a sample HTML payload below Gmail's
102 KB clipping threshold. Actual Gmail/Outlook/Apple Mail inbox rendering and
production MySQL execution remain deployment checks; no real messages were sent.
