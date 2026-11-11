# Crash-course lesson email builder

The Create lesson and Edit lesson pages share `admin.pages.crashcourses.lessons.editor` and `admin.components.email-builder.editor`. Existing lessons open as a text block without changing saved content. Saving the lesson converts that content to the structured design. Existing lessons that have never been saved in the builder keep their original email template.

## Editing

- Add Text, Hero, Image, Button, Panel, Two columns, Divider or Space. Click a component to insert it after the selected block, or drag it to the desired position in the email. Drag a block’s Move handle to reorder it; Move up and Move down provide keyboard and touch alternatives.
- Select a block to edit its text with TinyMCE, optional heading, image and description, button/link, colors, alignment, background image, padding, spacing and corners. Hero and Panel combine text, an optional image and an optional button. Two columns stack on phones.
- Click the email background or surrounding canvas to deselect a block. Escape also clears selection from the canvas and standard builder controls. Deselecting keeps pending edits; using the inspector, component palette or preview-size controls preserves selection.
- Delete a block with its canvas Delete control or Delete block in its settings. Undo restores it, including the latest writing. Canvas controls are hidden in Preview; deletions take effect in saved lessons only after Save lesson.
- Email style controls the content background, background image, default text color and font. The subject and inbox preview text are separate. `[first_name]` works in text, headings, button labels and preview text; previews use Joe. Subscriber values are escaped in HTML.
- Save for reuse adds a named snapshot to the shared admin block library. Inserting a saved block creates an independent copy. Deleting a library entry does not change lessons that used it.
- Undo/Redo covers content, appearance and block changes within the current editing session. The leave guard covers unsaved subject/design changes and pending uploads. Save lesson is explicit; Preview shows current unsaved content without saving or sending. Saved email opens the persisted version. Existing test-email actions still send the saved version only when explicitly requested.

TinyMCE **5.10.9** is pinned and served locally for this builder, retaining the existing TinyMCE 5 integration API without cloud-domain registration. `scripts/email-builder-assets.js` lists the shipped core, theme, icons, Link/Lists plugins, skins and LGPL license. Other editors retain their existing setup. No editor content is sent to a hosted email-builder service.

Image uploads use the shared drop/click field. Accept JPG, PNG and GIF up to 4 MB and 4000 × 4000 pixels. The admin/CSRF-protected endpoint saves unique public files under `crashcourses/emails/{course_id}`. Image URLs are absolute, so they work outside the website. Uploaded files are retained even when blocks are removed: previously delivered emails and saved blocks may still reference them. There is no automatic image cleanup in this change.

## Email rendering and compatibility

`Services/CrashCourses/EmailDesign` owns the bounded version 1 document, validation, rich-text sanitization, personalization and plain-text conversion. Requests allow at most 40 blocks and 200 KB of design JSON; rendered body HTML must fit within 60 KB. HTTP(S) media/link URLs and email-only `mailto:` links are allowed; scripts, event attributes, embedded frames, data URLs, credentials in URLs and arbitrary CSS are excluded. The browser preview uses the same server renderer as delivery in a sandboxed iframe; late preview results cannot replace a newer edit. Image upload completions remain tied to their original block/field.

Editable templates:

- `resources/views/emails/courses/lesson.blade.php`: shared message/footer metadata.
- `resources/views/emails/courses/content.blade.php`: content background and block sequence.
- `resources/views/emails/courses/block.blade.php`: table-based HTML for each component.
- `resources/views/emails/components/{html,text}/courses/lesson.blade.php`: HTML shell/responsive rules and plain-text counterpart.

The standard logo, 600px frame, 48px desktop/24px phone insets, footer and contact-based course cancellation remain shared. Desktop preview uses a 680px viewport so it actually exercises desktop insets; Phone uses 375px. Both scale to fit the editor without changing their internal viewport. Background images have solid-color fallbacks. Desktop Outlook and other inboxes may omit background images or rounded corners; content remains readable. Browser previews are not a substitute for Gmail, Apple Mail and Outlook acceptance checks.

`crash_course_email_designs` stores design JSON and an optimistic revision keyed to the lesson. It is separate from shared Eloquent attributes and relationships, preserving lesson JSON keys. Saving locks the lesson and commits the subject, compiled legacy `body`, reading time and structured design atomically. Stale revisions, including old plain-text forms opened before conversion, cannot overwrite a saved design. Validation restores the submitted design and revision. Duplicate copies the design; lesson deletion cascades to its design. Every nested lesson action checks the owning course. Course subscriptions, lesson timing, feedback emails, existing route names and delivery/queue behavior remain independent.

## Installation and verification

Install `2026_11_10_120000_create_crash_course_email_designs.php` before deploying matching code. It creates only the design and shared-block tables; it does not convert existing lessons. Installed on the verified local MySQL database on November 10, 2026. Production migration/deployment and real-inbox acceptance remain pending. Deploy the matching views, generated JS/CSS, local TinyMCE assets, icon catalogs and Mix manifest together, refresh compiled views and restart email workers as part of the usual code rollout.

Focused checks:

```sh
php -d error_reporting=8191 vendor/bin/phpunit -c phpunit-review.xml --filter 'CrashCourseEmailBuilderTest|AdminCrashCoursesTest|EmailViewComponentsTest|test_nested_lesson_breadcrumbs'
node --test tests/js/email-builder.test.cjs
npm run test:css
npm run test:build
git diff --check
```

PHP coverage includes legacy loading, create/update/duplicate/delete, independent reusable blocks, escaped personalization, HTML/plain text, preview without sending, safe uploads, invalid designs, ownership/authentication, stale revisions, validation retention and transaction rollback. Browser checks use a disposable SQLite fixture with fake mail/HTTP/Redis, never real lesson or subscriber records. Real inbox rendering, physical touch dragging and simultaneous MySQL requests are not claimed by these checks.
