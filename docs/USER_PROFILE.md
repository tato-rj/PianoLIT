# User profile and permanent account closure

As of 2026-10-26, the browser profile lives at the absolute main website’s
`/profile`. The account menu and web-app Settings link use `users.profile`.
The `webapp.users.profile` name remains compatible but generates that same main
URL. GET/HEAD requests to `/profile` on child hosts and legacy `/my-profile`
redirect permanently to the main website, preserving the raw query string.
New profile writes stay on the main host and reject other hosts rather than
forwarding credentials. Legacy `/my-profile/{user}` form endpoints remain
session-authenticated and enforce account ownership and the new security flow.

The page uses the public header/footer, shared fields/buttons/switches and the
shared subtle background. Its four anchor sections are Profile, Security &
linked accounts, Email preferences, and Delete account. The section menu reuses
`components.navigation.section-navigation` with the Support center, including its shared
stylesheet and versioned script; profile.js only handles profile-specific behavior.
The shared menu follows
scroll position with one blue, bold `aria-current="location"` link, including
restored/deep-linked positions, resizing and the short final section. Location is explicitly
approximate metadata, not proof of signup location. Missing dates/origins/
locations are shown as Not recorded rather than inferred.

## Downloads

My downloads is a standalone page at `/profile/downloads` on the absolute main
website. Its heading and library align with the logo in the shared public container, without the profile
section menu. The account dropdown keeps separate My profile and My downloads
links. The existing `users.purchases` name generates the downloads URL, including
from child hosts and purchase emails. Legacy `/my-profile/purchases` and
child-host `/profile/downloads` GET/HEAD requests redirect permanently before
authentication, preserving the raw query string. The canonical page requires
the web session and returns private, no-store responses.

Downloads renders `users.purchases.index` independently of `users.profile.index`.
Profile keeps its four scroll-tracked anchors in `users.profile.navigation` and
has no Downloads entry. The library reuses the public shell, profile panel,
shared accessible accordions, primary file buttons and native
pagination. It keeps eight newest account-owned purchases per page, eager-loads
their resources and preserves pagination queries. Dates and costs come from
the purchase record, with null/zero cost shown as Free. Missing resources or
resources without file links have an unavailable message rather than breaking
the library. Existing `shop.download` links, encrypted file parameters and
original bytes are retained; this redesign does not change file delivery or
mobile contracts.

`ProfileDownloadsTest` verifies canonical/legacy routing, account scoping,
formats, original-file delivery, pagination, navigation and empty/unavailable
states. Set `DOWNLOADS_PREVIEW_DIR` when running it to export synthetic Blade
previews without changing the normal database.

## Security and email preferences

Name edits allow only first and last name. Sensitive edits require a recent
verified login or the current password, checked against the freshly locked
account. Provider-only accounts reuse the existing password/email/provider
confirmation flow; Google and Apple connect/disconnect retain their existing
availability and last-sign-in-method safeguards.

Email changes send `App\Mail\ProfileEmailChange` to the proposed address. The
current login/email and mailing subscriptions stay unchanged until confirmation.
The one-hour signed URL contains an encrypted account/email/password-stamp token.
GET only renders confirmation; a CSRF-protected POST from the original signed-in
account commits it. Password or email changes invalidate older links. The new
email is normalized and uniqueness is rechecked at commit. Existing subscriber
preferences move/merge only after ownership of both addresses is proved, without
`joinAll`. Subscriber IDs and their course/delivery history survive the merge,
and overlapping memberships are consolidated so each list has one recipient. Password changes rotate the hash/remember token and session ID, remove
password-reset tokens and preserve provider links. Passwords are never flashed.

Each non-Test database email list appears with its editable description, whether
or not the account already has a subscriber record. Each switch explicitly saves
its desired enabled state, so repeated requests are idempotent and join only the
selected list. PianoLIT News still requires its own explicit opt-in. An
Unsubscribe from all button appears when more than one list exists. Concurrent
list edits serialize on the authenticated account; the browser disables bulk
unsubscribe while a save is pending and restores the confirmed switch on errors.
Without JavaScript, each preference has a Save preference button.

## Closure and retained records

Deploy `2026_10_26_120000_create_closed_accounts_table.php` **before** serving the
new deletion code. It adds a separate permanent closure marker containing the
old user ID, original signup time, recognized signup platform and closure time.
It contains no name, email address, password or provider subject. It deliberately
has no users foreign key.

The account itself is hard-deleted using the existing transactional
`App\Services\Admin\RemoveUser` cleanup and membership checks. It is not a
soft-deleted authenticatable User, and introduces no shared model scopes or JSON
fields. The confirmation email, current proof and all membership sources are
checked server-side. Active/uncertain billing prevents closure; this flow does
not cancel or contact Stripe or Apple. Private favorites/folders, newsletter
subscriptions, locations, tutorial requests, recent pieces, score markings,
provider links/challenges/handoffs and metronome recordings are removed by the
existing cleanup/cascades. Password-reset tokens are also removed. A failure
rolls back both private cleanup and the closure marker.

Existing purchases (including free downloads), payments, membership/provider
billing history, activity and retained security/email reports keep their existing
records and retention rules under the retired ID. They remain internal, with no
lookup that grants access by email or restores entitlements after signup.
The web session is invalidated, the web recaller and current-host campaign
cookies are cleared, and a later signup receives a new monotonically generated
user ID. Provider identities are released too. A newly created profile cannot
access the previous account’s purchases, downloads, activity or saved content.
Existing native API routes, JSON contracts and public media URLs are unchanged.
Admin Delete/Purge remain separate existing workflows.

### Retention policy still needs a decision

This is an account-lifecycle implementation, **not a claim that the retained data
is anonymous or may legally be kept forever**. Old account IDs, fine-grained
activity and links through payment providers are pseudonymous personal data.
Imported activity payloads may also contain historical identifying information;
this change does not rewrite those records, backups or third-party systems.

Before production rollout, specify and document a lawful purpose and retention
period for identifiable transaction/security/usage records, align the privacy
policy, and implement the resulting expiry/anonymization process. Keep only
necessary accounting/legal records for the applicable period. For analytics that
must last indefinitely, prefer genuinely anonymous aggregates and assess
re-identification risk; removing the name or hashing the email alone does not
make per-person history anonymous. Existing three-calendar-month email report
retention and analytics exclusions continue to apply.

Sources: [EU personal-data principles](https://commission.europa.eu/law/law-topic/data-protection/information-business-and-organisations/principles-gdpr_en),
[EDPB anonymisation and pseudonymisation](https://www.edpb.europa.eu/topics/ai-and-technology/anonymisation-pseudonymisation_en),
[GDPR Articles 5 and 17](https://eur-lex.europa.eu/eli/reg/2016/679/oj/eng/).

## Verification

Use `php -d error_reporting=8191 vendor/bin/phpunit -c phpunit-review.xml` for the
isolated regressions, and `node tests/js/profile.test.cjs` for switch retry/error
handling. `npm run production` rebuilds/version-controls the profile script and
styles and checks Sass and copied assets. No real account deletion, billing
mutation or email delivery is required for local verification. Browser layout
checks use a synthetic profile rendered from the in-memory review database.
SQLite does not establish MySQL locking behavior; verify competing preferences,
email changes, registrations and closure on disposable MySQL before deployment.
Shared web-session production prerequisites remain in `SHARED_WEB_SESSIONS.md`;
when that opt-in is disabled, moving from a child host can require another login.
