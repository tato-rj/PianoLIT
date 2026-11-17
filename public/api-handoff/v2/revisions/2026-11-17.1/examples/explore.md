# Explore for iOS

Use the existing Keychain v2 token. Requests send
`Authorization: Bearer <access_token>` and `Accept: application/json`.
The production base is `https://pianolit.com/api/v2`. Use the standard `data` and
`meta.request_id` envelope. The endpoints are read-only.

| Request | Purpose |
| --- | --- |
| `GET /api/v2/explore` | Load the Explore screen. |
| `GET /api/v2/explore/pieces` | Load matching pieces for selected tags or past highlights. |
| `GET /api/v2/pieces/{id}` | Open a piece or resolve Harmony video access. |

## Render the feed

Read `data.sections` in array order. Each section has a stable string `id`,
`kind`, display `title`, and typed `items`. Switch on `kind` and skip unknown
types. Omit missing sections. A catalog with no Explore content returns
`sections: []`. Keep entity IDs as strings.

| Section kind | Item kind | Tap behavior |
| --- | --- | --- |
| `categories` | `category` | Show the named Technique, Mood and Genre groups with selectable `tags`. Each tag has a name, piece count and results target. Combine selected tag IDs. |
| `highlights` | `piece` | Use the existing piece summary, including artwork, composer and difficulty. Open by piece ID. The section's labeled `action` loads all past highlights. |
| `originals` | `original` | The most recently published article has a title, nullable description and artwork, and a public article destination in `target.web_url`. |
| `periods` | `period` | Show its name, piece count and nullable artwork. Use its results target. |
| `levels` | `level` | Show its name and piece count in difficulty order. Use its results target. |
| `featured_composer` | `composer` | Uses the composer summary plus nullable `lifespan`, `born_year`, `died_year`, `country` and `curiosity`. Opens the public composer profile. |
| `harmony` | `harmony_lesson` | Show the piece title, composer, nullable description, artwork and access. Open `target.piece_id` and find `target.video_id` in the lesson groups. |

Highlights shows up to 16 past picks, newest first. Featured composer uses the
current Free Pick's composer, with the newest past pick as a fallback when
needed. Harmony shows up to four recent lesson cards, one per piece.

## Load matching pieces

For `target.kind = "explore_results"`, pass the target's `scope` and `tag_ids`
to `GET /api/v2/explore/pieces`. Repeat `tag_ids[]` once per ID. A single
period or level tap passes its one ID. In the Categories picker, send all
selected IDs in one call. The API accepts 1–12 distinct IDs for `catalogue`
(the default scope). It matches any selected tag within the same tag type and
requires a match from every selected type. Level and sublevel form one type.
Only current Explore tag types are accepted. Unknown or removed tags return
`422 invalid_request`; reload the feed and drop stale selections.

```http
GET /api/v2/explore/pieces?tag_ids[]=12&tag_ids[]=14&tag_ids[]=41&page=1
GET /api/v2/explore/pieces?scope=highlights&page=1
```

`highlights` excludes the current Free Pick and accepts optional tag filters.
Results contain `data.pieces` (up to 24 piece summaries), `data.page`
(one-based, defaults to 1) and `data.next_page` (integer or null). Append the
next page using the same scope and tags. Start at page 1 when filters change.
A valid selection with no matches returns an empty list and a null next page.

## Playback and web destinations

Harmony uses the same native membership check as piece detail. Active
members, active trials, accounts in their grace period, super users and the
current Free Pick receive the corresponding `access` state. Use the card
state for the lock indicator. On tap, reload the piece and match the lesson
by video ID. Play only when that video has `mode: "full"` and a URL. For
`blocked`, use the membership flow. For `unavailable` or a missing lesson,
show unavailable content. The feed supplies no media URLs. Send the token
only to the v2 API origin.

Originals and Featured composer use public web destinations. A fully native
article reader and composer profile page would each need a separate detail
endpoint. Categories, periods, levels and Highlights View all already use the
native results endpoint, and Harmony already uses native piece detail.
General text search is outside this contract.

| HTTP status | Error code | Client behavior |
| --- | --- | --- |
| 401 | `unauthenticated` | Use the existing sign-in recovery. |
| 422 | `invalid_request` | Read `error.fields` and correct the filters or page. |
| 429 | `rate_limited` | Honor `Retry-After`. |
| 500 | `server_error` | Show retry and retain the request ID for support. |

Use `explore()`, `explorePieces(scope:tagIDs:page:)` and `piece(id:)` in the
[Swift sample](PianoLITV2Client.swift). Verify all seven section types with the
[full fixture](../fixtures/explore-full.json), empty handling with the
[empty fixture](../fixtures/explore-empty.json), and result decoding with the
[results fixture](../fixtures/explore-pieces.json). See the
[OpenAPI contract](../openapi.yaml) for field types and nullability.
