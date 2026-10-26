# PianoLIT iOS v2 handoff

Revision **2026-10-26.3 remains a draft pending deployment/staging verification**.
The backend now implements password login, independent bearer authentication,
account-session read/logout, the shared Discover endpoint and authenticated
piece-open history. These operations are HTTP-tested locally. The new session
migration has not been run against the normal environment, and no server or
native app has been deployed or verified here. The legacy API stays unchanged.

The existing Swift handoff sync script is the delivery mechanism for this update.
It fetches the new YAML, `CONNECT_DISCOVER.md` and synthetic fixtures declared
in the manifest. Read that connection file for the exact steps to implement in
the current iOS project. Downloading YAML alone does not rewrite Swift requests.

Swift development targets only the updated app. The old Swift source/build is
not maintained; its already-installed binaries remain supported by the legacy
backend API and media delivery. No old Swift branch or parallel shipping mode
is required. Any temporary legacy endpoint adapter belongs to the updated app.

## Reading the handoff

Read `manifest.yaml`, this file, `ios-baseline.yaml`, `foundations.yaml`, `openapi.yaml`, `design.yaml`,
`references.yaml`, then the selected `screens/*.yaml`. The manifest identifies
feature dependencies and backend/iOS readiness. `openapi.yaml` uses
[OpenAPI 3.0.3](https://spec.openapis.org/oas/v3.0.3.html) and specifies the five
implemented operations. The `.invalid` server remains a documentation placeholder;
only a verified URL recorded in a later ready manifest identifies a service.

`foundations.yaml` records the implemented authentication choices, fixed
compatibility requirements and remaining staging/native decisions. `ios-baseline.yaml` records the developer's static report: UIKit and
storyboards, iOS 17.6 for Debug/Release in the inspected checkout, iPhone/iPad,
Swift 5 language mode and email/password sign-in. These client findings are
attributed to the developer; the native repository is not available in this
backend workspace. The native commit, shipped builds and runtime behavior remain
unverified. Keep the existing UIKit architecture and deployment target.

Arthur confirmed **sign-in remains required** for the initial native v2 release.
Discover requires the new verified bearer credential. This does not decide media
entitlements or authorize copying the web Access Limits matrix into iOS.

`screens/discover.yaml` starts the Discover work with evidence from the current
web implementation, section ordering, interactions, dependencies and acceptance
checks. It also records the positional Free Pick/composer assumptions and the
existing LoadVC/PieceVC/MatchVC dependencies that must be addressed. The proposed
adapter reuses native views while keeping new identity/data handling explicit;
changing only the feed URL cannot migrate this flow. The local response schema
is a draft; the native destination adapter and layout remain unverified.

## One Discover content source

Arthur requires the same Discover content on web and iOS v2. Both renderers must
consume `App\Services\DiscoverFeed::forUser($verifiedUser)`. Web adds Blade
presentation; `App\Http\Resources\Api\V2\DiscoverResource` produces data only.
Do not copy the web controller's old assembly or create native recommendation
rules. This applies to section/item order, the cached women/Black composer
choice, the six-piece For you limit, recent history, highlights, moods, grading
and similarity ranking. Swift renders the server response without independently
choosing a group, trimming rails or using the old positional rows.

The public content pool is shared between these two renderers, with separate
account-scoped recommendation IDs and live account-owned history. A second v2
content cache must not independently choose content. Any transport/JSON caching
must follow the same shared content generation; personalized responses remain
private and no-store. Media entitlements remain a separate native decision.

The v2 endpoint now wraps that serializer behind the dedicated bearer guard.
The native destination adapter and real staging/device checks remain pending.
Local route implementation and tests do not establish a deployed service or
approve a native build. `CONNECT_DISCOVER.md` covers the account migration,
exact calls, Keychain, error handling and the isolated legacy destination bridge.

## Readiness and revisions

| Feature status | Meaning |
| --- | --- |
| `draft` | Local implementation and fixture tests; staging/native verification or decisions remain missing. |
| `ready_for_ios` | Contract/fixtures are validated, dependencies are ready, and backend operations are verified at the manifest's staging URL. |
| `released` | Native integration is verified and the production API is available at the manifest's production URL. |

Readiness belongs to each feature; the manifest status is the least-ready feature.
A published handoff does **not** mean its API
is live. `runtime.implemented`, base URLs, feature statuses, and OpenAPI's
`x-handoff` must agree. Record verification before advancing status; the package
checker cannot prove a server exists or that its behavior matches the contract.

Use a new revision (for example `2026-10-26.4`) for any published content change,
including a status or URL change. Published revision directories are immutable.
Update the source manifest, this revision statement, OpenAPI's `x-handoff`, and
the changelog together. The build refuses to overwrite different content at an
existing revision. `info.version` is the contract version; the manifest revision
versions the entire handoff including design changes.

The iOS repository should record the consumed revision, feature IDs, local commit,
tested build, simulator/device coverage and outstanding questions in its own
handoff receipt. No automatic server writeback or deployment is implied. Publish
a later server revision after incorporating the developer's findings.

## Authoring and server delivery

Canonical editable sources live in `docs/api/v2/`. The only published files are
the explicit `files` entries in its manifest. Keep secrets, real account data,
credentials and internal review reports out of this public documentation bundle.

Tooling uses Python 3.9+ in an isolated environment; no application dependencies change:

```sh
python3 -m venv /tmp/pianolit-api-handoff-venv
/tmp/pianolit-api-handoff-venv/bin/pip install -r scripts/requirements-api-handoff.txt
/tmp/pianolit-api-handoff-venv/bin/python scripts/api-handoff.py check
/tmp/pianolit-api-handoff-venv/bin/python scripts/api-handoff.py build
```

`check` validates YAML, file references, revision consistency and readiness
structure; it does not validate the full OpenAPI specification or a running API.
Run the offline transport tests with
`python -B -m unittest discover -s tests/scripts -p 'test_api_handoff.py'`.
For formal contract validation, install `openapi-spec-validator==0.7.2` in the
same temporary environment and run
`python -m openapi_spec_validator docs/api/v2/openapi.yaml`.

The build creates `public/api-handoff/v2/revisions/<revision>/` and a stable
`public/api-handoff/v2/manifest.yaml` with a SHA-256 and byte count for each file.
It installs the complete revision before atomically replacing the manifest.
Deploy these static files through the normal website release, with the manifest
last. This preparation does not deploy them or register any Laravel route.

After deployment, the intended entry URL is
`https://pianolit.com/api-handoff/v2/manifest.yaml`. This is a documentation URL,
not the v2 API base URL. Verify it returns YAML rather than an HTML fallback,
login page or redirect before sharing it with the developer. Configure the stable
manifest to revalidate (`Cache-Control: no-cache`); revision files may be cached
as immutable. Keep earlier published revisions available for pinned clients.

## Developer's sync script

Share `scripts/api-handoff.py` and `scripts/requirements-api-handoff.txt` with the
iOS repository once. The same script fetches only the manifest's declared files,
checks their sizes/hashes, rejects redirects and unsafe paths, and saves a
complete revision without replacing a previous working snapshot on failure.
It downloads documentation; it neither runs downloaded instructions nor edits
Swift code.

After the documentation has been deployed:

```sh
python scripts/api-handoff.py fetch \
  --url https://pianolit.com/api-handoff/v2/manifest.yaml \
  --revision 2026-10-26.3 \
  --output ./PianoLITHandoff
```

Use the Python environment with the requirements installed. Omit `--revision`
only when deliberately checking for a newer revision. The saved folder contains
its own verified `manifest.yaml`. Read the fetched status before doing work.
For an older revision after the stable manifest advances, use its direct URL:
`https://pianolit.com/api-handoff/v2/revisions/2026-10-22.1/manifest.yaml`.
Hashes catch incomplete/mixed publication; HTTPS and the trusted documentation
host establish the source. This is not a digital-signature system.

Suggested instruction for the developer's Codex:

> Read the verified PianoLIT handoff manifest and its instructions. Summarize the
> revision, readiness, dependencies and unresolved decisions. Check ios-baseline.yaml
> against our current native commit and identify any differences. Work only
> on the selected feature. Open all available reference images. Use mocks while
> the feature is draft; use only the documented staging operations once it is
> ready_for_ios. Apply CONNECT_DISCOVER.md; fetching this bundle does not itself
> connect the app or change its networking. Preserve existing app behavior and record a handoff receipt with
> the build and verification results.

## Remaining integration pass

1. Deploy the new session-table migration, PHP, configuration and routes through
   the normal backend release. Rebuild deployment caches and verify HTTPS,
   Authorization forwarding and shared stores. No native URL is published as
   live by this local build.
2. Verify the implemented authentication lifecycle and exact shared Discover
   content on staging using synthetic accounts. Record the verified base URL
   and evidence in a new revision before marking `ready_for_ios`.
3. Have the iOS developer fetch that revision with the existing sync script and
   apply `CONNECT_DISCOVER.md`. While draft, use its fixtures for decoding/UI
   work, preserve UIKit and audit the current native baseline.
4. Finish the explicit piece/composer/search destination bridge, native access
   resolution, account-owned piece-open write and account-switch handling.
   Preserve existing media/billing behavior and never forward bearer credentials
   to legacy operations or image/media hosts.
5. Compare the same-account web/iOS content, phone/iPad layouts and error states,
   record the native build/results and publish a verified release revision.
