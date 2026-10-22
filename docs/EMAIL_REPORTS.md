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
Earlier nonzero reports remain stored locally until the three-month cleanup
described below.

## Three-month local retention

By Arthur's October 19 request, email reporting history is retained for three
calendar months. The Laravel scheduler runs `email-reports:prune --execute` in
the background at **03:00 America/New_York on the first of each month**, with
overlap prevention. This becomes active when the code is deployed and the
existing server cron continues invoking `php artisan schedule:run` every minute.
Apply the Mailgun receipt-table migration below before enabling the command.
No additional queue worker or database migration is introduced by retention.

The cutoff is the run time minus three calendar months, without month-end
overflow. Campaigns with `sent_at` strictly before that cutoff are removed from
`email_campaign_reports`, along with all their `email_logs` and associated
`mailgun_event_receipts`. A recent callback or summary rebuild does not extend
an old campaign's lifetime. Recipient rows and deduplication receipts belonging
to a retained campaign stay together, preserving its totals and replay handling.
Logs without a campaign summary, including unassociated transactional-email
tracking, expire by their own `created_at`. Orphaned event receipts left by
manual deletion are also removed. Rows exactly at the cutoff remain until a
later run. Monthly execution means history may remain for nearly four months
between cleanups.

The command changes only those three local email-history tables. It preserves
email-list definitions, subscriber records and list memberships, accounts,+billing, activity history, saved music and annotations. It neither sends email
nor calls or deletes anything in Mailgun. Expired local reports and recipient
histories are permanently removed by the scheduled run; rebuilding summaries
or receiving later provider callbacks cannot recreate their deleted source rows.

Preview the current deletion counts without any writes:

```sh
php artisan email-reports:prune
```

To apply the same retention policy manually:

```sh
php artisan email-reports:prune --execute
```

`--batch` accepts 1–1,000 recipient rows (default 500). Each batch removes
receipts and recipients transactionally; the final batch also removes its
campaign summary. Earlier committed batches survive an interruption, and
rerunning continues the cleanup. The command prints actual deletion totals.
No cleanup has been executed against the normal or production database during
development. Local tests use isolated in-memory SQLite; production MySQL and
the server cron still need deployment verification.

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

## PianoLIT newsletter

Deploy `2026_10_21_230000_create_newsletter_digests_tables.php` before enabling
matching scheduler/queue code, then restart Redis workers. For a rolling
release, deploy the `Subscription::joinAll` newsletter exclusion on every web
server and worker before exposing signup or creating the PianoLIT News list;
older code otherwise enrolls new accounts in every existing list. This adds
`newsletter_digests` and `newsletter_deliveries`, without changing shared/mobile
content models, JSON, existing lists or media delivery.

The newsletter is PianoLIT-wide, independent of Free Pick and other lists. The
shared modal uses `@input` and the primary button. `newsletter.subscribe` and
subscription AJAX stay on the request host for CSRF/session compatibility. The
legacy blog `posts.subscribe` and already-open blog `/subscriptions` forms also
join this newsletter. Other existing Free Pick/gift flows retain their behavior.
Signing up sends no immediate email and preserves all other list preferences.

`newsletter:send-monthly --execute` runs on the first at 08:00 America/New_York.
It automatically selects the previous calendar month's published blog posts,
crash courses, quizzes, infographics, eBooks and eScores using `published_at`.
Pianist profiles are public immediately and use `created_at`. The interval is
inclusive at its start and exclusive at the next month. Drafts, future releases,
older items, and coming-soon hub roots are not announced automatically.

Code-based feature, game and tool launches have no publication table. Add a
stable, dated entry to `config/newsletter.php` alongside each launch, with its
public route, title, description, hub and type. The monthly collector picks it
up automatically in the matching month; there is no manual monthly campaign
assembly. Preserve the key after publication. Remove an entry to withdraw it;
removing its route also excludes it from delivery. Rebuild cached configuration
when deploying changes. Do not backdate existing tools or infer release dates
from route discovery, modified timestamps or deployments. The config includes a
commented example and starts empty, so existing tools are not presented as new.

No news means no campaign or emails. A monthly record freezes content keys and
the opted-in audience, deduplicated by normalized email. Each recipient job
rechecks membership and the selected content's current public availability;
withdrawn items are omitted, and an empty selection skips delivery. New items
or subscribers added after scheduling are not inserted into that campaign.

The command without `--execute` prints a read-only selection preview. Re-running
with `--execute` does not duplicate an already queued month. If Redis fails while
scheduling, restore it and rerun during the same month. Failed jobs retain normal
retry behavior; recipient locks and sent markers guard against ordinary duplicates.
As with Free Pick, a provider accepting a message immediately before a process
or database failure can leave an ambiguous delivery.

Mail uses the existing tracking/report system and ExcludedAccounts guards.
Recipient delivery records follow the three-calendar-month retention policy,
pruned in batches of 500; non-recipient monthly markers remain to prevent
historical resends. Existing report/receipt pruning remains intact. Email copy
is editable in `resources/views/emails/newsletter/`; see the email README. The
PianoLIT News list's manual Send action uses the same monthly/empty guards.
Browser and test-email previews remain available through Email Lists.
