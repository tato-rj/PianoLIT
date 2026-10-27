#!/usr/bin/env python3
"""Build the offline .3 delivery ZIP; never deploy or modify canonical revisions."""

import argparse
import hashlib
import json
from pathlib import Path
import shutil
import struct
import subprocess
import zlib
import zipfile

ROOT = Path(__file__).resolve().parents[1]
REVISION = "2026-10-26.3"
DELIVERY = ROOT / "handoffs/ios" / REVISION
NAME = "PianoLIT-iOS-handoff-" + REVISION


def write_json(path, data):
    path.write_text(json.dumps(data, indent=2, ensure_ascii=False) + "\n", encoding="utf-8")


def build(fixture_source, output):
    canonical = ROOT / "public/api-handoff/v2/revisions" / REVISION
    copied = DELIVERY / "Canonical" / REVISION
    source_files = {p.relative_to(canonical).as_posix(): p.read_bytes() for p in canonical.rglob("*") if p.is_file()}
    copy_files = {p.relative_to(copied).as_posix(): p.read_bytes() for p in copied.rglob("*") if p.is_file()}
    if source_files != copy_files:
        raise ValueError("Canonical .3 copy differs; never rewrite an existing revision")
    index = json.loads((fixture_source / "http-index.json").read_text())
    if index.get("revision") != REVISION:
        raise ValueError("Wrong fixture source revision")
    for name in ["http-index.json"] + [entry["response_file"] for entry in index["cases"].values()]:
        if Path(name).name != name or not name.endswith(".json"):
            raise ValueError("Unsafe fixture path")
        shutil.copyfile(fixture_source / name, DELIVERY / "Fixtures" / name)

    # Local PNG fixture: no remote media, generation service or new dependency.
    assets = DELIVERY / "Fixtures/Assets"
    assets.mkdir(exist_ok=True)
    def chunk(kind, data):
        return struct.pack(">I", len(data)) + kind + data + struct.pack(">I", zlib.crc32(kind + data) & 0xffffffff)
    pixels = b"".join(b"\x00" + b"".join(bytes((38, 79, 150) if (x // 16 + y // 16) % 2 else (235, 240, 247)) for x in range(128)) for y in range(128))
    png = b"\x89PNG\r\n\x1a\n" + chunk(b"IHDR", struct.pack(">IIBBBBB", 128, 128, 8, 2, 0, 0, 0)) + chunk(b"IDAT", zlib.compress(pixels)) + chunk(b"IEND", b"")
    (assets / "artwork.png").write_bytes(png)

    scenarios = {
        "revision": REVISION, "category": "client_scenarios_not_api_responses", "synthetic": True,
        "network_mode": "all_network_disabled", "native_entitlement_contract_available": False,
        "cases": [
            {"id": "confirmed-open", "piece_id": "4", "discover_response": "discover-complete.json",
             "legacy_status_response": "legacy-status-active-override.json", "legacy_detail_response": "legacy-piece-detail.json",
             "local_test_double_access_decision": "allowed", "native_view_outcome": "detail_did_open",
             "expected_history_requests": [{"method": "POST", "path": "/api/v2/pieces/4/view", "body": {}, "http_status": 200, "response_file": "history-success.json"}],
             "then_discover_response": "discover-after-open.json"},
            {"id": "denied-regular-piece", "piece_id": "4", "legacy_status_response": "legacy-status-no-membership.json",
             "local_test_double_access_decision": "denied", "native_view_outcome": "detail_did_not_open", "expected_history_requests": [],
             "note": "Authored client test double only; no server locked field or approved native entitlement policy is implied."},
            {"id": "failed-detail-load", "piece_id": "4", "native_view_outcome": "detail_load_failed", "expected_history_requests": []},
            {"id": "impression-prefetch-or-cancel", "piece_id": "4", "native_view_outcome": "not_confirmed_open", "expected_history_requests": []},
            {"id": "free-pick-without-membership", "piece_id": "1", "discover_response": "discover-complete.json",
             "legacy_status_response": "legacy-status-no-membership.json", "display_free_pick_badge": True,
             "local_test_double_access_decision": "unresolved", "expected_history_requests": [],
             "note": "Free Pick identity is true; media/open access is not supplied by v2 and must not be inferred."},
            {"id": "retry-confirmed-open", "piece_id": "4", "responses": ["history-success.json", "history-retry-success.json"],
             "observed_backend_assertions": {"one_row_per_account_piece": True, "retry_updates_server_recency": True},
             "note": "Not a strict event-idempotency guarantee; retries may reorder recency."},
            {"id": "membership-unavailable-in-v2", "login_response": "login-success.json", "discover_response": "discover-complete.json",
             "expected_membership_field": "absent", "must_not_infer": ["membership_from_account_id", "access_from_media_availability", "access_from_row_position"]},
        ],
    }
    write_json(DELIVERY / "Fixtures/navigation-scenarios.json", scenarios)
    historical = {}
    for name, schema, method, path, status, bearer, body in [
        ("login", "LoginEnvelope", "POST", "/api/v2/auth/login", 200, False, {"email": "account-a@example.test", "password": "REDACTED_NOT_A_PASSWORD"}),
        ("account", "AccountEnvelope", "GET", "/api/v2/auth/me", 200, True, None),
        ("logout", "LogoutEnvelope", "POST", "/api/v2/auth/logout", 200, True, {}),
        ("discover", "DiscoverEnvelope", "GET", "/api/v2/discover", 200, True, None),
        ("discover-with-history", "DiscoverEnvelope", "GET", "/api/v2/discover", 200, True, None),
        ("discover-empty", "DiscoverEnvelope", "GET", "/api/v2/discover", 200, True, None),
        ("unauthenticated", "ErrorEnvelope", "GET", "/api/v2/auth/me", 401, False, None),
        ("invalid-credentials", "ErrorEnvelope", "POST", "/api/v2/auth/login", 401, False, {"email": "missing@example.test", "password": "REDACTED_NOT_A_PASSWORD"}),
        ("invalid-request", "ErrorEnvelope", "POST", "/api/v2/auth/login", 422, False, {"email": [], "password": ""}),
    ]:
        headers = {"Accept": "application/json"}
        if bearer:
            headers["Authorization"] = "Bearer EXAMPLE_TOKEN_DO_NOT_USE"
        if body is not None:
            headers["Content-Type"] = "application/json"
        request = {"method": method, "path": path, "headers": headers}
        if body is not None:
            request["body"] = body
        historical[name] = {"request": request, "http_status": status, "schema": schema,
                            "response_file": "Canonical/" + REVISION + "/fixtures/" + name + ".json"}
    write_json(DELIVERY / "Fixtures/canonical-fixture-index.json", {"revision": REVISION,
               "category": "historical_canonical_snapshots", "sequence_compatible": False,
               "note": "Original snapshots use different synthetic states/accounts; use the supplemental fixtures for coherent navigation scenarios.", "cases": historical})

    commit = subprocess.check_output(["git", "rev-parse", "HEAD"], cwd=ROOT, text=True).strip()
    audited = ["routes/api-v2.php", "app/Auth/ApiV2/Sessions.php", "app/Api/V2/Input.php", "app/Api/V2/Responses.php",
               "app/Http/Resources/Api/V2/DiscoverResource.php", "app/Services/DiscoverFeed.php", "app/Services/RecentlyViewedPieces.php",
               "app/Http/Controllers/Api/V2/PieceViewsController.php", "app/Http/Controllers/Api/PiecesController.php",
               "app/Http/Controllers/MembershipsController.php", "app/Api/Search.php", "config/api-v2.php"]
    provenance = {"revision": REVISION, "backend_commit": commit, "audit_date": "2026-10-26", "canonical_revision_rewritten": False,
                  "fixture_generator": "tests/Review/ApiV2HandoffDeliveryTest.php",
                  "fixture_generator_sha256": hashlib.sha256((ROOT / "tests/Review/ApiV2HandoffDeliveryTest.php").read_bytes()).hexdigest(),
                  "fixture_environment": "in-memory SQLite, fake HTTP/mail/Redis; no normal .env",
                  "normalizations": ["bearer tokens replaced with non-credential placeholder", "request IDs fixed", "URL origins replaced with example.invalid"],
                  "audited_source_files": [{"path": name, "sha256": hashlib.sha256((ROOT / name).read_bytes()).hexdigest(),
                                            "matches_commit": (ROOT / name).read_bytes() == subprocess.check_output(["git", "show", commit + ":" + name], cwd=ROOT)} for name in audited]}
    write_json(DELIVERY / "PROVENANCE.json", provenance)

    entries = []
    for path in sorted(DELIVERY.rglob("*")):
        if path.is_symlink():
            raise ValueError("Delivery symlinks are refused")
        if path.is_file() and path.name != "delivery-manifest.json":
            raw = path.read_bytes()
            entries.append({"path": path.relative_to(DELIVERY).as_posix(), "bytes": len(raw), "sha256": hashlib.sha256(raw).hexdigest()})
    write_json(DELIVERY / "delivery-manifest.json", {
        "delivery_schema_version": 1, "revision": REVISION, "delivery_supplement": 1, "status": "fixture_only_incomplete_runtime", "live_integration_ready": False,
        "verified_https_api_base_url": None, "canonical_manifest": "Canonical/" + REVISION + "/manifest.yaml",
        "canonical_revision_rewritten": False, "instructions": "CONNECT_DISCOVER.md", "gaps": "GAPS.md",
        "fixture_index": "Fixtures/http-index.json", "client_scenarios": "Fixtures/navigation-scenarios.json", "files": entries,
    })
    output.mkdir(parents=True, exist_ok=True)
    archive = output / (NAME + ".zip")
    with zipfile.ZipFile(archive, "w", compression=zipfile.ZIP_DEFLATED) as package:
        for path in sorted(DELIVERY.rglob("*")):
            if path.is_file():
                info = zipfile.ZipInfo(NAME + "/" + path.relative_to(DELIVERY).as_posix(), date_time=(2026, 10, 26, 12, 0, 0))
                info.external_attr = (0o100755 if path.suffix in {".sh", ".py"} else 0o100644) << 16
                package.writestr(info, path.read_bytes(), compress_type=zipfile.ZIP_DEFLATED)
    digest = hashlib.sha256(archive.read_bytes()).hexdigest()
    archive.with_suffix(".zip.sha256").write_text(digest + "  " + archive.name + "\n")
    print(str(archive))
    print("SHA-256 " + digest)


if __name__ == "__main__":
    parser = argparse.ArgumentParser(description=__doc__)
    parser.add_argument("--fixture-source", required=True, type=Path)
    parser.add_argument("--output", type=Path, default=ROOT / "artifacts")
    args = parser.parse_args()
    build(args.fixture_source, args.output)
