# PianoLIT iOS v2 handoff

Revision **2026-10-26.1 remains a draft**. The shared Discover content service
and its v2 JSON resource are implemented locally. No v2 endpoint, credential,
database migration or native app change is implemented. The existing API stays
unchanged. Authentication, routing and staging/native verification remain
required before the new Discover response can be served to an app.

## Reading the handoff

Read `manifest.yaml`, this file, `ios-baseline.yaml`, `foundations.yaml`, `openapi.yaml`, `design.yaml`,
`references.yaml`, then the selected `screens/*.yaml`. The manifest identifies
feature dependencies and backend/iOS readiness. `openapi.yaml` uses
[OpenAPI 3.0.3](https://spec.openapis.org/oas/v3.0.3.html); its `paths` is
intentionally empty until operations are specified. The `.invalid` server is a
documentation placeholder, not a service to call.

`foundations.yaml` separates fixed compatibility requirements from proposed
decisions. `ios-baseline.yaml` records the developer's static report: UIKit and
storyboards, iOS 17.6 for Debug/Release in the inspected checkout, iPhone/iPad,
Swift 5 language mode and email/password sign-in. These client findings are
attributed to the developer; the native repository is not available in this
backend workspace. The native commit, shipped builds and runtime behavior remain
unverified. Keep the existing UIKit architecture and deployment target.

Arthur confirmed **sign-in remains required** for the initial native v2 release.
Discover will require a verified bearer credential. This does not decide media
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

The local serializer emits stable section IDs/kinds and allowlisted summaries;
it does not make an API available. Wire it only behind the dedicated verified
bearer boundary. The draft schema and local checks do not complete D1/D2 or
approve the native destination adapter.

## Readiness and revisions

| Feature status | Meaning |
| --- | --- |
| `draft` | Planning and local mockups only; APIs or decisions may be missing. |
| `ready_for_ios` | Contract/fixtures are validated, dependencies are ready, and backend operations are verified at the manifest's staging URL. |
| `released` | Native integration is verified and the production API is available at the manifest's production URL. |

Readiness belongs to each feature; the manifest status is the least-ready feature.
A published handoff does **not** mean its API
is live. `runtime.implemented`, base URLs, feature statuses, and OpenAPI's
`x-handoff` must agree. Record verification before advancing status; the package
checker cannot prove a server exists or that its behavior matches the contract.

Use a new revision (for example `2026-10-26.2`) for any published content change,
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
  --revision 2026-10-26.1 \
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
> ready_for_ios. Preserve existing app behavior and record a handoff receipt with
> the build and verification results.

## First implementation pass

1. Use the reported native baseline and sign-in-first decision. Resolve the
   authentication lifecycle, staging destination and remaining native entitlement
   decisions in `foundations.yaml`; attach the native commit/release identifier.
2. Implement a separate v2 route/auth/resource/error boundary and verify that the
   legacy API has not changed. Do not inherit the legacy identity middleware.
3. Finalize Discover's typed contract, bounds, navigation and synthetic fixtures.
   Capture the current web references listed in `references.yaml`; do not use
   old marketing screenshots as design approval.
4. Wire the existing shared Discover service/resource behind verified
   authentication; do not rebuild content selection. Complete the piece-open
   history flow. Review the native destination adapter and downstream legacy
   calls. Test account isolation, Free Pick rotation and legacy parity.
5. Publish a `ready_for_ios` revision only after staging verification. Compare the
   native implementation with approved references before release.
