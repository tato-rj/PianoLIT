"""Offline handoff packaging/transport checks; no Laravel, database or live server."""

from contextlib import contextmanager, redirect_stdout
from functools import partial
from http.server import SimpleHTTPRequestHandler, ThreadingHTTPServer
import importlib.util
import io
from pathlib import Path
import shutil
import tempfile
import threading
import unittest


ROOT = Path(__file__).resolve().parents[2]
spec = importlib.util.spec_from_file_location("api_handoff", ROOT / "scripts/api-handoff.py")
handoff = importlib.util.module_from_spec(spec)
spec.loader.exec_module(handoff)


class QuietHandler(SimpleHTTPRequestHandler):
    def log_message(self, *args):
        pass

    def do_GET(self):
        if self.path == "/redirect/manifest.yaml":
            self.send_response(302)
            self.send_header("Location", "/manifest.yaml")
            self.end_headers()
        else:
            super().do_GET()


class HandoffTest(unittest.TestCase):
    def setUp(self):
        self.temp = tempfile.TemporaryDirectory()
        self.addCleanup(self.temp.cleanup)
        self.root = Path(self.temp.name)
        self.source = self.root / "source"
        shutil.copytree(ROOT / "docs/api/v2", self.source)
        self.public = self.root / "public"
        self.client = self.root / "client"
        self.revision = handoff.parse((self.source / "manifest.yaml").read_bytes())["revision"]
        self.silent = redirect_stdout(io.StringIO())
        self.silent.__enter__()
        self.addCleanup(self.silent.__exit__, None, None, None)

    @contextmanager
    def server(self):
        server = ThreadingHTTPServer(("127.0.0.1", 0), partial(QuietHandler, directory=str(self.public)))
        thread = threading.Thread(target=server.serve_forever, daemon=True)
        thread.start()
        try:
            yield f"http://127.0.0.1:{server.server_port}"
        finally:
            server.shutdown()
            server.server_close()
            thread.join()

    def build(self):
        handoff.build(self.source, self.public)

    def fetch(self, url, **kwargs):
        handoff.fetch(url, self.client, allow_local_http=True, **kwargs)

    def test_published_bundle_round_trips_from_current_and_historical_manifest(self):
        self.build()
        with self.server() as origin:
            self.fetch(origin + "/manifest.yaml", revision=self.revision)
            # Repeated fetch is idempotent, including a pinned historical URL.
            self.fetch(origin + f"/revisions/{self.revision}/manifest.yaml", revision=self.revision)
        for entry in handoff.parse((self.source / "manifest.yaml").read_bytes())["files"]:
            self.assertEqual((self.source / entry["path"]).read_bytes(),
                             (self.client / self.revision / entry["path"]).read_bytes())

    def test_corrupted_download_never_replaces_the_previous_local_snapshot(self):
        self.build()
        with self.server() as origin:
            self.fetch(origin + "/manifest.yaml")
            before = {str(path): path.read_bytes() for path in self.client.rglob("*") if path.is_file()}
            path = self.public / "revisions" / self.revision / "screens/discover.yaml"
            content = path.read_bytes()
            path.write_bytes(content.replace(b"Discover", b"DIscover", 1))  # Same size, different hash.
            with self.assertRaisesRegex(ValueError, "Size/hash mismatch"):
                self.fetch(origin + "/manifest.yaml")
        after = {str(path): path.read_bytes() for path in self.client.rglob("*") if path.is_file()}
        self.assertEqual(before, after)

    def test_revision_is_immutable_and_failed_build_keeps_stable_manifest(self):
        self.build()
        before = (self.public / "manifest.yaml").read_bytes()
        with (self.source / "README.md").open("a") as handle:
            handle.write("\nChanged published content.\n")
        with self.assertRaisesRegex(ValueError, "publish a new revision"):
            self.build()
        self.assertEqual(before, (self.public / "manifest.yaml").read_bytes())

    def test_mismatched_requested_revision_and_redirect_fail_without_writing(self):
        self.build()
        with self.server() as origin:
            with self.assertRaisesRegex(ValueError, "differs from --revision"):
                self.fetch(origin + "/manifest.yaml", revision="2000-01-01.1")
            with self.assertRaisesRegex(ValueError, "redirects are refused"):
                self.fetch(origin + "/redirect/manifest.yaml")
        self.assertFalse(self.client.exists())

    def test_remote_path_traversal_and_unknown_schema_are_refused(self):
        self.build()
        path = self.public / "manifest.yaml"
        original = handoff.parse(path.read_bytes())
        with self.server() as origin:
            invalid = dict(original, schema_version=999)
            path.write_bytes(handoff.encode(invalid))
            with self.assertRaisesRegex(ValueError, "Unsupported handoff schema"):
                self.fetch(origin + "/manifest.yaml")
            original["files"][0]["path"] = "../outside.md"
            path.write_bytes(handoff.encode(original))
            with self.assertRaisesRegex(ValueError, "Unsafe relative path"):
                self.fetch(origin + "/manifest.yaml")
        self.assertFalse(self.client.exists())

    def test_readiness_cannot_advance_past_a_draft_dependency(self):
        manifest = handoff.parse((self.source / "manifest.yaml").read_bytes())
        manifest["features"][1]["status"] = "ready_for_ios"
        with self.assertRaisesRegex(ValueError, "draft foundation"):
            handoff.validate_manifest(manifest)

    def test_duplicate_yaml_keys_and_missing_reference_images_fail_validation(self):
        with self.assertRaisesRegex(ValueError, "Duplicate YAML key"):
            handoff.parse("status: draft\nstatus: released\n")
        path = self.source / "references.yaml"
        references = handoff.parse(path.read_bytes())
        references["images"] = [{"path": "assets/missing.png"}]
        path.write_bytes(handoff.encode(references))
        with self.assertRaisesRegex(ValueError, "Referenced image is missing"):
            handoff.load_source(self.source)


if __name__ == "__main__":
    unittest.main()
