# Freepick scheduling

The Pieces directory's **Schedule** action opens the shared modal. Administrators can select the piece immediately or choose a future local date/time. The form displays `America/New_York`; schedule instants are stored in UTC so the autumn clock change cannot restore an older selection. A saved cover image is required, as it was for the previous editor action. Future reservations at the same instant are rejected by a database unique constraint.

`free_picks` is authoritative for selection. The current entry is the non-retired row with the latest `scheduled_at <= now`, then highest ID. It remains current until another entry is due. Future entries never grant early access. An immediate selection replaces the current selection and leaves future bookings in place. The table's scheduled date displays the earliest upcoming booking for that piece.

Fields:

| Field | Purpose |
| --- | --- |
| `id`, `piece_id` | Selection identity and piece foreign key; piece deletion cascades. |
| `scheduled_at` | UTC selection time. |
| `reserved_at` | Nullable unique UTC reservation for future bookings; immediate selections have no reservation. |
| `activated_at` | UTC marker that legacy highlight history has been synchronized. |
| `retired_at` | Excludes imported historical selections from competing with the explicitly selected legacy Freepick. |
| `created_at`, `updated_at` | Record bookkeeping. |

`pieces.is_free` is removed. `Piece::is_free` remains a computed boolean in the existing mobile JSON, and `Piece::free()` / `free(false)` retain their interfaces. Current selection lookup uses one request-owned scalar snapshot, never a per-piece query or shared access cache. Discover and Explore retain their existing response shapes and refresh prefixes; their internal cache keys also identify the current selection and history-sync state, so a due change cannot wait for daily feed expiry. No new schedule fields or relationships are appended to piece JSON.

`pieces.highlighted_at` remains a compatibility history date for the existing public/mobile history queries and responses. Immediate selection synchronizes it in the selection transaction. The existing Laravel scheduler runs `freepicks:sync-history` every minute for due future entries. This idempotent task changes only that legacy date and the synchronization marker; it sends no email and leaves piece metadata/media untouched. Current selection and content access resolve directly from the schedule and do not depend on this worker running. Legacy historical listings can lag a due selection until the next scheduler tick.

For a newly due current piece, the model supplies its scheduled highlight timestamp immediately in the existing JSON field, including before the history task runs. Existing migrated current picks retain their original highlight timestamp. The shared selection snapshot reads both the current row and next due boundary in one statement; regular clock ticks do not add queries per rendered row. Piece scopes use a scalar subquery, avoiding MySQL's restriction on `LIMIT` inside `IN` subqueries.

Deployment requires a coordinated application/schema release: drain requests or use the normal maintenance window, back up the database, run `2026_10_20_120000_create_free_picks_table.php`, deploy the matching PHP/Blade/JS/manifest together, then resume all web/API workers on the new code. Old application workers must not continue using the removed column. The migration imports historical dates, retains the explicit old current selection even when history dates are out of order, then drops only `is_free`. Native column removal avoids Laravel 8's SQLite table-rebuild cascade behavior. Verify the production cron still invokes Laravel's scheduler every minute. No iOS app update, media URL change, email campaign, Redis flush, or new mobile endpoint is required.

Rollback restores the boolean column from the selection due at rollback time before removing the schedule table; back up future reservations before rollback if they must be retained. Verify migration/rollback and unique booking behavior on a disposable MySQL database before production; the local review suite uses isolated SQLite. Never use live selections or send Freepick email as a verification step.
