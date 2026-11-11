# PianoLIT iOS v2 connection

Revision **2026-11-11.1** adds the bearer-authenticated Collections catalog and
detail endpoints, production verified on November 11. Use `collections()` and
`collection(id:)` in the Swift example.
See [Collections integration](examples/collections.md) for ordering, featured
access, membership locks and the four synthetic JSON responses.

Revision **2026-11-09.1** adds favorite-folder list/save/remove/create operations
and enables the piece-detail favorite-write flag. These changes are implemented
and verified locally, including disposable MySQL concurrency checks. Their
production migration/deployment and native acceptance remain pending. See [favorite-folder integration](examples/favorite-folders.md).

The existing native piece page at `GET /api/v2/pieces/{id}` was production
verified on November 4. See [piece integration](examples/piece.md), the full/blocked
synthetic fixtures and `piece(id:)` / `V2PieceDetail` in the Swift example. That
earlier verification does not establish live favorite-write support.

Login, Discover and confirmed-open history remain available. Find my match from
2026-11-03 is still disabled by default and awaits its separate media preparation,
activation and production verification; preserve its completed native steps 1–4.
The expanded composer-pool acceptance status is unchanged. Check each feature in
`manifest.yaml`; the overall draft status denotes pending staging/native
acceptance, rather than an unavailable verified backend operation. The handoff
URL supplies documentation, not an API response or credential.

## Connect the existing sign-in screen

Keep UIKit, storyboards, the current email/password fields and existing accounts.
The backend uses the same `users` provider and password hashes as existing login.
The new app changes its network adapter, not the user's account or password:

1. Send `POST https://pianolit.com/api/v2/auth/login` with JSON
   `{"email":"<existing email>","password":"<existing password>"}` and
   `Accept: application/json` / `Content-Type: application/json`.
2. Require HTTP 200. Decode `data.access_token`, `data.token_type`,
   `data.expires_at` and `data.user` (`id`, `first_name`, `last_name`). Store the
   credential/session in **Keychain**. Map the identity fields into the existing
   UI; do not pass the envelope to the old flat-user JSON parser.
3. Send `GET https://pianolit.com/api/v2/discover` with
   `Authorization: Bearer <data.access_token>` and `Accept: application/json`.
   Decode `data.sections`; the old `/api/discover` parallel-array parser is not
   compatible with this typed response.
4. On an authenticated 401, discard the credential and personal feed, cancel or
   ignore in-flight requests, and show the same sign-in screen. Credentials
   expire after 30 days; there is no refresh-token endpoint in this revision.
5. On logout, `POST /api/v2/auth/logout` with that bearer, then clear Keychain,
   personal feed/images and local account state. Only this credential is revoked.
   Offline local logout cannot claim server revocation succeeded.

`examples/PianoLITV2Client.swift` is a concrete URLSession/Keychain adapter with
Codable models, HTTP/error handling, redirect refusal and account-switch guards.
Add it to the **new app** target and call `signIn(email:password:)`, `discover()`,
`piece(id:)` and `signOut()` from the existing controllers. The new favorite-folder
methods are listed in [their guide](examples/favorite-folders.md). It has been type-checked in Swift 5
language mode; it has not been integrated into the unavailable iOS repository or
verified on an iPhone/iPad. Keep the existing iOS deployment target.

The old app saves `userID`/`isLoggedIn`, not a password or reusable credential.
Those values cannot securely mint a token. A user upgrading from that app must
sign in once with their **existing** email/password. A v2 session subsequently
survives app restarts through Keychain until expiry/revocation. Never accept a
stored/submitted `user_id`, browser cookie or downloaded YAML as v2 proof.

Legacy registration and password-recovery flows remain available. After successful
registration or recovery, sign in through v2 with the password the user just
entered. Do not persist that password or assume the legacy registration JSON is a
v2 session. No new signup/provider flow or email is added here.

## One Discover content source

`App\Services\Discover\Feed` builds content for both the webapp controller and v2.
It uses the existing web catalogue cache and admin refresh/Free Pick clock. There
is no separately curated native Discover and no copied SQL selection in a v2
controller. `Discover\Levels` also supplies both clients' numeric level grouping.

The same account/catalogue state gets the same piece IDs/order, composer choice,
recent history, recommendation IDs and daily fallback artwork. Personalization is
composed outside the public cache. The recommendation cache stores only IDs under
an account ID + favorites/tag-state hash + admin-refresh prefix + Free Pick key,
expires daily and is rehydrated from current records. No account data goes into
the shared catalogue cache or an HTTP cache. Artwork choices are pinned daily
under source metadata; saved cover changes select a new key.

The browser still honors its own Access Limits settings. Native sign-in is
required and receives its account history/recommendations; the web access matrix
is not applied to native media or browsing. A browser account with personalization
explicitly disabled is an intentional policy difference, not another feed source.
`media` flags and `is_free_pick` describe content, not playback/export entitlement.

Sections have stable IDs/kinds, string entity IDs, booleans, plain text and
absolute URLs. Empty sections are omitted; unsupported kinds can be skipped.
RCM/ABRSM/Suzuki appear once as `levels`, in numeric order. The composer feature
can be women, Black, Latin American, American, Italian, Russian or Ukrainian
composers, exactly as chosen for the webapp
cache. Each available group has an equal chance when the admin refresh or daily
expiry rebuilds that cache; browser/API reloads retain the chosen group. Its IDs
are `women_composers`, `black_composers`, `latin_american_composers`,
`american_composers`, `italian_composers`, `russian_composers` and
`ukrainian_composers`, all with
kind `composer_feature`. Only one appears, and groups without pieces are skipped.
Country groups use the composer's saved country `flag_code`: `us` (United States),
`it`, `ru` or `ua`. No names, birthplace guesses or continent-wide matches are used.
See `screens/discover.yaml`, `openapi.yaml` and synthetic `fixtures/*.json`.

## Navigation and history boundary

Each card/action has a typed `target` (`kind`, `id`, `query`, `web_url`). Use fixed
native adapters for known kinds. `web_url` is a supported public browser fallback
while native detail/search adapters are being migrated; it does not share the
native bearer session with a browser. Never attach a bearer to that URL, artwork,
legacy `card.source` or arbitrary response-supplied origins.

A card is **not** the full legacy PieceVC model. Load `GET /api/v2/pieces/{id}` for the new native detail model and explicit
media modes described in [the piece guide](examples/piece.md). Favorite writes
use [the new folder operations](examples/favorite-folders.md) after their deployment.
Search, annotation writes and membership mutations remain separate migrations. Existing native
screens may retain explicitly identified legacy calls until their v2 replacement
is verified; do not call them authenticated v2 or derive full access from a row
position. The old app's row-zero Free Pick bypass must not be copied.

After a successful actual piece open, call
`POST /api/v2/pieces/{decimal-piece-id}/view` with the v2 bearer. No body is needed.
HTTP 204 updates one owned history row (or skips an analytics-excluded account).
A feed request/image prefetch never writes history. This is separate from analytics.

## Deployment and acceptance

The following instructions cover the existing login/Discover release.
Find my match additionally requires its own migration, private sample preparation,
activation flag and HTTPS checks; follow [its deployment procedure](examples/find-my-match.md#deploy-and-verify).

Deploy code through Git. Run
`2026_10_28_230000_create_api_v2_tokens.php` before exposing the routes; existing
Free Pick/history schema must already be installed. Refresh configuration/routes
and compiled views using the server's normal process. No new keys, identity
server, billing change, email, queue or scheduler is needed. Expired token rows
are inert; routine bounded cleanup can delete rows with expired timestamps.

Verify missing credentials return **401 JSON**, then verify existing-password
login → authenticated Discover → owned history → logout/revoked-401 and compare
web/v2 content IDs. Test legacy login/Discover/media independently. Use synthetic
fixtures/accounts; publish no real credentials or account data in this bundle.

Backend deployment verification and native acceptance are distinct. Do not mark
`ready_for_ios`/`released` until the documented staging/native checks are complete.
The iOS developer still needs to verify login, expiry, logout/account switch,
empty/retry/offline states, target adapters and phone/iPad UI and record their
commit/build. The legacy API remains supported **indefinitely**, including clients
that never update. No legacy maintenance release is required by this change.

## Fetch and validate the immutable handoff

Canonical sources are in `docs/api/v2/`. Existing published revisions remain
immutable. Every change to published files requires a new revision and matching
manifest/OpenAPI/changelog. The stable documentation entry remains
`https://pianolit.com/api-handoff/v2/manifest.yaml`; keep it revalidated, and cache
revision files as immutable.

```sh
python3 -m venv /tmp/pianolit-api-handoff-venv
/tmp/pianolit-api-handoff-venv/bin/pip install -r scripts/requirements-api-handoff.txt
/tmp/pianolit-api-handoff-venv/bin/python scripts/api-handoff.py check
/tmp/pianolit-api-handoff-venv/bin/python scripts/api-handoff.py build
```

The Swift developer can fetch hash-verified files with:

```sh
python scripts/api-handoff.py fetch \
  --url https://pianolit.com/api-handoff/v2/manifest.yaml \
  --revision 2026-11-11.1 --output /path/to/ios-repository/BackendHandoff
```

This fetch copies documentation/examples, not an installed network connection.
Integrate the adapter explicitly. Run transport tests with
`python -B -m unittest discover -s tests/scripts -p 'test_api_handoff.py'`.
For schema validation, install `openapi-spec-validator==0.7.2` in the temporary
venv and run `python -m openapi_spec_validator docs/api/v2/openapi.yaml`.
