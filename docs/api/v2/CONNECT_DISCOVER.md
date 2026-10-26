# Connect iOS Discover to the shared feed

Handoff revision **2026-10-26.3** documents the backend operations implemented locally.
It has **not been deployed or verified on staging**. Read `manifest.yaml` for
readiness and the verified base URL. Do not treat the `.invalid` OpenAPI server
or an example token as a callable service. The existing sync script fetches
this file, the YAML contract and fixtures through the manifest; it does not
rewrite Swift code.

All Swift development targets the updated app. The old Swift app/source is no
longer maintained. The backend still supports its already-installed binaries
through the unchanged legacy API, authentication behavior and media URLs. Do
not maintain an old Swift branch, parser or shipping mode for this purpose.
Any temporary legacy endpoint adapter below belongs to the updated app only.

## Instructions for the iOS developer

Implement the following in the current UIKit/storyboard project. Preserve its
deployment target, email/password onboarding, App Store billing and existing
piece/media views. Use the packaged synthetic fixtures while the manifest is
draft. Activate real requests only after the backend team supplies a verified
HTTPS base URL and publishes readiness. Compare `ios-baseline.yaml` with the
current checkout and record the native commit/build in your handoff receipt.

1. Create a dedicated v2 client using the deployment's base URL ending in
   `/api/v2`. Keep it separate from the legacy Alamofire/parser helpers. Send
   `Accept: application/json`; POST bodies use `Content-Type: application/json`.
   GET requests have no body or query parameters. Reject redirects on v2
   credential requests and constrain bearer headers to the configured HTTPS
   host and `/api/v2/` path. Never install the bearer header globally or forward
   it to response-supplied media, artwork or legacy `source` URLs.
2. On password sign-in call `POST /auth/login` with only `email` and `password`.
   A successful response contains `data.access_token`, `data.token_type`,
   `data.expires_at` and `data.account`. Store the opaque token in Keychain,
   associated with that account and environment. Do not parse its prefix, log
   it, put it in UserDefaults, or retain the password. Validate HTTP status and
   the JSON envelope before accepting login success.
3. Users moving to the updated app must sign in with their password once;
   users staying on an old build keep its existing API/authentication flow.
   A saved legacy `userID`/`isLoggedIn` value cannot be exchanged for a v2 token. Keep
   current registration and password-recovery flows; after successful
   registration, obtain the v2 token through the same password login. After
   password recovery, require login with the new password. Do not infer v2
   authentication from the flat legacy registration response.
4. On launch/resume, validate a stored token with `GET /auth/me`, sending
   `Authorization: Bearer <token>`. Bind the active account to the returned
   `data.account.id`. Fetch `GET /discover` using that same token. Decode
   `data.sections` in returned order. Use section `id` for identity and `kind`
   for presentation; remove row-0 Free Pick and row-1 composer assumptions.
   Do not select a composer group, reshuffle pieces, truncate rails or recreate
   recommendation rules in Swift. The server already returns the shared
   women/Black feature, at most six For you items, shared artwork and history.
5. Render `featured_piece`, `composer_rail`, `artwork_piece_rail`,
   `compact_piece_rail`, `composer_feature`, `featured_piece_list` and
   `level_systems` using native views. `items` are arrays. Levels uses ordered
   `systems`; select the first nonempty system and disable empty systems.
   Skip unsupported kinds safely. Handle empty arrays deliberately, with no
   fabricated recommendations. Styling and layout remain in the iOS project.
6. Resolve `destination.kind` through an explicit native adapter. A Discover
   piece summary is not a complete PieceVC model. Keep the existing detail,
   composer-repertoire and search destinations through a clearly marked
   legacy bridge until their separate v2 contracts exist. Whitelist those
   legacy operations and bind their account IDs to the verified active
   account. Never send the v2 bearer to them or describe their submitted IDs
   as v2 authentication. This release adds no v2 detail, search, favorites,
   billing or media-entitlement endpoints. Do not enable a View all action
   until its native destination/filter has been verified. Remove positional
   access bypasses; `is_free_pick` identifies a piece and is not an access grant.
7. After the native piece detail actually opens successfully, call
   `POST /pieces/{piece_id}/view` with the bearer and an empty JSON object `{}`
   or no body. The response is `data.accepted: true`. Do not call it for feed
   fetches, image prefetch, selection before loading, or failed/cancelled opens.
   The server owns account identity, deduplication and tracking exclusions.
   Reloading Discover then shows the same Recently viewed row as the web.
8. Use pull-to-refresh to refetch `/discover`; it reads the current shared feed
   and does not force a public reshuffle. Admin refresh, expiry and scheduled
   Free Pick changes update the common content source. Keep the previous
   successful same-account screen while a refresh is pending or fails.
9. Logout calls `POST /auth/logout` with the bearer and `{}` or no body, then
   clears Keychain/session state and personal feed caches. The server revokes
   that login only, so another device stays signed in. If offline, clear local
   state immediately; retain a pending revocation only in secure storage and
   send it through the isolated v2 client when possible. Do not keep personal
   content visible while a revocation is pending.
10. A token expires after 30 days by default; there is no refresh-token or
    renewal endpoint. Password reset/change, confirmed email change, account
    deletion or server revocation also invalidate it. On `401`, clear personal
    state and return to password sign-in. On account/environment change, cancel
    in-flight work and discard late responses. Do not fall back to legacy
    identity when a v2 call fails. Handle `422` as a client request problem,
    honor `429`'s `Retry-After`, and use bounded retry for failed reads. Keep
    `meta.request_id` for diagnostics without logging credentials or response
    bodies containing a token.

The response supplies media availability, not membership authorization. Preserve
existing native access/billing behavior until the separate native entitlement
contract is approved. The shared web personalization content switch controls
the same Discover account rows; other web access limits are not imported.

## Endpoint summary

Paths below are relative to the verified `/api/v2` base URL. All successful
responses use `{ "data": ..., "meta": { "request_id": "..." } }`. Errors use
`error.code`, `error.message`, `error.fields` (an object), and the same `meta`.
All responses are private/no-store. Account/piece IDs are decimal strings.

| Method | Path | Authorization | Request | Success data |
| --- | --- | --- | --- | --- |
| POST | `/auth/login` | Password proof | `email`, `password` | `access_token`, `token_type`, UTC `expires_at`, `account` |
| GET | `/auth/me` | Bearer | No fields | `account` |
| POST | `/auth/logout` | Bearer | `{}` or no body | `logged_out: true` |
| GET | `/discover` | Bearer | No fields | Ordered `sections` |
| POST | `/pieces/{piece_id}/view` | Bearer | `{}` or no body | `accepted: true` |

Do not send `user_id`, `api`, token parameters, filters or other extra fields to
these endpoints. Unsupported fields return `422` after authentication on
protected routes. Missing/invalid/expired/revoked credentials return `401` with
`WWW-Authenticate: Bearer`. Wrong email/password also returns `401`, with
`error.code: invalid_credentials`. Login is limited to five attempts per
normalized email/IP and twenty per IP per minute. Other v2 requests are limited
to 120 per IP per minute. The limits have their own namespace and leave legacy
limits unchanged. `openapi.yaml` is the exact request/response contract.

## Backend release and acceptance

The backend team must deploy `2026_10_26_220000_create_api_v2_sessions_table.php`
before enabling the matching routes and scheduled session pruning. Existing
Discover dependencies, including the recent-history table, must already exist.
Rebuild the normal configuration/route caches so the new guard and route file
are loaded. Preserve the application key across servers. All servers must share
the user/session database and the existing Discover/rate-limit/lock cache store.
Serve v2 through HTTPS, preserve Authorization through the reverse proxy, and
exclude Authorization and login bodies from infrastructure logs. Do not change
legacy API middleware, browser cookies, App Store billing or media delivery.

Verify login, account isolation, logout/revocation, expiry/reset behavior, error
envelopes, due Free Pick rotation and exact web/v2 content parity on staging
using synthetic accounts. Record the verified base URL and checks, then publish
a **new** handoff revision with `ready_for_ios`; never edit an existing published
revision. Record phone/iPad and destination-adapter results before release.

The packaged fixtures are isolated synthetic HTTP responses. Their token is an
explicit non-credential placeholder and artwork URLs use `example.invalid`.
They support decoding/UI tests; they cannot log in or identify a live account.
