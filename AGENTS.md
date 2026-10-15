# PianoLIT project guidance

## Purpose and compatibility

PianoLIT serves the public website, the `my.*` web app, administration, and mobile app APIs from one Laravel application. Preserve existing appearance, working behavior, route names, and mobile JSON contracts unless a task explicitly calls for a change. Favor small, demonstrated fixes over broad rewrites. The user has deferred iOS updates to a later task: web changes must not affect iOS behavior, mobile API contracts, or existing mobile media delivery. Do not assume a route is unused simply because this repository has no caller: mobile clients are separate.

Administration lives at `admin.pianolit.com` (locally `admin.pianolit.test`), with no `/admin` path prefix. Register admin routes before host-independent public routes, preserve `admin.*` names and the admin session guard, and keep non-piece public preview links on the public website. Legacy `/admin/...` GET links redirect permanently to the matching subdomain path with the query string intact. Editor uploads send the session CSRF token.

Admin piece-title and viewing links (including saved/viewed/activity records, rankings and View controls) open `webapp.pieces.show` in a new tab with `target="_blank"` and `rel="noopener noreferrer"`, so administrators see the user-facing piece page. Explicit Edit controls and editor return links retain `admin.pieces.edit`. Activity selections link to the resolved destination piece rather than the piece on the page the user was leaving.

As of 2026-09-22, `my.*` allows all visitors to browse repertoire and open piece pages. Piece media uses `hasActiveSubscription()` from the existing membership trait: visitors and ordinary accounts without an active paid or trial subscription get 10-second audio/video previews and a blurred first score page; super users, paid subscribers, active trials, and subscriptions in their grace period retain full media access. The existing `super_user` flag is an unconditional full-access override, including when no subscription exists or an old subscription has expired/ended; preserve it in every content-access check. Scheduled cancellation (including during trial) preserves access through `membership_ends_at`; that date takes precedence over a stale renewal date. An actually ended subscription remains restricted unless the account has the super-user override. The current free pick (`Piece::is_free`) has full media/score access for everyone, including visitors. Use `Piece::hasWebMediaAccess()` for piece content and score annotation authorization; turning off `is_free` restores normal restrictions on subsequent requests. Visitors still cannot favorite or save account markings; the free-pick score viewer skips account annotation requests. The preview duration is in `config/webapp.php`. Keep browsing (`hasFullAccess` in gallery templates) separate from piece content (`hasMediaAccess`); do not restore page-level membership redirects. Account data, favorites, uploads, and other writes still require a web session; guests receive no personalized suggestions or saved search history. Keep guest views safe when `auth()->user()` is null. Browser actions must use session-authenticated web routes, not the legacy mobile API's `user_id` identity. Preserve mobile access contracts unless explicitly requested.

Media preview/blur is currently enforced in the browser. Sources still use public storage URLs; protecting original files requires private delivery plus truncated media and pre-blurred score assets. Do not describe the player rules as protection against direct-file access. The user explicitly deferred PDF URL protection until web and mobile delivery can be addressed together; keep existing public URLs and mobile contracts unchanged for now.

Signed-in piece-page visits populate `recently_viewed_pieces`. Discover shows the latest 12 distinct pieces above Latest pieces, outside the shared feed cache. Never record guest visits or derive history ownership from request parameters. Deploy the history-table migration before serving this feature.

As of 2026-10-12, website, `my.*` and existing iOS API activity use SQL, including anonymous website visitors by explicit user request. This is separate from account-owned recently viewed pieces: guests still get no personalized history or account writes. Activity, account activity summaries and indexes must not dual-write to Redis. Browser events use CSRF-protected web sessions and encrypted page tokens checked against the current session/account/platform; submitted user/visit/page IDs never establish browser ownership. The user authorized moving iOS logging to SQL without an app update: preserve mobile routes, request/response contracts, traffic exclusions and media delivery. Existing mobile requests have no trustworthy visit/playback context; retain them as ungrouped app events, mark legacy submitted identity as unverified, and never use analytics identity for authorization. Deploy `2026_10_12_120000_create_activity_history_tables.php` first. After every server has switched and old requests have drained, use `activity:import-redis --verify` for the final resumable, read-only legacy import. Redis is still used for non-activity features; never flush it to retire logs. See `docs/ACTIVITY_HISTORY.md` for deployment, import and timing limits.

Arthur (`arthurvillar@gmail.com`) and Elena (`elena.dare@gmail.com`) are excluded from tracking and analytics by explicit request; the previously configured `arthurvilar@gmail.com` spelling also remains excluded. Reuse `App\Activity\ExcludedAccounts` for new collection paths and reports, including background/mobile activity and imports. Match normalized account emails independently of admin permissions; never use the privacy cookie or submitted analytics identity to authorize access. Preserve their accounts, favorites, annotations, billing and security records. See `docs/ANALYTICS_EXCLUSIONS.md` for the targeted history cleanup and recognized-browser behavior.

Full-access web users (including super users) can annotate public-domain score PDFs. Markings belong to the authenticated account, route-bound piece, and PDF identity (score path hash plus PDF.js fingerprint); never share them between pieces or accounts. Annotation reads/writes use session-authenticated web routes and optimistic revisions to avoid overwriting another tab/device. The editor loads when the Score tab opens. Original public PDF URLs/downloads and mobile contracts remain unchanged; downloads contain the original PDF. Deploy `2026_09_23_120000_create_score_annotations_table.php` before serving the editor.

## Project map

Admin analytics visual preferences are recorded in docs/ANALYTICS_DESIGN.md, including the user's saved chart/map reference. Future analytics pages should follow its rounded grouped bars, subtle gridlines, soft sparklines, colored country maps with rankings, and gauge patterns while using the shared PianoLIT typography tokens.

Redesigned admin tables and paginated lists default to 12 results per page. Reuse `App\Support\AdminPagination::PER_PAGE` for new admin pagination instead of separate numeric defaults. Keep each list's page parameter, filters and sorting intact; this admin default must not change public/mobile pagination or non-paginated dashboard rankings and detail limits.

Feedback is the visual standard for admin tables. New or redesigned admin tables use the shared `admin.components.table-card` wrapper (white card, 24px corners and padding), readable vertically aligned rows, contained horizontal scrolling, and DataTables controls where applicable. Keep filters and bulk actions inside the card. See `docs/ADMIN_TABLES.md`; adopt the pattern on pages within the requested scope rather than redesigning unrelated pages.

Standard DataTables preload their first page and use the shared `admin.components.datatable-frame` skeleton to reserve controls and preserve nonempty row shapes during initialization and requests. Empty tables/results bypass the skeleton. The standard `@datatable` layout includes this frame automatically; keep `ready`, trusted `initialBody`, initial totals/`deferLoading`, and non-sortable columns consistent. See `docs/ADMIN_TABLES.md` for fallback, accessibility and asset requirements.

- PHP application: `app/`; shared helpers: `support/helpers.php`.
- Route registration and domain/middleware boundaries: `app/Providers/RouteServiceProvider.php`, `app/Http/Kernel.php`, and `routes/`.
- Shared app feeds/search: `app/Api/`; billing: `app/Billing/`; integrations: `app/Services/` and webhook controllers.
- Templates: `resources/views/`; JavaScript sources: `resources/js/`; SCSS: `resources/sass/`.
- Asset entry points: `resources/js/app.js`, `admin.js`, `tone.js`, plus the SCSS entries in `webpack.mix.js`.
- Models live directly under `app/` as well as domain folders. This project uses legacy Laravel factories.
- `public/js` and `public/css` contain tracked build outputs. Edit sources first, build, and include the relevant outputs and manifest changes. `resources/js/vendor/lookup.js` is project-owned autocomplete code despite its directory name.

## Development and verification

The installed stack reviewed on 2026-09-22 was Laravel 8, PHP 8.3.12, PHPUnit 9.6.11, Laravel Mix 2.1.14/Webpack 3, Node 14.17.5, npm 6.14.14, and node-sass 4.14.1. These are observed working tools, not a recommendation to retain old versions indefinitely. Upgrade the toolchain separately with compatibility testing; do not run blanket dependency updates during a bug fix.

Commands:

```sh
php -d error_reporting=8191 vendor/bin/phpunit -c phpunit-review.xml
npm run test:js
npm run production
git diff --check
```

The PHP command suppresses legacy dependency deprecations on PHP 8.3; it does not suppress warnings/errors. The review suite uses in-memory SQLite, skips the project's `.env`, mocks Redis/HTTP/mail, and checks the connection before running migrations. Use this suite for local regression checks. New tests belong in `tests/Review` when they require that isolation.

The original `phpunit.xml` suite is **not fully isolated**: `Tests/AppTest.php` creates broad fixtures and shells out to Redis cleanup, and billing helpers call Stripe. Inspect a test and its setup before running it. Run broader integration tests only with dedicated disposable databases, Redis, sandbox billing credentials, and fake mail/storage. SQLite passing does not prove MySQL behavior.

For an isolated asset build, copy `artisan`, `package.json`, `webpack.mix.js`, and `resources/` into a temporary directory. Mix 2 detects Laravel using `artisan` and resolves paths from its installed module; if sharing `node_modules` via a symlink, set `Mix.paths.setRootPath(process.cwd())` in a Node preload before invoking Webpack. Copy resources rather than symlinking them so Sass loader rules match their resolved paths. Keep temporary build logs outside the repository.

## Working rules

- Check `git status` first; preserve unrelated user changes. Do not edit `.env`, expose credentials, or execute maintenance/queue/billing/email commands against the normal environment as verification.
- Validate request shapes and restrict user-controlled model names, columns, paths, and sort keys. Authenticate identity independently of submitted user IDs; folder ownership checks alone do not solve mobile API authentication.
- Preserve API payloads while optimizing. Eloquent appended attributes can trigger queries and expose loaded relationships; inspect them before changing eager loading or serialization.
- Use transactions for operations that delete and recreate related records. Test rollback as well as success. Account for database uniqueness and concurrency separately.
- Reuse eager-loaded relations; prefer `exists()` for existence checks. Measure query counts for performance claims. Do not add shared caches to personalized responses without an explicit user-aware key and invalidation strategy.
- Read environment variables through config files. Keep command constructors free of database, Redis, network, and filesystem mutations.
- Verify webhooks against the raw body before processing. Stripe now requires `STRIPE_WEBHOOK_SECRET`; verify the deployed endpoint secret and signing setup before releasing billing changes. There must be no test-only production bypass.
- Keep asynchronous UI results tied to the current request. Handle failures and restore controls/spinners. Avoid design changes and broad CSS cleanup without visual verification.
- Use focused regression tests for demonstrated defects. Describe what was verified and what remains untested; do not characterize a static review as a complete production audit.

## Improvement tracking

Maintain `docs/CODE_REVIEW.md` as the initial findings and follow-up register. For future work, add a dated entry with evidence, affected files, priority, compatibility constraints, verification, and remaining work. Mark an item complete only after implementation and relevant checks.

Next priorities are authenticated mobile identity, webhook retry/idempotency handling, reproducible isolated integration tests, missing route actions, and a staged dependency/toolchain upgrade. See the report for specifics.
