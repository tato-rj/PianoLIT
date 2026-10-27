# Campaign attribution

Updated 2026-10-27: general traffic reporting remains in Google Analytics. Arthur
subsequently authorized restoring the small **campaign-to-conversion link**, plus
server-confirmed trial and first-payment events in GA. The changes below supersede
the historical automatic-arrival behavior documented later in this file.

## Current conversion link

`attribution.enabled` remains false. Successful tagged GETs retain a bounded first
touch only in the existing session, separately per host; they create no SQL arrival
or new campaign cookie. `attribution.conversion_links_enabled` enables the consented
link. Existing opt-in, saved refusals, session/host-bound signed tokens, bot/prefetch
filters and `ExcludedAccounts` still apply. No visible campaign controls return.
New visitors without an existing grant remain unlinked. An ordinary GA pageview
is not permission to bind an account or export a membership conversion.

The hidden acknowledgement retrieves `client_id` and `session_id` through Google's
supported `gtag('get', …)` API only for an existing grant. Browser IDs are encrypted
in `campaign_visits.google_context`; they never authenticate a visitor. No email,
name, internal account ID, raw query string or payment details are sent to GA.
No ID is invented when the tag is blocked. A direct/organic checkout with a granted
Google context can have a blank campaign link; it is omitted from the campaign table.

Verified checkout preparation stores only its owned operation-to-visit association.
Completion links the verified subscription, and the minutely reconciler recovers
checkout tabs that never return. Account, customer, subscription and US/Swiss
billing account must match the persisted purchase operation. Only verified live-mode
subscriptions enter the Google outbox. Legacy/iOS and shop transactions stay separate.
Signed invoice receipts trigger delivery work without making Google a billing dependency.

Analytics → **Campaign conversions** shows only retained, consented campaigns with
membership links, grouped by saved labels and landing page. It shows trials, first
paid subscriptions and first payment revenue, with separate currencies. There is no
arrivals counter, traffic ranking or conversion rate. The period is the campaign
capture period; a conversion can happen later. Legacy read-only report machinery
remains available for historical reports. General acquisition lives in GA. The weekly admin snapshot also omits arrival
counts and traffic-only campaign rankings in this mode; it retains confirmed
campaign trials/first payments.

## Google delivery and rollout

Deploy `2026_10_27_190000_create_google_analytics_conversions.php` before the matching
PHP/Blade/config/JS changes. It adds encrypted browser context, a live-mode marker,
checkout recovery links and a durable delivery outbox. Existing rows default to
non-exportable: no historical backfill or guessed identities. Existing campaign
migrations and the modern Stripe operations migration are prerequisites.

Configure `GA4_API_SECRET` through the normal private deployment configuration, or
install the secret in `storage/app/private/google-analytics-api-secret` (mode 0600,
outside version control and public storage). `GA4_API_SECRET_FILE` can select another
private path. Never put this secret in Blade, JS, source control, logs or a screenshot.
The default measurement ID is `G-12WWWZZMJF`; `GA4_MEASUREMENT_ID` overrides it.
`GA4_CONVERSIONS_ENABLED=false` stops delivery independently of internal links.
Production-only `analytics:send-conversions` runs every minute via the existing
scheduler. `analytics:send-conversions --status` is read-only, showing configuration
and state counts without provider calls, identities or credentials. Keep deployed
config/view caches current. Arthur approved Google's User Data Collection Acknowledgement and secret creation
on 2026-10-27. The acknowledgement is accepted and the secret is saved locally in
the private file above with mode 0600, excluded from Git. The subsequent production
activation audit installed that secret and verified the deployed migration/code and
automatic sender. Actual eligible conversion receipt is still unconfirmed; see the
production audit below. No synthetic event was sent to the live collector.

- `membership_trial_started`: a confirmed trial, without a made-up monetary value
- `purchase`: the first positive paid membership invoice only, with
  `membership_conversion=first_payment`, actual charged value/currency, one generic
  membership item and an opaque stable transaction ID

Both include saved `membership_campaign`, `membership_source`, `membership_medium`,
`membership_placement` and `membership_landing_host` where available. These are
custom conversion dimensions, separate from Google's session-acquisition dimensions.
The sender reads complete paid-invoice history from the correct Stripe account,
including pagination, and fails closed if history is incomplete. Zero-value trial
invoices, renewals and test subscriptions never become first-payment exports.

Events use their actual server-confirmed timestamp within Google's 72-hour delivery
window. A post-trial charge is not backdated into a weeks-old browser session; its
saved campaign parameters remain available even when session attribution is absent.
Only a recent real session near the conversion is included. No artificial engagement
time, location or User-ID is added; advertising consent is denied in these payloads.
The GA dimensions/key-event settings can be saved before events begin arriving.

Outbox uniqueness prevents duplicate local conversion creation. Purchase retries
reuse the encrypted payload/client/transaction ID, using GA purchase deduplication.
Trials have no guaranteed equivalent: an interrupted or ambiguous HTTP outcome is
marked `uncertain`, never blindly resent. Rate limits and safe payment retries use
bounded backoff. A 2xx is recorded as `submitted`, meaning HTTP receipt, not proof
that Google processed the event. Check real permitted conversions in GA after rollout;
do not create test conversions in the live property or charge customers to verify it.
Withdrawal and account exclusion/delete cleanup remove campaign links, checkout
contexts and queued/frozen payloads by cascade. Existing 90-day campaign pruning
also removes them; this does not erase already submitted Google events.

## Production activation audit — 2026-10-27

Arthur published the migration/code and authorized the SSH rollout. Both required
migrations and matching analytics source files were verified on `/var/www/html`.
The private secret is installed server-side with mode 0600 and matches its local
source; config/route/view caches were refreshed and the supervised queue worker
was gracefully restarted. `analytics:send-conversions --status` reports configured.

The SSH user's crontab had no Artisan jobs, and root's crontab was not readable
without sudo. A conversion-only cron now runs every minute under `arthur`, with
`flock` preventing overlap and a success heartbeat at
`storage/app/private/google-analytics-conversions.last-success`. Multiple automatic
successful runs were observed. The cron is scoped to analytics and does not run
unrelated billing/email jobs. The Kernel's normal schedule registration remains;
if root's full scheduler is later confirmed, consolidate the duplicate entry.
The previous crontab is retained privately at
`storage/app/private/crontab-before-google-analytics`.

Google's `/debug/mp/collect` validation endpoint accepted both representative event
formats under `ENFORCE_RECOMMENDATIONS`, with empty validation messages. No event
was sent to the live collection endpoint for testing. Validation does not prove
processing or validate the API secret. No real billing mutations, webhook replays,
backfills or consent changes were performed.

Production currently has zero attribution opt-ins, checkout contexts, live links
and outbox rows. Today's GA searches for `membership_trial_started` and `purchase`
are empty. Arthur explicitly confirmed keeping those consent rules. There is no
visible interface granting new attribution permission, so conversion exports may
remain empty even with real trials/payments. Server activation is complete;
actual eligible event ingestion remains unconfirmed.

## Historical implementation (superseded collection behavior)


Updated on 2026-10-20: valid tagged visits now count automatically. Account, trial and payment attribution still requires an existing opt-in. The visible preference panel has been removed at Arthur’s request. Campaign measurement is enabled by default in application configuration. An explicit `CAMPAIGN_ATTRIBUTION_ENABLED=false` still disables collection. This is PianoLIT's own SQL report; it does not set up or export to Google Analytics. Deployment is separate from local implementation.

## Links and measurement

Any rendered public website or `my.*` web page can recognize a tagged arrival. Supply any combination of `utm_source`, `utm_medium` and `utm_campaign`: each works independently, including a campaign or medium without a source. Values do not need to be registered in code, configuration or admin first. For example:

```text
https://my.pianolit.com/pieces/174?utm_source=youtube.com
https://my.pianolit.com/pieces/175?utm_campaign=autumn_repertoire
https://my.pianolit.com/?utm_medium=newsletter
https://my.pianolit.com/?utm_source=reddit.com&utm_medium=social&utm_campaign=autumn_repertoire&utm_content=post
```

Use `?` for the first parameter and `&` for later parameters. Use literal `&` in a YouTube description, not HTML `&amp;`. The public `/pieces/{piece}` redirect preserves the four supported UTM fields on its way to the web app. Other redirect destinations must preserve tags to measure their final rendered page; a redirect response itself does not record an arrival.

`App\Services\CampaignParameters` validates four fields: `utm_source`, `utm_medium`, `utm_campaign` and the optional placement `utm_content`. At least one of source, medium or campaign must be nonblank; `utm_content` alone does not establish an arrival. Each label is at most 100 characters, starts with a letter or number and uses ASCII letters, numbers, spaces, dots, underscores or hyphens. Sources cannot contain spaces. Omitted fields are stored as empty strings, displayed as not specified, and never inferred from another field. Arrays, HTML, URL syntax, email addresses and overlong fields cause the tagged arrival to be ignored; the page still opens normally. Use descriptive campaign labels, never personal information.

The report trims and lowercases labels and removes leading `www.` from sources. For compatibility, `youtube`/`youtube.com`, `chatgpt`/`chatgpt.com` and `reddit`/`reddit.com` each share the domain-style source. These aliases do not restrict other sources: `newsletter` or `new-site.example` work immediately. Other names and domains remain distinct. `.com` is a consistent naming choice, not a requirement of UTM tracking. These values describe the supplied link labels; they do not authenticate the referring site.

As of October 25, Free Pick subscriber emails generate `utm_source=pianolit`,
`utm_medium=email`, `utm_campaign=freepick` on their three body buttons only.
`utm_content` is `explore_piece`, `browse_library` or `go_premium`. All three
land directly on `my.*`, preserving the current host-local arrival/membership
flow. Other email links, including unsubscribe and footer links, are unchanged;
previews and excluded recipients receive no tags. This uses the existing
first-touch arrival report without new redirects, click counters or consent
behavior. See `docs/FREEPICKS.md` for the editing map and release requirements.

Campaign storage contains the four normalized labels, a canonical landing-page URL, a random visit UUID, timestamps, account binding and the existing subscription/payment identities. The landing URL contains only the site host and matched route path: query strings and fragments are omitted. Public content route parameters (such as piece IDs and post slugs) are retained; other dynamic parameters, including reset, verification, account and unsubscribe identities, become `[redacted]` and are shown without a link. No raw query URLs, arbitrary referrers, visitor names, emails, IP addresses, browser profiles or payment-card fields are added. The stored SHA-256 campaign key remains based on the four UTM labels. The expansion migration backfills original Notturno visits as `youtube.com` / `organic_video` / `respighi_notturno` / `description`, preserving their visit and subscription identities.

## Visitor choice and ownership

A successful HTML GET on the public website, `blog.*`, `shop.*`, `learn.*`, `games.*` or web app records a valid tagged arrival on the server, without JavaScript or any visitor-facing campaign controls. The arrival stores no account identity and has `consented_at = NULL`. Its random ID is retained in the existing browser session, separately for each host, so refreshes and later campaigns in that session do not replace or duplicate the first touch. This introduces no additional campaign cookie before opt-in. HEAD, JSON/AJAX, prefetch, known bots, redirects and error responses do not create arrivals. Existing exclusions, saved refusals and the collection-enabled switch still apply. Legacy blog, eBook/eScore and learning-resource redirects preserve UTM labels; the successful destination on `blog.*`, `shop.*` or `learn.*` records the arrival and canonical landing URL. Infographics and Great Pianists use the clean `/infographics` and `/great-pianists` paths on learn. Theory/technique tools use `/circle-of-fifths`, `/chord-finder`, `/scales` and `/arpeggios` there; legacy `/tools/...` page redirects preserve UTM labels and record only the successful clean destination. The temporary hub root pages follow these same rules, including Games, and retain host-local consent acknowledgements and account exclusions. Existing dynamic-parameter redaction remains unchanged.

Arthur subsequently requested removal of the visible “Campaign link measurement” panel. The public website and web app no longer render its link, explanatory text, buttons or status message, including on tagged pieces and searches. New visitors remain anonymous; removing the panel does not silently grant account/conversion attribution. There is currently no visible interface for granting a new campaign opt-in.

A hidden acknowledgement retains compatibility with a previously saved `granted` choice. Tagged pages supply an encrypted token; the acknowledgement script loads only when that choice is already granted. It submits the same CSRF-protected, host/session-bound token as before, without displaying controls or adding a new permission. No JavaScript acknowledgement runs for new visitors or saved refusals. The token expires in ten minutes; forged, expired and other-host/session tokens remain rejected. The existing endpoints remain compatible with saved choices and already-open older pages, including withdrawal; posted account IDs are ignored.

Before opt-in, deduplication lasts for the browser session (at most the 30-day touch lifetime); a new session can count another anonymous arrival. After opt-in, one first eligible touch is kept for a 30-day cookie window. Refreshes, direct traffic and subsequent campaigns do not replace it. Host-only encrypted HttpOnly SameSite=Lax cookies survive signup/login session regeneration. Only a consented visit can be claimed by the authenticated web guard; another account cannot claim it. Logout clears the cookies and anonymous session references. The cookies remain on the arrival host: moving from the public website to `my.*` does not carry attribution. For membership campaigns, link directly to `my.pianolit.com` to retain the complete arrival-to-membership flow. Cross-device attribution is not provided.

Withdrawal removes the current anonymous or consented visit and its subscription attribution links, plus earlier links owned by the authenticated account. The saved refusal prevents further automatic arrivals. If an excluded account signs in after an anonymous arrival, the existing exclusion cleanup also removes that session’s anonymous campaign records. It does not change accounts, access or accounting payments. Allowing measurement later on an untagged page does not invent an arrival: the visitor must reopen the tagged link. Attribution expires after 30 days for new checkout linkage; successful payments of an already attributed subscription remain linked until retention removes the campaign cohort.

`App\Activity\ExcludedAccounts` excludes Arthur, Elena, the legacy Arthur email spelling and recognized opted-out browsers from collection. Reports also filter owned excluded-account visits. Admin, impersonation and mobile/API traffic do not create campaign arrivals. Shop product purchases remain outside membership-conversion attribution. Account deletion cascades campaign links. Existing activity analytics and GTM/Meta behavior are unchanged; this control covers only first-party campaign linkage.

## Billing correctness

The web purchase action uses the exact newly created Stripe subscription returned by the existing factory. Trial starts require provider status `trialing`. An immediate `active` subscription is recorded without inventing a trial; an `incomplete` subscription may later become paid. The shared `NewTrial` notification, Apple/mobile controllers, existing membership logic and billing API payloads are unchanged.

Signed `invoice.payment_succeeded` events create minimal financial receipts only for paid, positive subscription invoices. They do not write a second row to `payments`. Receipt identity is unique by type and Stripe invoice/charge ID. The existing successful-charge payment handler uses an atomic receipt marker and transaction when this feature is enabled; duplicate and concurrent deliveries cannot append another payment, and a failed handler rolls back the marker for retry. Existing historical duplicate payments are not deleted or rewritten. This does not fix unrelated out-of-order subscription-status updates in the legacy billing code.

The report joins exact subscription AND customer identity. Positive invoice receipts can arrive before the checkout response or campaign binding; the eventual join reconciles them without storing a raw webhook body or making Stripe API calls. Unattributed receipts do not appear in campaign reporting. The earliest paid invoice (paid timestamp, then invoice ID for ties) contributes one conversion and one initial payment amount per subscription, regardless of delivery order. Renewals, zero invoices, failures, manually marked-paid events and shop payments do not add conversions. A late first invoice can correct a report that previously saw only a renewal. Conversion means first paid subscription, not necessarily a first-ever customer: a returning customer who creates a new subscription can be counted again.

Financial receipt storage is separate from optional campaign consent, matching the existing accounting integration; it stores only invoice identity, subscription/customer IDs, amount, currency and successful-payment time. Unmatched invoice receipts and campaign links are pruned after 90 days. Tiny charge identity markers stay for replay protection; normal accounting payments are preserved. Reports are limited to retained campaign cohorts. The existing Laravel scheduler runs `attribution:prune` daily, which is a no-op while disabled. Verify the scheduler operates in production.

## Reporting

Open **Analytics → Campaigns** on `admin.*` (`admin.analytics.campaigns`, `/analytics/campaigns`). It uses the admin session guard and shared server-rendered table, with 12 rows by default. SQL search covers all four labels and the landing-page URL; sorting and pagination apply to complete grouped results. Arrival periods are the last 7, 30 or 90 calendar days (including today), or all retained arrivals. No source allowlist or code update is necessary for new rows.

Each source/medium/campaign/placement/landing-page combination shows anonymous and consented arrivals, provider-confirmed trials, first paid subscriptions, first-payment gross revenue by currency and the latest arrival. Campaign-only and medium-only links appear in the same admin and CLI reports, with omitted fields left unspecified. Different combinations remain separate groups even when the campaign name matches. The Landing page column shows the piece title (loaded in one query per report page) with its host/path, or the path for another page, and opens recorded public destinations in a new tab. Piece links use the user-facing web app. The same UTM tags leading to different destinations have separate counts, trials and revenue. The first destination stays fixed through later tagged navigation and opt-in. Records predating destination capture show **Not recorded**; they are not inferred from UTM labels or later visits. Deleted piece pages retain their recorded URL. The CLI keeps its existing UTM-level totals and output fields, combining destinations. An arrival is the first eligible tagged visit in a browser session, or the existing 30-day cookie window after opt-in, not every click or a unique person. First payment means a positive subscription payment, not necessarily a first-ever customer. Filters select the arrival cohort: a later trial/payment still belongs to that earlier arrival. Renewals, zero payments, unrelated customers, denied measurement, known bots, opted-out accounts and expired/deleted cohorts are outside the report. Blocked scripts do not prevent anonymous arrivals; they can prevent consent acknowledgement and therefore conversion linkage. Cross-device conversion linkage is not provided. Fees/refunds are not deducted; currencies are never added together.

The read-only CLI discovers the same dynamic groups:

```sh
php artisan attribution:report --since=2026-10-17
```

The daily `attribution:prune` scheduler retains 90 days of campaign links and removes aged unmatched invoice receipts. Billing payments and charge retry markers remain intact. Reports display retained data even when new collection is disabled.

## Deployment and verification

1. Deploy `2026_10_07_030000_create_campaign_attribution_tables.php`, `2026_10_17_160000_expand_campaign_attribution_sources.php`, `2026_10_20_230000_allow_anonymous_campaign_arrivals.php` and **`2026_10_20_234000_add_campaign_landing_url.php` before serving the new application code**. The anonymous-arrival migration makes `consented_at` nullable; the landing-page migration adds `landing_url` with an empty default for legacy rows. Existing timestamps, campaign/subscription identities and counts remain intact. Landing-page capture/reporting requires its migration before the PHP/Blade code. Hidden acknowledgement tokens now allow up to 4,096 characters to carry a bounded landing URL, while previously issued tokens without a URL remain accepted. Deploy the matching PHP and Blade changes together; the panel removal also updates `public/js/views/campaign-attribution.js` and its manifest entry, which must deploy together. No CSS changes are required. A code rollback can keep the nullable column. Schema rollback restores NOT NULL only when no anonymous rows remain; otherwise it deliberately retains nullability and all records, without inventing consent or deleting arrivals.
2. The application now defaults to enabled as requested. If a deployed environment explicitly sets `CAMPAIGN_ATTRIBUTION_ENABLED=false`, change that setting through the normal deployment configuration process and refresh the deployed configuration cache. Local work does not edit `.env`, deploy or migrate the normal environment.
3. Verify the deployed Stripe endpoint signing secrets, US/Swiss account routing, `invoice.payment_succeeded` delivery and the normal scheduler. Keep raw-body signature verification. Do not change provider API versions or use live purchases for verification. See `docs/STRIPE_MEMBERSHIPS.md` for the modern billing deployment checks.
4. In a disposable sandbox, validate tagged arrival → immediate anonymous admin row with no visible preference panel. Using a previously granted test choice, verify acknowledgement upgrading that same row → signup/login → web checkout → trial → first positive invoice, retry handling, out-of-order invoices and separate currencies. Check that checkout without opt-in creates no campaign subscription link. Isolated SQLite tests do not prove concurrent MySQL behavior or actual Stripe delivery.
5. Smoke-check `https://my.pianolit.com/pieces/174?utm_source=youtube.com&utm_medium=video`, a source-only YouTube link and an entirely new source in a fresh, non-excluded browser session on the deployed site. Each first arrival should appear without clicking Allow measurement, with the correct landing page; refreshing must not increment it. Check two different destination pieces using the same tags in separate fresh sessions: the admin must show separate destination rows with their own totals. An incognito browser is anonymous until it signs in; recognized Arthur/Elena sessions remain excluded. Verify the admin row, absence of all visible campaign controls, saved-refusal behavior, compatibility of the withdrawal endpoint and preserved account exclusions. Use `my.*` links when measuring membership conversions. Review pre-existing GTM/Meta measurement separately; no GA4 integration is introduced here.

Earlier clicks that created no campaign records are not backfilled by this fix. The live production change is complete only after the migration/code rollout and the deployed anonymous-visit smoke check.
