#!/usr/bin/env python3
"""Verify/install the offline PianoLIT .3 delivery; never edit Swift or use HTTP."""

import argparse
import hashlib
import json
import os
from pathlib import Path, PurePosixPath
import re
import shutil
import sys
import tempfile

REVISION = "2026-10-26.3"
MANIFEST = "delivery-manifest.json"
MAX_FILE = 8 * 1024 * 1024
MAX_TOTAL = 64 * 1024 * 1024


def require(condition, message):
    if not condition:
        raise ValueError(message)


def unique_object(pairs):
    result = {}
    for key, value in pairs:
        require(key not in result, "Duplicate JSON key")
        result[key] = value
    return result


def safe_path(name):
    require(isinstance(name, str) and re.fullmatch(r"[A-Za-z0-9_./-]+", name), "Invalid file path")
    path = PurePosixPath(name)
    require(not path.is_absolute() and ".." not in path.parts and str(path) == name
            and name not in {".", MANIFEST}, "Unsafe/reserved file path")
    return path


def inventory(root):
    result = set()
    for path in root.rglob("*"):
        require(not path.is_symlink(), "Symlinks are refused: " + str(path))
        require(path.is_file() or path.is_dir(), "Non-regular file is refused")
        if path.is_file():
            result.add(path.relative_to(root).as_posix())
    return result


def verify(root, revision):
    require(revision == REVISION, "Unexpected requested revision; this delivery is pinned to " + REVISION)
    require(not root.is_symlink() and root.is_dir(), "Source must be a real extracted delivery directory")
    root = root.resolve()
    actual_files = inventory(root)
    manifest_path = root / MANIFEST
    require(not manifest_path.is_symlink() and manifest_path.is_file(), "Missing delivery manifest")
    require(manifest_path.stat().st_size <= 1024 * 1024, "Oversized manifest")
    manifest_bytes = manifest_path.read_bytes()
    manifest = json.loads(manifest_bytes, object_pairs_hook=unique_object)
    require(manifest.get("delivery_schema_version") == 1, "Unsupported delivery schema")
    require(manifest.get("revision") == revision, "Unexpected source revision")
    require(manifest.get("status") == "fixture_only_incomplete_runtime" and manifest.get("live_integration_ready") is False,
            "This delivery must be explicitly fixture-only, not live-ready")
    require(manifest.get("verified_https_api_base_url") is None, "This fixture delivery must not advertise a live API URL")
    entries = manifest.get("files")
    require(isinstance(entries, list) and 1 <= len(entries) <= 200, "Invalid file inventory")
    names, total = set(), 0
    for entry in entries:
        name = entry["path"]
        safe_path(name)
        require(name not in names, "Duplicate file path")
        names.add(name)
        size, digest = entry["bytes"], entry["sha256"]
        require(type(size) is int and 0 < size <= MAX_FILE, "Invalid declared file size")
        require(isinstance(digest, str) and re.fullmatch(r"[a-f0-9]{64}", digest), "Invalid checksum")
        path = root / name
        require(path.is_file() and not path.is_symlink(), "Missing/unsafe file: " + name)
        require(path.stat().st_size == size, "Incomplete/changed file: " + name)
        require(hashlib.sha256(path.read_bytes()).hexdigest() == digest, "Checksum mismatch: " + name)
        total += size
    require(total <= MAX_TOTAL, "Delivery exceeds size limit")
    require(actual_files == names | {MANIFEST}, "Unexpected or missing delivery files")
    canonical = manifest.get("canonical_manifest")
    require(canonical in names, "Canonical manifest is not verified")
    text = (root / canonical).read_text(encoding="utf-8")
    matches = re.findall(r'''^revision:\s*(?:"([^"\n]+)"|'([^'\n]+)'|([^\s#]+))\s*$''', text, re.MULTILINE)
    require(len(matches) == 1 and next(value for value in matches[0] if value) == revision,
            "Canonical YAML revision differs from the requested revision")
    return manifest, manifest_bytes


def sync(source, output, revision, verify_only=False):
    manifest, manifest_bytes = verify(source, revision)
    source = source.resolve()
    require(not output.is_symlink(), "Output symlinks are refused")
    destination = output / revision
    require(not destination.is_symlink(), "Destination symlinks are refused")
    if destination.exists():
        _, installed_manifest = verify(destination, revision)
        require(installed_manifest == manifest_bytes, "Existing handoff differs; preserve it and choose a new output directory")
        print("Verified existing delivery at " + str(destination.resolve()))
        return destination
    require(not verify_only, "No installed delivery to verify")
    require(not output.exists() or output.is_dir(), "Output must be a directory")
    output.mkdir(parents=True, exist_ok=True)
    temporary = Path(tempfile.mkdtemp(prefix=".pianolit-handoff-", dir=output))
    try:
        for entry in manifest["files"]:
            target = temporary / entry["path"]
            target.parent.mkdir(parents=True, exist_ok=True)
            target.write_bytes((source / entry["path"]).read_bytes())
            target.chmod(0o755 if entry["path"].startswith("Scripts/") and target.suffix in {".sh", ".py"} else 0o644)
        (temporary / MANIFEST).write_bytes(manifest_bytes)
        verify(temporary, revision)
        require(not destination.exists() and not destination.is_symlink(), "Destination appeared during sync; refusing replacement")
        os.rename(temporary, destination)
    finally:
        if temporary.exists():
            shutil.rmtree(temporary)
    print("Installed verified fixture-only delivery at " + str(destination.resolve()))
    print("No Swift/project files were changed. No verified HTTPS API URL is supplied.")
    return destination


def main():
    parser = argparse.ArgumentParser(description=__doc__)
    parser.add_argument("--revision", required=True, help="Must be 2026-10-26.3")
    parser.add_argument("--source", type=Path, default=Path(__file__).resolve().parents[1],
                        help="Extracted delivery directory; defaults to this script's delivery")
    parser.add_argument("--output", type=Path, default=Path.cwd() / "PianoLITHandoff",
                        help="Only its revision subdirectory is installed; default ./PianoLITHandoff")
    parser.add_argument("--verify-only", action="store_true", help="Verify an already installed delivery without writing")
    args = parser.parse_args()
    try:
        require(sys.version_info >= (3, 9), "Python 3.9 or newer is required")
        sync(args.source, args.output, args.revision, args.verify_only)
    except (ValueError, KeyError, TypeError, OSError, json.JSONDecodeError) as error:
        parser.exit(1, "Handoff error: " + str(error) + "\n")


if __name__ == "__main__":
    main()
