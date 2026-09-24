# PianoLIT project guidance

## Purpose and compatibility

PianoLIT serves the public website, the `my.*` web app, administration, and mobile app APIs from one Laravel application. Preserve existing appearance, working behavior, route names, and mobile JSON contracts unless a task explicitly calls for a change. Favor small, demonstrated fixes over broad rewrites. Do not assume a route is unused simply because this repository has no caller: mobile clients are separate.

As of 2026-09-22, `my.*` allows all visitors to browse repertoire and open piece pages. Piece media uses `hasActiveSubscription()` from the existing membership trait: visitors and ordinary accounts without an active paid or trial subscription get 10-second audio/video previews and a blurred first score page; super users, paid subscribers, active trials, and subscriptions in their grace period retain full media access. The existing `super_user` flag is an unconditional full-access override, including when no subscription exists or an old subscription has expired/ended; preserve it in every content-access check. Scheduled cancellation (including during trial) preserves access through `membership_ends_at`; that date takes precedence over a stale renewal date. An actually ended subscription remains restricted unless the account has the super-user override. The preview duration is in `config/webapp.php`. Keep browsing (`hasFullAccess` in gallery templates) separate from piece content (`hasMediaAccess`); do not restore page-level membership redirects. Account data, favorites, uploads, and other writes still require a web session; guests receive no personalized suggestions or saved search history. Keep guest views safe when `auth()->user()` is null. Browser actions must use session-authenticated web routes, not the legacy mobile API's `user_id` identity. Preserve mobile access contracts unless explicitly requested.

Media preview/blur is currently enforced in the browser. Sources still use public storage URLs; protecting original files requires private delivery plus truncated media and pre-blurred score assets. Do not describe the player rules as protection against direct-file access. The user explicitly deferred PDF URL protection until web and mobile delivery can be addressed together; keep existing public URLs and mobile contracts unchanged for now.

Signed-in piece-page visits populate `recently_viewed_pieces`. Discover shows the latest 12 distinct pieces above Latest pieces, outside the shared feed cache. Never record guest visits or derive history ownership from request parameters. Deploy the history-table migration before serving this feature.

Full-access web users (including super users) can annotate public-domain score PDFs. Markings belong to the authenticated account, route-bound piece, and PDF identity (score path hash plus PDF.js fingerprint); never share them between pieces or accounts. Annotation reads/writes use session-authenticated web routes and optimistic revisions to avoid overwriting another tab/device. The editor loads when the Score tab opens. Original public PDF URLs/downloads and mobile contracts remain unchanged; downloads contain the original PDF. Deploy `2026_09_23_120000_create_score_annotations_table.php` before serving the editor.

## Project map

- PHP application: `app/`; shared helpers: `support/helpers.php`.
- Route registration and domain/middleware boundaries: `app/Providers/RouteServiceProvider.php`, `app/Http/Kernel.php`, and `routes/`.
- Shared app feeds/search: `app/Api/`; billing: `app/Billing/`; integrations: `app/Services/` and webhook controllers.
- Templates: `resources/views/`; JavaScript sources: `resources/js/`; SCSS: `resources/sass/`.
- Asset entry points: `resources/js/app.js`, `admin.js`, `tone.js`, plus the six SCSS entries in `webpack.mix.js`.
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
