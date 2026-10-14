# Membership analytics and Apple monitoring

Arthur and Elena are excluded from membership/super-user reports and new Apple reporting checks under the [analytics exclusion policy](ANALYTICS_EXCLUSIONS.md). Their actual memberships, access overrides, billing sources and receipts are preserved. The dedicated cleanup removes old reporting evidence only.

Implemented locally on 2026-10-13. The admin Membership analytics page combines
Stripe billing records and independently verified Apple receipt evidence. It has
provider totals, current status cards, a searchable/paginated directory and the
latest scheduled Apple check. The bulk Users validation button is removed. Old
admin validation URLs now lead to the Apple membership directory without making
network calls or mutating billing records.

## Deployment

1. Deploy `2026_10_13_170000_create_apple_membership_monitor_tables.php` before
   enabling the new scheduler command. It adds two independent admin tables and
   does not alter `apple_memberships`, `memberships` or mobile models.
2. Deploy PHP, Blade, config, `public/css/admin.css` and its manifest entry together.
3. Confirm the production app bundle ID. The default
   `com.leftlaneapps.PianoLIT` comes from this repository's existing Apple fixtures.
   Set `APPLE_MEMBERSHIP_BUNDLE_ID` in server configuration if the production app
   differs. `APPLE_MEMBERSHIP_SHARED_SECRET` optionally supplies the current
   app-specific shared secret; otherwise each existing record's secret is used.
   Rebuild the server's config cache after configuration changes. Never paste
   secrets into logs, tickets or the analytics page.
4. The existing Laravel scheduler needs to execute `php artisan schedule:run`
   every minute on the designated scheduler server. It will pick up the new
   hourly `memberships:sync-apple` entry automatically. If the server currently
   runs individually listed Artisan tasks, add `php artisan memberships:sync-apple`
   as an hourly task instead. Do not install both mechanisms for the same job.
5. After the migration and configuration are ready, the initial catch-up can be
   run with `php artisan memberships:sync-apple`. The default/max batch is 200
   receipts; `--limit=50` reduces it. Larger backlogs drain over later runs.
   The command is disabled outside production (isolated tests are an exception),
   and can be disabled with `APPLE_MEMBERSHIP_MONITOR_ENABLED=false`.
6. Check the analytics page for run time, checked/verified/failed counts, provider
   totals and individual failures. A command exit code of 1 reports receipt
   failures after completing the rest of the batch; it does not mean every check
   failed. Exit code 0 also covers no due receipts, a disabled job or a held lock.

No production receipt requests, migrations or billing maintenance were executed
during implementation. Real receipt validity and production MySQL behavior still
need verification after deployment.

## Verification rules

The job uses a separate admin verifier with bounded connection/request timeouts,
production-first verification and Apple's `21007` sandbox fallback. It checks the
configured app bundle, finds the subscription's original transaction chain (or
the existing product when no chain ID is saved), then selects the greatest
expiration timestamp. Trial flags are parsed as booleans; `'false'` is not a trial.
Refunds/revocations, billing grace periods, disabled renewal and expired purchases
have separate reporting states. A valid expired legacy receipt (`21006`) is usable
evidence. Sandbox purchases and the four test account IDs excluded by the old bulk
checker are excluded from billing totals. Super users are still checked; their
access override is separate from their billing status.

Active receipts refresh every six hours, with an earlier check at their expiry or
grace boundary. Confirmed expired/revoked receipts refresh daily. Failures retry
after 1, 2, 4, 8, 16 and then 24 hours. The hourly job bounds the batch and uses a
two-hour cache lock; schedule overlap protection uses the same duration. Configure
a shared lock-capable cache if scheduling from multiple servers, or use one
designated scheduler node. Job diagnostics retain 30 days; the latest per-source
evidence is retained.

Each receipt fails independently. Failed checks retain the previous confirmed
date for context, but never count that evidence as currently active or expired.
Evidence older than 48 hours becomes Needs review. A source updated after the
captured version becomes Not checked until the next check. The worker rechecks
the input fingerprint under a source row lock before persisting evidence, avoiding
results for a receipt replaced while an Apple request was in flight. Receipt
payloads, shared secrets and raw HTTP exceptions are never saved in monitor tables
or exposed in the page. The source tables are never updated by this monitor.

The monitor can only check receipts already stored by the existing app. A missing,
unrecoverable or rejected receipt cannot establish the subscriber's true current
status. The screenshot's `21010` corresponds to Apple being unable to find the
purchasing account or that account being deleted. It does **not** establish that
the PianoLIT membership is expired. A mismatched secret (`21004`) needs server
configuration correction; other unresolved receipts may need a restored purchase
or investigation in App Store Connect. Repeated failures remain visible and retry
without stopping checks for other accounts.

Apple marks `verifyReceipt` deprecated. This implementation accommodates saved
receipts without requiring an iOS update. A future server integration can use App
Store Server API and signed notifications with dedicated Apple credentials and
transaction identity mapping. That migration is not implied by receipt polling.

Sources: [Apple receipt status codes](https://developer.apple.com/documentation/appstorereceipts/status)
and [Validating receipts with the App Store](https://developer.apple.com/documentation/storekit/validating-receipts-with-the-app-store).

## Compatibility and metric definitions

The mobile API routes, request/response payloads, `AppleValidator`, Apple billing
model, mobile status/subscribe/history actions and media delivery remain unchanged.
Automatic checking updates independent admin evidence only. It does not grant or
revoke content access, change existing mobile billing rows, cancel subscriptions,
send email or issue charges. Existing super-user overrides are preserved.

Totals count one existing membership per living account, matching the current
`hasOne` membership relation's first record. Active includes billing grace periods;
Trials and Ending are separate categories. Turning off renewal preserves Ending
through the known end date, including canceled trials. Inactive combines expired,
paused and revoked/refunded records. Review combines verification failures, not-yet
checked receipts and Stripe payment issues. The directory offers finer status
filters. Summary cards always show all providers; directory filters affect rows
only. They do not count ordinary free accounts or super-user access as paid members.
Stripe uses saved webhook/billing updates, not live queries to Stripe. Its
scheduled end date takes precedence over renewal dates; an actually ended record
is inactive even if a renewal date is still in the future.

The admin SQL report aggregates and paginates in the database. It does not load
every user's Eloquent model or make per-row source/ownership queries. The legacy
load-more routes remain registered for compatibility but the new directory does
not use them. All browser requests remain on session-authenticated admin routes.
The combined membership directory uses the shared admin default of 12 rows per
page (`App\Support\AdminPagination::PER_PAGE`) with the `page` parameter. Search,
provider, status and sorting apply to billing and super-user rows together.

Overview uses the same Active count (active plus billing grace periods), directly
after Total users in At a glance. Its period badge counts memberships whose record
started in the selected period and is still active now; it does not claim to count
all historical activations or renewals. The card links to membership analytics.


## Super-user access reporting

Membership analytics lists accounts whose current `super_user` flag is true in
the same directory as Stripe and Apple memberships. Each account appears once:
super users have Provider / plan **Super User**, Status **Active**, a blank Access
until, and their current grant date in Latest check. Unknown grant dates stay
blank; account creation and first-observed dates are never substituted. The
separate Full access without payment table and its independent filters are removed.

All providers includes super users; Super User selects only those accounts;
Stripe + Apple excludes super-user rows while retaining both billing providers.
Individual Stripe and Apple filters remain available. A super user with a billing
record still appears once as Super User in the directory. Provider billing totals,
paid status cards and the dashboard's active-membership count continue to include
their actual billing record independently. The compact provider comparison shows
the super-user total separately; adding it to billing totals does not establish
distinct accounts with access. Ordinary accounts without memberships are omitted.

Deploy `2026_10_13_190000_create_super_user_access_periods_table.php` with the new
admin controller/report/template and rebuilt admin CSS/manifest. Running the normal
`php artisan migrate` during deployment applies it. No additional scheduled command
is required for super-user tracking. The migration records existing super users as
first observed at migration time with an unknown historical grant date and admin;
it does not change any user's flag or entitlement. The page can still show current
super users before this migration, with blank grant dates.

The existing admin PATCH route `admin.users.super-status` now uses an admin-only
controller. It preserves the route, session/CSRF checks, current toggle behavior,
JSON response and existing grant email. Under a user row lock and transaction it
records newly enabled access with the time and authenticated admin, closes revoked
access periods and creates a fresh period on re-grant. The audit table enforces one
current period per user. Audit-write failure rolls back the access change before
any grant mail is sent. The `users` table, User model, shared/mobile controller and
iOS routes/serialization remain untouched. The current flag remains the source of
truth for access and for this directory.

Grant dates capture changes made through the existing admin toggle after the
tracking migration. Direct database edits or future alternate grant paths are not
audited by this controller and may have unknown dates. If an external flag change
left an old current period, a later admin re-grant replaces that association without
inventing a historical revocation date. Historical access periods are kept in SQL;
the directory displays the current period only. Deleted accounts cascade-delete
their periods; deleting a granting/revoking admin preserves the timestamps while
removing the admin reference. Existing super users require evidence before any
historical grant date could be backfilled.
