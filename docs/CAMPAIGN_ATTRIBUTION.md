# Video campaign attribution

Prepared locally on 2026-10-07. Disabled by default. Nothing in this change sets up Google Analytics or deploys tracking.

## Measurement and consent

The supported description link is:

https://my.pianolit.com/pieces/174?utm_source=youtube&utm_medium=organic_video&utm_campaign=respighi_notturno&utm_content=description

Use literal `&` in the YouTube description, not HTML `&amp;`.

`config/attribution.php` allowlists the exact four UTM values and piece ID. The component only creates a short-lived encrypted acknowledgement on that rendered guest/authenticated piece page. JavaScript posts it after explicit opt-in, or after the visitor previously allowed this measurement. No record is made merely from a GET, trial button click or successful-account signup. Forged, expired and other-piece campaign tokens are rejected. The token has a random visit identity so acknowledgement retries are idempotent.

One first eligible touch is kept for a 30-day cookie window. Refreshes, pricing navigation and direct traffic do not replace it. Host-only encrypted HttpOnly SameSite=Lax cookies survive Laravel's signup/login session regeneration. The first authenticated web account claims the visit; another account cannot claim it. Logout clears both cookies. Attribution expires after 30 days for new checkout linkage; an already attributed subscription's later successful payment remains linked.

The small “Video link measurement” control lets a visitor allow, decline or withdraw. Withdrawal deletes the current visit and its subscription attribution links (plus earlier links owned by the signed-in account), without changing the account, membership or accounting payments. If a visitor allows measurement later from an untagged page, they must reopen the tagged link to establish an arrival. This is intentionally a separate choice for first-party campaign linkage: existing GTM/Meta behavior is unchanged, and this control does not claim to control every existing tracker. Review that distinction and the wording before activation. This is a conservative product design, not a legal-compliance certification.

The analytics records store only a fixed campaign key, random visit UUID, timestamps, internal account binding and the attributed Stripe subscription/customer/account/plan. No names, emails, IPs, arbitrary referrers, URLs, query strings, browser profiles or payment-card fields are added. Account deletion cascades campaign links. Only the authenticated web guard controls ownership; posted `user_id` is ignored. Admin/impersonation traffic is excluded.

## Billing correctness

The web purchase action uses the exact newly created Stripe subscription returned by the existing factory. Trial starts require provider status `trialing`. An immediate `active` subscription is recorded without inventing a trial; an `incomplete` subscription may later become paid. The shared `NewTrial` notification, Apple/mobile controllers, existing membership logic and billing API payloads are unchanged.

Signed `invoice.payment_succeeded` events create minimal financial receipts only for paid, positive subscription invoices. They do not write a second row to `payments`. Receipt identity is unique by type and Stripe invoice/charge ID. The existing successful-charge payment handler uses an atomic receipt marker and transaction when this feature is enabled; duplicate and concurrent deliveries cannot append another payment, and a failed handler rolls back the marker for retry. Existing historical duplicate payments are not deleted or rewritten. This does not fix unrelated out-of-order subscription-status updates in the legacy billing code.

The report joins exact subscription AND customer identity. Positive invoice receipts can arrive before the checkout response or campaign binding; the eventual join reconciles them without storing a raw webhook body or making Stripe API calls. Unattributed receipts do not appear in campaign reporting. The earliest paid invoice (paid timestamp, then invoice ID for ties) contributes one conversion and one initial payment amount per subscription, regardless of delivery order. Renewals, zero invoices, failures, manually marked-paid events and shop payments do not add conversions. A late first invoice can correct a report that previously saw only a renewal. Conversion means first paid subscription, not necessarily a first-ever customer: a returning customer who creates a new subscription can be counted again.

Financial receipt storage is separate from optional campaign consent, matching the existing accounting integration; it stores only invoice identity, subscription/customer IDs, amount, currency and successful-payment time. Unmatched invoice receipts and campaign links are pruned after 90 days. Tiny charge identity markers stay for replay protection; normal accounting payments are preserved. Reports are limited to retained campaign cohorts. The existing Laravel scheduler runs `attribution:prune` daily, which is a no-op while disabled. Verify the scheduler operates in production.

## Reporting

After activation:

```sh
php artisan attribution:report --since=2026-10-07
```

This read-only report groups consented arrivals, confirmed trials, first paid subscriptions and initial gross revenue by the fixed campaign. `--since` filters ARRIVAL cohorts; a payment received later belongs to that earlier cohort. Denied cookies, blockers, visits without opt-in, deleted/expired data and cross-device visits without the original cookie are not counted. It is neither all YouTube clicks nor unique people. Revenue excludes renewals and is gross before refunds and fees. Currency amounts are never added across currencies; unsupported formatting currencies display exact minor units. No GA4 export is included or required.

## Activation / deployment checklist

1. Approve the first-party opt-in wording, 30-day attribution window, 90-day retention and separate financial receipt handling. Review the pre-existing GTM/Meta consent behavior independently. Keep `CAMPAIGN_ATTRIBUTION_ENABLED=false` until the database is ready.
2. Deploy PHP, Blade, JavaScript and Mix manifest together; apply `2026_10_07_030000_create_campaign_attribution_tables.php` using the normal production release process. Do not run this migration against production as a local test.
3. Verify the deployed Stripe endpoint signing secret, both US/Swiss billing accounts and which endpoint(s) deliver their events. Keep raw-body signature verification. Ensure each relevant endpoint includes `invoice.payment_succeeded` and the existing `charge.succeeded` events. The installed API version may use invoice `subscription`, while newer versions use `parent.subscription_details.subscription`; both are accepted. Do not change endpoint API versions casually.
4. In a disposable Stripe sandbox and database, validate the complete opt-in → signup/login → web checkout → 7-day trial → positive invoice flow, duplicate/concurrent retries, an invoice before local linkage, both billing accounts and no duplicate `payments` rows. SQLite regressions do not prove concurrent MySQL behavior or real Stripe delivery. Use no live purchases.
5. Enable `CAMPAIGN_ATTRIBUTION_ENABLED=true` through the approved deployment/configuration process; refresh Laravel's deployed configuration cache as appropriate. Confirm consent denial writes no campaign record, opt-in records one arrival, privacy withdrawal works, and scheduler pruning runs. Only then put the tagged link in the description and validate the report with the deployed site.

GA4 can later mirror these verified server events using the existing PianoLIT property and explicit campaign dimensions. Audit GTM first to avoid duplicates. A seven-day-later payment must retain the stored campaign and be timestamped when paid, rather than relying on the original browser session or backdating to the trial. Any GA4 export requires separate verification and server-only credentials; none were created here.
