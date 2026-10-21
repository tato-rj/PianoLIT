# Campaign attribution

Updated on 2026-10-20: valid tagged visits now count automatically; account, trial and payment attribution still requires opt-in. Campaign measurement is enabled by default in application configuration. An explicit `CAMPAIGN_ATTRIBUTION_ENABLED=false` still disables collection. This is PianoLIT's own SQL report; it does not set up or export to Google Analytics. Deployment is separate from local implementation.

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

Only the four normalized labels, a random visit UUID, timestamps, account binding and the existing subscription/payment identities enter campaign storage. No raw URLs, unrelated query parameters, arbitrary referrers, names, emails, IP addresses, browser profiles or payment-card fields are added. A SHA-256 campaign key keeps the existing storage contract; reports group by the four readable labels. The expansion migration backfills original Notturno visits as `youtube.com` / `organic_video` / `respighi_notturno` / `description`, preserving their visit and subscription identities.

## Visitor choice and ownership

A successful HTML GET on the public website or web app records a valid tagged arrival on the server, even when JavaScript is blocked or the visitor never uses the measurement control. The arrival stores no account identity and has `consented_at = NULL`. Its random ID is retained in the existing browser session, separately for each host, so refreshes and later campaigns in that session do not replace or duplicate the first touch. This introduces no additional campaign cookie before opt-in. HEAD, JSON/AJAX, prefetch, known bots, redirects and error responses do not create arrivals. Existing exclusions, saved refusals and the collection-enabled switch still apply.

The shared “Campaign link measurement” control offers allow, decline and withdrawal for optional account/conversion linkage. A tagged GET also renders an encrypted acknowledgement. JavaScript posts it only after explicit opt-in or previously granted measurement, upgrading the same arrival with a real consent timestamp rather than adding a second visit. The acknowledgement expires in ten minutes and is bound to the arrival host and browser session. Forged, expired and other-host/session tokens are rejected. Retries of the same acknowledgement are idempotent. Both website and web app endpoints use the current host, session CSRF protection and throttling; posted account IDs are ignored.

Ordinary web-app search pages omit the control and its script from the initial
HTML so the link cannot appear while results load. A tagged search still offers
the initial opt-in choice when none is saved. Tagged searches with a saved choice
keep the controls hidden while retaining the script's existing acknowledgement
behavior. Other pages retain the shared control for changing or withdrawing a choice.

Before opt-in, deduplication lasts for the browser session (at most the 30-day touch lifetime); a new session can count another anonymous arrival. After opt-in, one first eligible touch is kept for a 30-day cookie window. Refreshes, direct traffic and subsequent campaigns do not replace it. Host-only encrypted HttpOnly SameSite=Lax cookies survive signup/login session regeneration. Only a consented visit can be claimed by the authenticated web guard; another account cannot claim it. Logout clears the cookies and anonymous session references. The cookies remain on the arrival host: moving from the public website to `my.*` does not carry attribution. For membership campaigns, link directly to `my.pianolit.com` to retain the complete arrival-to-membership flow. Cross-device attribution is not provided.

Withdrawal removes the current anonymous or consented visit and its subscription attribution links, plus earlier links owned by the authenticated account. The saved refusal prevents further automatic arrivals. If an excluded account signs in after an anonymous arrival, the existing exclusion cleanup also removes that session’s anonymous campaign records. It does not change accounts, access or accounting payments. Allowing measurement later on an untagged page does not invent an arrival: the visitor must reopen the tagged link. Attribution expires after 30 days for new checkout linkage; successful payments of an already attributed subscription remain linked until retention removes the campaign cohort.

`App\Activity\ExcludedAccounts` excludes Arthur, Elena, the legacy Arthur email spelling and recognized opted-out browsers from collection. Reports also filter owned excluded-account visits. Admin, impersonation, mobile/API and shop traffic do not create campaign arrivals. Account deletion cascades campaign links. Existing activity analytics and GTM/Meta behavior are unchanged; this control covers only first-party campaign linkage.

## Billing correctness

The web purchase action uses the exact newly created Stripe subscription returned by the existing factory. Trial starts require provider status `trialing`. An immediate `active` subscription is recorded without inventing a trial; an `incomplete` subscription may later become paid. The shared `NewTrial` notification, Apple/mobile controllers, existing membership logic and billing API payloads are unchanged.

Signed `invoice.payment_succeeded` events create minimal financial receipts only for paid, positive subscription invoices. They do not write a second row to `payments`. Receipt identity is unique by type and Stripe invoice/charge ID. The existing successful-charge payment handler uses an atomic receipt marker and transaction when this feature is enabled; duplicate and concurrent deliveries cannot append another payment, and a failed handler rolls back the marker for retry. Existing historical duplicate payments are not deleted or rewritten. This does not fix unrelated out-of-order subscription-status updates in the legacy billing code.

The report joins exact subscription AND customer identity. Positive invoice receipts can arrive before the checkout response or campaign binding; the eventual join reconciles them without storing a raw webhook body or making Stripe API calls. Unattributed receipts do not appear in campaign reporting. The earliest paid invoice (paid timestamp, then invoice ID for ties) contributes one conversion and one initial payment amount per subscription, regardless of delivery order. Renewals, zero invoices, failures, manually marked-paid events and shop payments do not add conversions. A late first invoice can correct a report that previously saw only a renewal. Conversion means first paid subscription, not necessarily a first-ever customer: a returning customer who creates a new subscription can be counted again.

Financial receipt storage is separate from optional campaign consent, matching the existing accounting integration; it stores only invoice identity, subscription/customer IDs, amount, currency and successful-payment time. Unmatched invoice receipts and campaign links are pruned after 90 days. Tiny charge identity markers stay for replay protection; normal accounting payments are preserved. Reports are limited to retained campaign cohorts. The existing Laravel scheduler runs `attribution:prune` daily, which is a no-op while disabled. Verify the scheduler operates in production.

## Reporting

Open **Analytics → Campaigns** on `admin.*` (`admin.analytics.campaigns`, `/analytics/campaigns`). It uses the admin session guard and shared server-rendered table, with 12 rows by default. SQL search covers all four labels; sorting and pagination apply to complete grouped results. Arrival periods are the last 7, 30 or 90 calendar days (including today), or all retained arrivals. No source allowlist or code update is necessary for new rows.

Each source/medium/campaign/placement combination shows anonymous and consented arrivals, provider-confirmed trials, first paid subscriptions, first-payment gross revenue by currency and the latest arrival. Campaign-only and medium-only links appear in the same admin and CLI reports, with omitted fields left unspecified. Different combinations remain separate groups even when the campaign name matches. An arrival is the first eligible tagged visit in a browser session, or the existing 30-day cookie window after opt-in, not every click or a unique person. First payment means a positive subscription payment, not necessarily a first-ever customer. Filters select the arrival cohort: a later trial/payment still belongs to that earlier arrival. Renewals, zero payments, unrelated customers, denied measurement, known bots, opted-out accounts and expired/deleted cohorts are outside the report. Blocked scripts do not prevent anonymous arrivals; they can prevent consent acknowledgement and therefore conversion linkage. Cross-device conversion linkage is not provided. Fees/refunds are not deducted; currencies are never added together.

The read-only CLI discovers the same dynamic groups:

```sh
php artisan attribution:report --since=2026-10-17
```

The daily `attribution:prune` scheduler retains 90 days of campaign links and removes aged unmatched invoice receipts. Billing payments and charge retry markers remain intact. Reports display retained data even when new collection is disabled.

## Deployment and verification

1. Deploy `2026_10_07_030000_create_campaign_attribution_tables.php`, `2026_10_17_160000_expand_campaign_attribution_sources.php` and **`2026_10_20_230000_allow_anonymous_campaign_arrivals.php` before serving the new application code**. The latest migration makes `consented_at` nullable without changing existing consent timestamps or subscription links. Deploy the matching PHP and Blade changes together; this fix changes no JavaScript/CSS assets and requires no asset rebuild. A code rollback can keep the nullable column. Schema rollback restores NOT NULL only when no anonymous rows remain; otherwise it deliberately retains nullability and all records, without inventing consent or deleting arrivals.
2. The application now defaults to enabled as requested. If a deployed environment explicitly sets `CAMPAIGN_ATTRIBUTION_ENABLED=false`, change that setting through the normal deployment configuration process and refresh the deployed configuration cache. Local work does not edit `.env`, deploy or migrate the normal environment.
3. Verify the deployed Stripe endpoint signing secrets, US/Swiss account routing, `invoice.payment_succeeded` delivery and the normal scheduler. Keep raw-body signature verification. Do not change provider API versions or use live purchases for verification. See `docs/STRIPE_MEMBERSHIPS.md` for the modern billing deployment checks.
4. In a disposable sandbox, validate tagged arrival → immediate anonymous admin row → opt-in upgrading that same row → signup/login → web checkout → trial → first positive invoice, retry handling, out-of-order invoices and separate currencies. Check that checkout without opt-in creates no campaign subscription link. Isolated SQLite tests do not prove concurrent MySQL behavior or actual Stripe delivery.
5. Smoke-check `https://my.pianolit.com/pieces/174?utm_source=youtube.com&utm_medium=video`, a source-only YouTube link and an entirely new source in a fresh, non-excluded browser session on the deployed site. Each first arrival should appear without clicking Allow measurement; refreshing must not increment it. An incognito browser is anonymous until it signs in; recognized Arthur/Elena sessions remain excluded. Verify the admin row, refusal/withdrawal behavior and preserved account exclusions. Use `my.*` links when measuring membership conversions. Review pre-existing GTM/Meta measurement separately; no GA4 integration is introduced here.

Earlier clicks that created no campaign records are not backfilled by this fix. The live production change is complete only after the migration/code rollout and the deployed anonymous-visit smoke check.
