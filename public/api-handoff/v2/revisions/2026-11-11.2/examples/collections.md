# Collections for iOS

Use the existing v2 session for the curated repertoire catalog. Both GETs take
`Authorization: Bearer <access_token>` and `Accept: application/json`. Send no
request body or query parameters. Reuse the Keychain token from Discover. If
the device only has a legacy account ID, use the existing email/password
screen to sign in via `POST /api/v2/auth/login` and store the returned v2 token.
Attach that bearer only to requests to the fixed PianoLIT v2 API origin.

| Request | Swift example | Success payload |
| --- | --- | --- |
| `GET https://pianolit.com/api/v2/collections` | `client.collections()` | Catalog, featured ID, book series, suggestions and categories |
| `GET https://pianolit.com/api/v2/collections/{collection}` | `client.collection(id:)` | `data.collection` and `data.pieces` |
| `GET https://pianolit.com/api/v2/pieces/{piece}` | `client.piece(id:)` | Existing native piece detail with media modes |

## Catalog and detail

All success payloads also include `meta.request_id` (UUID string). Every field
listed for a payload is present. Collection IDs are stable positive decimal
strings. Keep them as strings in Swift. The path uses the ID from the selected
collection card. Collection IDs and piece IDs refer to different entities.

| Field | Type | Meaning |
| --- | --- | --- |
| `featured_collection_id` | string or null | ID of the featured card within `collections`. Null when no collection qualifies. Hide the hero when null. |
| `collections` | array of cards | Complete catalog in display order. The featured card is included once. If the hero is displayed separately, filter that ID out of the grid. |
| `book_series` | array of series | Series and their books in editorial display order. |
| `suggested_collection_ids` | array of string IDs | Up to three catalog IDs in the website's weekly suggestion order. |
| `categories` | array of categories | Ordered filter IDs, labels and assigned catalog IDs. |
| Card `id`, `title` | string | Stable ID and display title. |
| Card `subtitle` | string or null | Saved subtitle. Null if blank. |
| Card `artwork_url` | absolute URL string | Saved cover or the shared fallback. Load as an image without the bearer. |
| Card `piece_count` | integer | Full count of pieces with tutorials, including on locked cards. |
| Card `featured_badge` | string or null | Saved editorial tag on the featured card, if set. Null on regular cards and untagged featured cards. The ID determines the hero even when its badge is null. |
| Card `access` | string enum | `available` or `membership_required`. |
| Detail `collection` | object | Same card fields plus `description` (saved text string or null if blank). |
| Detail `pieces` | array of Discover piece summaries | Full ordered array when available, or `[]` when locked. |

The catalog shares the web app's eligibility and featured selection. Eligible
collections are published general collections with at least six attached pieces,
at least five of which have tutorials. iOS catalog order is ascending saved
collection order, then numeric ID. Detail rows include only pieces with
tutorials and follow ascending saved collection-piece order, then pivot row
ID for ties. The underlying SQL orders older unset positions first. There is
no pagination, cursor, or additional request for the rest of a collection.
The featured ID is normally cached for seven days and may change sooner if
editorial changes invalidate the selection.

## Book series, suggestions and filters

Read all three sections from the same `GET /api/v2/collections` response. Each
array is present in this revision and may be empty. In the Swift example,
the three properties are optional so older fixtures can still be decoded.
Use an empty array when a section is missing from an older response.

- **Book series.** Each series has string `id` and `title` fields and a `books`
  array. Books have string `id`, `title` and `level` fields, integer `number`,
  absolute `cover_url`, `availability` (`available` or `coming_soon`) and
  `detail_url` (public HTTPS URL or null). Preserve array order. Use the public
  destination for an available book. The current Piano Solos series has eight
  coming-soon books with null destinations. Show their status without a tap
  action. Load covers and open destinations without the API bearer.
- **Suggested collections.** Resolve each ID against `collections`, preserving
  the order of `suggested_collection_ids`. The weekly selection can include the
  featured card. Use the matched card's `access` for its tap behavior.
- **Category filters.** Each category has a string `id`, string `label` and
  `collection_ids` array of string IDs. Categories follow natural label order
  with case-insensitive sorting. Saved tag IDs survive a rename. Older unlinked
  tags use a key based on their trimmed label, so editing that label can change
  the key. Filter the loaded Browse cards by ID, keeping catalog order. **All**
  clears the filter and includes untagged cards. Keep the featured card and
  suggestions outside the filter. The API takes no category parameter.

An empty repertoire catalog returns empty `collections`,
`suggested_collection_ids` and `categories` arrays. Book series can still appear
because their publication is independent of the collection catalog.

Decode rows as the existing `V2Piece` / OpenAPI `PieceSummary`. Use `id` for the
canonical piece ID, `title` for the display title, `composer.name` or
`composer.short_name`, and `difficulty` (string or null). For row icons,
`media.score`, `media.audio`, and `media.performance` are booleans. Score and
audio use the Discover flags for a stored score file and a full audio track.
The Collections performance flag represents a saved PianoLIT performance
video with a nonempty URL. External score purchase links and playback access
come from the piece detail. Collections responses contain no original media URLs.

## Access and navigation

- Every signed-in account can see all collection cards, including locked cards
  and their full piece counts. Every signed-in account can open the current
  featured collection and browse all of its piece rows.
- Active members, including active trials and eligible grace periods, and super
  users can browse all collection details. Other accounts get `access:
  membership_required` on regular cards. On tap, show the existing membership
  paywall. A direct or stale detail request rechecks access and returns HTTP 200
  with the title, subtitle, description, artwork, full count, and `pieces: []`
  for a locked collection. Use `access` to distinguish a lock from an empty state.
- Tapping a visible row opens the v2 piece page. Use its `access` and individual
  `data.media` modes for playback and score controls. Nonmembers can see piece
  metadata. Their full media remains gated unless the piece is the current
  Free Pick. Being featured in Collections does not grant media access.
- Omit **Follow a path** in the new app. There is no v2 replacement for
  `/api/playlists/journey`. Legacy clients retain that endpoint.
- Fetch detail once to render the collection. There is no separate collection
  analytics event or extra tracking request in this contract. After an actual
  piece open, keep the existing `POST /api/v2/pieces/{piece}/view` (HTTP 204) for
  recently viewed history.

## Errors and fixtures

Errors use `{ "error": { "code": "...", "message": "...", "fields": {} },
"meta": { "request_id": "..." } }`. The API sends `Cache-Control: private, no-store`.

| HTTP status | Code | Client action |
| --- | --- | --- |
| 401 | `unauthenticated` | Clear the current expired, revoked, or invalid session and use the existing sign-in. |
| 404 | `not_found` | The collection is missing, unpublished, scheduled for later, or outside the curated catalog. Close the unavailable detail and refresh the catalog. |
| 404 | `request_failed` | The path did not match a valid route, for example an ID of `0`, a slug, or `journey`. |
| 429 | `rate_limited` | Honor `Retry-After` before retrying. |
| 500 | `server_error` | Show a retry state and retain the request ID for support. |

The synthetic [catalog](../fixtures/collections-catalog.json),
[detail](../fixtures/collections-detail.json),
[empty catalog](../fixtures/collections-empty.json), and
[locked detail](../fixtures/collections-locked.json) files are full example
responses. [Unauthenticated](../fixtures/unauthenticated.json) shows the shared
error envelope. The [OpenAPI contract](../openapi.yaml) defines all field types.

## Native acceptance

On iPhone and iPad, verify reuse of the Discover session, the empty state, hero
with and without a badge, regular collection paywall, member and featured detail
ordering, row icons, and piece navigation. Verify membership changes and a
featured rotation between index and detail loads. Clear account-specific results
on sign-out or account switch and ignore stale responses.
Verify book order and coming-soon cards, suggestion order and card access, and
local Browse filtering with **All**, a selected category and an empty category list.
