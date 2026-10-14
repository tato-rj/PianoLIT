# Sign in with Apple

Apple extends the existing Google web authentication infrastructure on `pianolit.com` and `my.pianolit.com`. Both providers use `App\User`, the web guard, the same four authentication tables, ownership challenges, recent authentication, first-password setup, Connected Accounts and cross-host browser handoff. Existing Google route names and OAuth configuration remain intact. Apple is hidden until its credentials, protected signing key and new migration are present.

## Apple Developer configuration

Use an Apple Developer account with permission to manage Certificates, Identifiers & Profiles. Configure web authentication only; the existing iOS application continues using its current email/password API.

1. Open **Certificates, Identifiers & Profiles → Identifiers → App IDs**. Select the appropriate existing PianoLIT app identifier, enable **Sign in with Apple**, configure it as a **primary App ID** and save. Use the actual bundle identifier from the developer account; the numeric App Store ID is not an App ID or Team ID. [Apple's App ID guidance](https://developer.apple.com/help/account/capabilities/about-sign-in-with-apple/)
2. In **Identifiers**, click **+ → Services IDs → Continue**. Enter a description and a unique web Services ID, register it, then open it. Its identifier becomes `APPLE_CLIENT_ID`.
3. Enable **Sign in with Apple → Configure** and select the primary App ID from step 1. Register these domains/subdomains: `pianolit.com` and `my.pianolit.com`. Register both exact **Return URLs**:

   ```text
   https://pianolit.com/auth/apple/callback
   https://my.pianolit.com/auth/apple/callback
   ```

   Click **Done → Continue → Save**. These are POST endpoints. Current Apple web configuration does not require uploading a domain-association file. Do not add `.test`, localhost, IP-address or HTTP production return URLs. [Apple's Services ID instructions](https://developer.apple.com/help/account/capabilities/configure-sign-in-with-apple-for-the-web/)
4. Open **Keys → +**, name the key, enable **Sign in with Apple → Configure**, associate the same primary App ID, then register/download the `.p8` key. Record its ten-character **Key ID**. Keep the original download securely; never put it in Git, `public/`, frontend code or an API response. [Apple's key instructions](https://developer.apple.com/help/account/capabilities/create-a-sign-in-with-apple-private-key/)
5. Sign in to your Apple Developer account and open its membership details to find the ten-character **Team ID**. Use the Team ID belonging to the primary App ID and key, rather than the Services ID or Key ID.
6. Install the key as a protected server file outside the web root, for example `/etc/pianolit/apple/AuthKey_KEYID.p8`. Give only the deployment owner and PHP worker account/group the required read access (for example, directory mode `0750`, key mode `0640` with the appropriate group). The actual PHP-FPM user/group must be checked on the server; do not make the key world-readable. `.gitignore` also excludes `.p8` files. The application rejects signing keys located under its public directory or public storage disk, including resolved symlinks.
7. Configure **Services → Sign in with Apple for Email Communication → Configure**. Register the actual transactional sender domains/subdomains or sender addresses used by the application's mail transport. Register the exact envelope sender domain for SPF, and configure DKIM correctly when the mail provider uses its own envelope sender. Keep both SPF and DKIM working where possible. Use the actual `From`/Return-Path/DKIM values supplied by the mail service, without guessing them from the website hostname. Test confirmation and password-reset mail to a designated Apple private-relay address. No domain verification file is required for this registration either. [Apple's private relay instructions](https://developer.apple.com/help/account/capabilities/configure-private-email-relay-service/)

Enabling the web Services ID does not add Apple login to the installed iOS binary. This implementation does not change iOS endpoints, guards, tokens or response fields, and does not request an iOS build/update.

## Server environment

```env
APPLE_CLIENT_ID=your-web-services-id
APPLE_TEAM_ID=YOURTEAMID
APPLE_KEY_ID=YOURKEYID
APPLE_PRIVATE_KEY_PATH=/etc/pianolit/apple/AuthKey_YOURKEYID.p8
APPLE_REDIRECT_URI=https://pianolit.com/auth/apple/callback
APPLE_WEBAPP_REDIRECT_URI=https://my.pianolit.com/auth/apple/callback
```

The last two settings are optional overrides, defaulting to the exact callbacks above from the canonical hosts derived from `APP_URL`. An override must exactly match its host's callback. Retain `APP_URL=https://pianolit.com`, existing session/cookie settings and trusted-proxy HTTPS handling. All production Apple authentication routes require HTTPS.

No static `APPLE_CLIENT_SECRET` is needed. `ClientSecret` reads the protected P-256 key and signs a fresh ES256 JWT for each authorization-code exchange, with a one-hour expiration. It uses the configured Team ID, Services ID and Key ID. This automatically renews the client secret without a scheduler and remains within Apple's maximum expiration. Key revocation/rotation still requires installing the replacement key and updating its ID. [Apple's client-secret specification](https://developer.apple.com/documentation/accountorganizationaldatasharing/creating-a-client-secret)

Keep PHP OpenSSL/mbstring available and server clocks synchronized. Allow outbound server HTTPS to `https://appleid.apple.com/auth/token` and `https://appleid.apple.com/auth/keys`; the browser navigates to `https://appleid.apple.com/auth/authorize`. Public keys are cached for 30 minutes, with a refresh for an unknown key ID. Fixed endpoints use TLS verification, bounded timeouts and no redirects.

## Additive schema and dependencies

`2026_10_13_220000_support_apple_authentication_providers.php`:

- Makes `user_auth_providers.provider_email` nullable so a first connection can lack an Apple-supplied email. Later missing emails never overwrite a saved provider email.
- Adds nullable `auth_link_challenges.provider` as the final change. New challenges explicitly record `google` or `apple`. A NULL legacy challenge is interpreted only as Google; Apple never accepts it. The old session namespace, browser secret, account email snapshot, expiry, attempt UUID and single-use checks remain mandatory.
- Retains all four deployed table names, identity/user uniqueness constraints, original user IDs/data and the original Google migration. It does not modify the users table or rewrite provider subjects, Google connections, passwords or API tokens.
- Refuses rollback while Apple provider/challenge rows or nullable provider emails exist, avoiding lost metadata or reinterpretation of Apple challenges as Google. Disable Apple entry points and plan recovery before rolling back deployed functionality; do not delete authentication records as a shortcut.

Laravel 8 requires Doctrine DBAL for supported column changes. Added `doctrine/dbal` 3.10.6, `doctrine/deprecations` 1.1.6 and `doctrine/event-manager` 1.2.0, all supporting the production server's PHP 8.0. Every pre-existing locked package remains unchanged. There is no new OAuth/JWT/Apple SDK dependency; token cryptography uses existing Laravel HTTP and OpenSSL. Run `composer install` from the updated lock file, rather than a blanket update. SQLite uses DBAL's column-change emulation in the disposable review database; production MySQL uses an ALTER operation and was not executed here.

The local install skipped only the PHP platform check because a pre-existing locked Cloudinary dependency excludes the Mac's PHP 8.3. The added packages were independently resolved for PHP 8.0 and their PHP constraints verified. Do not copy that local workaround into production deployment.

## Callback, linking and account behavior

Apple requests `code`, `form_post`, `name email`, a random state and nonce. The initial request binds the transaction to the host, original web session and current account. Apple returns to `/auth/apple/callback` by cross-site POST, which may omit the normal SameSite session cookie.

Only that callback POST excludes session, cookie-queue, campaign-session and CSRF middleware. It validates a short-lived state/host record, rejects a supplied foreign Origin, bounds input shapes/sizes and records the return once in the existing application cache. The authorization code is encrypted in this cache for at most ten minutes. The POST cannot authenticate, link an account, create a user, consume the OAuth authorization or issue a replacement session cookie.

The response initiates a same-host GET to `/auth/apple/complete/{attempt}` and provides an ordinary Continue link as a fallback. That GET restores the original session and requires its session HMAC, OAuth state, nonce, host and unchanged initiating account. The receipt UUID alone is not a credential. State consumption is atomic and single-use. Only then does the server exchange the code and validate Apple's RSA signature, issuer, exact Services ID audience, timing, subject and nonce. An email is used only if it is present and explicitly verified in the signed token. Browser-supplied email and identity tokens are ignored. Display-name hints are sanitized and used for new accounts only.

All other Apple writes retain normal web sessions and CSRF. The existing Google POST browser handoff keeps its previous scoped exception and both-session/Origin checks. Session configuration is unchanged; no global SameSite or CSRF relaxation is needed. The cache must support atomic add operations and be shared across servers handling one flow, as already required for Google replay protection. Do not flush Redis to deploy authentication.

- An existing Apple subject signs in the same user without requiring email or name on later authorization. Its stored provider email and PianoLIT name remain intact when absent.
- A new Apple subject with a verified email matching a PianoLIT account enters the existing ownership challenge. A password or signed, expiring, same-browser email confirmation is required before linking. No account is merged or authenticated solely by an email match.
- An unfamiliar email, including Hide My Email, first offers **I already have a PianoLIT account** and **Create a new PianoLIT account**. Entering an existing PianoLIT email starts that account's shared ownership challenge. No duplicate account or signup event is created before the user explicitly chooses registration.
- A new subject without an email uses a signed, expiring, same-browser email-completion flow. GET does not consume proof; CSRF-protected POST is single-use. The primary email must be verified before registration. The Apple provider email remains NULL when Apple did not supply it; no fake address is invented. A matching existing primary email is directed into ownership linking rather than duplicate registration.
- New accounts reuse `User::create`, `Registered` listeners, newsletter signup, normal access and server-derived web/webapp origin. The undisclosed random-password marker preserves the legacy NOT NULL password schema. It is carried across every connected provider so disconnecting one cannot misclassify that password as usable.
- Google and Apple can coexist on one user. Connection conflicts are rejected under transaction/uniqueness constraints; primary emails, existing passwords, Google links, subscriptions, favorites, folders and history are retained.
- Connection/disconnection and first-password setup require recent proof. Either connected provider can confirm the account. Disconnection refuses the last available login method, including when the other provider is temporarily unconfigured. Local disconnection unlinks the PianoLIT association; it does not delete the account or automatically revoke the consent grant in Apple Account settings.
- First-password setup clears all unusable-password markers and uses the existing password-based iOS endpoint. Google and Apple sign-ins enable the existing long-lived Remember me behavior, including handoff; browser cookie limits still apply.
- Audits explicitly name their provider for linking, disconnection, verification and failures. The shared first-password operation retains the provider of its ownership proof.

## Deployment and designated-account checks

Do not deploy automatically. Before enabling Apple:

1. Review/deploy the added and modified files together and run the usual backup process.
2. Install the lock file's dependencies with the normal production command, such as `composer install --no-dev --prefer-dist --optimize-autoloader`.
3. Apply `2026_10_13_220000_support_apple_authentication_providers.php` with the normal migration process. The original Google migration must already be present. Apply the additive migration before adding Apple credentials; Google/password login can continue without Apple enabled.
4. Configure the Services ID, both exact return URLs, private-relay senders, protected key and environment values above.
5. Rebuild configuration/views/routes through the normal deployment process (`php artisan config:cache`, `php artisan view:clear`, and refresh route caching if used). Reload the existing PHP 8.0-FPM service through the server's normal process. No new queue or scheduled job is required; verification uses the existing mail transport.
6. Keep upstream access logs/APM from recording query strings or sensitive bodies under `/auth/google/*` and `/auth/apple/*`, including codes, state, callback user JSON, passwords, signed email tokens, handoff credentials, client secrets and private-key material. Application errors/audits omit these values.
7. Use designated accounts to check both sites: new registration, existing email matching/password and email linking, Hide My Email linking, later login without a name, Google-plus-Apple settings, conflicting identity, last-method refusal, first password in the unchanged iOS app, cancellation, and a destination on the other host. Check relay email delivery and re-test the production Google flows.

Real Apple consent, Developer eligibility/configuration, provider endpoints, private-relay mail, production browser cookie behavior and MySQL concurrency remain manual deployment checks. No real credentials, normal database migrations, live account writes or mail were used here. Apple access/refresh/identity tokens are not retained after validation. Provider revocation notifications, token refresh/revocation and account-transfer lifecycle processing are separate from this local web sign-in/linking feature.

## Verification and file inventory

Focused and combined test commands:

```sh
php -d error_reporting=8191 vendor/bin/phpunit -c phpunit-review.xml tests/Review/AppleAuthenticationTest.php
php -d error_reporting=8191 vendor/bin/phpunit -c phpunit-review.xml --filter 'AppleAuthenticationTest|GoogleAuthenticationTest|CampaignAttributionTest|MobileActivityTest|SecurityRegressionTest|PieceContentAccessTest|ScoreAnnotationsTest|AdminSubdomainTest|FavoritesRegressionTest|FolderRequestsRegressionTest'
node tests/js/apple-auth.js
node tests/js/google-auth.js
npm run test:js
git diff --check
```

Results and visual checks are recorded in the dated Apple entry in `docs/CODE_REVIEW.md`. Tests use temporary RSA/P-256 keys, fake Apple HTTPS/mail and disposable in-memory SQLite; their key files are removed after each test. No credentials are required.

Added files:

- `app/Auth/Apple/{Configuration,Jwt,ClientSecret,IdTokenVerifier,Provider}.php`
- `app/Http/Controllers/Auth/AppleController.php`, `routes/apple-auth.php`
- `database/migrations/2026_10_13_220000_support_apple_authentication_providers.php`
- `resources/views/auth/apple/{button,logo,return,choose,signup-email}.blade.php`
- `resources/images/auth/apple-logo-{black,white}.png` and matching `public/images/auth/` outputs, downloaded from [Apple's logo button endpoint](https://developer.apple.com/documentation/signinwithapplerestapi/get-a-sign-in-with-apple-button-that-contains-just-the-apple-logo.) without modifying the artwork
- `tests/Review/AppleAuthenticationTest.php`, `tests/js/apple-auth.js`, this document

Modified files:

- `app/Auth/Google/{Accounts,Challenges}.php` — provider parameters default to Google; shared ownership/password logic
- `app/Http/Controllers/Auth/{GoogleController,GoogleSecurityController,GoogleHandoffController}.php` — explicit auditing, shared settings/verification, provider-aware proof and existing handoff
- `app/Http/Middleware/GoogleAuthenticationBoundary.php`, `app/Notifications/User/ConfirmGoogleAccount.php` — shared web response/email behavior
- `app/Providers/RouteServiceProvider.php`, `config/services.php` — additive routes/configuration
- `resources/views/auth/google/{accounts,button,confirm,email-confirm}.blade.php` — shared controls and verification views
- `.gitignore`, `composer.json`, `composer.lock`, `tests/js/regressions.js`, `docs/GOOGLE_LOGIN.md`, `docs/CODE_REVIEW.md`

The existing Google Provider, IdTokenVerifier, Configuration, routes and deployed migration remain unchanged. No task changes were made to `App\User`, existing auth API controllers/routes/guards, password reset or traditional registration/login handlers, session config, membership/access or mobile media delivery. Concurrent unrelated work was preserved.
