# PianoLIT code review — 2026-09-22

## Scope and limits

Repository-wide static checks and targeted manual review covered PHP, route registration, Blade templates, JavaScript, SCSS imports/build configuration, model relationships, search/feeds, favorites, authentication, billing/webhooks, file delivery, and maintenance/test infrastructure. The starting inventory included 415 application PHP files, 68 route files, 62 JavaScript files, 53 stylesheet files, and 908 Blade templates.

This is not a claim that every behavior or integration has been exercised. No production database, Redis cleanup, billing action, email campaign, or deployment was run. The full legacy suite, real mobile clients, browser visual flows, and MySQL-specific behavior remain unverified. Working UI/design and mobile response formats were preserved; SCSS required no source changes after compilation checks.

## Implemented fixes

| Area | Evidence and change | Verification |
| --- | --- | --- |
| Gift downloads | `UsersController::gift` accepted arbitrary paths relative to `public`, including traversal outside it. Resolve canonical paths and allow only public/static gifts and public-storage gifts; reject symlink escapes. Missing fallback files return 404. | Traversal, symlink, invalid input, and valid-file regression checks using temporary fixtures. |
| Stripe webhooks | Signature verification was commented out. Verify the raw request body using the installed Stripe SDK, configured secret and tolerance; reject invalid signatures, fail closed without a secret, and acknowledge signed unhandled event types. Updated the legacy webhook test helper to sign requests. | Valid signature, forged/unsigned/tampered/expired payloads, and missing-secret tests. Real billing lifecycle was not exercised. |
| Restricted API token | Missing bearer/config tokens compared equal; direct `env()` use was incompatible with config caching. Use config and a nonempty constant-time comparison. | Missing, incorrect, and matching token tests. |
| Favorites | Moving a favorite deleted its source before destination validation. Wrap the move in a transaction. | Successful move, duplicate destination rollback, foreign-folder rollback. |
| Favorite folders | Foreign/missing folder reads dereferenced null. PDF generation lacked a web ownership check. Form lookups used `firstOrFail` incorrectly, body fields could override the route's ownership target, and unchanged names failed uniqueness validation. Reorder input was not constrained to an array of IDs in the folder. | Ownership, route/body mismatch, missing folder, unchanged name/description, malformed reorder, and PDF authorization tests. |
| Folder performance | `hasPiece` ran an extra query per folder even when favorites were already loaded. Reuse that relation and batch-load piece favorites for flat folder lists. | Zero-query assertion for loaded membership checks. |
| Recommendations | Calls supplied named argument `order` while the scope accepts `ordered`. Use a positional boolean compatible with the declared PHP range. | Empty-favorites recommendation query runs successfully. |
| Search | Request-controlled classes could be instantiated; malformed search/filter input and missing local matches could crash. Validate input, allow only the existing tag/composer models, and handle empty/missing matches. | Invalid model/search/filter/page cases, empty filtered search, empty count, missing tag tests. |
| Feed caches | Uninitialized Redis version keys could collapse different caches onto an empty key. Personalized/platform rows could mutate an in-memory cached collection. Add distinct fallback keys and copy collections before insertion. | Cache separation, repeated discover calls, and web/mobile explore isolation tests. |
| Empty catalog | No free pick caused null dereferences while creating free/similar rows. Return empty row content; tolerate absent explore highlight. | Empty free/similar row tests. |
| Admin autocomplete | Request `field` was interpolated into raw SQL. Restrict it to fields used by the UI; select distinct scalar rows without hydrating full Piece models and relations. | SQL-expression rejection test; source caller review. |
| Artisan startup | `RefreshDailyLogsCount` scanned Redis from its constructor during unrelated commands. Defer that scan until the command executes. | All isolated database migrations execute with Redis calls forbidden unless explicitly mocked. |
| Browser helpers | First-cookie reads included subsequent cookies, malformed escapes could throw, and writes appended `undefined`. Query parameter values did not encode `&`/`=` correctly. | Cookie and URL encoding checks in `npm run test:js`. |
| Audio | `audio.pause` was referenced rather than invoked; clearing the source assigned `null`. Correct stop/reload and reset icons on end/error/rejected playback. | Mock audio lifecycle checks. |
| Async search/autocomplete | Stale failures could erase current results or hide a newer spinner. Lookup's timeout called autocomplete immediately. Cancel obsolete searches, guard all callbacks by request ID, debounce lookup correctly, and respond to input/paste. | Out-of-order success/error, repeated query, clear-input, cancellation, and debounce checks. |

Compiled `public/js/app.js`, `public/js/admin.js`, the copied audio/lookup scripts, and the two bundle manifest entries were updated from the successful isolated production build. Stylesheet outputs were not changed.

## Validation

- Initial syntax scan: 659 non-Blade PHP files passed.
- All 62 JavaScript source files passed `node --check`.
- All 908 Blade templates compiled and their generated PHP passed syntax checks.
- All six SCSS entry points and three JS entry points compiled in the production build; Mix's copy/concatenation/versioning steps completed. Initial temporary-build attempts needed corrections to the test harness's symlink paths and Laravel detection; no production build configuration changes were needed.
- Isolated regression suite: 33 tests, 55 assertions passing.
- JavaScript regression runner passed; no new dependencies were installed.
- Route reflection audit: 455 routes, with 16 missing action targets listed below.
- No measured end-to-end latency claim is made. The query reduction is verified narrowly, and frontend request reduction follows from tested debounce/cancellation behavior.

## Follow-up register

### P0 — Mobile authentication requires a coordinated rollout

`app/Http/Middleware/AttemptLoginAppUser.php` logs in whichever account is identified by a caller-supplied `user_id`. `Auth/Api/LoginController` returns user data after password checking but issues no verifiable API credential. Protected data and mutations must use authenticated tokens and derive ownership from the authenticated principal. Coordinate server and mobile releases, token issuance/revocation, existing-client migration, and negative authorization tests. Retrofitting mandatory tokens here without mobile changes would break existing clients. This serious issue remains open; the folder fixes are not a replacement for authentication.

### P1 — Complete webhook reliability and integration checks

- Before deploying the signature change, confirm `STRIPE_WEBHOOK_SECRET` matches the actual endpoint. There is one configured signing secret; review the US/Swiss billing setup and whether separate endpoints/secrets are needed.
- `StripeWebhooks::whenChargeSucceeded` inserts a payment on every delivery; `payments.charge_id` has no unique index. Design idempotency using Stripe event/charge IDs, audit existing duplicates before introducing uniqueness, and test concurrent retries/out-of-order events with real schema constraints.
- `MailgunWebhook` uses the mail API secret and a 15-second acceptance window. Verify the actual signing configuration and delivery/retry behavior before changing it; add malformed-input and signature tests. No live webhook delivery was tested.

### P1 — Isolate and restore the broader test suite

The new review suite is safe for local in-memory checks. Original tests use `Tests/AppTest`, real Redis-oriented setup/cleanup, and Stripe sandbox calls. Establish a disposable integration environment, replace shell Redis cleanup with the configured connection, fake outbound services by default, and explicitly group integration tests. Add CI for PHP tests, JS regressions, syntax/template checks, and asset builds. Then baseline failures before a framework upgrade.

### P1 — Resolve missing route actions without guessing intended behavior

These registered routes refer to nonexistent methods. They were left unchanged because the intended replacement/removal and external callers need review. Confirm access logs and client use; implement supported behavior or retire the route, then add a route-action integrity check.

| Route | Missing action |
| --- | --- |
| `GET ebooks/topics/{topic}` | `Shop\eBooksController@topic` |
| `GET escores/topics/{topic}` | `Shop\eScoresController@topic` |
| `test-upload` | `HomeController@filetest` |
| Subscription resource: index, create, show, update, destroy | `SubscriptionsController` methods |
| `admin/blog/images/remove` | `Admin\BlogController@removeImage` |
| `admin/ebooks/{ebook}` | `Admin\eBooksController@show` |
| `admin/escores/{escore}` | `Admin\eScoresController@show` |
| `admin/pieces/datatable` | `PiecesController@datatable` |
| `admin/quiz/images/remove` | `Admin\QuizzesController@removeImage` |
| `admin/tutorial-requests/{tutorialRequest}` | `Admin\TutorialRequestsController@show` |
| `admin/pieces/{piece}` | `Admin\PiecesController@show` |
| `admin/playlists/create` | `Admin\PlaylistsController@create` |

### P2 — Serialization, search correctness, and query budgets

`Piece::$appends` computes many attributes, including relationship and sibling queries. Review explicit API resources and bounded eager loading with mobile payload snapshots before changing it. Favorites relations can include other users' favorite metadata; define and test a minimal public payload. `Api\Search` applies Scout filters after retrieval/pagination, while count requests bypass them; plan consistent filtering/count/pagination against representative Algolia data. Current fixes deliberately preserve the existing response shape. Profile large composer/playlists feeds and `ORDER BY RAND()` paths before replacing them.

### P2 — Redis maintenance and concurrency

`FlushRedis` and `RefreshDailyLogsCount::flushAll` invoke `redis-cli` through a shell rather than honoring the configured Redis connection. Other maintenance code uses `KEYS`. Replace these with bounded `SCAN` batches and configured connection operations, validating namespaces and testing empty/multiple pages. Review favorite toggle races and nullable-folder uniqueness with the actual MySQL version; the move transaction does not solve simultaneous duplicate inserts.

### P2 — Staged dependency and frontend modernization

Inventory Composer/npm advisories and plan framework/PHP, Mix/Webpack/node-sass, jQuery/Bootstrap, and Axios updates in tested stages. No dependency security audit or updates were performed in this pass. Introduce bundle-size baselines and measure page-specific loading before splitting bundles. Keep Bootstrap/Font Awesome/SCSS ordering stable; visual smoke tests are needed before CSS consolidation or switching Sass compilers.

### P2 — Integration and asset edge cases

Review direct Guzzle calls (Apple receipts and reCAPTCHA) for bounded timeouts, response validation, and provider-specific retry/fallback rules. Review runtime `env()` calls outside config, registration/upload CSRF exceptions, admin upload validation, and source-file exposure on the web server. The gift endpoint's legacy default JPEG is absent in this local checkout; invalid/missing gifts now return 404 if that fallback is unavailable. Confirm the deployed gift inventory.

## Future entries

Add a date, issue reference, affected paths, evidence, implemented change, verification, and any rollout dependency. Keep unresolved findings visible until the relevant change and verification are complete.

### 2026-09-22 — Full web access through subscription grace periods

- Request: preserve full content access during grace periods, including trials canceled before their end date.
- Updated the existing web `hasActiveSubscription()` check to honor Stripe's `isOnGracePeriod()` whenever a scheduled membership end exists. That end date takes precedence over a leftover renewal date, so cancellation preserves access until the scheduled end without extending it afterward. An actually ended subscription remains restricted. Mobile contracts and cancellation/billing actions are unchanged.
- Verification: 50 isolated PHP tests / 541 assertions and JavaScript regressions passed. Tests cover full scores/downloads, audio and videos during active/trial/canceled/paused grace periods, restriction after grace expires even with a future renewal date, and immediate-ended subscriptions. No live billing calls or deployment were performed.

### 2026-09-22 — Full web content access during active trials

- Correction: active trials must have the same full content access as paying subscribers. The previous paid-only predicate explicitly excluded trials.
- Renamed the web content predicate to `hasActiveSubscription()` and reused the billing source's `isActive()` with the existing expiry/end checks. Applied it to the piece view, AJAX audio/tutorial fragments, and score-download controller. Stripe trial states and Apple trial periods now receive readable scores and unrestricted media. Paused, ended, expired, and unpaid subscriptions remain restricted. Mobile API and billing behavior are unchanged.
- Verification: 49 isolated PHP tests / 422 assertions and JavaScript regressions passed. Coverage includes paid Stripe/Apple accounts, Stripe `trialing`/`trial`, Apple seven-day trials, full-score downloads, untagged audio/video playback, and expired/paused/ended trials. No live billing calls or deployment were performed.

### 2026-09-22 — Version the piece-access script

- Follow-up: the user still saw all blurred pages after the first-page change. Both the public script and the script served by `my.pianolit.test` contain the first-page renderer, but the template used an unversioned `asset()` URL, allowing browsers to reuse the previous file.
- Changed the template to `mix()` and explicitly included the copied `piece-access.js` in Mix versioning. Included the generated manifest entry; future builds change its URL when its content changes. Existing full-score and mobile behavior is unchanged.
- Verification: isolated Mix build, 49 PHP tests / 359 assertions, and JS regressions passed. The PHP checks assert that restricted piece pages emit a versioned script URL. A browser fixture using a multi-page PDF loaded that URL and rendered exactly one canvas with `blur(8px)` and no preview-load error. The user's existing browser cache could not be inspected directly.

### 2026-09-22 — First-page score preview; URL protection deferred

- Request: show only the first blurred score page to visitors and non-paying accounts. The user explicitly deferred direct PDF URL protection until web and mobile can be addressed together.
- Changed the restricted PDF.js renderer to request/render page 1 only, retaining the existing blur and subscriber viewer. Removed the incomplete server-preview service, route, and Ghostscript configuration from this work; no new server dependency or file-delivery system is introduced.
- Direct storage URLs remain public by request, including the mobile app's existing links. Browser blur is a presentation restriction, not file protection. Coordinate future private delivery and mobile authentication before changing these URLs.
- Verification: 49 isolated PHP tests / 350 assertions and JavaScript regressions passed. The four-page JS fixture now asserts that only page 1 is requested and one canvas is rendered; subscriber and 10-second media checks still pass. Isolated Mix build passed and the public page script was updated. No production deployment or new browser visual check was performed.

### 2026-09-22 — Piece content access rules (implemented locally)

- Request: keep piece pages public while limiting visitor/non-paying content; the user chose 10-second previews. This replaces the temporary unrestricted media policy below. The free-pick flag does not bypass these new web content rules.
- Inspection: route middleware controls page entry; `HasMembership` and Stripe/Apple billing sources control subscription status; `webapp/piece/index.blade.php` initializes Plyr and AJAX/native audio; the score tab uses PDF.js or a Safari embed. There was no existing preview limiter/modal, only the Go Premium pricing flow. The implementation reuses those players, billing methods, Bootstrap modal component, button styles, and pricing destination.
- Added `hasActivePaidSubscription()` to the existing membership trait, using source paid/expired/ended/trial checks. Content templates receive a separate `hasMediaAccess` flag; the established `isAuthorized()` method and mobile API remain unchanged. Trials, paused/expired/ended subscriptions, and accounts without a paid subscription receive previews. No synthetic subscription is created for visitors or privileged accounts.
- `config/webapp.php` sets the 10-second limit. The page-specific `piece-access.js` handles playback, seeking, replay attempts, early endings, fullscreen exit, and AJAX-loaded players. Paid players have no limit attributes/listeners. All audio variants and tutorial fragments use the same rules. The tutorial endpoint also validates that a tutorial belongs to its piece.
- Restricted scores render every PDF page sequentially inside one blurred container, including on Safari. Download/share controls and native PDF embeds are absent for restricted viewers; the Laravel score-download endpoint also checks paid access. Existing paid score rendering/download controls are retained. Corrected the old PDF render-state variable typo and skipped PDF initialization when no viewer exists.
- Verification: 49 isolated PHP tests / 350 assertions passed, plus JavaScript regressions for cutoff/seeking/replay, subscriber passthrough, dynamic audio, multi-page rendering, and load errors. Isolated Mix asset build succeeded; the copied page script is included in `public/js/views`. Browser fixture checks verified the upgrade prompt, AJAX audio stopping at 10 seconds, Synthesia seeking clamped to 10 seconds, three-page blur, subscriber playback beyond 10 seconds, and readable subscriber scores. These use synthetic local media, not production billing or content. One native audio-control interaction crashed the test browser; the check succeeded through the existing hand-selection controls after reopening the fixture. Mobile Safari and production CDN behavior still require deployment smoke checks.
- Delivery limitation / follow-up: original videos/audio/PDFs remain publicly hosted and the browser must fetch originals for playback/PDF rendering. The UI restrictions and protected Laravel download route do not prevent direct URL access or developer-tools bypass. Strong file-level enforcement requires private/signed originals, real 10-second preview files, and server-generated blurred page images; coordinate that work with the separately deployed media service and mobile clients. No storage permissions or remote media were changed, and no production deployment was performed.

### 2026-09-22 — Recently viewed repertoire (implemented locally)

- Request: show Recently viewed directly above Latest pieces on Discover, only for registered users after opening a piece.
- Added account-scoped history through `app/Services/RecentlyViewedPieces.php` and a `recently_viewed_pieces` migration. Successful GETs of the piece detail page update one unique account/piece row using an atomic upsert. Indexed reads return the latest 12 distinct pieces, with repeat visits moving a piece forward. Foreign keys remove history when an account or piece is deleted.
- `WebApp/PiecesController` records visits using the authenticated session; `WebApp/TabsController` inserts the row outside the shared discovery cache using the existing gallery/card templates. Guests, HEAD requests, and piece subresource requests do not create history. Mobile API feeds are unchanged.
- Verification: four isolated feature tests cover first visit, placement, repeat ordering, account isolation/forged IDs, guest exclusion, cache/API isolation, card limit, and deletion cleanup. Full isolated suite: 44 tests / 192 assertions passed. No live database or visual browser check was performed; no assets changed.
- Rollout: run `2026_09_22_120000_create_recently_viewed_pieces_table.php` before serving the updated controllers. The migration was exercised only against disposable SQLite; production MySQL migration/deployment remains pending. History starts with visits after rollout; old activity logs are not backfilled.

### 2026-09-22 — Public web app browsing (implemented locally)

- Request: allow visitors to browse `my.*`; explicitly open all repertoire temporarily while retaining account-only favorites and personalized suggestions.
- Removed blanket web authentication and repertoire membership gates. Added explicit session authentication to private folders, saves, requests, uploads, sharing, logout, and billing actions. Public My Pieces/Profile pages show sign-in prompts.
- Made guest templates null-safe, removed guest save/upload controls, and skipped personalized discovery, saved search history, account activity logging, geolocation, and Google Tag Manager for guests. Standard framework session/CSRF cookies and infrastructure access logs are not anonymous-profile features and remain in place.
- Web discovery/search/location resolve identity from the session, ignoring submitted user IDs. Folder viewing now checks ownership. Folder ordering and performance applause use dedicated authenticated web actions; legacy `/api/*` requests on the `my.*` host are rejected. Main-domain mobile API authentication remains the P0 follow-up above; this change does not repair that broader vulnerability or verify which hosts deployed mobile clients use.
- A directly requested empty AJAX search exposed a null collection error; the web controller now renders an empty result without changing the mobile search payload.
- Affected areas: `RouteServiceProvider`, webapp routes/controllers/templates, `AppServiceProvider`, shared feed/search identity handling, and authentication/location middleware. No SCSS or compiled bundle changes were needed.
- Verification: isolated PHP suite passes 40 tests / 161 assertions, including 7 new guest/session access regressions covering public pages, premium content, denied writes, forged identity, folder ownership/reordering, and applause. JavaScript regression runner passes. No production deployment, live billing/upload integration, or browser visual verification was performed.
- Remaining: define the next content limits. The preexisting `pieces/{piece}/appleMusic` route renders a missing `webapp.piece.options.apple-music` template and has no current webapp template caller; confirm intended behavior before restoring or retiring it. It is excluded from the passing public-page smoke checks.


### 2026-09-23 — Visitor search limit and webapp query reduction (implemented locally)

- Priority: P1 behavior change and P2 performance. Visitors now receive at most the first three matching search results, followed by Sign up / Sign in links. The server rejects subsequent visitor result pages with an empty response, ignores caller-supplied identity for access, and applies local filters before limiting. Scout filters scan backend pages in relevance order until three matches are found. Registered accounts, including accounts without a subscription, retain all results and pagination. The mobile `Search::get()` behavior remains separate.
- Evidence and fixes: piece-card templates previously performed separate video, performance, Synthesia, and favorite lookups per piece. `Services/WebApp/PieceCards.php` batches tags/composers and uses database EXISTS aggregates for card status. Discover clones cached piece models before adding browser attributes; personalized rows and favorite status are not shared. Explore/highlights/Synthesia releases and favorite-folder contents load tags in batches. The Discover composer picker no longer loads all composers' pieces, and unused post/category queries are removed.
- Piece detail loads tutorials once and reuses them for media/category checks, computes similar pieces once, and passes the already-bound Piece to Timeline. Timeline no longer loads unused countries. Playlist completeness reuses tutorial counts; the browser playlist index computes qualifying piece counts without hydrating the entire repertoire. Save-to responses aggregate per-folder saved status, and My Pieces reuses its folder collection/counts. Profile renders reuse membership and newsletter-list relationships.
- The webapp's browser bootstrap serializes only the identity fields it uses, avoiding incidental favorite/media/activity serialization on every signed-in page. Search count reuses one resolved search, exact-match middleware passes its resolved model forward, and browser pagination omits the unused database total count. The search client stops visitor scrolling, prevents overlapping scroll requests, ignores stale filter responses, and restores controls after failures.
- Affected files: `app/Api/{Api,Factory,Search}.php`, `app/Services/WebApp/PieceCards.php`, webapp controllers, `Http/Middleware/Search/CheckForLocalQueries.php`, `Piece`, `Playlist`, `Timeline`, `Subscription`, membership/media traits, webapp card/search/feed/account templates, the shared newsletter-preferences template and favorite-response controller, and new isolated PHP/JavaScript regressions.
- Query measurement: before/after measurements used the same disposable SQLite fixture (12 pieces with tags/tutorials, six folders, one playlist); cache was cleared for each measured route. Activity logging/geolocation middleware and external services were excluded. Counts are SQL statements, not latency measurements; cold Discover can vary slightly with randomized rows. Representative results:

  | Page | Before | After |
  | --- | ---: | ---: |
  | Discover, visitor / registered | 163 / 250 | 39 / 49 |
  | Explore | 23 | 11 |
  | Highlights results | 15 | 4 |
  | Playlist index, visitor / registered | 21 / 20 | 5 / 4 |
  | Playlist detail, visitor / registered | 41 / 53 | 6 / 6 |
  | Piece detail, visitor / registered | 70 / 73 | 15 / 18 |
  | Similar pieces, visitor / registered | 42 / 53 | 10 / 10 |
  | Search results, visitor / registered | 41 / 41 | 7 / 7 |
  | Search count | 5 | 3 |
  | My Pieces, registered | 89 | 11 |
  | Profile, registered without subscription | 5 | 2 |
  | Save-to, six folders | 10 | 4 |

- Verification: the full isolated PHP suite passed (66 tests / 836 assertions), JavaScript regressions passed, and `git diff --check` passed. No JS/SCSS bundle sources changed; browser script changes are rendered by Blade. Isolated PHP regressions cover route query budgets, constant card query counts for 2 versus 12 pieces, subscriber media payload parity, batched Synthesia tags, cache/account isolation, profile newsletter/membership reads, save/remove feedback, and playlist count parity. Search tests cover visitor limits, page/identity/count bypass attempts, filters, empty searches, free registered pagination, unchanged mobile result counts, and mocked Scout pagination. JavaScript tests cover duplicate requests, stopping visitor scrolling, stale responses, retries, and empty responses. The signup fragment was visually checked in a local browser fixture with the existing stylesheet.
- Compatibility and remaining verification: no new schema migration, asset bundle, storage policy, mobile payload, or membership-access change is required for this work. Existing media/history changes retain their previously documented rollout requirements. No production deployment or production query profiling was performed; MySQL execution plans, real search-service latency, and production-size data still need deployment smoke checks. This is a measured reduction of demonstrated redundant queries, not proof of a globally minimal query count. The preexisting missing appleMusic view remains tracked separately.

### 2026-09-23 — Restore super-user full access (implemented and verified locally)

- Priority: P1 access regression. The new web media predicate omitted the existing `super_user` override honored by `getStatus()` / `isAuthorized()`, incorrectly applying previews and score restrictions to full-access accounts.
- `app/Traits/HasMembership.php` now honors `super_user` before inspecting subscription records. This restores unrestricted piece video, Synthesia, audio variants, tutorial fragments, readable scores, and score downloads even without a subscription or with an expired/ended historical subscription. Billing records and mobile contracts are unchanged. `AGENTS.md` now explicitly preserves this existing entitlement.
- Verification: the new regression in `tests/Review/PieceContentAccessTest.php` failed before the fix and passes afterward. It covers no subscription, ended/expired Stripe, expired Apple, all media/download endpoints, and restoring normal restrictions when the override is removed. Full isolated suite: 67 tests / 940 assertions passed; `git diff --check` passed.
- Remaining: deploy and smoke-check an existing super-user account. No production account or billing data was changed, and no deployment was performed.

### 2026-09-23 — Persistent web score annotations (implemented locally)

- Priority: P1 requested feature. Full-access accounts, including unconditional super-user overrides, now get a PDF.js score editor with pen strokes, fingering/text, colors, eraser, undo/redo, page navigation, and zoom. Normalized coordinates keep markings aligned when the page changes size. Guest/non-paying score previews and copyrighted-score purchase links retain their existing behavior.
- Persistence: `score_annotations` has a unique account/piece/PDF key, validated bounded annotation data, cascading cleanup, and revision checks. Session-authenticated web endpoints derive identity from the session and route, bind the current score path and PDF fingerprint, and return private/no-store responses. Another piece using the same PDF still has separate markings. Transactional saves reject stale revisions, roll back failures, and acknowledge identical retries after a lost response. No original PDF or mobile API is modified; downloads remain the original PDF.
- Browser behavior: the editor starts only when its Score tab is visible, loads saved annotations once, debounces writes, serializes in-flight saves, and retains unsaved changes on request failure. Save status, retry controls, conflict feedback, and a navigation warning make failures visible. Editing waits for the saved state to load. A normal subsequent annotation save performs one annotation UPDATE (measured in the isolated test); no duplicate insert or read is needed. The initial save may insert the row, and a conflict/retry reads it to resolve the revision.
- Affected files: `WebApp/ScoreAnnotationsController`, `routes/webapp/pieces.php`, the score-table migration, piece index/score/editor Blade templates, `resources/js/views/score-editor.js`, score-editor SCSS/app import, Mix configuration and relevant tracked assets/manifest, and PHP/JavaScript regressions. `AGENTS.md` records the identity and rollout rules.
- Verification: isolated PHP suite passes 76 tests / 1,066 assertions; JavaScript regressions and isolated production asset build pass. New tests cover account/piece/PDF isolation, session identity, access rules (including super users), validation, persistence/deletion, idempotent retries, stale writes, transaction rollback, and query count. JavaScript tests cover undo/redo, page coordinates, save serialization, failed/lost responses, and conflicts. Browser checks used the actual Blade editor and PDF.js against a local stub persistence service: pen/text saved and survived reopening, another piece remained blank, eraser/undo worked, zoom preserved alignment, and the 390px layout remained usable. Real Laravel endpoints were exercised separately by the isolated PHP tests.
- Rollout and remaining verification: deploy `2026_09_23_120000_create_score_annotations_table.php` before serving the new editor. No production migration or deployment was performed. SQLite tests do not verify production MySQL concurrency/execution; real-device iPad/Apple Pencil and production storage/CORS smoke checks remain. Annotation export into a modified PDF is not part of this implementation.

### 2026-09-23 — Write score text directly on the page (implemented locally)

- Priority: P2 usability. Removed the separate toolbar text input. Selecting Fingering / text and clicking the PDF now focuses a native text field at that point, with the normal blinking caret and live text. Existing text can be clicked to edit. Enter or clicking elsewhere finishes editing; an empty draft disappears without creating an annotation, and clearing existing text removes it.
- Text still autosaves while typing. Edits within one typing session share one undo checkpoint, avoiding an undo action for every character. The temporary field uses the score's normalized coordinates, font/color/size, and measured text baseline so it stays aligned with the saved SVG text through resizing and zoom. Page changes finish the current edit. No endpoint, schema, membership, PDF-delivery, or mobile-contract changes were needed.
- Affected files: score-editor JavaScript, Blade partial, SCSS, built assets/manifest, and `tests/js/score-editor.js`. Preserved the user's intervening score-heading/download placement changes.
- Verification: JavaScript regressions and the isolated template test pass; isolated production asset build and diff whitespace check pass. Added behavior checks for blank drafts, autosaving active text, editing without duplication, removal, and grouped undo. A local browser fixture verified the caret and live text on the page, alignment when finishing an edit, reopening/editing saved text, no extra save for a blank new entry, and clearing an existing annotation. Native keyboard deletion was verified. Production deployment and real-device keyboard testing remain pending.

### 2026-09-23 — Native score color picker (implemented locally)

- Replaced the score editor's four-color dropdown with a browser-native `<input type="color">`, starting at the existing dark score color. Pen and text continue to use the selected value.
- The annotation endpoint now validates any six-digit hex color, so custom native-picker choices persist and remain safe to render as SVG/CSS colors. Existing saved palette colors remain valid. No database or mobile change is required.
- Verification: focused isolated annotation suite passes 10 tests / 80 assertions, including arbitrary text and pen colors and invalid CSS color rejection. JavaScript regressions and isolated production asset build pass. The local browser recognizes the control as a color well and opens its native chooser. No production deployment was performed.

### 2026-09-23 — Palette-icon color control (implemented locally)

- Replaced the visible color label and square with the existing toolbar's Font Awesome palette icon and a narrow underline showing the selected color, following the provided screenshot. The native color input covers the icon's hit area, so clicking the icon opens the browser's color chooser directly. Keyboard focus remains visible, and the native input keeps its accessible annotation-color name.
- The editor updates the underline on color input and disables the control while the score is loading or unavailable. Selected colors continue to apply to text and pen marks through the existing persistence path.
- Verification: isolated annotation tests pass (10 tests / 81 assertions), JavaScript regressions pass, isolated production asset build passes, and `git diff --check` passes. A local browser fixture showed the palette icon and underline, opened the native chooser by clicking the icon, and showed the underline turn red after changing the color. Production deployment remains pending.

### 2026-09-23 — Plain palette toolbar icon (implemented locally)

- Removed the selected-color underline from the palette control at the user's request. The Font Awesome palette icon now matches the neighboring pen and eraser controls. The native color input still covers the icon's click area and retains keyboard focus and accessible naming.
- Removed the unused JavaScript underline update; annotation colors and persistence are unchanged. Rebuilt the relevant tracked CSS/JavaScript assets and manifest. No schema or API change.

### 2026-09-23 — Toggle score editing tools off (implemented locally)

- The commented-out Read button remains absent; Read is the default mode. Clicking an active Pen, Text, or Eraser button again now returns to Read. A pointer click outside the score page also returns to Read, while switching directly between tool buttons still selects the requested tool. Finishing a text edit or pen stroke uses the existing completion path before deselection.
- Affected file: `resources/js/views/score-editor.js` and its tracked build/manifest. The local browser fixture verified repeat-click toggling and outside-click deselection for Pen, Text, and Eraser. JavaScript regressions, isolated production asset build, and `git diff --check` passed. No backend or database change.

### 2026-09-23 — Restore Pen after toolbar simplification (implemented locally)

- Cause: the toolbar's Pen width dropdown was commented out, but the Pen pointer handler still dereferenced `[data-width]`. A stroke therefore failed before pointer capture or save.
- Pen now uses the prior Medium width (`0.004`) when that optional control is absent and still reads a selected width if the control exists. Tool toggling and annotation persistence are unchanged.
- Verification: JavaScript regression with the width control absent draws a two-point stroke at the Medium width. A local browser fixture showed a visible stroke and “All markings saved.” Isolated production asset build and `git diff --check` passed. No backend, migration, or mobile change.

### 2026-09-23 — Score box follows PDF page height (implemented locally)

- Removed the editor's `80vh` height cap and inner vertical scrolling. The box now grows with the PDF page at the current zoom; the browser page handles vertical scrolling. Horizontal scrolling remains available when zoom makes the page wider than the box.
- Affected files: score-editor SCSS, tracked `public/css/app.css`, and its Mix manifest entry. No JavaScript, route, persistence, or mobile behavior changed.
- Verification: isolated production asset build and `git diff --check` passed. In a local browser fixture, the default score box and its scroll content were both 1,107 px tall for a 1,083 px PDF sheet; at 125% zoom both grew to 1,378 px while horizontal content exceeded the 870 px box width. No inner vertical overflow was present in either view. No production deployment was performed.

### 2026-09-23 — Default Small score text (implemented locally)

- Removed the Text size selector and its extra instruction block from the score toolbar. Text remains a toggleable tool: selecting it and clicking the PDF opens the inline cursor. New text marks use the former Small setting (`size = 0.02`); existing saved marks retain their own sizes.
- Removed the JavaScript dependency on the deleted controls. A JavaScript regression checks that a new text mark starts at Small with no selector. The local browser fixture verified the simplified toolbar, an inline cursor, and text rendered at 16.92 px on an 846 px score sheet (the expected 2%). The isolated template test, JavaScript regressions, production asset build, and `git diff --check` passed. No backend, database, or mobile change.

### 2026-09-23 — Keep Text active when reopening a saved mark (implemented locally)

- Cause: clicking an existing SVG text mark replaces that SVG node with the inline input during `pointerdown`. The document-level outside-click listener ran afterward, saw the detached target outside the score sheet, and incorrectly deselected Text, closing the input.
- The outside-click listener now runs in the capture phase, while the original target is still attached. Clicking saved text keeps Text active and permits editing; clicking outside still closes the input and returns to Read. The current score template has no “click existing text to edit” instruction after the prior simplification.
- Verification: the local browser fixture created and saved text, reopened it by clicking the rendered text, edited it inline, and then confirmed outside-click deselection. JavaScript regressions, isolated template test, production asset build, and `git diff --check` passed. No backend, database, or mobile change.

### 2026-09-23 — Show selected color on palette icon (implemented locally)

- The Font Awesome palette icon now uses the native color input's current value, starting with the default dark score color. `input` and `change` events update the icon as the user chooses a new color; the same input value continues to set the color of new pen and text marks. No extra swatch or underline is shown.
- Affected file: score-editor JavaScript and its tracked build/manifest. The local browser fixture confirmed the icon's computed color changed from `#20252b` to `#e3342f`, with the red icon visible. JavaScript regressions, isolated production asset build, and `git diff --check` passed. No backend, database, or mobile change.

### 2026-09-23 — Stop mobile page scrolling during score edits (implemented locally)

- Priority: P2 usability. A finger drag on the PDF could move the whole page while Pen, Text, or Eraser was selected. The editor now disables native touch panning on the HTML score sheet as well as its SVG overlay. A non-passive `touchmove` handler on the sheet prevents page scrolling during those editing modes, including browsers that do not fully honor SVG `touch-action`. Returning to Read restores normal touch scrolling.
- Affected files: score-editor JavaScript, its tracked build/manifest, and JavaScript regressions. No PDF delivery, backend, database, or native mobile app contract changed.
- Verification: JavaScript regressions cover all three editing modes and Read; isolated PHP suite passes 77 tests / 1,063 assertions; isolated production asset build and `git diff --check` pass. An actual mobile Safari finger-drag check remains pending.

### 2026-09-24 — Redesign the Score tab controls (implemented locally)

- Priority: P2 requested UI and feature completion. The former compact toolbar placed Listen above the PDF, navigation in a separate row, and Download below the editor. The new score-only layout follows the supplied desktop/mobile reference: rounded drawing controls above the page, a pale score stage, and a persistent lower bar for page navigation, zoom, fullscreen, Listen (when audio exists), Download, and Print. It keeps the existing piece header and tabs. Download still opens the original public PDF without annotations; Print prints the visible page with its annotations.
- Added a translucent highlighter with a separately remembered color, persisted under the same account/piece/PDF identity and revision rules as pen strokes. Clear all removes markings across every page as one undoable edit after confirmation. The fullscreen control fills the viewport and can be closed with the button or Escape. Pen, Text, Eraser, palette, save/retry, zoom, and page navigation retain their prior behavior. The color picker now leaves the selected tool active.
- Affected files: score-editor Blade/SCSS/JavaScript, annotation validation in `ScoreAnnotationsController`, tracked app CSS/editor JS/manifest, and focused PHP/JavaScript regressions. No PDF URL protection, download format, mobile API payload, or database schema changed. Preserved the user's unrelated layout and `.DS_Store` changes.
- Verification: focused annotation tests cover highlight persistence and bounded stroke fields; JavaScript tests cover drawing and undoing a clear-all operation. A local PDF.js browser fixture showed the desktop and 390px layouts, visible highlight ink, save state, and fullscreen enter/exit. Isolated PHP suite, JavaScript suite, production asset build, and `git diff --check` pass. Production storage/CORS, browser print output, and real-device touch behavior remain to be smoke-checked after deployment.

### 2026-09-24 — Lower default zoom and drag saved score text (implemented locally)

- Priority: P2 requested usability. The score now starts at 75% zoom and the minus control reaches 50% without going lower. In normal Read mode, saved text can be dragged to another spot on its PDF page. A stationary tap creates no edit; a move previews the new position, then saves once on release so undo restores the prior position. Text mode still edits the text content as before.
- Affected files: score-editor JavaScript, its Blade zoom label, score-editor SCSS, tracked assets/manifest, and JavaScript regressions. Existing account/piece/PDF annotation identity and mobile API contracts remain unchanged.
- Verification: JavaScript regressions cover tap versus drag, coordinates, undo, 50% lower bound, and touch-scroll suppression while dragging. A local PDF.js browser fixture opened at 75%, reached 50%, and showed a saved fingering move from approximately (210, 269) to (255, 307) after reopening. The isolated PHP suite passes 78 tests / 1,077 assertions; the production asset build and `git diff --check` pass. Real-device touch dragging remains to be checked after deployment.

### 2026-09-24 — Responsive opening score zoom (implemented locally)

- Priority: P2 requested mobile usability. When the Score tab first opens at a viewport width of 767px or less, the PDF starts at 100%; larger widths continue to start at 75%. The zoom label is set before PDF loading begins. Manual zoom still ranges from 50% to 250%, and resizing after opening does not overwrite a user's selected zoom.
- Affected files: score-editor JavaScript, tracked editor asset/manifest. No annotation data, endpoint, PDF URL, mobile API, or desktop default changed. Preserve the unrelated user changes already in the working tree.
- Verification: the local PDF.js browser fixture opened at 100% in a 390px viewport and at 75% in a 1280px viewport. The isolated production build, JavaScript regressions, and `git diff --check` pass. Physical-device responsive behavior remains to be checked after deployment.

### 2026-09-24 — Single-row mobile score toolbar and undoable clear (implemented locally)

- Priority: P2 usability. At phone widths, the drawing controls, Undo, Redo, and Clear all now remain on one row; the save status sits underneath. Clear all shows only its trash icon on mobile while retaining an accessible name. Clicking it clears immediately without a confirmation dialog, and Undo restores the markings.
- Affected files: score-editor Blade, SCSS, JavaScript, JavaScript regression test, and tracked CSS/editor assets and manifest. Annotation storage, access rules, original PDF delivery, and mobile API contracts are unchanged.
- Verification: the local PDF.js preview showed one toolbar row at 390px and 320px. Clear all removed the preview markings immediately, and Undo restored them. JavaScript regressions, focused isolated annotation tests, production asset build, and `git diff --check` pass. Physical-device touch behavior remains to be checked after deployment.

### 2026-09-24 — Restore the score Listen player (implemented locally)

- Priority: P2 regression. The new Listen button still used the existing audio-loading handler, but piece pages hide the bottom menu that previously supplied `#bottom-popup`; the response therefore had no player container. Piece pages with audio now render that existing popup above the score footer, including in fullscreen. The established hand selection, speed, playback, and close handlers remain unchanged.
- Affected files: piece page Blade, score-editor SCSS, tracked app CSS/manifest, and the focused piece access regression test. Audio routes, media access rules, and mobile API contracts are unchanged.
- Verification: the isolated piece content suite passes 7 tests / 542 assertions and confirms the Listen button and exactly one popup container on a full-access piece page. The isolated production asset build and `git diff --check` pass. A 390px local preview showed the player controls opening above the score footer. Live audio playback on a deployed page remains to be smoke-checked.

### 2026-09-24 — Print every score page with annotations (implemented locally)

- Priority: P2 requested behavior. Print now prepares every PDF page in document order, renders that page's account-specific text and drawing marks into a temporary print layout, and sends the complete layout to the browser print dialog. The visible editor stays on its current page and zoom. Printing waits until saved markings finish loading; a failed page render stops printing rather than producing an incomplete document. The temporary pages are removed after printing.
- Affected files: score-editor JavaScript, score-editor print SCSS, Print button title, tracked editor JavaScript/app CSS/manifest, and JavaScript regression tests. Annotation storage, PDF downloads, and mobile API contracts are unchanged.
- Verification: JavaScript regressions cover a three-page document with marks on different pages, unchanged editor position, cleanup, and render failure. A local PDF.js browser fixture prepared both pages of a two-page PDF before invoking the print handler. Production asset build and `git diff --check` pass. The native print dialog and physical printer output remain to be smoke-checked across browsers.
