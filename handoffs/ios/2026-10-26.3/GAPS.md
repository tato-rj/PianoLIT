# Exact gaps in revision 2026-10-26.3

The backend contains implemented local login/session/Discover/history routes.
That is distinct from a deployed, verified integration. This delivery is
fixture-only and does not advance readiness.

| Gap | Evidence | Consequence / required completion |
| --- | --- | --- |
| Verified HTTPS API origin | Canonical manifest has null staging/production base URLs and `deployment_status: not_deployed` | A backend release must apply the session migration, load the guard/routes/config and verify HTTPS/Authorization forwarding. Supply a verified origin separately; never guess a production host |
| Native membership/media entitlement contract | Neither AccountSummary nor Discover includes membership, permissions, preview limits or locked state; no v2 status/access operation exists | Product/backend/native must specify and test actual native access semantics. A Free Pick badge or availability icon cannot authorize access |
| Full v2 piece detail | `.3` registers no v2 detail route; PieceSummary contains no media/PDF URLs or full PieceVC model | Implement a separate detail contract or verify an explicit legacy detail adapter. The included legacy fixture demonstrates current serialization, not a completed native adapter |
| Composer navigation by ID | Destination carries an ID, but existing legacy search looks up a composer by name | Approve/test an ID-safe native mapping or supply a v2 repertoire operation; do not assume numeric composer ID is a piece ID or search query |
| Full result-list / View all navigation | No v2 search/catalogue/highlights operations or section action DTOs; empty legacy search returns null | Specify exact destinations/filters/pagination before enabling those actions. Existing legacy requests are enumerated in CONNECT_DISCOVER.md |
| Provider-backed membership cases | Existing legacy status can contact Apple; no live provider verification was performed | Offline `active` is an actual synthetic super-user override result, and `expired` is an actual no-membership result. Neither is proof of a paid subscription or a completed App Store flow |
| History confirmation/access enforcement | History POST checks bearer/piece existence and upserts; it does not prove a view rendered or authorize native playback | Client must send it only after confirmed allowed detail opens. A duplicate updates recency. Native permission resolution still needs verification |
| Native UI/build acceptance | No current iOS checkout, commit, device build or approved phone/iPad render was inspected here | Verify decoder, destination adapters, Keychain, account switch, errors, actual opens and rendering in the one updated UIKit app |
| Registration/recovery callbacks | No v2 register/reset operations exist; existing flows must be followed by v2 password login | Implement and verify those completion paths in the updated app before enabling the full account migration |
| Original `.3` delivery omissions | Canonical manifest omitted the sync script; its nine fixtures lacked full section-kind/history/navigation/access coverage | This artifact supplies real scripts and expanded fixtures in an independently checksummed supplement while retaining the canonical snapshot |
| Draft schema omissions | Canonical OpenAPI omits some generic application failures, and does not define legacy detail/status or client-scenario schemas | Decode the common error envelope for non-200 statuses. Fixture index labels every actual status and distinguishes canonical v2 schemas, captured legacy JSON and authored client scenarios |

The supplied scenarios may set a **local test-double decision** to allowed or
denied to test call sequencing. Those decisions are not API fields, not an
approved native access policy, and not evidence of a server-enforced lock.

No new verified API URL, entitlement field, detail endpoint, legacy Swift branch,
live credential, billing operation or production-media request is introduced.
Published canonical `.3` files are unchanged. A changed canonical contract or
readiness statement requires a new revision.
