# Email campaign tracking and recovery

## October 19, 2026 incident

Mailgun's `mg.pianolit.com` domain webhook was configured as
`https://www.pianolit.com/webhooks/mailgun`. The public server redirects that
address with HTTP 301 to `https://pianolit.com/webhooks/mailgun`. Following this
redirect changed the webhook POST to a GET, which the POST-only Laravel route
rejects. Mailgun's live failure log showed **Method Not Allowed**. This accounts
for saved send records with no delivery/open/click updates; the dates alone do
not establish when the server's redirect configuration changed.

The saved domain webhook was corrected to the direct, non-redirecting URL on
October 19. Mailgun's endpoint test then returned **200 OK four times** for the
four selected event types: delivered, opened, clicked and permanent failure.
The existing production signature credential passed that test. No real email
was sent by this endpoint test.

The live Logs date picker reported **five days of detailed log retention**.
Current delivery/open/click events are present in Mailgun, including Swift
message IDs matching the format saved by PianoLIT. The manual export dialog
limits exports to 1,000 events; use the paginated API recovery command for a
whole campaign. The Metrics date picker separately advertises two months of
hourly aggregates, twelve months of daily aggregates and indefinite monthly
aggregates. These aggregate periods are not the detailed-event retention period.

The October 19 blast can be reconstructed from events still retained by the
provider, subject to exact record matching and successful API access. October 5
and 12 fall outside the displayed detailed-log window. Later opens/clicks for
those messages may still be retained and can restore partial counts. Complete
older campaign totals require separately attributable provider aggregates,
exports or backups containing the actual events/counters. Domain-wide daily
metrics also contain transactional mail and engagement with older campaigns;
do not copy them into one campaign or invent missing recipient histories.
Earlier nonzero reports remain stored locally.

## Deploy

Deploy the new service, command, receiver/middleware, tracking trait and config
together. Apply `2026_10_19_230000_create_mailgun_event_receipts_table.php` before
serving the new receiver. The table holds only event identity, a hashed signature
token, the existing email-log ID, event type and occurrence/receipt times; it does
not retain IPs, user agents, URLs, raw signatures or duplicate recipient addresses.
The migration has only been exercised in isolated SQLite locally.

The sending credential remains `MAILGUN_SECRET`, with `MAILGUN_DOMAIN` and the
existing US/EU `MAILGUN_ENDPOINT`. The optional `MAILGUN_WEBHOOK_SIGNING_KEY` is the
account's HTTP webhook signing key, not a replacement sending key. When set, it
takes precedence for signature verification. Without it, the receiver preserves
the existing legacy signing credential that passed the live endpoint test. Never
paste either key into chat or rotate the sending key merely to change tracking.

The receiver verifies body-provided signature fields with HMAC-SHA256 and a
constant-time comparison before processing any event. It allows delayed
deliveries for `MAILGUN_WEBHOOK_TOLERANCE` seconds (default 86,400), rejects request
timestamps more than five minutes ahead, and persists event/token deduplication.
The former 15-second cutoff rejected otherwise authentic delayed callbacks.
An explicit signing key never falls back to another key after a signature fails.

Do not introduce a GET handler for webhook updates or disable verification to
work around redirects. The Mailgun endpoint must remain the direct HTTPS POST
URL. Check the correct account, region, sending domain and four event types.

## Preview and apply retained events

Run on the server with the **production email-log database** and a Mailgun API
key permitted to read Events. A domain sending-only key may lack that access.
Do not run against a development database and assume it repairs production.
The command constructor performs no I/O.

After deploying the migration and code, preview the affected period:

```sh
php artisan email-reports:recover --from=2026-10-01 --to=2026-10-21
```

Dates are UTC, and `--to` is exclusive. The default is a true dry run: it makes
read-only Mailgun GET requests and SELECTs locally, then shows projected unique
campaign counts. It neither sends mail nor changes the provider or local data.
Only exact stored message-ID/recipient matches are eligible. Angle-bracket and
recipient-case differences are normalized. Temporary failures, unsupported
events and the shared excluded-account identities are ignored. Ambiguous matches
are counted and left untouched; no campaign is guessed from a subject, recipient
alone or a similar send time.

Review the projected counts, then apply the same range:

```sh
php artisan email-reports:recover --from=2026-10-01 --to=2026-10-21 --apply
```

The apply operation changes local email tracking and affected campaign summaries
only. Each event and its unique-count changes commit together, and retries are
deduplicated by Mailgun's event ID plus UTC day. Repeating an overlapping recovery
is supported. Open/click totals retain at least their existing value and the
number of known distinct retained events; partial windows cannot establish an
exact all-time repeat-open/repeat-click total. Unique-recipient report counts
are restored only where there is provider evidence. A retained open/click event's
explicit `delivered-at` field can also restore that delivery time.

Use repeatable `--campaign` options to restrict the local repair, for example:

```sh
php artisan email-reports:recover --from=2026-10-01 --to=2026-10-21 \
  --campaign=free-pick.1791207298 \
  --campaign=free-pick.1791812081 \
  --campaign=free-pick.1792420806
```

The three IDs were read from the live October 5, 12 and 19 report links. The
default page budget is 100 pages of at most 300 provider events. If the command
reports an incomplete run, increase `--max-pages` (maximum 1,000) and repeat. No
pagination link may change the HTTPS host or sending-domain Events path, and
HTTP redirects are disabled so authentication cannot follow an arbitrary URL.
The date range is bounded to 31 days per run. Empty results outside retention
are **unknown history**, not evidence of zero deliveries or zero engagement.

The agent has not run this recovery command against production or modified
production report rows. Production recovery remains a deployment/operator step.

## Rebuild summaries without deleting historical reports

`email-reports:rebuild` repairs aggregate snapshots from existing recipient rows;
it cannot recreate events that never reached PianoLIT. It now updates each
campaign transactionally instead of truncating the entire summary table. Reports
without surviving source rows remain intact. Optional `--campaign` scopes the
operation and `--dry-run` makes it read-only:

```sh
php artisan email-reports:rebuild --campaign=free-pick.1792420806 --dry-run
```

Ordinary tracked-send record creation and its initial summary increment now
share a transaction. Incoming updates serialize on the matching email-log row;
receipt identity, row flags and model summary increments commit together. The
summary rebuild locks its campaign summary before taking its source snapshot.
These prevent partial updates in the tested paths; production MySQL locking
still requires deployment/integration validation.

Future tracked campaigns also receive a stable MIME `X-Mailgun-Tag`, computed as
`pianolit-campaign-` plus the SHA-256 of their local campaign ID. The same campaign
uses one bounded-length tag for all recipients, allowing aggregate attribution
after detailed logs expire. Previews and excluded accounts receive no campaign
tracking tag. Existing email content, subjects, public/mobile contracts and
delivery URLs remain unchanged.

## Provider references

- [Mailgun webhook signatures](https://documentation.mailgun.com/docs/mailgun/user-manual/webhooks/securing-webhooks)
- [Mailgun Events API and pagination](https://documentation.mailgun.com/docs/mailgun/api-reference/send/mailgun/events/get-v3-domain_name-events)
- [Event identity and timestamps](https://documentation.mailgun.com/docs/mailgun/user-manual/events/event-structure)
- [Mailgun tracking and aggregate statistics](https://documentation.mailgun.com/docs/mailgun/user-manual/tracking-messages/tracking-messages)
- [MIME sending options and tracking tags](https://documentation.mailgun.com/docs/mailgun/user-manual/sending-messages/pass-sending-options)
