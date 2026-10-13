# Database activity history

Website, `my.*` and existing iOS API activity now write directly to SQL. Anonymous website visitors are included by explicit request. The iOS app keeps making the same requests and receives the same response shapes; no app update is needed for this storage change. Activity counts, last-active dates and admin App Logs also read SQL. Redis caches, sessions and other non-activity features are unchanged.

## Deployment order

1. Back up the application database and legacy Redis history.
2. Run the new migration **before** deploying the tracking code/templates:

   ```sh
   php artisan migrate --path=database/migrations/2026_10_12_120000_create_activity_history_tables.php --force
   ```

3. Deploy the PHP, Blade, JavaScript outputs and Mix manifest together to **every** web/API server. Clear/rebuild deployment caches and restart long-running application processes through the project's normal deployment procedure. No worker is needed: activity writes are synchronous SQL inserts, independent of Redis queues. `ACTIVITY_ENABLED=false` disables new activity recording for both website and mobile, with no Redis fallback.
4. For the expanded **Analytics → Users** system, also deploy `2026_10_12_150000_extend_activity_for_user_analytics.php` before its writers/views. It adds confirmed identity links, UTC companion columns, inactivity timestamps and indexes without rewriting history. See [USER_ANALYTICS.md](USER_ANALYTICS.md) for rollout, definitions and pending Swift integration. Open **Analytics → Users → Activity**, select a visit, and verify navigation, filters, favorites, media and score actions.
5. Let old requests/processes finish before taking the final Redis snapshot/import. Ensure the import cutoff is later than the last old Redis write: wait at least a full second after the last old request finishes. Then import **all three platforms** and verify as described below. Once every server uses this code, new web and iOS activity never adds records to Redis; there is no ongoing history migration to schedule.

## Data model and meanings

| Table | Contents |
| --- | --- |
| `activity_visits` | A random visit ID, optional account ID, random browser-session visitor ID, initial platform, start and last observed activity. A new page after 30 minutes without meaningful interaction starts a new visit; passive checkpoints do not keep it alive. Signing in/out or changing account/platform starts a separate visit. Shared visitor identity can span public and `my.*` hosts; separate host sessions remain separate. |
| `activity_visit_links` | Explicit association of only the current anonymous visit with the account confirmed by browser authentication; original recorded identities remain unchanged. |
| `activity_pages` | A unique page ID, visit, page URL/route, opening time, maximum observed elapsed time and foreground-visible time. Multiple tabs have separate page IDs. |
| `activity_events` | Unique event ID, visit/page, account/visitor, platform, type, source, occurrence/receipt time, page offset/sequence, URL/route, HTTP method/status, route-bound resource and restricted metadata. Imported records additionally retain their original key, field and payload. |
| `activity_imports` | Durable scan checkpoints, source connection/prefix/platforms and exclusive timestamp cutoff. |
| `activity_legacy_daily` | Snapshots of old daily counters. These are **not added to** detailed event counts: same-second overwrites can make the old counters larger than the surviving history. |

`server` events record completed requests and their HTTP status. Favorites have a specific `favorite_changed` event with the confirmed add/remove result. Folder changes, annotation count deltas, authentication and subscription milestones also have confirmed events; see the Users analytics vocabulary. Exceptions reaching logging middleware record their failure status, without inventing an opened page, exposing exception messages or submitting account fields. Requests rejected before that middleware (for example CSRF/authentication checks), infrastructure failures and non-web routes are outside its coverage.

Current iOS `app` events retain the existing middleware coverage and traffic exclusions. They record the API URL, route, method, request time and allowlisted search/filter/piece context **before** the action runs, as the old logger did. HTTP outcome, visits and playback duration remain unknown. Multiple requests in one second now survive as separate events. The legacy app submits `user_id` without authenticated identity, so metadata explicitly marks this association as unverified; it is never an authorization mechanism. Requests without a valid positive identifier or excluded personal/test identifiers are still omitted. A future app update is needed for richer mobile visit/media events and authenticated mobile identity. SQL logging failures are reported without changing the API response, with no fallback to Redis.

`browser` events record page readiness/checkpoints/visibility/exit/restoration, clicked links and controls, search submission, filter selections and applied state, sections/tabs/dialogs, and media play/pause/end/seek/error/progress. Playback includes the piece/context, playhead, duration, rate and estimated seconds actually playing (excluding observed buffering and seeks). This covers native piece videos, dynamically loaded tutorials and the detached audio players used by pieces, playlists, Explore and Find your match. Metadata-only audio probes are not tracked as playback.

Browser timing is an estimate anchored to the server page-opening time. Foreground visibility does not prove attention. A browser closing, connectivity loss, disabled scripts, session expiry or storage limits can prevent delivery; this is analytics, not a guaranteed audit trail. Events batch every three seconds, checkpoint every fifteen seconds, and attempt beacon delivery on hiding/leaving. Unacknowledged batches remain in tab session storage for retries across navigation; event IDs prevent duplicates. The bounded outbox retains up to 100 batches of 20 events, and page tokens expire after 24 hours. No session replay, keystroke capture, fingerprinting, raw session IDs, IP history, password fields, payment fields or arbitrary form-body capture is added.

The ingestion endpoint is a CSRF-protected web-session route for signed-in users and guests. Encrypted page tokens are checked against the current browser session, account identity and host platform. Submitted account, visit and page IDs never establish ownership. Account writes still require their existing authentication/authorization.

New request metadata uses an allowlist of search/filter context. Original imported payloads preserve whatever the old logger recorded and are visible only through administration; access/backups should be restricted accordingly. New activity has no automatic deletion policy yet. Agree retention and capacity based on observed volume before enabling pruning; the importer never deletes Redis data.

## Importing Redis history

For a large dataset, restore a Redis backup taken **after the complete server cutover** to an isolated Redis instance and configure a named connection in `config/database.php` through the normal deployment process. Use its original application key prefix. Importing a stable source permits meaningful verification and avoids competing with production traffic. Do not flush the production Redis instance: caches and other application features still use it.

With the existing configured connection:

```sh
php artisan activity:import-redis --verify
```

With a separately configured snapshot connection (replace its name/prefix with your actual configuration):

```sh
php artisan activity:import-redis --connection=activity_import --prefix= --verify
```

The default platforms are `web,webapp,app`; keep that default for the final complete migration. `--platforms=web,webapp` limits a run to website history. The default exclusive cutoff is the Unix timestamp at command start. Records at/after that timestamp are excluded from both import and verification; malformed timestamp fields are retained as undated historical records. All old activity keys stop growing after the full server cutover. Start the final import after its cutoff is later than the last old write. If an earlier run used an older cutoff or a snapshot from before cutover, one final catch-up against the complete source is needed.

`SCAN` and `HSCAN` iterate source keys/fields. `--batch=500` is a Redis COUNT hint, not a fixed response limit; SQL writes are split into at most 100 rows. `--sleep-ms=20` limits load. Example bounded run:

```sh
php artisan activity:import-redis --batch=250 --sleep-ms=50 --max-batches=1000
```

Run the same command again to resume its unfinished checkpoint and cutoff. Checkpoints and copied rows commit together. After a source Redis restart/restore, use `--restart`: scan cursors are not durable identities across source rebuilds. `--restart` resets only scan progress, retaining SQL records. The original key plus field is the unique import identity, so a restored snapshot and a subsequent live catch-up do not duplicate events even when their connection names differ. If the old source value changed at that identity, rescanning updates the preserved historical payload.

After a completed run, a later invocation without `--before` starts a new cutoff and safely catches up. To repeat or verify a particular historical cutoff, pass the timestamp printed by that run:

```sh
php artisan activity:import-redis --before=YOUR_UNIX_TIMESTAMP --restart --verify
```

`--verify` makes a separate scan and compares each eligible source field with its preserved SQL payload, decoding archived binary payloads when necessary. It exits unsuccessfully if any checked source record is missing or different. SCAN can revisit records, so the reported checks are not a unique record count. Verify against the same stable source; no scanner can promise a consistent snapshot while keys are being deleted or rewritten. Keep the backup and reconcile source hash lengths/SQL counts and sample dates before separately retiring the old **web, webapp and app history keys, daily counters and user-log indexes**. The command never deletes or writes any Redis key.

Deleted-account IDs, invalid JSON and invalid timestamp fields are retained rather than silently discarded. Binary payloads are base64 encoded with an encoding marker. Legacy records have no fabricated visit, playback duration, action success or high-resolution timestamp. Already overwritten/deleted events cannot be reconstructed. Daily counters retain their capture time and do not fabricate missing detailed events.

## Compatibility and verification

The existing `RecordAppLog`/`AppLog` entry points now write SQL through `LogFactory`; API route coverage and JSON field names/types remain intact. Account `logs_count` and `last_active_at` use SQL in web, API and command contexts, so existing mobile responses keep their fields with current database-derived values. Counts reflect stored events rather than the old whole-second Redis hash slots. App Logs load five SQL records at a time, including imported mobile records. Website middleware names stay available for route exclusions, and the general activity overview and daily activity chart use SQL for all platforms.

The obsolete Redis user-log index and daily-counter writer classes are removed, along with account lifecycle hooks that maintained them. The old `redis:rebuild-user-log-index` and `redis:refresh-daily-logs` command names remain as informational no-ops for deployment compatibility. `logs:record-to-database` now builds its old summary table from SQL through the same account helpers. Explicit old-source cleanup commands remain separate from live tracking; they are not needed for ongoing SQL activity.

Run the isolated PHP suite and JavaScript regressions described in `AGENTS.md`. Tests cover visitor/account ownership, CSRF, retried event identities, bounded inputs, foreground/playback timing, navigation retry persistence, unchanged mobile JSON responses with SQL writes, SQL activity attributes/admin reads, mobile logging failures/exclusions, account lifecycle hooks, retired commands, duplicate/empty Redis scans, checkpoint resume, malformed/orphan records and transactional rollback. Review tests disable Bugsnag delivery as well as mocking mail/HTTP/Redis. SQLite and mocked Redis checks do not establish concurrent MySQL behavior, production throughput or the duration of a real large import. The old non-isolated Redis-specific feature tests describe the retired implementation; use the Review regressions for the new storage behavior.
