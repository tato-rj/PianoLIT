# Stripe web memberships

Implemented locally on 2026-10-15. This changes web membership checkout and account management. It does not recreate existing subscriptions, move billing dates, change App Store purchase APIs, or protect/change media URLs.

## Application flow

- Pricing and checkout keep the existing PianoLIT layout. Trial eligibility is calculated on the server; returning subscribers are shown a paid start. Local prices must match the selected account's active USD Stripe Price, amount and interval before a subscription is created.
- The membership card form uses Stripe.js Card Element, SetupIntents and PaymentIntents. PAN/CVC never pass through PianoLIT. A server-created SetupIntent must have succeeded for the current operation and customer before it can authorize a subscription or card replacement. The server retrieves it; posted IDs do not establish ownership.
- Checkout provisions a membership customer, independently of the shop customer. It creates a subscription with `default_incomplete`, an explicit default payment method, and an operation idempotency key. The browser completes any invoice/bank authentication, then asks the server to retrieve and confirm the subscription. Only confirmed active/trialing subscriptions reach the welcome page.
- Card replacement sets the subscription's verified payment method and, for a PianoLIT-owned plan schedule, its future default. It never deletes the previous card first or edits a different shop customer. Updating a card does not silently charge an outstanding invoice; the user uses the Stripe-hosted invoice's Pay/View link to complete payment.
- Changes between monthly/yearly plans are scheduled at the existing billing-period boundary with no current-period proration. The current plan remains displayed until the change happens. Existing discount IDs and supported tax/default settings are retained. Selecting the current plan removes the pending change. Complex item discounts, multiple subscription items or external schedules require billing review rather than an automatic rewrite.
- Cancel schedules an end at the existing period boundary (an unpaid incomplete checkout is canceled immediately without a new invoice or proration), updates the full returned state and releases a PianoLIT-owned pending plan change. Resume withdraws scheduled cancellation. Both preserve the existing access policy, including trial/grace access and the unconditional super-user override.
- Pause/unpause are explicit desired states, not a toggle. Pause uses `pause_collection.behavior=void`, preserves the billing calendar, suspends ordinary web Premium media immediately and carries no unused paid time forward. An open unpaid invoice blocks pause. Cancellation, renewal, pause and content access are separate states; existing mobile status methods remain unchanged.
- Invoice history loads when its tab opens, with 12 rows per Stripe page, older-page navigation, escaped output and a recoverable failure state. Opening My Membership itself does not call Stripe for invoice history. Ended subscriptions retain invoice access.

## Ownership, retries and local state

`app/Billing/Stripe` contains account selection, the isolated provider gateway, durable operations, normalized subscription state, membership checkout/management and webhook processing.

The additive migration adds exact subscription/account identity and payment/schedule metadata to `stripe_memberships`. New bookkeeping fields are hidden from the model's existing JSON serialization. Legacy records are linked lazily or during reconciliation only when Stripe returns one unambiguous current subscription (or one historical subscription when none is current). Multiple candidates require review; no first-subscription guessing is used.

`stripe_billing_profiles` stores each account's membership customer. `stripe_billing_operations` reserves purchase ownership across tabs and records provisioning/setup/subscription identities. Stable idempotency keys cover provider mutations. All membership mutations and refreshes serialize on the account row. Local writes roll back together; a provider subscription created before a local failure is recovered by its operation metadata. Mutating responses are followed by a current provider read, because an idempotent response can contain an older snapshot.

An unresolved subscription creation older than 23 hours is not blindly replayed: the operation's customer subscriptions are searched for its provider metadata first, and an unmatched old operation requires review. This avoids creating another subscription after Stripe can discard an idempotency key. Minimal operation identities are retained, rather than pruned and later mistaken for new requests.

Accounts still associated with Apple billing are blocked from purchasing a second billing source through the web. Existing Apple records, history and iOS contracts are retained. Switching such an account to web billing requires an explicit provider-transition workflow; this implementation does not silently replace its Apple membership. Multiple existing local membership rows also require review.

## Stripe accounts and API versions

The installed SDK is `stripe/stripe-php` **22.0.0**. It requires PHP 7.4 or later; the application already runs on PHP 8.3 locally. Only Stripe changed in the dependency lockfile. The pre-existing `teapot/status-code` PHP upper-bound mismatch remains a separate dependency issue; the local Stripe-only Composer update used `--ignore-platform-req=php+` rather than updating unrelated packages.

Membership requests use per-client credentials and the explicit **2025-03-31.basil** API contract. This version supports modern payment confirmation and item-level billing periods. The shop's existing static client keeps `STRIPE_VERSION`, falling back to `2020-03-02`; its token/source purchase flow is retained. Its legacy upcoming-invoice helper retains the legacy endpoint through an explicit raw SDK request. The application does not change the Stripe account-wide API version.

Configure these through server environment/configuration, never by exposing secrets in a page:

- `STRIPE_US_KEY`, `STRIPE_US_SECRET`: US publishable/secret API keys. The pre-existing `STRIPE_KEY`/`STRIPE_SECRET` remain US fallbacks.
- `STRIPE_SWISS_KEY`, `STRIPE_SWISS_SECRET`: Swiss account keys for existing Swiss memberships.
- `STRIPE_US_WEBHOOK_SECRET`, `STRIPE_SWISS_WEBHOOK_SECRET`: separate endpoint signing secrets. `STRIPE_WEBHOOK_SECRET` is the legacy **US-only** fallback. A Swiss endpoint needs its own signing secret.
- Optional `STRIPE_US_MONTHLY_PRICE`, `STRIPE_US_YEARLY_PRICE`, `STRIPE_SWISS_MONTHLY_PRICE`, `STRIPE_SWISS_YEARLY_PRICE`: Price IDs when they differ from the existing local Plan names (`monthly`/`yearly`). Changing these affects future selections; it does not replace existing subscribed prices.

For existing memberships, account selection uses the source's persisted account, then the existing historical Swiss customer list. It never lets an unrelated shop customer choose the membership account. Verify old customer/account mappings before activation, particularly manually created or unlisted historical Swiss customers.

## Webhooks and reconciliation

The same `/webhooks/stripe` URL accepts **snapshot events** from both accounts. The raw body must pass signature verification with that account's secret and timestamp tolerance. The verified account, rather than posted identity, chooses provider credentials. Missing signing configuration fails closed. Valid unsupported event types receive 200 without side effects.

`WebhookProcessor::EVENTS` is the canonical list of required subscriptions, customer/default-card updates, payment-method updates/detachment, successful/failed/action-required invoices, charges, refunds and disputes. Configure the existing dashboard endpoints with this list and the snapshot API version. Do not create an extra endpoint merely to update an existing one.

Handled event IDs are stored uniquely per billing account in `stripe_webhook_events`. The inbox retains only object identities and minimal accounting fields, not raw contact/address/card/authentication payloads. Processing reads current provider subscription state under the account lock instead of applying delayed snapshots. Events about a different subscription cannot overwrite the linked one. A failure retains the inbox entry, rolls back local processing and returns a failure so Stripe retries.

Charge recording is idempotent regardless of campaign-attribution settings. Minimal charge receipt markers remain durable; historical duplicate payment rows are not deleted or silently rewritten. Refund events refresh recorded refunded amounts. Dispute events refresh the associated charge; they do not introduce an automatic account ban or refund policy. Existing financial history is retained for excluded analytics accounts, while `Activity\ExcludedAccounts` continues to exclude their analytics collection.

`stripe:reconcile` replays pending inbox entries and rotates through the least recently synchronized memberships. It makes **read-only Stripe requests** and updates local state; it does not charge, cancel, create subscriptions, change provider data or send email. Completed inbox payloads older than 90 days are reduced to `{}` while their event identities remain for deduplication. The Laravel scheduler runs it hourly with overlap protection. Servers scheduling individual commands must add it explicitly.

`stripe:reconcile --audit` is read-only locally and remotely (no provider requests). It reports counts of ambiguous ownership, historical duplicate charges, missing subscription links, pending webhooks and old unfinished checkouts. Resolve ambiguity using verified Stripe records; never choose a subscription from its list position.

`stripe:webhook --account=us` (or `swiss`) prints configuration requirements without changing Stripe. `--create` explicitly creates a new endpoint only when one is actually needed. Secrets are obtained from the appropriate Stripe Dashboard, not printed by the command.

## Release sequence and validation

1. Back up the database and review the existing US/Swiss customer, Price, signing-secret and endpoint mappings. Retain current subscriptions and billing dates. Review historical duplicates before any future uniqueness/data-cleanup migration.
2. Deploy **`2026_10_15_120000_modernize_stripe_membership_billing.php` before serving the new routes or running the new scheduler**. Existing campaign receipt/history migrations must already be deployed. The migration is additive, makes no provider requests and does not backfill by guessing.
3. Install the locked Stripe SDK and deploy matching application code, copied JavaScript and manifest. Preserve the shop's legacy API version; do not update the account-wide API default as part of deployment.
4. Verify both existing Stripe endpoint signatures, required events and snapshot API versions. Update the endpoints in the Stripe Dashboard; do not duplicate them. Confirm that the scheduler runs reconciliation.
5. Run `stripe:reconcile --audit` in the deployment environment and resolve ambiguous records. Run a bounded reconciliation only after reviewing its configuration. No normal-environment reconciliation or migration was executed during local development.
6. In a disposable Stripe sandbox, exercise first trial, returning paid purchase, successful/declined/3DS cards, abandoned/reloaded checkout, retry after provider success/local failure, two tabs, coupon applicability, safe card replacement, paid-invoice recovery, pause/unpause, cancel/resume, monthly/yearly changes at the boundary, existing discounts, refunds and old/new webhook replay. Verify US and Swiss separately. This requires actual test credentials and endpoint configuration; local mocks do not establish live Stripe behavior.
7. Verify MySQL uniqueness/locking and simultaneous requests on the real schema using disposable data. Monitor pending inbox counts and payment issues after rollout.

Local verification uses `phpunit-review.xml` with in-memory SQLite, fake mail/HTTP and a fake Stripe gateway. SDK transport tests additionally intercept the SDK HTTP client to inspect actual account/version/idempotency headers and the retained legacy card path. JavaScript checks simulate authentication, network failure, card-decline recovery, duplicate clicks and stale coupon responses. Synthetic Blade/browser previews verify desktop and phone layouts without loading Stripe or submitting a payment. These checks do not replace live sandbox and production configuration verification.

References: [Stripe subscription payment flow](https://docs.stripe.com/billing/subscriptions/overview), [deferred payments and SetupIntents](https://docs.stripe.com/billing/subscriptions/deferred-payment), [subscription schedules](https://docs.stripe.com/billing/subscriptions/subscription-schedules), [webhook retries and ordering](https://docs.stripe.com/webhooks), [API idempotency](https://docs.stripe.com/api/idempotent_requests).
