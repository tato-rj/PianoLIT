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
