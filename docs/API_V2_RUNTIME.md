# iOS v2 runtime deployment

Implemented locally on October 26, 2026. No deployment or normal-environment
migration was run. The public YAML handoff and `CONNECT_DISCOVER.md` are the
developer-facing delivery mechanism; do not hand out a bare guessed API URL.

## Implementation

- `routes/api-v2.php` is registered before legacy API routes with its own
  Boundary, Throttle and Authenticate middleware. It does not inherit `api`,
  AttemptLoginAppUser, browser sessions, location updates or legacy analytics.
- The `api_v2` request guard resolves only the Authorization bearer through
  `App\Auth\ApiV2\Sessions`. It clears cached identity when the request changes
  and never changes the default web guard. Stored legacy account JSON, cookies,
  query/body tokens and caller-supplied IDs cannot authenticate it.
- Tokens have 256 random bits and a 30-day absolute UTC expiry by default, with
  no sliding extension/refresh endpoint. The database holds only SHA-256 token
  hashes and a keyed fingerprint of the password hash/normalized account email.
  Password reset/change, confirmed email change or application-key rotation
  invalidates existing sessions. Account deletion cascades their removal.
- Login rechecks the account under a transaction/row lock after password proof,
  so a concurrent reset/closure cannot issue a session from the old credentials.
  Each login is independent. Logout deletes only the authenticated session.
  Reauthentication issues a fresh token; credentials are not stored in a model
  relationship or exposed in shared User JSON. No new package is installed.
- The v2 exception branch returns safe JSON and a server-generated request ID,
  including errors before route middleware. Unexpected failures log only that
  ID and exception class to the local daily channel, avoiding request-aware
  external reporters. Debugbar excludes v2 while keeping its other defaults.
- Authentication does not add an email-verification, membership or social-login
  requirement. Registration/recovery stay on their existing flows, followed by
  password login for a v2 credential. No billing or email is triggered here.
- `GET /discover` renders DiscoverFeed. It has no selection fork. The new
  authenticated piece-view POST reuses RecentlyViewedPieces and its exclusion
  rules, without invoking legacy analytics. No v2 detail/media/search/favorites
  or native entitlement contract is introduced in this change.

The design uses Laravel 8's [custom guard boundary](https://laravel.com/docs/8.x/authentication#adding-custom-guards)
and independent, expiring bearer sessions; see the primary
[OWASP session guidance](https://cheatsheetseries.owasp.org/cheatsheets/Session_Management_Cheat_Sheet.html)
for the underlying entropy, TLS and session-invalidation requirements.

## Release order

1. Use the normal release process and its database backup/rollback procedure.
   Apply `database/migrations/2026_10_26_220000_create_api_v2_sessions_table.php`
   before the PHP routes or daily prune schedule. The existing users and
   Discover schema, including `2026_09_22_120000_create_recently_viewed_pieces_table.php`,
   must already exist. Do not run billing, import or email jobs as a check.
2. Deploy the PHP, new configuration and route file to all application servers.
   Rebuild existing deployment configuration/route caches and reload long-lived
   application processes through the normal procedure. `config/api-v2.php`
   supplies lifetime and limits; no `.env` change is required. Every server needs
   the same application key, database and shared cache/atomic-lock stores. This
   uses the existing PHP-FPM lifecycle, not a new Octane deployment.
3. Require HTTPS, forward Authorization to PHP, and configure trusted proxies
   correctly. V2 rejects insecure requests outside local/testing without a
   redirect. Verify the actual edge headers/statuses. Do not log Authorization,
   password request bodies or token-bearing login responses in proxy/APM tools.
4. The existing scheduler runs `api-v2:prune-sessions` daily. It deletes expired
   rows only from `api_v2_sessions`, in batches. Logout deletes its current row
   immediately; invalidated password/email sessions are unusable immediately and
   age out through this cleanup. No existing retention policy/table changes.
5. Verify staging with synthetic credentials delivered separately: correct and
   wrong login, missing/malformed/expired/revoked bearer, both account histories,
   logout while another session stays active, password reset, JSON errors/rate
   limits and web/v2 parity after refresh/Free Pick rotation. Recheck legacy
   login/Discover and browser login. No production billing mutation is needed.
6. Record evidence and the verified HTTPS URL in a new manifest revision before
   promoting foundations/Discover to `ready_for_ios`. Deploy the complete static
   revision before its stable manifest, following `docs/api/v2/README.md`. Have
   the iOS developer fetch that revision with the existing sync script. Production
   readiness additionally requires the actual native adapter/device results.

## Rollback and local verification

Roll back the v2 PHP/routes/scheduler together before removing its table. Legacy
endpoints have no dependency on the table. Removing it invalidates all v2 tokens;
a later rollout requires password login again. Retain immutable static handoff
revisions, and publish a new readiness/URL revision instead of rewriting one.

Use `php -d error_reporting=8191 vendor/bin/phpunit -c phpunit-review.xml` with
the focused ApiV2, Discover, browser-auth and mobile-activity regressions. This
suite isolates SQLite and fakes mail/HTTP/Redis; no normal `.env` is loaded.
Synthetic fixture export is opt-in with `API_V2_FIXTURE_DIR` and redacts generated
tokens. Validate those fixtures and OpenAPI, then run the handoff package tests.
Passing local tests does not establish MySQL, proxy, staging or native readiness.
