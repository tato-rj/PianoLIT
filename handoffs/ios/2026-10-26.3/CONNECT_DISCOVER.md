# PianoLIT iOS integration contract for 2026-10-26.3

This is the reviewed **delivery supplement 1** for canonical revision
**2026-10-26.3**. `Canonical/2026-10-26.3/` preserves that original published
snapshot byte-for-byte. This document and the expanded fixtures are separately
listed and hashed in `delivery-manifest.json`; they do not silently replace the
canonical revision. The backend implementation was inspected at the commit and
file hashes recorded in `PROVENANCE.json`.

**Status: fixture-only; incomplete for live integration**. The manifest contains
no verified staging or production API URL. The OpenAPI `.invalid` server is a
placeholder. No URL in a fixture is a service or playable media location. Use
the delivered offline responses until a verified HTTPS base URL is supplied.
`GAPS.md` lists the exact unresolved contracts and verification work.

Only the updated UIKit app receives Swift development. Do not maintain an old
Swift branch or parallel shipping mode. The backend's legacy API must keep
working for already-installed binaries; this delivery changes no legacy routes,
controllers, authentication or media delivery.

## Transport, ownership and envelopes

All paths in the following v2 table are exact application paths. Once a verified
HTTPS origin is provided, append them to that origin. No origin is supplied by
this delivery. Never substitute a guessed production host.

| Method | Path | Authorization | Request fields | 200 response schema |
| --- | --- | --- | --- | --- |
| POST | `/api/v2/auth/login` | Email/password proof | `email`, `password` only | `LoginEnvelope` |
| GET | `/api/v2/auth/me` | Bearer | None | `AccountEnvelope` |
| POST | `/api/v2/auth/logout` | Bearer | None | `LogoutEnvelope` |
| GET | `/api/v2/discover` | Bearer | None | `DiscoverEnvelope` |
| POST | `/api/v2/pieces/{piece_id}/view` | Bearer | None | `PieceViewEnvelope` |

Send `Accept: application/json`. POST bodies, if present, use
`Content-Type: application/json`. Login requires a JSON object with its two
fields. Logout and history accept **no body or `{}`**, not `[]`. GET requests
have no body. No v2 operation accepts query parameters, `user_id`, `api`, filter
parameters, client timestamps, event IDs, or token fields. Valid authenticated
requests with unsupported fields receive `422`. Login rejects unsupported
fields without creating a credential. Browser cookies and legacy local account
JSON do not establish v2 identity.

Success is `{"data": <object>, "meta": {"request_id": <string>}}`.
Failure is `{"error":{"code":<string>,"message":<string>,"fields":<object>},
"meta":{"request_id":<string>}}`. `fields` maps field names to arrays of error
strings and is `{}` for non-validation errors. All application responses have
`Cache-Control: private, no-store` and a server-generated `X-Request-ID` matching
`meta.request_id`. Incoming request IDs are not trusted. IDs are opaque positive
decimal **strings**, not UUIDs, array positions or Swift floating-point numbers.
All v2 timestamps are UTC RFC3339 strings ending in `Z`.

The precise canonical schemas are in
`Canonical/2026-10-26.3/openapi.yaml`. This supplement documents implementation
behavior where that draft omits details; it does not add server fields.

## Password login and session lifecycle

`POST /api/v2/auth/login`, without a bearer header, accepts:

```json
{"email":"synthetic@example.test","password":"REDACTED_NOT_A_PASSWORD"}
```

This is a non-working request illustration. The email must be a string with
email syntax and at most 254 characters; the password must be a nonempty string
with at most 1024 characters. Email is trimmed and matched case-insensitively.
Password whitespace is preserved. An ambiguous normalized-email match is
rejected. This endpoint adds no email-verification or membership requirement.

Successful `data` has exactly these implemented fields:

| Field | Type | Nullable | Meaning |
| --- | --- | --- | --- |
| `token_type` | string, `Bearer` | No | Authorization scheme |
| `access_token` | opaque string | No | Returned once, never an account ID |
| `expires_at` | UTC RFC3339 string | No | Absolute server expiry |
| `account` | AccountSummary object | No | Verified account, defined below |

See `Fixtures/login-success.json` for a complete 200 envelope. Its token is an
explicit non-credential placeholder, not a token the server will accept.

Store the real token in iOS Keychain, scoped to the configured environment and
account. Do not store it in UserDefaults or logs. Do not retain the password.
Only send it as `Authorization: Bearer <opaque-token>` to the configured v2
origin and `/api/v2/` path. Reject credential redirects; never attach this
header globally, to artwork/media URLs, or to a legacy operation.

The default lifetime is **30 absolute days**. Reads do not extend it. There is
no refresh/renewal endpoint. Password change/reset, normalized email change,
account deletion, application-key rotation, server deletion of the session, or
expiry invalidates the token. Each password login creates an independent
session; another device's session survives a current-session logout. Server
storage contains token hashes and credential fingerprints, not retrievable raw
tokens. These are backend semantics, not fields for the app to decode.

`GET /api/v2/auth/me` returns `data.account`. On startup/resume, resolve the
stored token and bind caches to this account before accepting personal content.
AccountSummary requires non-null string `id`, `first_name`, `last_name`, and
`email` fields. Empty first/last-name strings are permitted by the contract.
It has no membership status, access flags, subscription or payment data.

`POST /api/v2/auth/logout` with the bearer and `{}` or no body returns
`{"data":{"logged_out":true},"meta":...}` and deletes only that session.
Repeating with the revoked token returns `401`, not a second 200. Clear local
credentials and personal caches on logout even when offline, and cancel late
requests. An uncompleted offline logout cannot guarantee server revocation;
any pending revocation secret must remain in secure storage only.

Users moving to the updated app need fresh password proof once. Old installs
that never update keep their old API flow. A saved legacy user ID is not a
token exchange credential. V2 has no registration/recovery/social-login
operations: existing onboarding flows must complete separately, followed by
v2 password login. Their native callback integration remains unverified.

## Error responses and limits

| Status | Implemented error code | Relevant trigger |
| --- | --- | --- |
| 400 | `invalid_json` | Malformed JSON request |
| 400 | `https_required` | Insecure request outside local/testing; no credential redirect |
| 401 | `invalid_credentials` | Wrong password, unknown or ambiguous login email |
| 401 | `unauthenticated` | Missing/malformed/expired/revoked bearer on a protected route |
| 404 | `not_found` | Unknown route or missing piece |
| 405 | `method_not_allowed` | Wrong HTTP method; inspect `Allow` |
| 413 | `request_too_large` | Application request-size rejection |
| 415 | `unsupported_media_type` | Non-JSON body where parsed |
| 422 | `invalid_request` | Invalid fields, non-object JSON or extra parameters |
| 429 | `rate_limited` | Independent v2 request limit; honor `Retry-After` |
| 500 | `server_error` | Unexpected redacted application error |
| 503 | `temporarily_unavailable` | Temporary application/shared-feed lock failure; `Retry-After` |

401 responses include `WWW-Authenticate: Bearer`. Protected operations resolve
authentication before their body/parameter validation; rate limiting may occur
first. Login allows five attempts per normalized email/IP and twenty per IP per
60-second window. Protected v2 requests have a 120-per-IP/60-second limit.
Defaults are server configuration, not request fields. Gateway behavior is not
verified; the table describes Laravel responses, not arbitrary proxy HTML.

On a protected 401, clear the v2 session/personal data and return to password
login; never fall back to an old user ID. On login 401, show the credential
error. Handle 422 as a request problem and 429 with the supplied delay. Retry
reads with bounds; do not blindly retry writes. Retain only a same-account
successful Discover screen after a refresh failure. Keep request IDs for
diagnostics, never token-bearing login bodies.

## Discover sections and cards

`GET /api/v2/discover` requires the bearer and accepts **no query parameters**.
It returns `data.sections`, an ordered array, with no pagination cursor. It
does not record a piece opening. Preserve the returned order of sections,
items, level systems and level cards. The client must not independently
select, rotate, replace, filter into a new recommendation set, or reshuffle
server-selected content. Native layout/styles belong in UIKit. Safely skip
unsupported presentation kinds instead of inventing their meaning.

| Section ID | `kind` | Content container | Implemented bound |
| --- | --- | --- | --- |
| `free_pick` | `featured_piece` | `items`: PieceSummary[] | At most 1 |
| `composers` | `composer_rail` | `items`: ComposerSummary[] | At most 12 |
| `recently_viewed` | `compact_piece_rail` | `items`: PieceSummary[] | At most 12, newest distinct first |
| `latest_pieces` | `artwork_piece_rail` | `items`: PieceSummary[] | At most 12 |
| `for_you` | `compact_piece_rail` | `items`: PieceSummary[] | At most 6 |
| `composer_feature` | `composer_feature` | `items`: PieceSummary[] | At most 12 |
| `levels` | `level_systems` | `systems`: LevelSystem[] | Three systems; available cards per system |
| `past_highlights` | `featured_piece_list` | `items`: PieceSummary[] | At most 7 |
| `mood` | `compact_piece_rail` | `items`: PieceSummary[] | At most 12 |
| `similar_to_free_pick` | `compact_piece_rail` | `items`: PieceSummary[] | At most 12 |

Normal shared order is the order above. Recent history and recommendations
are omitted when unavailable/empty or disabled by the shared personalization
content switch. Excluded accounts have no recent-history row. The composer
feature is omitted if neither group has pieces. All-empty grading systems omit
the levels section. Some other sections, including Free Pick and similarity,
can remain with empty `items`; do not fill them with client-selected content.
The same piece can legitimately appear in multiple sections.

For `featured_piece_list`, present the first item as the featured piece and
the remaining items as its list, keeping their returned order. This changes
presentation only; it must not select a replacement item.

Every section requires non-null string `id`, `title`, and `kind`. Standard
sections require `items`, including `[]`. Optional `subtitle` and `query` are
strings when supplied; absent is different from null. `query` is a server
display/search hint, not a URL or an authorization identity. No generic
section-level `destination` or View all endpoint is advertised in this revision.

`composer_feature` additionally requires `group` (`women` or `black`),
`composer` (ComposerSummary), and plain-text `biography_excerpt` (string).
The featured composer is the first selected piece's composer; the group list
may include other composers, so preserve each card's own composer. The server
chooses the nonempty group once per shared cache generation. Do not choose
another group on the phone. Past highlights is separate and excludes the
current Free Pick and future highlight dates.

`levels` requires string `subtitle` and `systems` rather than `items`.
Each LevelSystem has non-null string `id` (`rcm`, `abrsm`, `suzuki`), string
`label`, and `items` array. The server orders the systems and cards; activate
the first nonempty system and disable empty ones without reordering. Each
level card requires non-null string `number`, string `label`, integer
`piece_count`, and `destination: {"kind":"search","query":<string>}`.
`number` is text, not an instruction to parse or recalculate a grade.

### PieceSummary

All fields below are required, even those whose value can be null.

| Field | Type | Nullable | Meaning |
| --- | --- | --- | --- |
| `id` | opaque decimal string | No | Piece identity |
| `title` | string | No | Server display title |
| `composer` | ComposerSummary | No | This piece's composer |
| `attribution` | string | Yes | Attribution prefix, otherwise null |
| `artwork_url` | absolute URI string | No | Cover/shared fallback artwork |
| `difficulty` | object | No | Required nullable `name` and `label` strings |
| `is_new` | boolean | No | Server's existing five-day-cutoff result |
| `is_free_pick` | boolean | No | Current scheduled Free Pick identity only |
| `available_media` | object | No | Four required booleans: `video`, `audio`, `score`, `synthesia` |
| `destination` | object | No | `kind: "piece"`, `id: <piece-ID string>` |

`difficulty.name` and `difficulty.label` can both be null. Show a deliberate
missing-difficulty state; do not infer a level. `video` reflects non-Synthesia
tutorial availability; `synthesia` reflects Synthesia tutorial availability.
`audio` checks the stored audio path and `score` checks the stored public score
path. These booleans do not validate a file's existence or grant playback.
The summary includes **no** score, audio, performance or Synthesia URL, full
detail model, favorite state, media permissions, preview duration or membership.

### ComposerSummary

Every summary requires non-null string `id`, `name`, `short_name`,
`portrait_url` (absolute URI), integer `piece_count`, and
`destination: {"kind":"composer_repertoire","id":<composer-ID string>}`.
Counts describe the existing complete composer piece count. The portrait has
the existing image fallback. No full biography/country/detail contract is
implied by this summary; the feature's excerpt is supplied separately.

### Membership, access and media behavior

There is **no v2 membership/entitlement/locked-content field or operation in
.3**. A signed-in account can read Discover without proving membership.
Neither login AccountSummary, array position, `is_free_pick`, nor
`available_media` proves permission to open/play/read/export anything.
Do not invent `locked`, `can_play`, `preview_seconds`, or membership JSON fields.
Native media entitlements remain an unresolved contract; do not automatically
copy the web Access Limits matrix into iOS. The common personalization switch
only governs shared Discover content rows.

Artwork/fallback choices are shared with web. Deployed URI hosts/HTTPS/media
behavior have not been verified here. Existing original media delivery remains
legacy/public and unchanged; no private-file protection is introduced. In
fixture mode intercept every image/media URL locally and perform no network
requests. `Fixtures/Assets/artwork.png` is an offline placeholder. The `.invalid`
URLs are deliberate non-services, not a URL migration or a verified host.

## History after a confirmed native opening

`POST /api/v2/pieces/{piece_id}/view` requires the v2 bearer, with no query or
request fields and `{}` or no body. The 200 envelope has
`data: {"accepted": true}`. It returns no event ID, timestamp or exclusion flag.
The account comes only from the bearer. Missing pieces return 404. The
implementation accepts positive decimal route IDs up to 20 digits, then
requires the piece to exist; malformed path strings are 404 routes.

Call once after detail has loaded and the native view actually opens with
resolved access. Never call for an impression, card tap before success,
prefetch, cancellation, failed load, or denied open. **The server does not
verify that UIKit rendered a view or check a native media entitlement**: it
checks the bearer and piece existence, then applies account history/exclusions.
Client sequencing is necessary; the API is not proof of a successful opening.

The database has one row per account/piece. Repeating the POST does not add a
duplicate row but updates its server recency. This is not a strictly idempotent
event log: a delayed retry can move that piece to the top again. There is no
idempotency key, submitted timestamp or event ID parameter. For an ambiguous
network failure, a retry must represent the same confirmed open and may advance
recency; do not blindly replay an old queue as fresh browsing. The next
Discover request returns the latest twelve distinct pieces. Excluded accounts
can receive `accepted: true` while no history row is stored.

`Fixtures/history-success.json`, `history-retry-success.json` and
`discover-after-open.json` were captured from the real isolated endpoints.
`Fixtures/http-index.json` identifies every expected request, status, schema
and response file, including 400/401/404/415/422/429 history failures.
`Fixtures/canonical-fixture-index.json` separately identifies requests/statuses
for the nine unchanged original snapshots; those snapshots are not one coherent
account sequence and should not replace the supplemental navigation cases.
`Fixtures/navigation-scenarios.json` models confirmed, failed, denied and
Free Pick openings without a production call. Client scenario state is
explicitly separate from API JSON.

## Navigation and explicitly retained legacy operations

A destination is an intent, not a response-provided endpoint to execute. No
v2 piece-detail, composer-repertoire, search or native access endpoint exists
in `.3`. The updated app needs a tested adapter before these taps are enabled.
It may reuse current UIKit views; no old Swift branch is maintained.

The following operations exist in backend source. This list does not approve
their live host, a complete native adapter, or new v2 authorization semantics.
In fixture mode use their captured local responses only. If retained in the
updated app, use a separate client with **no v2 bearer header** and derive any
legacy account parameter from the active verified account; do not treat that
submitted parameter as server-verified authorization.

| Native intent | Existing legacy request | Response and constraint |
| --- | --- | --- |
| Piece ID | `POST /api/pieces/find` JSON `{search: <piece-ID>, user_id: <account-ID>}`; GET alias also exists | Full legacy piece **array** (one item); not a v2 envelope. `Fixtures/legacy-piece-detail.json` is a real isolated example for piece 4 |
| Composer repertoire | `GET /api/search?search=<composer-name>&model=App%5CComposer&user_id=<account-ID>` | Legacy piece collection; lookup uses a name, not the v2 composer ID. Duplicate-name/ID mapping remains unverified |
| Level results | `GET /api/search?search=<destination.query>&model=App%5CTag&user_id=<account-ID>` | Legacy piece collection. URL-encode values; use the server query string without reconstructing it |
| Mood/general results | `GET /api/search?search=<query>&user_id=<account-ID>` | Legacy search may use Scout/Algolia; staging/native matching remains unverified. No guessed filter/ID mapping |
| Existing access-status bridge | `POST /api/memberships/status` JSON `{user_id: <account-ID>}` | Legacy JSON scalar, not AccountSummary. Synthetic examples are `"active"` for a super-user override and `"expired"` without membership. Real provider-backed status can invoke Apple verification; not exercised by this delivery |

The active-status fixture represents a synthetic override, **not a paid
membership or a v2 entitlement**. It must not teach the client to infer payment
from the new account DTO. The no-membership fixture only demonstrates the
legacy scalar response. Other membership/provider cases remain unverified.

`GET /api/composers` (legacy directory) and
`GET /api/pieces/freepicks` (legacy paginated highlights) also exist, but their
new-app navigation/pagination adapters are not completed here. The legacy
empty-string search returns null rather than a verified complete Latest
catalogue; do not invent a View all Latest endpoint. No v2 View all destinations
are emitted. The selected feature exposes its composer/card destinations;
its group query is not a new server route. Prefer an unavailable action over
a guessed request until each missing destination is specified and tested.

Do not use `POST /api/pieces/views` for recently viewed history; it is the
separate legacy analytics counter. Do not forward bearer credentials to any
legacy URL, response-supplied `source`, artwork, PDF, audio or video host.

## Completion gates

This artifact completes file delivery and fixture coverage for the implemented
shapes, not the absent runtime contracts. Still missing are a verified HTTPS
API URL/deployment, native entitlement/access decisions, full v2 detail and
result-list contracts or approved temporary adapters, provider status coverage,
and real UIKit phone/iPad/build verification. `.3` stays draft. Preserve the
canonical snapshot; any revised canonical contract must use a new revision.
