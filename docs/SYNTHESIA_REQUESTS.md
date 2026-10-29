# Synthesia Requests

As of October 29, 2026, new browser requests are exclusively for Synthesia videos.
The canonical feature is `SynthesiaRequest`, stored in `synthesia_requests`.
PianoLIT's published `Tutorial` videos, media types, files and delivery remain
independent and unchanged.

## Editing map

- Model and relations: `app/SynthesiaRequest.php`, `User::synthesiaRequests()`,
  `pendingSynthesiaRequests()`, `publishedSynthesiaRequests()` and
  `Piece::synthesiaRequests()`.
- Browser submission: `WebApp/SynthesiaRequestsController`,
  `webapp.users.synthesia-requests.store` (`POST /users/synthesia-requests/{piece}`),
  and `webapp/piece/synthesia-request`. Ownership comes from the web session;
  membership, one-pending-request and repeat-piece checks are retained. The
  server fixes `types` to serialized `['Synthesia']`, even if an old browser form
  submits other choices. The multi-type form and its validation script are gone.
- Administration: `routes/admin/synthesia-requests.php`,
  `Admin/SynthesiaRequestsController`, `Services/Admin/SynthesiaRequestsTable`,
  and `admin/pages/synthesia-requests/`. The shared table has SQL search,
  status filters, fixed sortable columns and native 12-result pagination. Its
  Status column shows Pending or Published with the publication date, before
  the final action buttons. Publish uses the shared `btn-green` variant.
  There is no Details action or separate publication-date column. Piece opens
  the user-facing piece page in a safe new tab. Pending Publish uses a native browser confirmation; Delete retains the
  shared confirmation modal. Repeat publication preserves the first date and does
  not dispatch another publication notification. Writes preserve validated
  directory filters and clamp an emptied final page.
- Events, listeners, mailables and admin notifications live in their respective
  `SynthesiaRequests` directories. Editable emails are
  `emails/synthesia-requests/{new,published}.blade.php`; switches are
  `synthesia-request` and `synthesia-published` in the Synthesia Requests section.
- New activity outcomes are `synthesia_request_created` and
  `synthesia_request_rejected`. Readers continue to recognize historical
  `tutorial_request_*` outcomes and old form controls, without rewriting history.
- There are no dedicated request styles: the shared admin table, filters,
  status badges, buttons, pagination and modal components own the appearance.
  The renamed empty-state artwork is `webapp/icons/empty-synthesia-requests.svg`;
  the original public URL remains available to cached consumers.

## Permanent legacy-app boundary

These API routes and names must remain indefinitely:

| Method | URL | Laravel route name |
| --- | --- | --- |
| GET | `/api/tutorial-requests` | `api.tutorial-requests` |
| GET | `/api/users/tutorial-requests` | `api.users.tutorial-requests.show` |
| POST | `/api/users/tutorial-requests` | `api.users.tutorial-requests.store` |

`Api/Legacy/SynthesiaRequestsController` preserves their submitted `user_id` and
`piece_id`, optional `video_types`, validation, 403 messages, success JSON string,
missing-account response, date formatting and newest-first piece-array response.
GET retains its original `log.app` middleware; POST does not gain it. This change
does not impose browser membership policy, a v2 token, or a new input format on
the old app. Original choices and null `types` remain stored as received.

The legacy `User`/`Piece` relationship accessors remain, including the serialized
`tutorial_requests` relationship name. No shared Piece/User append or media field
is renamed. Legacy controller logic uses those accessors over the renamed table.
`App\TutorialRequest` and the two `App\Mail\Tutorials` classes are compatibility
shims so previously serialized mail/model identifiers can be restored. They
inherit the canonical implementations and delivery switches; do not remove them
while such jobs can still be retried. Old admin directory bookmarks and stored
notification URLs redirect permanently with their raw query intact; already-open
admin writes and browser submission URLs continue to work under their original
guards and CSRF context.

V2 currently has no request-list/submission module. Future new-app request APIs
must use Synthesia naming, bearer-derived ownership and explicit DTOs, independently
of the permanent legacy adapter. This change does not add an undocumented v2
endpoint, modify the existing immutable handoff or claim native acceptance.

## Migration and release

`2026_10_29_160000_rename_tutorial_requests_to_synthesia_requests.php` renames the
table in place and migrates the two saved email-switch keys. IDs, ownership,
original types, request/publication dates and switch states/auditing survive.
Rollback reverses both names without deleting request rows. Applied historical
migrations remain unchanged. Conflicting old/new switch keys stop the migration
before DDL, and a retry can resume after a separately committed MySQL table
rename without overwriting the saved switches.

This is a coordinated schema/code release, not a rolling-compatible table rename:
old application processes still address the old table. For production:

1. Back up the request table and both saved switch rows. Verify the new table
   and new switch keys do not already exist; stop and investigate any collision.
2. Quiesce web/API traffic and drain or stop queue workers and scheduled work.
   Deploy the committed code through Git on every application process.
3. Run only this migration with the matching code, regenerate the Composer
   autoloader, clear/rebuild route and Blade caches, then restart workers on the
   same revision before reopening traffic. Do not run old workers against the
   renamed table. The migration itself sends no mail or notifications.
4. Compare row counts and original values, read-test both legacy GET URLs and
   the new admin directory, and exercise fictional staging submissions,
   publication and disabled email switches. Do not send test customer mail.
5. If rollback is necessary, quiesce the same processes, roll this migration back
   with the matching release still available, restore the prior code/caches and
   restart workers together. Do not restore a backup over newer request rows.

Local installation and verification are recorded in `docs/CODE_REVIEW.md`.
Production migration, deployment and native/staging acceptance remain separate.

## Verification

`tests/Review/SynthesiaRequestsTest.php` checks the legacy HTTP contracts, canonical
and old browser submissions, fixed Synthesia type, session ownership, shared
admin UI, literal SQL search, filters/sorts/pagination, guarded old routes, raw
query redirects, idempotent publication, migration round-trip, interrupted-rename recovery, switch-collision protection and queued legacy
mail/model restoration. The activity, account-removal/profile and automatic-email
regressions exercise renamed consumers as well. Review tests use isolated
in-memory SQLite with fake mail/HTTP/Redis; they do not prove production MySQL,
SMTP or native-device behavior.
