# Find my match native integration

Revision **2026-11-03.1**, implemented and tested locally. Target:
`https://pianolit.com/api/v2`. **These new endpoints are not deployed or verified
in production.** The existing login/Discover service at that base does not imply
the match endpoints are live. Native/staging acceptance remains pending.

Keep the completed design for steps 1–4, UIKit, storyboards, equal-size level
choices and the existing iOS deployment target. Replace bundled examples and
local calculations with the returned data. No native source was available in
this workspace, so this deliverable supplies the backend and integration contract.

## Requests and navigation

Sign in through the existing v2 login and send the Keychain bearer only to the
fixed API origin. All four JSON operations use the usual `data`/`meta` or
`error`/`meta` envelopes and `Cache-Control: no-store, private`. No browser
cookies, CSRF token, user ID or subscription claim is required or accepted as
identity. Send `Accept: application/json`, and `Content-Type: application/json`
for writes. IDs are strings; `revision` is a JSON integer.

Start with a UUID generated once for this intended start:

```http
POST /api/v2/match-tours
Authorization: Bearer <Keychain credential>
Content-Type: application/json

{"request_id":"11111111-1111-4111-8111-111111111111"}
```

HTTP 200 returns a complete snapshot. This abbreviated response illustrates the
state fields; the executable fixture includes all choices and response fields:

```json
{
  "data": {
    "id": "33333333-3333-4333-8333-333333333333",
    "expires_at": "2026-11-03T19:00:00Z",
    "revision": 0,
    "status": "in_progress",
    "progress": {
      "current_step": 1,
      "round": null,
      "current_question": "level",
      "initial_library_total": {"value": 1200, "accuracy": "exact"},
      "remaining_pieces": {"value": 1200, "accuracy": "exact"}
    },
    "answers": {},
    "questions": {"level": {"choices": []}},
    "result": null
  },
  "meta": {"request_id": "44444444-4444-4444-8444-444444444444"}
}
```

Use `questions.level.choices` for the four ordered choices. Each includes `value`,
`label`, `description`, and `piece` containing `id`, `title`, composer
`id/name/short_name/portrait_url`, `artwork_url`, and `audio_preview` with
`audio_url`, `duration_seconds`, `expires_at`. Composer portraits and piece
artwork are distinct fields. Listening and taste choices use the same piece
shape; submit their `id`.

Submit the selected level:

```http
PUT /api/v2/match-tours/33333333-3333-4333-8333-333333333333/answers/level
Authorization: Bearer <Keychain credential>
Content-Type: application/json

{"request_id":"22222222-2222-4222-8222-222222222222","revision":0,"value":"beginner"}
```

The response has `revision: 1`, `answers.level: "beginner"`, current step 2,
question `listening`, and an explicitly estimated remaining count. Keep the
returned snapshot and revision. Continue in this order:

| Slot | Submitted `value` | Returned content |
| --- | --- | --- |
| `level` | `elementary`, `beginner`, `intermediate`, `advanced` | Four level examples and descriptions |
| `listening` | One of the four served string piece IDs | Title, composer, artwork, audio |
| `reading-1` | `easy` or `difficult` | Intermediate score excerpt and zoom sample |
| `reading-2` | `easy` or `difficult` | Advanced after easy; beginner after difficult, with elementary fallback |
| `taste-1` | One of that pair's string IDs, or JSON `null` | First two-piece comparison |
| `taste-2` | One of that pair's string IDs, or JSON `null` | Second two-piece comparison |
| `mood` | A served mood `value` | Website mood labels and icon hints |

`questions` includes every fixed set for back navigation. `reading-2` is `null`
until the first reading answer is saved. Native labels map `easy` to “Easy for
me” and `difficult` to “Difficult for me”. A taste skip must contain
`"value": null`; omission, empty string, a reading skip, numeric IDs or boolean
reading answers are rejected. Do not send local difficulty/count calculations.

Opening an earlier screen does not change answers. Submit a changed earlier
answer with a **new** request UUID and the **latest** revision. The server
invalidates every later answer and the result, then returns the first unanswered
question. The fixed draw remains stable; the second reading sample adapts to the
new first answer. Re-saving an unchanged value preserves later answers/revision.

Serialize intended writes in the app. Keep each write's request UUID, slot,
revision and value unchanged for network retries. An identical retry returns
the latest snapshot and never reapplies an older answer, even if later edits
have happened. Reusing that UUID with a different body/slot returns 409
`idempotency_conflict`. Concurrent edits with an outdated revision return 409
`stale_tour`; reload with `GET /match-tours/{id}` and reconcile before a new edit.
Ignore responses older than the current local revision and cancel work when the
account changes. Maximum 256 accepted update UUIDs per tour; restart on `tour_limit`.

Tours expire **two hours after creation**, with no sliding extension. Reuse the
original start UUID to recover the same tour; use a new UUID to deliberately
restart. Other tours remain independent. Known expired tours return **410**
`tour_expired`; retain the bearer and restart. State and retry receipts are
deleted one day after expiration by the hourly prune command, after which the
tour returns 404. Do not reuse start UUIDs for new tours.

## Mood and result

Use the returned mood choices: `calm`, `energetic`, `romantic`, `joyful`,
`dramatic`, `mysterious`, `playful`, `reflective`, `open`. Preserve their website
labels. `open` means “Open to anything”; it is an explicit mood answer.

After mood is accepted, `status` is `ready`, `current_step`, `round` and
`current_question` are null, and `result` remains null. Request:

```http
GET /api/v2/match-tours/33333333-3333-4333-8333-333333333333/result
Authorization: Bearer <Keychain credential>
```

HTTP 200 pins one result, increments revision once, returns `status: "matched"`,
`remaining_pieces: {"value": 1, "accuracy": "exact"}`, and
`result: {"piece": <existing PieceSummary>, "explanation": "..."}`. The piece
contains the existing string ID, composer summary, artwork, difficulty, media
availability and `target: {"kind":"piece","id":"...","query":null,"web_url":"..."}`.
Open the **existing native piece screen** with `result.piece.target.id` using
its current detail adapter. A summary is not the legacy complete detail model.

Repeated result calls return the pinned piece; they do not reroll. If the piece
is withdrawn or no eligible piece exists, return 503 `no_match`; never display a
successful final count/result. A new answer clears the old match. There are no
extra recommended pieces in this response.

The shared web `MatchTour` draw/keywords and `MatchQuiz` ranking, partial-match
fallback and video/score eligibility select the result. Initial library total
is the exact count at start, including pieces outside that eligible pool.
Intermediate remaining counts mirror the website's **display estimates**;
they are not exact database candidate counts or a guarantee of a final result.

None of these calls writes piece-open history. Only after the native piece page
actually opens, call the existing `POST /pieces/{id}/view`. Media flags, tour
previews and a recommendation grant no full playback, PDF or membership access.
Existing detail/media access checks remain in force.

## Native media

Load public HTTPS portrait/artwork URLs without bearer headers. These static
image URLs have **no defined expiry**; they may change/disappear with editorial
updates. Use a neutral placeholder on image failure. Do not forward the API
credential to artwork, third-party hosts, web fallbacks or sample URLs.

Audio is a server-produced MP3, starting 40% into the original recording and
bounded to the configured website tour duration (default **60 seconds**, up to
150ms encoder padding). Short recordings may yield a shorter sample; use its
actual `duration_seconds`. The URL delivers only those sample bytes, including
for Range requests, and never redirects to the original recording. The native
player needs no local excerpt calculation or full recording URL.

Sight-reading uses the website's middle strip of page one: crop height 40% of
page width, centered vertically. `excerpt_url` provides a 900px-wide PNG;
`sample_url` provides an 1800px-wide PNG of the **same excerpt**, suitable for
pinch zoom. Full pages/PDFs are not provided. Show retry on a failed score and
wait for the sample to load before offering an answer.

Signed sample URLs expire at the earlier of **15 minutes after issuance** or
tour expiry; their `expires_at` is explicit. Use them verbatim without cookies
or Authorization. Missing/tampered/expired signatures return **403**
`media_url_expired`; reload the authenticated tour to get fresh URLs. A deleted
prepared sample returns 503 `media_unavailable`. These errors never require
account sign-out. Cancel playback/downloads when leaving, restarting or changing
accounts. Signed samples remain valid until their short expiry; they are limited
public capabilities and are not full-media credentials.

## Failures

All errors have `error.code`, readable `message`, object `fields` and
`meta.request_id`. Use codes/statuses for behavior. Never display internal errors.

| HTTP | Code | Native behavior |
| --- | --- | --- |
| 401 | `unauthenticated` | Invalid account bearer; existing sign-in recovery |
| 403 | `media_url_expired` | Reload tour/sample URLs, keep account signed in |
| 404 | `not_found` | Tour missing, owned by another account, or pruned; offer restart |
| 409 | `stale_tour` | Load latest snapshot before editing |
| 409 | `idempotency_conflict` | Fix request UUID/body reuse; no automatic new mutation |
| 409 | `incomplete_tour` | Follow returned current question after load |
| 409 | `tour_limit` | Start a new tour |
| 410 | `tour_expired` | Restart with new UUID, keep bearer |
| 422 | `invalid_request`, `invalid_answer` | Correct payload/served choice; do not advance |
| 429 | `rate_limited` | Honor `Retry-After`; preserve retry UUID/body |
| 500 | `server_error` | Retry safely; keep the current screen |
| 503 | `tour_unavailable` | Disabled or incomplete catalogue; retry later |
| 503 | `media_unavailable` | Sample unavailable; retry, never substitute full media |
| 503 | `no_match` | No valid final piece; retry/change answer/restart |

The overall limit is 120 requests/minute/IP, including sample requests. Start
additionally allows 10/minute/IP. A tour failure never uses 401. The fixture
`fixtures/find-my-match-errors.json` contains synthetic failure envelopes.

## Deploy and verify

Deploy committed code through Git; do not copy tracked files to the server.
This is a separate release from the existing v2 login/Discover deployment.

1. Keep `API_V2_MATCH_TOUR_ENABLED=false`. Apply only
   `2026_11_03_120000_create_api_v2_match_tours.php` after the existing token and
   required catalogue/history migrations. Keep unrelated pending migrations
   outside this release. User deletion cascades tour state.
2. Install server `ffmpeg`/`ffprobe` with libmp3lame, Poppler `pdftoppm`, and PHP
   GD. Configure `MATCH_TOUR_FFMPEG`, `MATCH_TOUR_FFPROBE`,
   `MATCH_TOUR_PDFTOPPM` if executable paths differ. Originals must exist on the
   existing local public disk. No third-party downloads or queue are used.
3. Run `php artisan api-v2:prepare-match-media` as the application/deploy user.
   This reads the shared tour's entire eligible audio pool and selected score
   sources, writes only private derivatives under `storage/app/api-v2-match-media`,
   and exits nonzero on any failure. It never changes original media. Keep this
   directory outside public aliases, share it between app nodes, and keep the
   same app signing key on those nodes. Preparation is repeat-safe; rerun after
   eligible audio/score files or score-selection curation changes. New/unprepared
   draws fail closed with 503 rather than silently changing selection rules.
4. Verify the configured main `APP_URL`/TLS and private sample readability from
   PHP. Rebuild route/config caches using the normal deployment process, enable
   `API_V2_MATCH_TOUR_ENABLED=true`, then rebuild configuration. No iOS UI change
   or legacy media redirect accompanies activation.
5. Run a synthetic account through start → all seven answers (five steps) →
   result; compare shared web eligibility and final summary. Verify back edits,
   duplicate/concurrent retries, score easy/difficult branches, explicit skip,
   no-match/expired/error states, signed HTTPS audio/image/zoom/range delivery,
   absent cookies, no full source URLs and unchanged piece-open history. Verify
   existing v2 login/Discover/logout and legacy login/feed/media independently.
   SQLite tests do not prove MySQL locking; exercise simultaneous requests in
   this deployment check. Publish no real account data or bearer/capability URLs.
6. Schedule `php artisan api-v2:prune-match-tours` hourly. It is registered in
   Laravel's scheduler; if the full production scheduler is still unverified,
   install an equivalent private hourly cron with overlap protection. It removes
   expired state/receipts after one day. Retain derivative generations at least
   26 hours after their last tour reference; remove only unreferenced generations
   during operational cleanup, preserving current samples and originals.
7. Record the deployed Git revision, dated synthetic checks, verified HTTPS
   URL and iOS commit/build/device checks in the handoff before promoting
   readiness. Neither a local test nor a published static spec proves deployment.

Rollback: disable only the native tour flag and rebuild config. Keep the new
table/derivatives for outstanding short-lived tours and operational review;
existing browser, v2 and legacy routes keep their behavior. There is no email,
billing, Printful, subscription, native entitlement or original-media change.

Automated fixtures are generated from the isolated API test, then canonicalized
to the production target origin with explicitly fictional signatures. They are
contract examples, not playable URLs or credentials. The complete flow fixture
covers all five steps, both taste rounds, an explicit skip and the final summary.

Backend regression command:

```sh
php -d error_reporting=8191 vendor/bin/phpunit -c phpunit-review.xml \
  --filter 'ApiV2MatchTourTest|ApiV2DiscoverTest|MatchTourTest'
```

Use the temporary handoff Python environment described in the main README,
install `openapi-spec-validator==0.7.2` there, then run:

```sh
python -m openapi_spec_validator docs/api/v2/openapi.yaml
python -B -m unittest discover -s tests/scripts -p 'test_match_tour_contract.py'
python -B -m unittest discover -s tests/scripts -p 'test_api_handoff.py'
```
