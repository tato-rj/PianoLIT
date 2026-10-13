# Google login

Implemented on the public website and `my.*` using the existing `users` table and `web` guard. The feature is disabled until Google credentials and the additive migration are present. Email/password authentication continues to work while it is disabled.

## Configuration in Google Cloud

Use **one OAuth client of type Web application** for both websites. In Google Cloud Console, choose/create the PianoLIT project and open **Google Auth Platform**:

1. **Branding:** use PianoLIT, its support/contact email and logo; homepage `https://pianolit.com`, privacy URL `https://pianolit.com/privacy-policy`, terms URL `https://pianolit.com/terms-of-service`. Add the authorized domain `pianolit.com` and complete any ownership/branding verification Google requests.
2. **Audience:** select External for public users. During testing, add designated test users. Publish the OAuth application when ready for public sign-in.
3. **Data Access:** request only `openid`, `email` and `profile`. No Google Drive, Calendar, YouTube, offline access or other API scopes are used.
4. **Clients:** create a Web application client and add these **exact authorized redirect URIs**:

   ```text
   https://pianolit.com/auth/google/callback
   https://my.pianolit.com/auth/google/callback
   ```

   **Authorized JavaScript origins: leave empty.** This implementation exchanges authorization codes on the server and does not use Google's browser SDK. If a future browser SDK is introduced, its origins would be `https://pianolit.com` and `https://my.pianolit.com`; that change requires its own review.

5. Install its client ID and client secret in the server environment. Never commit them or expose the secret to JavaScript.

Google's [OpenID Connect server flow](https://developers.google.com/identity/openid-connect/openid-connect) describes the state/code/ID-token exchange. Its [Google authentication library](https://google-auth.readthedocs.io/en/latest/_modules/google/oauth2/id_token.html) uses the same fixed X.509 certificate endpoint used here.

## Server environment

```env
GOOGLE_CLIENT_ID=your-web-application-client-id
GOOGLE_CLIENT_SECRET=your-server-side-client-secret
GOOGLE_REDIRECT_URI=https://pianolit.com/auth/google/callback
GOOGLE_WEBAPP_REDIRECT_URI=https://my.pianolit.com/auth/google/callback
```

The client ID/secret are required. The redirect settings are optional overrides: their defaults are the exact canonical callbacks above, derived from `APP_URL`. An override that does not match its site's canonical callback disables Google sign-in on that site. Use `APP_URL=https://pianolit.com` and the existing production HTTPS/proxy setup. Production Google routes reject HTTP. Retain existing session cookie/domain settings; do not widen `SESSION_DOMAIN` for this feature. Production session cookies should already use `SESSION_SECURE_COOKIE=true`.

PHP needs its existing OpenSSL and mbstring extensions, and outbound HTTPS access to:

- `https://accounts.google.com/o/oauth2/v2/auth` (browser)
- `https://oauth2.googleapis.com/token` (server)
- `https://www.googleapis.com/oauth2/v1/certs` (server)

Keep server clocks synchronized. The normal application cache stores public Google certificates for 30 minutes and single-use OAuth state fingerprints for 10 minutes. This is authentication cache data, separate from SQL activity history. Retain a reliable shared cache/sticky-session setup appropriate to the existing deployment.

For staging, configure a separate Google Web client and the equivalent two HTTPS callbacks on the staging hosts derived from that deployment's `APP_URL`. Local `.test` hosts are used for automated mocks; they were not registered with Google. Do not put production credentials into test fixtures.

## Deployment

1. Back up normally and deploy/run `2026_10_13_200000_create_google_authentication_tables.php` **before enabling the feature**. It creates:
   - `user_auth_providers`: unique provider/subject and unique user/provider; Google subjects use case-sensitive ASCII on MySQL.
   - `auth_link_challenges`: expiring browser/account/identity-bound ownership challenges.
   - `auth_browser_handoffs`: expiring transfers bound to both host sessions.
   - `auth_provider_audits`: connection/disconnection/password-setup/failure events without credentials or tokens.
2. Ship the new authentication classes, routes, notifications and Blade templates together. There are no dependency, built CSS/JS, user-schema, mobile-route or media-delivery changes.
3. Add the environment values above, rebuild the application's configuration and route caches using the deployment's usual process (`php artisan config:cache`, and `php artisan route:cache` if this deployment supports it). Reload PHP workers/Octane and other long-lived processes if used. No new queue/scheduler requirement was added; ownership email uses the existing notification/mail transport.
   Keep production debugging disabled and configure upstream access logs/APM to omit query strings and sensitive bodies for `/auth/google/*` (especially `code`, `state`, `token`, `signature`, passwords and `handoff_token`). Application audit records exclude them; a sanitized unexpected-failure log records only its exception class.
4. Use designated test accounts on **both** websites. Check a new Google signup, an existing password account's ownership challenge, password and email verification, a subsequent Google login, and return to an intended protected page. Repeat with a destination on the other host to exercise the handoff.
5. Check Connected Accounts in `my.*` Settings: connect a different Google email to a password account, disconnect it, and confirm the primary email/password/data are retained. For a Google-only test account, set a password and use that password in the existing iOS app. Verify cancellation, wrong passwords, an expired email link and a previously consumed link.

Real Google consent, credentials, browser cookies across the production hosts, notification delivery and production MySQL locking remain deployment smoke checks. No production credentials, users, mail or migrations were modified during implementation.

Expired challenge/handoff rows may be pruned after their expiry under the site's normal database retention process. Do not remove provider rows to perform housekeeping. Before rolling back/removing Google sign-in, give Google-only users a way to set a password or use existing password recovery; their undisclosed random initial password cannot be used to sign in. The migration's `down()` drops only its four new tables and leaves users/repertoire/billing data intact.

## Account behavior and security

- A connected Google subject (`sub`) signs in its existing user ID. A changed Google email updates only the connection's email, never the primary PianoLIT email.
- A verified Google email matching an existing unconnected account creates a pending challenge. It never automatically links or signs into the account by email. Confirm with the existing PianoLIT password or a signed, single-use email link. Challenges expire after 15 minutes, are bound to the initiating browser and host, and stop working after the account's primary email changes. Opening an email link displays a confirmation form; a scanner's GET does not consume it.
- Password/email confirmation forms carry the specific attempt UUID and must match the current browser-bound challenge. An older tab cannot verify or send email for a newer attempt, even if the two accounts use the same password. The UUID is a stale-form check; session proof and account ownership verification remain mandatory.
- New Google users use `User::create`, the existing `Registered` listeners/admin notification and the usual `web`/`webapp` signup origin. Their Google-verified email is recorded as verified. They get ordinary access, with no paid subscription or super-user grant.
- The legacy password column remains NOT NULL and the User/API payload is unchanged. Google-only accounts initially receive the hash of a cryptographically random password that is never disclosed or retained in plaintext. Its separate provider-row hash marker identifies the absence of a usable local password. Secure password setup clears the marker; the existing password reset flow also establishes a usable password because it replaces the original hash. Existing passwords are never replaced by linking Google.
- Connecting/disconnecting and setting a first password require recent account confirmation. Password confirmation, browser-bound single-use email confirmation, or a fresh callback for the already connected Google subject provide it. Proof is scoped to the account and current password hash, lasts 10 minutes, and is checked again under the user row lock. A remember-me restoration does not count as recent confirmation.
- After the migration, changing a web profile's email/password also requires recent confirmation. This closes a bypass through the older profile form: a stolen long-lived session must not be able to replace the email/password and then use those replacements to authorize Google linking. The existing fields remain; after confirmation users re-enter/save their changes, with no pending plaintext password stored. Ordinary profile-name/preferences changes and legacy mobile password/reset handlers are unchanged. Google-only first-password writes are directed to the dedicated secure form.
- Disconnecting is a CSRF-protected DELETE, leaves the session/account/data intact and is refused while Google is the only working sign-in method. A disconnected subject cannot directly log in again; a matching email must complete a new ownership challenge.
- Authorization requests use only `openid email profile`, random state/nonce, a 10-minute state lifetime and PKCE S256. Server exchange requires the same host/session/account context and consumes state atomically. Tokens are never persisted. Only RSA SHA-256 signatures from Google's fixed HTTPS certificate endpoint are accepted, with RSA keys of at least 2048 bits. Issuer, client audience/authorized party, expiry/issued-at/not-before, nonce, subject and boolean verified-email status are checked. Redirected token/certificate HTTP responses are not followed.
- Sensitive routes have host boundaries, no-store responses, no credential logging, clear cancellation/error messages and rate limits per route plus per target account for ownership attempts. Session IDs regenerate after authentication/password setup.
- Both sites keep their existing sessions. When an allowed intended destination uses the other host, a 60-second transfer first binds that host's session, then returns to the authenticated source session for approval. Its credential is sent only in an HTML POST body and must match the stored target-session fingerprint, approved source Origin and an unused database row. The lookup UUID in navigation URLs is insufficient to authenticate. This one POST route has a scoped CSRF-middleware exception because the two sites have separate CSRF tokens; its Origin, credential and two session checks provide the transfer's forgery protection. Other new POST/DELETE routes retain normal CSRF protection. The transfer page exposes only the source origin as referrer, preserving the POST Origin header while withholding paths/query strings.
- Handoff conveys sign-in, not permission to change connected accounts. Existing host-only/shared-session and logout behavior remain as configured. Intended destinations are restricted to the two configured canonical hosts, with matching schemes and no userinfo/ports/auth loops.

## Dependency decision

Socialite was evaluated against Laravel 8. Its stable secure JWT dependency chain conflicts with the locked Google storage stack: `league/flysystem-cached-adapter` requires PSR Cache 1, while Google Auth releases supporting JWT 7 require PSR Cache 2/3. Composer also reports an advisory against the old JWT releases. Automatic approval review rejected disabling security blocking. The trial dependency changes were fully reverted; `composer.json`/`composer.lock` and installed packages match their originals.

To preserve the expressly frozen iOS/media integration, this feature uses existing Laravel HTTP and platform OpenSSL for a narrow Google authorization-code/ID-token flow. It does not call the older JWT library or Google storage authentication code. A future staged storage/dependency upgrade can migrate this flow to Socialite with the same account-linking and API regression tests.

## Verification and file inventory

Commands:

```sh
php -d error_reporting=8191 vendor/bin/phpunit -c phpunit-review.xml tests/Review/GoogleAuthenticationTest.php
php -d error_reporting=8191 vendor/bin/phpunit -c phpunit-review.xml --filter 'GoogleAuthenticationTest|CampaignAttributionTest|MobileActivityTest|SecurityRegressionTest|PieceContentAccessTest|ScoreAnnotationsTest|AdminSubdomainTest|FavoritesRegressionTest|FolderRequestsRegressionTest'
node tests/js/google-auth.js
npm run test:js
git diff --check
```

Google tests use temporary RSA keys, mocked HTTPS responses, fake notifications, in-memory SQLite and the isolated review bootstrap; no Google credentials are required. They cover valid/invalid signatures and claims, PKCE/state replay, linking proof and data preservation, both host callbacks/return destinations, handoff/session/Origin/replay checks, first password setup, existing password recovery, direct legacy iOS login/registration payloads/statuses, cancellation, rate limits, CSRF, uniqueness/rollback and migration safety. Browser QA uses rendered Blade/shipped CSS with synthetic accounts on a temporary localhost server.

Final local results on 2026-10-13: the Google-focused suite passes **41 tests / 435 assertions**; the combined regression command passes **121 tests / 5,061 assertions**. The Google JavaScript loading/back-navigation checks and scoped PHP syntax/whitespace checks pass. Twenty-seven rendered layouts at 1440, 390 and 320 pixels have no horizontal overflow; the confirmation/password screens also initialize the shipped application JavaScript without console errors. The full JavaScript runner currently fails an unrelated search-label assertion (`We found 24 results` versus `24 results`), reproducing with the Google test removed. That concurrent search issue is outside this change and remains a release-check limitation. These results do not establish real Google consent/mail delivery or production MySQL concurrency.

Files added:

- `app/Auth/Google/{Accounts,Challenges,Configuration,IdTokenVerifier,Provider}.php`
- `app/Http/Controllers/Auth/{GoogleController,GoogleSecurityController,GoogleHandoffController}.php`
- `app/Http/Middleware/GoogleAuthenticationBoundary.php`
- `app/Listeners/Users/RecordRecentPasswordLogin.php`
- `app/Notifications/User/ConfirmGoogleAccount.php`
- `routes/google-auth.php`
- `database/migrations/2026_10_13_200000_create_google_authentication_tables.php`
- `resources/views/auth/google/{layout,button,logo,confirm,email-confirm,password,accounts,handoff,error}.blade.php`
- `tests/Review/GoogleAuthenticationTest.php`, `tests/js/google-auth.js`, this document.

Existing files changed:

- `app/Providers/RouteServiceProvider.php`, `app/Providers/EventServiceProvider.php`
- `config/services.php`
- `app/Http/Controllers/UsersController.php` (web-only recent confirmation for sensitive profile updates and first-password redirect for Google-only accounts)
- `resources/views/auth/{modal,web/login,web/register,webapp/login,webapp/register}.blade.php`
- `resources/views/users/profile/tabs/profile.blade.php`, `resources/views/webapp/settings/index.blade.php`
- `docs/CODE_REVIEW.md`
- `tests/js/regressions.js` (adds the Google loading/back-navigation checks to the existing runner).

`app/User.php`, API controllers/routes/guards, existing password reset/verification handlers, membership/access checks, media delivery, dependency files and session configuration are unchanged.
