"""Offline OpenAPI and synthetic five-step handoff checks; no accounts/network."""

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
class MatchTourContractTest(unittest.TestCase):
    @classmethod
    def setUpClass(cls):
        cls.api = yaml.safe_load((DOCS / "openapi.yaml").read_text())
        cls.flow = json.loads((DOCS / "fixtures/find-my-match-flow.json").read_text())

    def validator(self, name):
        return OAS30Validator(self.api["components"]["schemas"][name], resolver=RefResolver.from_schema(self.api))

    def test_complete_flow_requests_and_responses_validate_against_the_contract(self):
        validate_spec(self.api)
        for exchange in self.flow["exchanges"]:
            self.validator("MatchTourResponse").validate(exchange["response"])
            request = exchange["request"]
            if request["method"] == "POST":
                self.validator("TourStartRequest").validate(request["body"])
            elif request["method"] == "PUT":
                self.validator("TourAnswerRequest").validate(request["body"])

    def test_five_steps_include_two_reading_rounds_two_taste_rounds_skip_and_one_result(self):
        exchanges = self.flow["exchanges"]
        self.assertEqual(9, len(exchanges))
        states = [exchange["response"]["data"] for exchange in exchanges]
        self.assertEqual([1, 2, 3, 3, 4, 4, 5, None, None], [state["progress"]["current_step"] for state in states])
        self.assertEqual(list(range(9)), [state["revision"] for state in states])
        self.assertEqual(1, len({state["id"] for state in states}))
        self.assertEqual(1, len({state["expires_at"] for state in states}))
        self.assertIsNone(states[0]["questions"]["reading-2"])
        self.assertIsNotNone(states[3]["questions"]["reading-2"])
        for before, after, exchange in zip(states, states[1:8], exchanges[1:8]):
            slot = exchange["request"]["path"].split("/")[-1]
            self.assertEqual(before["revision"], exchange["request"]["body"]["revision"])
            self.assertEqual(exchange["request"]["body"]["value"], after["answers"][slot])
        self.assertIsNone(states[6]["answers"]["taste-2"])
        self.assertEqual("ready", states[7]["status"])
        for state in states[:-1]:
            self.assertIsNone(state["result"])
        self.assertEqual({"value": 1, "accuracy": "exact"}, states[-1]["progress"]["remaining_pieces"])
        self.assertEqual("piece", states[-1]["result"]["piece"]["target"]["kind"])
        self.assertEqual(states[-1]["result"]["piece"]["id"], states[-1]["result"]["piece"]["target"]["id"])

    def test_choices_are_bound_to_their_tour_round_and_fixed_across_steps(self):
        states = [exchange["response"]["data"] for exchange in self.flow["exchanges"]]
        first = states[0]["questions"]
        level_ids = {choice["piece"]["id"] for choice in first["level"]["choices"]}
        listening_ids = {choice["id"] for choice in first["listening"]["choices"]}
        self.assertFalse(level_ids & listening_ids)
        for exchange in self.flow["exchanges"][1:8]:
            request = exchange["request"]
            slot = request["path"].split("/")[-1]
            value = request["body"]["value"]
            if slot in ("listening", "taste-1", "taste-2") and value is not None:
                self.assertIn(value, [choice["id"] for choice in first[slot]["choices"]])
        for state in states:
            for slot in ("level", "listening", "taste-1", "taste-2", "mood"):
                self.assertEqual(first[slot], state["questions"][slot])

    def test_failures_validate_and_only_account_authentication_uses_401(self):
        cases = json.loads((DOCS / "fixtures/find-my-match-errors.json").read_text())["cases"]
        for case in cases:
            self.validator("ErrorEnvelope").validate(case["response"])
            self.assertEqual(case["http_status"] == 401, case["response"]["error"]["code"] == "unauthenticated")
            if case["http_status"] == 429:
                self.assertGreater(int(case["headers"]["Retry-After"]), 0)

    def test_existing_v2_paths_and_shared_summary_schemas_are_unchanged(self):
        previous = yaml.safe_load((ROOT / "public/api-handoff/v2/revisions/2026-10-29.2/openapi.yaml").read_text())
        for path, operation in previous["paths"].items():
            self.assertEqual(operation, self.api["paths"][path])
        for name, schema in previous["components"]["schemas"].items():
            self.assertEqual(schema, self.api["components"]["schemas"][name])


if __name__ == "__main__":
    unittest.main()
