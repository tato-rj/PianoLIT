"""Offline .3 delivery safety tests; no server, account or iOS build is used."""

import hashlib
import json
from pathlib import Path
import shutil
import subprocess
import tempfile
import types
import unittest
from unittest import mock

ROOT = Path(__file__).resolve().parents[2]
SOURCE = ROOT / "handoffs/ios/2026-10-26.3"
MODULE_PATH = SOURCE / "Scripts/sync_api_handoff.py"
sync = types.ModuleType("pianolit_delivery")
sync.__file__ = str(MODULE_PATH)
exec(compile(MODULE_PATH.read_bytes(), str(MODULE_PATH), "exec"), sync.__dict__)


class DeliveryTest(unittest.TestCase):
    def setUp(self):
        self.temp = tempfile.TemporaryDirectory()
        self.addCleanup(self.temp.cleanup)
        self.base = Path(self.temp.name)
        self.source = self.base / "source"
        shutil.copytree(SOURCE, self.source)
        self.ios = self.base / "ios-repository"
        self.ios.mkdir()
        self.app = self.ios / "App.swift"
        self.app.write_text("existing application source\n")
        self.output = self.ios / "PianoLITHandoff"

    def test_install_repeat_and_verify_do_not_change_app_source_or_other_revisions(self):
        other = self.output / "older/notes.txt"
        other.parent.mkdir(parents=True)
        other.write_text("unrelated work")
        target = sync.sync(self.source, self.output, sync.REVISION)
        sync.sync(self.source, self.output, sync.REVISION)
        sync.sync(self.source, self.output, sync.REVISION, verify_only=True)
        self.assertEqual(self.app.read_text(), "existing application source\n")
        self.assertEqual(other.read_text(), "unrelated work")
        self.assertTrue((target / "CONNECT_DISCOVER.md").is_file())

    def test_wrong_revision_is_refused_without_a_destination(self):
        with self.assertRaisesRegex(ValueError, "Unexpected requested revision"):
            sync.sync(self.source, self.output, "2026-10-26.2")
        self.assertFalse(self.output.exists())

    def test_incomplete_and_corrupted_sources_never_install(self):
        fixture = self.source / "Fixtures/discover-complete.json"
        original = fixture.read_bytes()
        fixture.unlink()
        with self.assertRaises(ValueError):
            sync.sync(self.source, self.output, sync.REVISION)
        fixture.write_bytes(original[:-1] + b"X")
        with self.assertRaisesRegex(ValueError, "Checksum mismatch"):
            sync.sync(self.source, self.output, sync.REVISION)
        self.assertFalse(self.output.exists())

    def test_edited_or_extra_installed_files_are_preserved_and_refused(self):
        target = sync.sync(self.source, self.output, sync.REVISION)
        notes = target / "personal-notes.txt"
        notes.write_text("keep my notes")
        with self.assertRaisesRegex(ValueError, "Unexpected or missing"):
            sync.sync(self.source, self.output, sync.REVISION)
        self.assertEqual(notes.read_text(), "keep my notes")
        notes.unlink()
        doc = target / "CONNECT_DISCOVER.md"
        doc.write_text("my edited document")
        with self.assertRaises(ValueError):
            sync.sync(self.source, self.output, sync.REVISION)
        self.assertEqual(doc.read_text(), "my edited document")

    def test_existing_unrelated_empty_destination_is_not_replaced(self):
        target = self.output / sync.REVISION
        target.mkdir(parents=True)
        with self.assertRaisesRegex(ValueError, "Missing delivery manifest"):
            sync.sync(self.source, self.output, sync.REVISION)
        self.assertEqual(list(target.iterdir()), [])

    def test_source_and_destination_symlinks_are_refused(self):
        path = self.source / "Fixtures/discover-complete.json"
        saved = self.base / "same-response.json"
        saved.write_bytes(path.read_bytes())
        path.unlink()
        path.symlink_to(saved)
        with self.assertRaisesRegex(ValueError, "Symlinks"):
            sync.sync(self.source, self.output, sync.REVISION)
        path.unlink()
        path.write_bytes(saved.read_bytes())
        self.output.mkdir()
        (self.output / sync.REVISION).symlink_to(self.source, target_is_directory=True)
        with self.assertRaisesRegex(ValueError, "symlinks"):
            sync.sync(self.source, self.output, sync.REVISION)

    def test_manifest_path_traversal_and_unexpected_revision_are_refused(self):
        path = self.source / sync.MANIFEST
        manifest = json.loads(path.read_text())
        manifest["files"][0]["path"] = "../App.swift"
        path.write_text(json.dumps(manifest))
        with self.assertRaisesRegex(ValueError, "Unsafe"):
            sync.sync(self.source, self.output, sync.REVISION)
        manifest["revision"] = "2026-10-26.2"
        path.write_text(json.dumps(manifest))
        with self.assertRaisesRegex(ValueError, "Unexpected source revision"):
            sync.sync(self.source, self.output, sync.REVISION)

    def test_changed_canonical_revision_is_rejected_even_if_checksum_is_repaired(self):
        path = self.source / sync.MANIFEST
        manifest = json.loads(path.read_text())
        canonical = self.source / manifest["canonical_manifest"]
        canonical.write_text(canonical.read_text().replace("revision: 2026-10-26.3", "revision: 2026-10-26.2", 1))
        for entry in manifest["files"]:
            if entry["path"] == manifest["canonical_manifest"]:
                raw = canonical.read_bytes()
                entry.update(bytes=len(raw), sha256=hashlib.sha256(raw).hexdigest())
        path.write_text(json.dumps(manifest))
        with self.assertRaisesRegex(ValueError, "Canonical YAML revision"):
            sync.sync(self.source, self.output, sync.REVISION)

    def test_failed_final_install_cleans_only_its_own_staging_directory(self):
        with mock.patch.object(sync.os, "rename", side_effect=OSError("simulated install failure")):
            with self.assertRaisesRegex(OSError, "simulated"):
                sync.sync(self.source, self.output, sync.REVISION)
        self.assertFalse((self.output / sync.REVISION).exists())
        self.assertEqual(list(self.output.iterdir()), [])
        self.assertEqual(self.app.read_text(), "existing application source\n")

    def test_shell_entry_point_works_from_a_separate_ios_repository(self):
        command = ["bash", str(self.source / "Scripts/sync-api-handoff.sh"), "--revision", sync.REVISION]
        result = subprocess.run(command, cwd=self.ios, capture_output=True, text=True)
        self.assertEqual(result.returncode, 0, result.stderr)
        self.assertTrue((self.output / sync.REVISION / "delivery-manifest.json").is_file())
        result = subprocess.run(command + ["--verify-only"], cwd=self.ios, capture_output=True, text=True)
        self.assertEqual(result.returncode, 0, result.stderr)


if __name__ == "__main__":
    unittest.main()
