# PianoLIT iOS handoff delivery for 2026-10-26.3

This directory is the actual portable delivery: scripts, audited connection
instructions, canonical files and expanded offline fixtures. Download
`PianoLIT-iOS-handoff-2026-10-26.3.zip` from the supplied artifact link. No private
repository access or credentials are needed to use this ZIP. No API source URL
is invented or assumed. **No verified HTTPS API base URL is available**.

The immutable canonical `.3` snapshot is retained in
`Canonical/2026-10-26.3/`. The root `CONNECT_DISCOVER.md` and expanded `Fixtures/`
are **delivery supplement 1**, independently hashed in `delivery-manifest.json`.
They explain gaps and extend test coverage without pretending those additions
were originally part of canonical `.3`. Its readiness stays draft; use fixtures.

## Tools and one-time setup

- Bash 3.2 or newer and `unzip` to extract the provided ZIP.
- Python 3.9 or newer, available as `python3`; check with `python3 --version`.
  If needed, install Python through your approved developer toolchain. The
  primary sync command uses only Python's standard library: no pip packages,
  Homebrew, API account, SSH key or token is required.
- A writable directory for the downloaded ZIP and the isolated handoff output.
  This does not require modifying the Xcode project, Swift files, Pods or Git.

`PIANOLIT_HANDOFF_PYTHON` can name an existing Python executable if `python3`
does not identify the required version. It is an executable path, not shell code.

## Exact command from the iOS repository root

First save the provided ZIP in `~/Downloads/` using its exact filename. Then,
with the current directory at the iOS repository root, run:

```sh
delivery_dir="$(mktemp -d)"
unzip -q "$HOME/Downloads/PianoLIT-iOS-handoff-2026-10-26.3.zip" -d "$delivery_dir"
bash "$delivery_dir/PianoLIT-iOS-handoff-2026-10-26.3/Scripts/sync-api-handoff.sh" \
  --revision 2026-10-26.3 \
  --output "$PWD/PianoLITHandoff"
```

This fetches/copies from the **actual extracted artifact**, entirely offline.
The script defaults its source to the delivery directory containing itself.
If using a separately copied delivery directory, `--source /absolute/delivery`
selects it explicitly. No remote source, guessed host or live account is used.

The files appear at:

```text
PianoLITHandoff/2026-10-26.3/
  delivery-manifest.json
  README.md
  CONNECT_DISCOVER.md
  GAPS.md
  PROVENANCE.json
  Scripts/
  Canonical/2026-10-26.3/manifest.yaml
  Canonical/2026-10-26.3/openapi.yaml
  Fixtures/http-index.json
  Fixtures/navigation-scenarios.json
  Fixtures/*.json
  Fixtures/Assets/artwork.png
```

## Verify the installed revision

From the same iOS repository root:

```sh
bash ./PianoLITHandoff/2026-10-26.3/Scripts/sync-api-handoff.sh \
  --revision 2026-10-26.3 \
  --verify-only
```

The verifier checks the requested revision, JSON manifest, canonical YAML
revision, every included file's byte count/SHA-256, missing/extra files and
symlinks. A wrong revision, partial/corrupt download or edited installation
fails. A repeated identical sync verifies instead of replacing it. A differing
existing directory is preserved and rejected, including extra user files.
Only the revision subdirectory under `--output` is installed, after validation
in a temporary directory; an incomplete transfer never becomes the destination.
No app source or unrelated work is overwritten or deleted. Choose a different
output directory to keep a separately edited handoff; there is no force option.

The artifact is accompanied by a `.sha256` file. Verify the ZIP before extraction
with `shasum -a 256 <downloaded-zip>` and compare it with the supplied checksum.
Hashes detect mismatched/corrupt files, not an untrusted publisher; obtain the
ZIP/checksum from the trusted artifact delivery, not an unrelated URL.

## Included original transport tool

`Scripts/api-handoff.py` and `Scripts/requirements-api-handoff.txt` are actual
copies of the backend's existing tool and pinned requirements. That optional
tool requires Python 3.9+ and PyYAML 6.0.2 in a virtual environment. It can fetch
the canonical handoff from a future explicitly supplied HTTPS manifest URL,
rejects redirects/revision/hash mismatches and does not edit Swift. **It is not
the primary command for this expanded offline delivery**: the original canonical
manifest does not declare these supplemental files. There is no verified
remote URL configured here, and no credentials are embedded. Use the provided
`sync-api-handoff.sh` to obtain the complete delivery now.

## Start the updated app integration

Read the root `CONNECT_DISCOVER.md`, then `GAPS.md`, then the canonical OpenAPI.
Use `Fixtures/http-index.json` to select a named mock case with its expected
method/path/body and HTTP status. Cases with identical requests can represent
different test-controlled server states; they are not a password validator.
`Fixtures/canonical-fixture-index.json` indexes the nine original snapshots,
which are preserved but do not form one coherent account sequence. Use the
supplemental fixtures for the navigation sequences. The fixture clock is
October 26, 2026 at 16:00 UTC; use a mock clock and keep non-credential fixture
sessions separate from real account/Keychain state.
`navigation-scenarios.json` distinguishes client test states from
API responses. Block all network access in fixture mode, including `.invalid`
artwork/media URLs, and return the local PNG for image requests.

Render the complete fixture in server order. Mock the explicitly identified
legacy detail response before declaring a successful native detail open; then
mock the v2 history POST. Do not issue history for failed/denied/prefetched
opens. Only the updated UIKit app is developed; do not maintain a legacy Swift
branch. The old backend API remains unchanged for installed clients.

No iOS code is implemented by this delivery. Do not enable live calls until
the backend team supplies and verifies an HTTPS origin and resolves the
applicable access/navigation gaps. This package is not labeled `ready_for_ios`.
