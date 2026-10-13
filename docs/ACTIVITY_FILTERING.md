# Web activity filtering and short-visit cleanup

## Collection rule

The default minimum is **five seconds of observed foreground browser time**, accumulated across pages of the same visit. Observed media playback of at least five seconds also qualifies a visit, including background audio. The server additionally requires five seconds of real elapsed time. This is an engagement cutoff, **not proof of a human**: some real short visits are deliberately omitted and sophisticated automation can imitate browser activity.

Known crawler, preview and automation user agents are excluded before analytics collection, including declared Google/Bing crawlers, common SEO/AI crawlers, headless clients and command-line fetchers. HEAD requests are excluded. The page/API action still works normally; this does not block indexing, change robots.txt or authorize any account action. Detection uses bounded local string checks, without DNS lookups, IP storage or a new dependency. Patterns are heuristic and can be extended in App\Activity\TrafficFilter.

References for crawler identification and its limitations: [Google crawler documentation](https://developers.google.com/crawling/docs/crawlers-fetchers/google-common-crawlers), [Googlebot and spoofed user agents](https://developers.google.com/search/docs/crawling-indexing/googlebot), [Bing verification](https://www.bing.com/webmasters/help/how-to-verify-bingbot-3905dc26).

## Provisional visits do not enter analytics SQL

New browsing visits/pages are provisional until the threshold is met. Original URLs/routes, piece identity and opening timestamps travel in an encrypted, session/account/platform-owned page context. Successful HTML responses receive the completed token after rendering; failed responses cannot mint a valid opening. Context tokens stay within the existing 4,096-character limit; unusually long URL/query details may be truncated. HTML containing these tokens is private/no-store.

Unqualified events stay in the **existing browser outbox**, not a server activity buffer, Redis log or SQL staging table. The server session retains only the current visit identity and clock. Up to 16 page-token/event proofs can establish cumulative foreground time; client-supplied visit/user/platform IDs never establish ownership. The existing outbox limit is 100 batches of 20 events. It discards abandoned unqualified visits when the visit changes, so they cannot block later activity.

An initial one-second browser timer checks the accumulated visible time in memory and produces **one** qualifying checkpoint. It does not send per-second requests/events. Regular playback/visibility summaries remain at 15 seconds. A 202 response means the visit is still provisional; the browser keeps unchanged event IDs and retries. Once qualified, original page opens and buffered actions are saved transactionally and later page navigation uses the established visit. Failed promotion rolls back completely. Duplicate events cannot extend accepted timing. Replayed IDs cannot materialize another zero-duration visit.

Declared bots are ignored by ingestion as well as page middleware. Existing CSRF/session/page ownership checks, 100 KB request/8 KB metadata limits, batches of 50 root events and 120 requests/minute remain. Browser collection is additionally capped at 2,000 stored events per page. Confirmed server actions are not subject to that browser-event budget.

Successful favorites, folder/annotation changes, authentication and billing outcomes remain account events even before a browsing visit qualifies. They are stored without invented page/visit associations. Fast registration flows can therefore have a confirmed account outcome without an eligible browser journey; Insights describe available tracked steps rather than complete population conversion rates.

Qualified server opening events carry an internal engagement_qualified marker that browser metadata cannot assert. Old server-only visits cannot grandfather subsequent navigation past the new gate; the next page starts a provisional visit. Existing UUID-only encrypted page tokens remain supported for in-flight clients until their normal expiry. Existing iOS requests, identity caveats, JSON contracts and media access are unchanged; this policy applies to web/webapp browsing. Ongoing SQL logging does not dual-write to Redis. No new migration is required beyond the previously required activity-history/Users-analytics migrations.

## Clean up existing short visits

The Artisan command defaults to a **read-only preview**:

    php artisan activity:prune-noise

Apply the reviewed selection:

    php artisan activity:prune-noise --execute

Default selection: web/webapp visits with an observed span **strictly below five seconds**, whose start, last activity and all opened pages are older than **60 minutes**. The grace period protects requests still loading or delivering their first events. Visits with at least five seconds of recorded page/browser evidence are retained even when an older summary timestamp is inconsistent. UTC companion fields are preferred; original local timestamps remain supported.

Each transaction rechecks and locks at most 200 selected visits, deletes their ordinary events/pages/identity links/visit rows, and preserves confirmed account outcomes as ungrouped events. Imported or app events attached to a selected web visit are preserved rather than discarded. Account, favorite/folder/annotation, billing, import-checkpoint and legacy-daily tables are not deleted. Account-associated anonymous identity links are removed with the rejected visit. No Redis history is read, deleted or flushed.

The default run limit is 10,000 visits. Repeat to continue, or choose bounded options:

    php artisan activity:prune-noise --execute --older-than=60 --batch=200 --limit=10000

The minimum grace period is 30 minutes; batch bounds are 1–1,000 and run-limit bounds are 1–1,000,000. Errors roll back the current batch; earlier completed batches remain complete and rerunning resumes safely.

Laravel's existing scheduler runs this cleanup hourly with overlapping runs prevented. This requires the normal scheduler to be configured on the deployment host. No Codex automation, queue worker, general history-age retention rule or production cleanup was run while implementing/testing this change.

## Configuration and release

ACTIVITY_FILTER_BOTS defaults to true. ACTIVITY_MINIMUM_VISIT_SECONDS defaults to 5 (0 disables visit qualification for an explicit legacy deployment; supported values are 0–60). The browser-event budget is in config/activity.php. ACTIVITY_ENABLED still disables collection entirely.

Deploy PHP, Blade, the standalone public/js/components/activity.js and its manifest hash together. Run the preview against the intended database, review the counts, then execute cleanup. The command never loads normal credentials during isolated tests; implementation verification uses in-memory SQLite, mocked integrations and JS fixtures. MySQL query plans, production-scale concurrency and the existing host's scheduler configuration still need deployment verification.
