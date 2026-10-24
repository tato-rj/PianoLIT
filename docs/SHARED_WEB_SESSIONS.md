# Shared browser sessions

Implemented locally on 2026-10-24. Production has not been connected to, configured or deployed. This uses the existing Laravel users, password broker and server-side sessions; no identity server, new database, migration or package is required.

## Behavior

The main website, `www`, `my`, `learn`, `shop`, `blog`, `games` and `metronome` use one web login. Sign in on any participating host and subsequent requests on the others recognize the same account. Logout applies to all of them in that browser profile. Already-open pages update on navigation/reload; an old form can require reloading after login/logout rotates the CSRF token.

The first visit after enabling uses a new session-cookie name. Users sign in once again; old independent host sessions are not merged or selected arbitrarily. Remember-me also has a new name, so an old host cookie cannot silently sign a different account back in after shared logout. Existing Laravel remember-token behavior and duration are retained.

Administration keeps its original host-only session, CSRF token and `admin` guard. Public login/logout does not authenticate or log out an administrator. Authorized admin impersonation opens a separate public web session without copying admin session data.

Login, registration, recovery, provider/account-choice views, provider verification and account records remain the existing Laravel implementation. Google/Apple keep their existing supported entry hosts and callback addresses; successful login now shares the session instead of transferring it to a second session. Apple's cross-site callback POST remains stateless, with the original state-checked GET completion. This change does not enable a provider disabled in the environment, add email verification, or alter provider-console settings.

`/api/*` and native registration retain their original guard, cookies and JSON contracts. The installed iOS app does not adopt browser session sharing and requires no change for this feature.

## Configuration

The feature defaults to **off** in source. These values are enabled only in the local `.env`:

```dotenv
SHARED_WEB_SESSIONS=true
SHARED_SESSION_DOMAIN=pianolit.test
SHARED_SESSION_LOCK_STORE=file
SESSION_DRIVER=file
```

Keep `SESSION_DOMAIN` unset. Setting that globally would also share administration's session. The parent domain must exactly match the normalized `APP_URL` host. Local HTTP works with the existing local cookie setting; `APP_ENV=production` requires HTTPS and forces Secure on the shared session and remember cookies.

For a future approved release, use the production parent domain (`pianolit.com`) and consistent configuration on every participating host. Do not enable this on only part of a mixed application release. Every host needs the same application/key and access to the same session backend and atomic lock store. File sessions/file locks suit this local, single-filesystem installation. Multiple application servers require a shared session backend and shared locks, such as Redis, not separate local file stores. PHP requests must finish within the 120-second lock lease; a competing request waits up to 15 seconds and then receives a retryable 503 without replacing the session cookie. This implementation targets the existing PHP-FPM request lifecycle, not a new long-running Octane deployment.

No new provider credentials, tunnel, DNS change or purchased service is needed. All production hosts must already serve valid HTTPS before activation. Confirm that **every subdomain is controlled and trusted**: browsers send a parent-domain cookie to all children, even though this application's participating-host list is narrower. Do not host untrusted or independently operated applications beneath the same parent domain. This is an inherent property of parent-domain cookies, not something a Laravel allowlist can prevent.

## Cookie and concurrency boundaries

| Cookie | Scope | Purpose |
| --- | --- | --- |
| `pianolit_web_session_v1` | Parent domain, `/`, HttpOnly, SameSite=Lax | Shared public/web-app Laravel session |
| `pianolit_web_remember_v1` | Parent domain, `/`, HttpOnly, SameSite=Lax | Existing Laravel remember-me behavior |
| Original session/admin remember cookies | Original host-only scope | Independent admin/native behavior |
| `XSRF-TOKEN` | Host-only | Same-host form/AJAX CSRF compatibility |
| Campaign/analytics and other queued cookies | Host-only | Preserve existing host-specific consent/attribution boundaries |

Session writes use Laravel's atomic session blocking so simultaneous requests from different hubs cannot overwrite each other's state. When login/logout rotates a session, its retired ID is marked in the lock store for the session lifetime. A request queued with that old ID receives 419 with no session cookie, rather than recreating the retired session and overwriting the new login. The old ID never forwards to an authenticated session. JSON clients receive a reload message; HTML has an explicit sign-in-again link if a browser lost the response containing its new cookie. The recovery link creates a fresh session only when the supplied session is retired. Do not routinely clear this lock/retirement store during active requests.

Normal session expiry, token rotation, same-host form actions and CSRF validation remain. This does not synchronize rendered DOM across tabs, guarantee delivery/order of network responses, or replace the browser's normal cookie behavior.

## Verification and remaining environment checks

- New tests use real encrypted response cookies, temporary file sessions and file locks, with independent guard/store instances per request. They cover all eight hosts, previsited hubs, logout, remember-me, old-cookie conflicts, admin isolation/impersonation, CSRF, password reset, registration, native login/registration, production cookie requirements, retired-session recovery and busy-session responses. Independent forked workers prove that concurrent writes serialize.
- Existing Google/Apple tests remain, with additional full verified-provider callback tests under shared sessions. Provider responses/keys are synthetic; no real provider sign-in was performed for this change.
- Full isolated suite: **1,650 tests / 43,205 assertions / 35 failures / no errors**. The 35 failure names exactly match the pre-change rollback baseline. The final busy-session handling refinement is verified separately in the focused final run.
- Final focused run: **165 tests / 5,367 assertions, all passing**, covering shared sessions plus Google/Apple authentication, admin boundaries, campaign attribution and web activity. PHP syntax and diff whitespace checks pass.
- Actual local browser: signed in with an existing synthetic account on Learn; verified main, My (including Settings), Shop, Blog and Games without another login. Logged out on Shop and confirmed all six show guest state after reload. The original login views remain.
- Local Metronome HTTPS was repaired and browser-verified on 2026-10-24. The expired component was Valet's CA (expired in 2023); its renewal retains the same key. Metronome's replacement server certificate adds the required server-authentication EKU and expires on 2027-10-24. Both pass macOS SSL policy and live TLS validation. Browser checks confirm Learn login → HTTPS Metronome, main logout → Metronome guest, Metronome login → main/My, and Metronome logout → main/My guest. No certificate warning was bypassed. The follow-up shared-session/Metronome run passes **26 tests / 246 assertions**. Backups, installer and evidence: `/Users/arthur/.codex/recovery/pianolit-metronome-tls-20261024-135407/`.
- Real Apple/Google accounts, Safari and the installed iOS app were not retested. No billing, payment, email-send or production mutation was used for verification. Automated tests use the isolated review configuration; no new frontend assets need building.

Private local evidence and the pre-enable environment backup are in `/Users/arthur/.codex/recovery/pianolit-shared-sessions-20261024-083622/`. Credentials are not stored in this repository.

## Disable locally / future rollback

Set `SHARED_WEB_SESSIONS=false` and refresh the application's configuration through the existing local process. No database rollback or data deletion is needed. This returns to the original per-host session names; pre-existing host cookies may still represent an old login. A production rollback therefore needs a deliberate browser-session transition, not repeated live toggling. Do not flush shared Redis or revoke users' credentials as part of this change.

References: [Laravel 8 session blocking and stores](https://laravel.com/docs/8.x/session#session-blocking), [cookie Domain, Secure and SameSite behavior](https://developer.mozilla.org/en-US/docs/Web/HTTP/Reference/Headers/Set-Cookie).
