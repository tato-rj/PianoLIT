# Freepick scheduling

## Freepick email design

The October 25 redesign keeps `FreePickEmail`, its subject, list identity,
tracking and pinned piece delivery. Edit `resources/views/emails/lists/freepick.blade.php`
for copy and actions. It uses the shared editorial email shell/footer and the
same compiled typography/button theme as the three welcome emails. The header
centers the shared shadow-free `mail::editorial.logo` at 48px, followed by
**Free pick of the week**. The cover begins below both, cropped to 240px high on
desktop and 170px on phones. Its top corners are square. The white content panel
extends from the piece identity through the end of the resource features. It retains its rounded 24px
top corners, overlapping the bottom of the cover by 24px. It leads into the familiar composer portrait,
colored level marker, piece
title and composer credit. The primary action links directly to the pinned
piece. The former Prefer your phone / iOS app line is removed.
The following More music to discover section returns to the shared email/web app
background (`#f8f9fa`).

Icon rows describe only the piece's available public-domain score/annotations,
performance, Synthesia and audio. A **Comments as you watch** row highlights
comments synchronized with video playback and passage navigation. It appears
only when the first performance or Synthesia recording used by the piece player
has commentary. The retired lessons promotion is removed. Separate-hand listening
is mentioned only when available. The weekly free-account access note stays
distinct from the Premium invitation for the wider library and public-domain
score downloads/printing. The section heading is **Let's dive in**, and account
copy says PianoLIT account without free. Regular Free pick copy, captions and
buttons use at least the default 16px size; only legal/footer notes stay smaller.
The piece description is left-aligned. Icons remain compact at 24px, with tight
row spacing. On phones the latest cards become small image/text rows so their
16px captions fit. No media access rules or original URLs change.

The next section shows up to three latest repertoire pieces as small cards,
excluding the pinned Freepick. `FreePickEmailPieces` matches the web app's
tutorial eligibility and requires artwork/composer, ordering by creation date
then ID. Cards resolve once per message for matching HTML/plain text; a smaller
catalog produces fewer cards and an empty selection hides this section. The
library action opens `webapp.explore`. Exported previews use fixed fictional
cards with inert destinations, without repertoire/account queries.

More music to discover uses the same 36px top padding as Keep the inspiration
going. Neither title has a trailing period; this rule applies to every title and
heading in both HTML and plain text. Body sentences keep their punctuation.

`components/{html,text}/editorial/footer.blade.php` is shared by all four
redesigned emails. Subscribers keep their existing direct Free Pick unsubscribe
URL; test/browser previews use email preferences without a subscriber opt-out
link or campaign log. The footer has a left-aligned regular YouTube social icon,
subscription-reason note, contact text, unsubscribe/privacy/help/password links
and copyright, with the shadow-free PianoLIT icon at the right. No postal address
or other social channels appear. There are no footer social buttons or App Store
badge/link. The note says “because you are
subscribed to PianoLIT Free Picks.” HTML/plain text share the copy and destinations.
`EditorialLayout` clones the Markdown renderer so the
theme cannot leak into the next unrelated email in a queue worker. Deploy the
matching views, mail classes/trait and compiled theme together, then reload
queue workers normally; no new migration, schedule change or live test send
is required. See `resources/views/emails/README.md` for the editing map and safe
`scripts/preview-freepick-email.php` exporter.

## Scheduling and delivery

The Pieces directory's **Schedule** action opens the shared modal. Administrators can select the piece immediately or choose a future local date and whole hour. The hour selector defaults to **8am**. The form displays `America/New_York`; schedule instants are stored in UTC so the autumn clock change cannot restore an older selection. A saved cover image is required, as it was for the previous editor action.

**Automatically send the Freepick email** is checked for every new modal selection. Uncheck it to send manually later from Emails. Both immediate and future modal selections refresh Discover after activation, using the same `DiscoverRefresh` operation as the admin button: it rotates the existing `app.discover` and `app.explore` Redis prefixes. This invalidates the existing feed caches; they rebuild on the next request, just as with the button. When checked, the email campaign is queued only after that refresh succeeds. It uses the existing Free Pick template and all current subscribers to that list, preserving opt-outs; it does not enroll unsubscribed accounts.

The shared calendar disables past dates and dates with an existing scheduled Freepick. On today's date, elapsed hours are disabled; after the last whole-hour slot, today itself becomes unavailable. The server rejects past instants, submitted minute values and duplicate scheduled dates. One planned selection reserves the entire America/New_York calendar date, even at a different hour or across a daylight-saving offset change. Immediate selections remain immediate and do not reserve a scheduled day.

Existing bookings keep their original instants, including previously selected minutes. Their dates also block new bookings. New reservations use the UTC instant at the start of the selected local date in the existing unique `reserved_at` column, so concurrent new bookings for different hours still conflict at the database level. A date-range check includes older instant-based reservations. Whole-hour/date validation needs no timestamp backfill or rounding. The release/email actions require the separate migration below; deploy all scheduling writers with the matching code.

`free_picks` is authoritative for selection. The current entry is the non-retired row with the latest `scheduled_at <= now`, then highest ID. It remains current until another entry is due. Future entries never grant early access. An immediate selection replaces the current selection and leaves future bookings in place. The table's scheduled date displays the earliest upcoming booking for that piece: `Oct 23, 2026` with `9am` beneath it in smaller text. The `calendar-clock` icon aligns with the date's first line and alone uses `text-warning`; both date and time retain the normal text color. Older nonzero-minute bookings retain their accurate time in this display.

Fields:

| Field | Purpose |
| --- | --- |
| `id`, `piece_id` | Selection identity and piece foreign key; piece deletion cascades. |
| `scheduled_at` | UTC selection time. |
| `reserved_at` | Nullable unique UTC start of the reserved New York calendar date; older future bookings can retain their original instant. Immediate selections have no reservation. |
| `activated_at` | UTC marker that legacy highlight history has been synchronized. |
| `retired_at` | Excludes imported historical selections from competing with the explicitly selected legacy Freepick. |
| `refresh_discover`, `send_email` | Saved release choices. The directory modal always requests a refresh and saves the checkbox choice; legacy/programmatic selection remains silent by default. |
| `discover_refreshed_at` | Committed checkpoint after rotating both cache prefixes. |
| `email_queued_at` | The automatic campaign has been queued with a stable per-selection campaign ID. |
| `release_completed_at` | Release actions finished, or the entry was superseded before processing and must not be announced. |
| `created_at`, `updated_at` | Record bookkeeping. |

`pieces.is_free` is removed. `Piece::is_free` remains a computed boolean in the existing mobile JSON, and `Piece::free()` / `free(false)` retain their interfaces. Current selection lookup uses one request-owned scalar snapshot, never a per-piece query or shared access cache. Discover and Explore retain their existing response shapes and refresh prefixes; their internal cache keys also identify the current selection and history-sync state, so a due change cannot wait for daily feed expiry. No new schedule fields or relationships are appended to piece JSON.

`pieces.highlighted_at` remains a compatibility history date for the existing public/mobile history queries and responses. Immediate selection synchronizes it in the selection transaction, then runs its requested release actions after commit. The existing Laravel scheduler runs `freepicks:sync-history` every minute: it first synchronizes due history, then processes pending release actions. Scheduled refresh/email queueing normally begins on the first scheduler tick after the chosen instant (up to one minute later). Redis queue workers deliver email in the background. Current selection and content access resolve directly from the schedule and do not depend on these workers; legacy history and release actions can lag until they run.

Release rows are locked and checkpointed. A failed refresh prevents email; a queue failure retains the successful refresh and is retried on the next tick. Overdue selections already superseded by a newer due entry are completed without announcing the old pick. The automatic campaign uses bounded 500-subscriber fan-out jobs, a stable campaign ID and a unique `free_pick_email_deliveries` row per selection/subscriber. Recipient jobs recheck list membership and serialize on that row, skipping completed deliveries even after duplicate fan-out/worker retries. The scheduled piece is pinned in the email rather than looking up a later current pick. This delivery bookkeeping is independent of email analytics, so excluded accounts retain duplicate protection without gaining tracking logs. Mailgun tracking/reporting, unsubscribe links and the manual list's existing cooldown remain unchanged; automatic releases do not silently drop a newly chosen campaign because of that manual cooldown.

As with ordinary SMTP delivery, a worker dying after the provider accepts a message but before its SQL checkpoint commits can leave an ambiguous delivery; this is not an exactly-once provider guarantee. Failed Redis jobs use the existing retry/failed-job tooling. No emails are sent during migration or GET requests.

For a newly due current piece, the model supplies its scheduled highlight timestamp immediately in the existing JSON field, including before the history task runs. Existing migrated current picks retain their original highlight timestamp. The shared selection snapshot reads both the current row and next due boundary in one statement; regular clock ticks do not add queries per rendered row. Piece scopes use a scalar subquery, avoiding MySQL's restriction on `LIMIT` inside `IN` subqueries.

Deployment requires a coordinated application/schema release: drain requests or use the normal maintenance window, back up the database, run `2026_10_20_120000_create_free_picks_table.php` if pending and `2026_10_20_160000_add_free_pick_release_actions.php`, deploy the matching PHP/Blade/JS/manifest together, then resume all web/API and Redis queue workers on the new code. Old application workers must not continue using the removed column. The original migration imports historical dates, retains the explicit old current selection even when history dates are out of order, then drops only `is_free`. Native column removal avoids Laravel 8's SQLite table-rebuild cascade behavior. The release migration adds the choice/checkpoint columns and delivery table. It enables refresh for existing future bookings while leaving their email choice **off**; no existing booking acquires an automatic email implicitly.

Arthur runs production commands himself. Use the normal deployment's `php artisan migrate --force`, refresh cached views/configuration as usual, and restart the Redis workers (`php artisan queue:restart`). Verify the production cron still invokes `php artisan schedule:run` every minute, the existing Redis/default queue has a running worker, and the Free Pick email list exists. No additional scheduled task or iOS update is needed. No media URL, mobile endpoint or Redis flush is required. Do not run a real scheduled selection or campaign merely to test deployment.

Roll back the release-actions migration only after draining its queued jobs; it removes release choices/checkpoints and delivery bookkeeping while retaining selections. Rolling back the original schedule migration restores the boolean column from the selection due at rollback time before removing the schedule table; back up future reservations before that rollback if they must be retained. Verify migration/rollback and unique booking/delivery locking on a disposable MySQL database before production; the local review suite uses isolated SQLite. Never use live selections or send Freepick email as a verification step.
