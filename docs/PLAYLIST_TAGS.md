# Playlist tags

Featured tags are managed from **Repertoire → Playlists → Manage tags**. Create,
rename and delete happen through session-authenticated, CSRF-protected AJAX
requests. Edit switches one name to an inline input with Save/Cancel; duplicate
names and request failures stay in the modal. A tag can be selected in the
playlist creation and editing forms, or cleared with No tag.

`playlist_tags` owns the names. `playlists.featured_tag_id` identifies the selected
tag; `playlists.featured` remains a compatibility copy of its name for the legacy
iOS API and existing web collection readers. Use `App\Services\Admin\PlaylistTags`
for tag writes and its `assignment()` inside playlist write transactions.
Renaming updates every associated playlist, including retained Journey records.
Deleting clears both the association and compatibility value without deleting
playlists or changing their pieces. Assignment, rename and delete lock tags
before updating playlists so a concurrent write cannot restore a deleted name.

The migration imports exact existing nonblank names, sharing one tag for equal
names. Case and surrounding whitespace remain distinct during import to retain
existing values; blank legacy strings keep their old API values without creating
an empty tag. New names are trimmed and limited to the existing 255-character
database capacity. A SHA-256 name key enforces exact-name uniqueness independently
of the database collation. New IDs and loaded tag relationships are hidden from
playlist JSON. No API route, response key, playlist eligibility or media changes.

## Deployment and rollback

1. Deploy the `Playlist` model's hidden-field additions to **every server first**,
   including any long-running PHP processes. They are safe before migration and
   prevent the new association ID from appearing in legacy JSON during rollout.
2. Pause admin playlist writes during the schema/code switch. Run
   `2026_10_20_180000_create_playlist_tags_table.php` through the owner's normal
   migration workflow. It creates the tag table, association and backfill without
   changing existing `featured` values, dates or piece ordering.
3. Deploy matching controllers, service, routes, Blade, the new
   `public/js/views/admin-playlist-tags.js` and Mix manifest; refresh compiled
   views through the usual release process. Resume admin writes after verifying
   the directory, modal and playlist selector. No queue or scheduler is needed.
4. Verify existing General/Journey API payloads and a tag rename in the intended
   environment. Local regressions use isolated SQLite; MySQL foreign-key and
   concurrent-write checks belong in a disposable deployment rehearsal.

Rollback removes the association and tag table while retaining each playlist's
latest `featured` name. Revert the dependent admin UI/service with that migration;
keep the model's compatibility hiding until the new column is removed on every
server. Unassociated tags have no representation in the old schema, so retain a
database backup if they need to survive rollback.
