"""Offline favorite-folder schemas, synthetic flows and compatibility checks."""

import json
from pathlib import Path
import unittest

import yaml

try:
    from jsonschema import RefResolver
    from openapi_schema_validator import OAS30Validator
    from openapi_spec_validator import validate_spec
except ImportError:
    OAS30Validator = None


ROOT = Path(__file__).resolve().parents[2]
DOCS = ROOT / "docs/api/v2"


@unittest.skipUnless(OAS30Validator, "Install openapi-spec-validator==0.7.2 for contract checks")
class FavoriteFoldersContractTest(unittest.TestCase):
    @classmethod
    def setUpClass(cls):
        cls.api = yaml.safe_load((DOCS / "openapi.yaml").read_text())
        cls.flow = json.loads((DOCS / "fixtures/favorite-folders-flow.json").read_text())

    def validator(self, name):
        return OAS30Validator(self.api["components"]["schemas"][name], resolver=RefResolver.from_schema(self.api))

    def test_success_requests_and_responses_validate_against_openapi(self):
        validate_spec(self.api)
        for exchange in self.flow["exchanges"]:
            method = exchange["request"]["method"]
            self.validator("FavoriteFolderListResponse" if method == "GET" else "FavoriteFolderMutationResponse").validate(exchange["response"])
            self.assertEqual(201 if method == "POST" else 200, exchange["http_status"])
            if method == "POST":
                self.validator("FavoriteFolderCreateRequest").validate(exchange["request"]["body"])

    def test_flow_preserves_other_memberships_and_retry_returns_current_state(self):
        cases = {case["case"]: case for case in self.flow["exchanges"]}
        data = lambda key: cases[key]["response"]["data"]
        self.assertEqual([], data("empty_account")["folders"])
        self.assertFalse(data("include_empty_folder")["folders"][0]["has_piece"])
        self.assertEqual(data("save"), data("repeated_save"))
        self.assertEqual(["12", "13"], data("multiple_folders")["favorite_folder_ids"])
        self.assertEqual(["13"], data("remove_one_folder")["favorite_folder_ids"])
        self.assertTrue(data("remove_one_folder")["is_favorited"])
        self.assertFalse(data("remove_last_folder")["is_favorited"])
        self.assertEqual(data("remove_last_folder"), data("repeated_remove"))
        self.assertEqual(cases["atomic_create_and_save"]["request"], cases["creation_replay_after_removal"]["request"])
        self.assertFalse(data("creation_replay_after_removal")["is_favorited"])
        self.assertEqual(0, data("creation_replay_after_removal")["folder"]["piece_count"])

    def test_error_codes_and_retry_after_have_valid_envelopes(self):
        cases = json.loads((DOCS / "fixtures/favorite-folders-errors.json").read_text())["cases"]
        self.assertEqual({401, 403, 404, 409, 422, 429}, {case["http_status"] for case in cases})
        for case in cases:
            self.validator("ErrorEnvelope").validate(case["response"])
            if case["http_status"] == 429:
                self.assertGreater(int(case["headers"]["Retry-After"]), 0)
        self.assertEqual(4, sum(case["http_status"] == 403 for case in cases))

    def test_existing_paths_schemas_and_old_published_fixtures_remain_compatible(self):
        previous = yaml.safe_load((ROOT / "public/api-handoff/v2/revisions/2026-11-04.1/openapi.yaml").read_text())
        for path, operations in previous["paths"].items():
            self.assertEqual(operations, self.api["paths"][path])
        for name, schema in previous["components"]["schemas"].items():
            self.assertEqual(schema, self.api["components"]["schemas"][name])
        for filename in ("piece-full.json", "piece-blocked.json"):
            old = json.loads((ROOT / "public/api-handoff/v2/revisions/2026-11-04.1/fixtures" / filename).read_text())
            current = json.loads((DOCS / "fixtures" / filename).read_text())
            self.assertFalse(old["data"]["account"]["favorite_writes_supported"])
            self.assertTrue(current["data"]["account"]["favorite_writes_supported"])
            current["data"]["account"]["favorite_writes_supported"] = False
            self.assertEqual(old, current)


if __name__ == "__main__":
    unittest.main()
