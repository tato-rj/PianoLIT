# Native piece page integration

Revision **2026-11-04.1** adds the **live, production-verified** `GET /pieces/{piece}`.
The manifest records backend verification separately from pending native acceptance. Keep the existing v2 email/password
sign-in and Keychain session, UIKit and the current iOS deployment target.

## Load a piece

Take the string piece ID from a Discover card or a match result. Fetch:

```http
GET https://pianolit.com/api/v2/pieces/123
Accept: application/json
Authorization: Bearer <existing Keychain access_token>
```

Replace `123` with the actual returned piece ID. Do not send `user_id`, a browser
cookie or a subscription claim. HTTP 200 contains `data` with:

| Field | Contents |
| --- | --- |
| `piece` | Display title, catalogue, artwork, current Free Pick flag, composer biography, portrait, lifespan and country |
| `metadata` | Names, key, collection/movement, period, level, tags, stored years and estimated page-count label |
| `about` | Description, composer-biography fallback, “Is this your next piece?” text and ranking labels |
| `account` | This verified account's `is_favorited` and supported-action flags |
| `access` | Native `full_media` and `basis`, resolved before making an access decision |
| `media` | Performance, synthesia, lesson groups, audio tracks, score information and listening markers |
| `timeline` | Composer period and curated events/milestones with source/image credits |
| `recommendations` | Shared web collection and recommendation rows, including bounded summaries |
| `synthesia_releases` | Existing release draw with tutorial IDs, thumbnails and piece summaries |
| `share_url` | Public browser link, containing no account identity or credential |

The response is an explicit detail DTO, not the legacy one-element piece array.
Use the `V2PieceDetail` models and `piece(id:)` method in
[PianoLITV2Client.swift](PianoLITV2Client.swift):

```swift
let detail = try await client.piece(id: discoverCard.id)
// Render detail.piece / metadata / about / timeline / recommendations.
// Initialize a player or PDF only for a resource with mode == "full"
// and a non-nil URL. Ignore stale responses after an account/page change.
```

An HTTP 401 uses the existing sign-in-again handling; 404 means the piece is
unavailable, not that the session expired. Follow `Retry-After` on 429. Render
missing, locked and retry states deliberately. Read `meta.request_id` when
reporting a backend problem. Responses are private/no-store.

## Playback and score

Use `media.performance` and `media.synthesia`, not the older summary's
`piece.media.performance` compatibility flag. A video supplies its own ID,
title, category, `mode`, nullable `url` and ordered `moments`. Marker times are
JSON numbers in seconds (`start_seconds`, nullable `end_seconds`); comments and
names are display text. Audio's marker comments are intentionally null, matching
the web audio guide. Audio track kinds are `full`, `right_hand` and `left_hand`.

The native media rule preserves existing full-access accounts (including active
trials, grace periods and the unconditional super-user grant) and the current
scheduled Free Pick for a signed-in account. Other accounts receive the same
page metadata with blocked original media. The browser Access Limits matrix and
its preview timing are not imported. The endpoint reads stored membership state
without contacting Apple/Stripe or changing billing.

Initialize AVPlayer/audio/PDFKit only when the resource's `mode == "full"` and its
URL is present. `blocked` means membership is required; `unavailable` means no
usable resource exists. A missing video is JSON null. Score `external` means open
`purchase_url` as a normal external link; `pdf_url` is null. Respect unknown modes
by leaving playback disabled. Re-fetch detail after membership/Free Pick changes;
neither row position nor stale card data grants access.

Use the returned original resource URLs with a **plain media request**. Keep the
API bearer only on the fixed `pianolit.com/api/v2` API origin/path; do not attach it
to video/audio/PDF, image, purchase, public web or timeline source URLs. Existing
original-file delivery is unchanged. This read endpoint adds no annotation,
export or billing workflow.

## Navigation, favorites and confirmed opens

Use each related card's `id` with the same detail endpoint. Recommendation
`web_url`/card `target.web_url` values remain public browser fallbacks for search,
composer and View all destinations that do not yet have a native v2 adapter.
Recommendations and release draws reuse the current web services, including
existing per-request random choices; they are not a separate native catalogue.

`account.is_favorited` is read from the verified account's existing folder-owned
favorites. `favorite_writes_supported` and `annotations_supported` are currently
false. Keep those writes disabled until their separate authenticated v2 contracts
exist; do not submit native actions to browser session/CSRF routes or assume a
submitted legacy user ID is v2 authentication.

The GET does **not** record a view. After the native page successfully opens,
call the existing `recordConfirmedOpen(pieceID:)` client method, which sends:

```http
POST https://pianolit.com/api/v2/pieces/123/view
Authorization: Bearer <existing Keychain access_token>
```

No body is required; expect HTTP 204. `account.history_path` is the complete
origin-relative `/api/v2/pieces/{id}/view` path. Never write history during card,
image or detail prefetch. Keep account/page requests cancellable and discard
responses that belong to a previous account or navigation.

## Verification and remaining work

`fixtures/piece-full.json` and `fixtures/piece-blocked.json` are synthetic API
responses for decoding/UI development. The server reuses `Pieces\PageContent`,
`WebTimeline`, `PieceRecommendations`, `Tutorial.listeningMoments` and the existing
metadata/media getters used by the web page. It does not serialize raw models,
change shared mobile JSON, mutate accounts or call billing providers.

No new migration, key, feature activation, queue or scheduler is required for
piece detail. Deploy the committed code and refresh route/view caches through
the usual Git deployment. Find my match still requires its separate deployment
and activation checks; this endpoint does not activate it.

The Swift sample is supplied for the new app target. Native source and devices
are outside this workspace; validate actual AVPlayer/PDFKit behavior, lesson and
marker selection, composer/related navigation, locked/empty/error states, account
switching and iPhone/iPad layouts before native acceptance. The legacy API stays
supported indefinitely, without an old-app update.
