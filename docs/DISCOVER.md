# Shared Discover content

`App\Services\DiscoverFeed::forUser(?User $user)` is the content source for web
Discover and the new iOS v2 response resource. Edit content selection, ordering,
recommendation limits and account-row rules here. Do not implement another feed
in a controller, Blade template, JSON resource or Swift code.

The web controller passes its authenticated web account and adds browser-only
card presentation and the membership confirmation. The v2 JSON renderer is
`App\Http\Resources\Api\V2\DiscoverResource`; it accepts the same feed rows,
allowlists fields and returns `data.sections`. It does not select, shuffle or
truncate pieces. The native app supplies its own views/styles. Both renderers
use the same level grouping/order and composer biography excerpt helpers.

`GET /api/v2/discover` now serves that resource behind the independent
`api_v2` bearer guard. Password login, account-session read/logout and the
confirmed-piece-open POST are implemented in `routes/api-v2.php`. The controller
passes only the verified principal to DiscoverFeed; it never derives identity
from user_id or browser cookies. Error/meta handling, token expiry/revocation
and the session-table migration are described in `docs/API_V2_RUNTIME.md`.
These operations are implemented/tested locally, with deployment, staging and
native verification still pending. The existing YAML handoff and its new
`CONNECT_DISCOVER.md` deliver the contract, fixtures and native wiring steps.

## Content and caches

- The existing non-personal content pool in `Api::for('webapp')->discover()` is
  shared by both modern renderers. Its historical cache suffix is retained to
  avoid changing the existing refresh/schedule behavior. The legacy mobile
  `/api/discover` pool, positional rows, ranking and serialization stay separate.
- Women/Black selection happens once per public-pool rebuild, using only
  nonempty groups. `DiscoverFeed` places that selection in one stable
  `composer_feature` slot and adds Past highlights, excluding the current pick
  and future dates. Fallback artwork is stable per shared generation/piece, using
  an opt-in seed in the existing artwork helper; other callers retain randomness.
  Admin refresh, daily expiry and scheduled Free Pick changes
  reach both renderers through this source.
- For you contains at most `DiscoverFeed::FOR_YOU_LIMIT` pieces (currently six).
  It uses the existing account recommendation algorithm. The chosen IDs are
  cached under an account-specific key, with a lock for concurrent first loads.
  Keys include the limit, admin refresh prefix, calendar day, public content
  identities and the account's favorite IDs/tag names. Favorite changes, public
  pool/Free Pick changes and refresh therefore invalidate the selection. Entries
  expire after one day. No user model, entitlement or history enters the shared
  public cache.
- Recently viewed remains a live account-owned read, capped at twelve distinct
  pieces above Latest pieces. Feed retrieval never records an opening. The
  existing excluded-account rule remains in `RecentlyViewedPieces`.
- Arthur's October 26 parity request makes the personalization content switch
  common to these modern Discover renderers. It does not copy the web media,
  score, favorites, collection or billing access policies into native iOS.
- Sections carry stable IDs and presentation kinds. The three legacy grading
  pools are rendered together as one Levels section, ordered RCM/ABRSM/Suzuki and
  numerically within each system. Eloquent models are cloned before rendering;
  v2 never serializes them directly or includes browser HTML, colors, classes,
  legacy API sources, private paths or access grants.

## Verification

`tests/Review/DiscoverFeedParityTest.php` compares web view data against v2
serialized section/item order for both composer groups, checks the six-piece
account selection, history/account isolation, favorite invalidation, admin
refresh, due Free Pick rotation and the JSON allowlist. Existing Discover,
cache, history, schedule and browser-access tests remain compatibility checks.

The HTTP runtime and token lifecycle are covered in `tests/Review/ApiV2Test.php`.

Run isolated regressions with `php -d error_reporting=8191 vendor/bin/phpunit -c
phpunit-review.xml`. No deployment, production data change or native build is
implied by local checks or by publishing the draft handoff bundle.
