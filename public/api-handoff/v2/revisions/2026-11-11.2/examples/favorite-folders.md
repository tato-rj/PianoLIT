# Favorite folders on the native Piece screen

Revision **2026-11-09.1** implements these operations locally. SQLite regressions
and disposable MySQL 8.4.2 concurrency checks pass. Production deployment and
native iPhone/iPad acceptance remain pending. Keep
existing legacy calls and website behavior intact. This guide does not mark the
new routes as live.

Send the existing v2 `Authorization: Bearer <access_token>` and
`Accept: application/json`. Send `Content-Type: application/json` for JSON bodies.
Do not send `user_id`, browser cookies or CSRF tokens. IDs are positive decimal
strings, without leading zeros. All JSON responses use `data`/`meta` or
`error`/`meta`, a fresh server-generated `meta.request_id`, and private/no-store
caching. GET reads never write history or analytics.

| Method | Exact path | Success |
| --- | --- | --- |
| GET | `/api/v2/favorite-folders?piece_id=1032` | 200, `FavoriteFolderListResponse` |
| PUT | `/api/v2/favorite-folders/12/pieces/1032` | 200, `FavoriteFolderMutationResponse` |
| DELETE | `/api/v2/favorite-folders/12/pieces/1032` | 200, `FavoriteFolderMutationResponse` |
| POST | `/api/v2/favorite-folders` | 201, `FavoriteFolderMutationResponse` |

PUT and DELETE accept no body or `{}`. They ensure presence/absence, rather than
toggle it. Unknown body fields are rejected with 422. GET requires `piece_id`;
it lists every owned folder, including empty ones, ordered by name then ID. Counts
refer to pieces saved in that folder. `favorite_folder_ids` is ordered by numeric
folder ID. `can_create_folder` reflects the same saved folder policy as the website.
An empty account returns `folders: []`, `favorite_folder_ids: []` and
`is_favorited: false`.

```json
{
  "data": {
    "piece_id": "1032",
    "is_favorited": true,
    "favorite_folder_ids": ["12"],
    "folders": [
      {"id": "12", "name": "Pieces to learn", "piece_count": 8, "has_piece": true},
      {"id": "13", "name": "Reading", "piece_count": 0, "has_piece": false}
    ],
    "can_create_folder": true
  },
  "meta": {"request_id": "00000000-0000-4000-8000-000000000001"}
}
```

The same `favorite_folders` and `favorites` rows serve web, legacy and v2.
`is_favorited` requires a saved row and its folder to belong to this account.
Legacy unfiled rows stay intact and do not fill the web/native v2 heart.
Removing from folder 12 preserves membership in folder 13. Removing the last
owned folder membership returns `is_favorited: false`. Repeated requests do not
create duplicate rows or alter counts. Empty folders remain available to save into.

After either PUT or DELETE, use the returned state:

```json
{
  "data": {
    "piece_id": "1032",
    "is_favorited": true,
    "favorite_folder_ids": ["12"],
    "folder": {"id": "12", "name": "Pieces to learn", "piece_count": 8, "has_piece": true},
    "can_create_folder": true
  },
  "meta": {"request_id": "00000000-0000-4000-8000-000000000002"}
}
```

## Create and optionally save atomically

```http
POST /api/v2/favorite-folders
Authorization: Bearer <existing access_token>
Accept: application/json
Content-Type: application/json
Idempotency-Key: 550e8400-e29b-41d4-a716-446655440000

{"name":"Pieces to learn","piece_id":"1032"}
```

`name` is a string, trimmed, 3–36 characters, unique within the account using the
database's existing name comparison. `piece_id` may be omitted; when present it
must be a decimal string referencing an existing piece. Explicit null, numeric
JSON IDs and additional properties are invalid. Folder creation, optional saving,
web color preservation and the retry receipt commit together or all roll back.
No media entitlement is needed to save piece metadata.

Creation returns 201 and the same mutation response shown above. With no piece,
`piece_id`, `is_favorited` and `folder.has_piece` are null;
`favorite_folder_ids` is empty and `folder.piece_count` is zero.

`Idempotency-Key` is an optional UUID header, recommended for every creation.
Generate it once for the user's submission and retain it with the unchanged
payload through connection failures and retries. The Swift example accepts that
UUID from its caller; do not generate a new key inside each retry.

- Keys are scoped to the authenticated account across tokens and devices and
  normalized to lowercase. Receipts remain active for **24 hours from the first
  successful creation**, without extending on retries.
- Identity is the trimmed name plus optional piece ID; JSON property order and
  surrounding name whitespace do not change it. Reusing an active key with a
  different valid payload returns 409 `idempotency_conflict`, without writes.
- An identical retry returns **201**, the original folder identity and its
  **current** authoritative piece state and name. `meta.request_id` is new. It
  bypasses new-add quota checks and name uniqueness, since no addition occurs.
  It never restores a later removed membership or reverts a web rename.
- Ownership is checked again on replay. A deleted/non-owned folder or missing
  piece returns 404; the receipt remains active and the folder is not recreated.
- Rejected attempts consume no key. At or after 24 hours, a key is a new request
  and normal uniqueness/quota checks apply; a still-existing name gives 422.
  Use a new UUID for a new submission. Without a key, there is no replay guarantee.
- The private receipt table stores a payload hash and folder identity, never a
  bearer or raw name. Receipts cascade on account deletion. Expired receipts may
  be deleted by bounded maintenance (`expires_at <= now()`); an expired matching
  receipt is also replaced transactionally on key reuse. Expiry is enforced on
  reads regardless of physical cleanup; no new scheduler is required.

## Account limits and errors

The website's saved `folders` and `folder_pieces` limits and role permissions are
authoritative for these writes, including active member/trial/grace state and
the unconditional super-user override. Limited currently means 2 folders and
5 saved pieces per folder; Available has no cap and Blocked prevents additions.
Existing saves remain readable. A repeated already-saved PUT succeeds even above
a lowered limit; DELETE always remains available under quota/permission limits.
Normal authentication, ownership and request throttling still apply to DELETE.
These favorite limits do not change the independent native media access rules.

| HTTP | `error.code` | Meaning / action |
| --- | --- | --- |
| 401 | `unauthenticated` | Missing, expired, invalid or revoked bearer; show existing sign-in |
| 404 | `not_found` | A valid piece/folder ID is missing or the folder is not owned; reload |
| 422 | `invalid_request` | Invalid JSON, UUID header, name, duplicate name, ID type or body fields; inspect `error.fields` |
| 403 | `favorite_folders_blocked` | Saved account policy blocks folder creation/new folder memberships |
| 403 | `favorite_folder_limit_reached` | Owned-folder cap reached |
| 403 | `favorite_saves_blocked` | Saved account policy blocks adding pieces |
| 403 | `favorite_piece_limit_reached` | Per-folder piece cap reached |
| 409 | `idempotency_conflict` | The active key identifies different valid request data |
| 429 | `rate_limited` | Obey `Retry-After` in seconds before retrying |
| 500 | `server_error` | Retry later; retain the creation key/payload |

Malformed/nonmatching route paths use the existing generic v2 404
`request_failed`; no resource/ownership information is disclosed.
Favorite writes share a 30-request/minute authenticated-account limit. The
existing outer v2 120-request/minute limit also applies (IP before v2 identity).
Retries count toward these limits. Every 429 includes `Retry-After`.
Rejected mutations leave folders and saved memberships unchanged.

```json
{
  "error": {
    "code": "favorite_piece_limit_reached",
    "message": "You have reached the limit of pieces in this folder.",
    "fields": {}
  },
  "meta": {"request_id": "00000000-0000-4000-8000-000000000003"}
}
```

## Native UI and verification

`GET /api/v2/pieces/{piece}` now returns
`account.favorite_writes_supported: true` and the same owned-folder definition
of `account.is_favorited`. Open the native folder picker from the heart, fetch the
list for this piece and display every folder's `has_piece`. After successful save,
set the pink heart from `data.is_favorited` and play its small save animation.
After removal, use the returned flag without assuming the last membership was
removed. On failure, preserve the previous confirmed heart/count state and show
the readable message. Serialize mutations per piece; cancel/ignore outdated list
responses and responses for a previous account or piece. Refresh on return from
the website or another session; there is no push synchronization contract.

Use `favoriteFolders(pieceID:)`, `saveFavorite(folderID:pieceID:)`,
`removeFavorite(folderID:pieceID:)` and
`createFavoriteFolder(name:pieceID:idempotencyKey:)` from the Swift example. Use
`V2ClientError.rateLimited(retryAfter:)` to delay a retry; retain the same creation
key. The sample is an adapter, not a native screen implementation.

Deploy `2026_11_09_120000_create_api_v2_favorite_folder_receipts.php` **before**
matching code/routes, then publish the committed handoff bundle through the normal
Git deployment. No new secret, environment flag, billing change or provider call
is required. Do not enable Match tours as part of this release.

`ApiV2FavoriteFoldersTest` exercises idempotent saves/removals, one/all folders,
empty accounts/folders, quotas and blocked policies, atomic rollback, strict
validation, bearer-only auth, cross-account/missing resources, retry conflicts,
expiry/replays after later edits, web→v2/v2→web synchronization and Retry-After.
A temporary socket-only MySQL 8.4.2 instance successfully applied the migrations.
Eight simultaneous same-key folder+piece creations produced one new folder,
membership and receipt; eight distinct requests respected the shared two-folder
cap. Eight simultaneous PUTs and DELETEs preserved membership/count/heart state.
Six concurrent browser/v2 additions into a four-piece folder admitted exactly one
addition under the five-piece cap and kept contiguous ordering. The temporary
server was stopped; the normal database was untouched.

Before promotion, verify the new routes over HTTPS, then record native commit/build
and iPhone/iPad picker, heart/animation, offline/lost-response, account-switch and
quota/error acceptance. Local checks do not establish production/native acceptance.
