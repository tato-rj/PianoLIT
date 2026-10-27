#!/usr/bin/env python3
"""Check/package the local handoff, or fetch a verified static revision for iOS."""

import argparse
import hashlib
import json
import os
from pathlib import Path, PurePosixPath
import re
import shutil
import sys
import tempfile
from urllib.parse import urljoin, urlsplit
from urllib.request import HTTPRedirectHandler, Request, build_opener

try:
    import yaml
except ImportError:
    sys.exit("Install scripts/requirements-api-handoff.txt in a Python virtual environment first.")

ROOT = Path(__file__).resolve().parents[1]
MAX_FILE = 8 * 1024 * 1024
MAX_TOTAL = 64 * 1024 * 1024
STATUSES = {"draft", "ready_for_ios", "released"}


class UniqueLoader(yaml.SafeLoader):
    pass


def unique_mapping(loader, node, deep=False):
    result = {}
    for key_node, value_node in node.value:
        key = loader.construct_object(key_node, deep=deep)
        if key in result:
            raise ValueError(f"Duplicate YAML key: {key}")
        result[key] = loader.construct_object(value_node, deep=deep)
    return result


UniqueLoader.add_constructor(yaml.resolver.BaseResolver.DEFAULT_MAPPING_TAG, unique_mapping)


def parse(content):
    return yaml.load(content, Loader=UniqueLoader)


def encode(value):
    return yaml.safe_dump(value, sort_keys=False, allow_unicode=True).encode("utf-8")


def require(condition, message):
    if not condition:
        raise ValueError(message)


def safe_path(value):
    require(isinstance(value, str) and re.fullmatch(r"[A-Za-z0-9_./-]+", value), "Invalid file path")
    path = PurePosixPath(value)
    require(not path.is_absolute() and str(path) == value and ".." not in path.parts,
            f"Unsafe relative path: {value}")
    require(value not in {".", "manifest.yaml"}, f"Reserved file path: {value}")
    return value


def validate_manifest(manifest, published=False):
    require(isinstance(manifest, dict) and manifest.get("schema_version") == 1,
            "Unsupported handoff schema_version (expected 1)")
    revision = manifest.get("revision", "")
    require(isinstance(revision, str) and re.fullmatch(r"\d{4}-\d{2}-\d{2}\.\d+", revision), "Invalid revision")
    require(manifest.get("status") in STATUSES, "Invalid handoff status")
    require(manifest.get("api_major_version") == 2, "Expected API major version 2")
    runtime = manifest["runtime"]
    require(type(runtime["implemented"]) is bool, "runtime.implemented must be boolean")
    require(runtime.get("existing_api") == "unchanged", "Existing API compatibility must be explicit")
    for key in ("staging_base_url", "production_base_url"):
        url = runtime.get(key)
        if url is not None:
            parsed = urlsplit(url)
            require(parsed.scheme == "https" and parsed.hostname and not parsed.username
                    and not parsed.password and not parsed.query and not parsed.fragment,
                    f"{key} must be a credential-free HTTPS URL")
    entries = manifest["files"]
    require(isinstance(entries, list) and 1 <= len(entries) <= 100, "Expected 1–100 handoff files")
    paths = [safe_path(entry["path"]) for entry in entries]
    require(len(paths) == len(set(paths)), "Duplicate handoff file path")
    for name, path in manifest["entrypoints"].items():
        require(path in paths, f"Missing entrypoint: {name}")
    features = manifest["features"]
    ids = [feature["id"] for feature in features]
    require(len(ids) == len(set(ids)), "Duplicate feature ID")
    by_id = {feature["id"]: feature for feature in features}
    require(features, "At least one feature is required")
    rank = {"draft": 0, "ready_for_ios": 1, "released": 2}
    require(all(feature["status"] in rank for feature in features), "Invalid feature status")
    require(manifest["status"] == min((feature["status"] for feature in features), key=rank.get),
            "Manifest status must describe the least-ready feature")
    for feature in features:
        require(feature["document"] in paths and feature["status"] in STATUSES, "Invalid feature document/status")
        for dependency in feature["depends_on"]:
            require(dependency in by_id and dependency != feature["id"], "Unknown/self feature dependency")
            if feature["status"] != "draft":
                require(by_id[dependency]["status"] != "draft", "Feature depends on a draft foundation")
        if feature["status"] != "draft":
            require(runtime["implemented"] and runtime.get("staging_base_url"), "Ready feature needs implemented staging API")
            require(feature["backend_status"] in {"staging_verified", "production_verified"}, "Backend has not been verified")
        if feature["status"] == "released":
            require(runtime.get("production_base_url") and feature["backend_status"] == "production_verified"
                    and feature["ios_status"] == "verified", "Released feature needs verified production and iOS")
    def visit(feature_id, ancestors):
        require(feature_id not in ancestors, "Circular feature dependency")
        for dependency in by_id[feature_id]["depends_on"]:
            visit(dependency, ancestors | {feature_id})
    for feature_id in ids:
        visit(feature_id, set())
    if published:
        require(manifest.get("base_path") == f"revisions/{revision}/", "Unexpected revision base_path")
        total = 0
        for entry in entries:
            require(type(entry.get("bytes")) is int and 0 < entry["bytes"] <= MAX_FILE, "Invalid file byte count")
            require(re.fullmatch(r"[0-9a-f]{64}", entry.get("sha256", "")), "Invalid SHA-256")
            total += entry["bytes"]
        require(total <= MAX_TOTAL, "Handoff exceeds total size limit")


def validate_documents(manifest, contents):
    documents = {}
    for path, content in contents.items():
        if path.endswith((".yaml", ".yml")):
            documents[path] = parse(content)
        elif path.endswith(".json"):
            documents[path] = json.loads(content)
    api = documents[manifest["entrypoints"]["contract"]]
    require(api.get("openapi") == "3.0.3", "Expected OpenAPI 3.0.3")
    require(api["x-handoff"]["revision"] == manifest["revision"], "OpenAPI revision mismatch")
    require(api["x-handoff"]["implemented"] == manifest["runtime"]["implemented"], "OpenAPI readiness mismatch")
    require(api["x-handoff"]["status"] == manifest["status"], "OpenAPI status mismatch")
    for feature in manifest["features"]:
        document = documents[feature["document"]]
        require(document["id"] == feature["id"] and document["status"] == feature["status"], "Feature status mismatch")
    references = documents[manifest["entrypoints"]["references"]]
    for reference in references["images"]:
        require(reference["path"] in contents, "Referenced image is missing from the package")
        for field in references["required_image_metadata"]:
            require(field in reference, f"Image missing {field}")


def load_source(source):
    source = source.resolve()
    manifest = parse((source / "manifest.yaml").read_bytes())
    validate_manifest(manifest)
    contents = {}
    for entry in manifest["files"]:
        path = source / entry["path"]
        require(path.resolve().is_relative_to(source), "Source file escapes handoff directory")
        require(0 < path.stat().st_size <= MAX_FILE, f"Invalid file size: {entry['path']}")
        contents[entry["path"]] = path.read_bytes()
    require(sum(map(len, contents.values())) <= MAX_TOTAL, "Handoff exceeds total size limit")
    validate_documents(manifest, contents)
    return manifest, contents


def atomic_file(path, content):
    with tempfile.NamedTemporaryFile(dir=path.parent, delete=False) as handle:
        temporary = Path(handle.name)
        try:
            handle.write(content)
            handle.flush()
            os.chmod(temporary, 0o644)
            os.replace(temporary, path)
        finally:
            temporary.unlink(missing_ok=True)


def install_revision(destination, contents):
    """Install a full revision, or verify an identical existing revision without overwriting it."""
    if destination.exists():
        actual = {str(path.relative_to(destination)) for path in destination.rglob("*") if path.is_file()}
        require(actual == set(contents), "Existing revision differs; publish a new revision")
        for name, content in contents.items():
            require((destination / name).read_bytes() == content, "Existing revision differs; publish a new revision")
        return
    destination.parent.mkdir(parents=True, exist_ok=True)
    temporary = Path(tempfile.mkdtemp(prefix=".handoff-", dir=destination.parent))
    try:
        os.chmod(temporary, 0o755)
        for name, content in contents.items():
            path = temporary / name
            path.parent.mkdir(parents=True, exist_ok=True)
            path.write_bytes(content)
            os.chmod(path, 0o644)
        os.rename(temporary, destination)
    finally:
        if temporary.exists():
            shutil.rmtree(temporary)


def build(source, output):
    manifest, contents = load_source(source)
    manifest["base_path"] = f"revisions/{manifest['revision']}/"
    manifest["files"] = [dict(path=name, bytes=len(content), sha256=hashlib.sha256(content).hexdigest())
                         for name, content in contents.items()]
    validate_manifest(manifest, published=True)
    snapshot = dict(contents, **{"manifest.yaml": encode(manifest)})
    install_revision(output / manifest["base_path"], snapshot)
    atomic_file(output / "manifest.yaml", snapshot["manifest.yaml"])
    print(f"Built {manifest['revision']} ({manifest['status']}) at {output}; no deployment performed.")


class NoRedirect(HTTPRedirectHandler):
    def redirect_request(self, req, fp, code, msg, headers, newurl):
        raise ValueError("Handoff redirects are refused; use the verified direct URL")


def fetch(url, output, revision=None, allow_local_http=False):
    parsed = urlsplit(url)
    local_http = allow_local_http and parsed.scheme == "http" and parsed.hostname in {"localhost", "127.0.0.1", "::1"}
    require(parsed.scheme == "https" or local_http, "Use HTTPS (local HTTP requires --allow-local-http)")
    require(parsed.hostname and not parsed.username and not parsed.password and not parsed.query and not parsed.fragment,
            "Use a direct credential-free manifest URL without query/fragment")
    require(parsed.path.endswith("/manifest.yaml"), "URL must end in /manifest.yaml")
    opener = build_opener(NoRedirect())

    def read(remote, maximum):
        request = Request(remote, headers={"Cache-Control": "no-cache", "Accept": "application/yaml, text/yaml, */*"})
        with opener.open(request, timeout=30) as response:
            require(response.status == 200, "Expected HTTP 200")
            content = response.read(maximum + 1)
        require(len(content) <= maximum, "Remote file exceeds its size limit")
        return content

    manifest = parse(read(url, 1024 * 1024))
    validate_manifest(manifest, published=True)
    require(revision is None or manifest["revision"] == revision, "Remote revision differs from --revision; review before syncing")
    # A pinned historical manifest is also available inside its immutable revision.
    pinned_suffix = f"/revisions/{manifest['revision']}/manifest.yaml"
    base = urljoin(url, "./" if parsed.path.endswith(pinned_suffix) else manifest["base_path"])
    contents = {}
    for entry in manifest["files"]:
        content = read(urljoin(base, entry["path"]), entry["bytes"])
        require(len(content) == entry["bytes"] and hashlib.sha256(content).hexdigest() == entry["sha256"],
                f"Size/hash mismatch: {entry['path']}")
        contents[entry["path"]] = content
    validate_documents(manifest, contents)
    contents["manifest.yaml"] = encode(manifest)
    destination = output / manifest["revision"]
    install_revision(destination, contents)
    print(f"Fetched {manifest['revision']} ({manifest['status']}) to {destination}; no API or Swift code was changed.")


def main():
    parser = argparse.ArgumentParser(description=__doc__)
    commands = parser.add_subparsers(dest="command", required=True)
    for name in ("check", "build"):
        command = commands.add_parser(name)
        command.add_argument("--source", type=Path, default=ROOT / "docs/api/v2")
        if name == "build":
            command.add_argument("--output", type=Path, default=ROOT / "public/api-handoff/v2")
    command = commands.add_parser("fetch")
    command.add_argument("--url", required=True)
    command.add_argument("--output", type=Path, required=True)
    command.add_argument("--revision")
    command.add_argument("--allow-local-http", action="store_true", help="Permit HTTP on loopback only, for local verification")
    args = parser.parse_args()
    try:
        if args.command == "check":
            manifest, contents = load_source(args.source)
            print(f"Valid handoff structure: {manifest['revision']} ({len(contents)} files, {manifest['status']}).")
        elif args.command == "build":
            build(args.source, args.output)
        else:
            fetch(args.url, args.output, args.revision, args.allow_local_http)
    except (ValueError, KeyError, TypeError, OSError, yaml.YAMLError) as error:
        parser.exit(1, f"Handoff error: {error}\n")


if __name__ == "__main__":
    main()
