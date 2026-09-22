# PianoLIT project guidance

## Purpose and compatibility

PianoLIT serves the public website, the authenticated `my.*` web app, administration, and mobile app APIs from one Laravel application. Preserve existing appearance, working behavior, route names, and mobile JSON contracts unless a task explicitly calls for a change. Favor small, demonstrated fixes over broad rewrites. Do not assume a route is unused simply because this repository has no caller: mobile clients are separate.

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
