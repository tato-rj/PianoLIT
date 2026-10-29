# User performance submissions retired

Arthur retired user-uploaded piano recordings on October 29, 2026. The runtime
feature is removed across the website, administration and legacy clap API.

Removed:

- The Performance/Clap models, relationships, policies and factories.
- Web upload credentials, submission/deletion/clap endpoints, resumable upload
  code, confirmation/progress overlays and community performance views.
- The file-manager processing/deletion webhook and its outbound client.
- Stage administration, review/approval/edit/delete controls and the legacy
  What's new announcement advertising user uploads.
- Submission/approval events, listeners, admin notifications, both mailables and
  editable email bodies, automatic sending switches and preview entries.
- Submission badge queries and icons on repertoire cards.

PianoLIT's own videos, Synthesia, audio and score delivery still use the existing
Tutorial/HasMedia system. Private metronome voice counting is independent.
Neither is part of this retirement.

The old web-app URLs follow the app's existing missing-page redirect to Discover.
The removed admin, legacy clap API and file-manager endpoints return 404. Retired
email keys return 404 for preview/settings and fail send validation, including
when an old enabled setting still exists in the database.

The v2 Discover response retains its required `media.performance` boolean as
false for compatibility. That field represented community submissions; the
remaining tutorial/Synthesia/audio/score fields retain their existing behavior.
Shared legacy piece serialization and published media URLs are unchanged.

## Data and deployment

Historical schema migrations, existing performances/claps rows, stored uploads,
past notifications and saved email settings are not deleted by this code change.
They have no active model, route, renderer or delivery source. Their eventual
disposition is a separate data operation; do not rewrite applied migrations or
delete provider files while deploying this removal.

Deploy the code together through the normal Git release. Refresh route/view
caches and Composer's autoloader, and restart long-lived workers so the old
listeners cannot remain loaded. Before restarting, review any queued or failed
jobs referencing the removed submission/approval mail classes and quarantine
those jobs rather than replaying them or purging unrelated work. Existing cached
admin pages may need a reload to show the current catalog. The subsequent piece-sharing email removal is documented in
`docs/LINK_SHARING.md`.

No migration, normal-environment cleanup command, external file-manager request
or customer email was executed for local verification.

## Verification

`PerformanceRetirementTest` exercises the closed endpoints, rejects old email
keys even with saved enabled settings, and renders the published piece media
without upload scripts or database queries against submissions/claps. Existing
email catalog/switch, piece-card, catalogue-index and v2 Discover checks cover
the remaining paths. All tests use the isolated in-memory review environment.
