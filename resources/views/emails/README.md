# Editing emails

Email bodies live here, grouped by purpose. New emails follow the same convention:
`welcome/`, `memberships/`, `shop/`, `tutorials/`, and so on. Each meaningful
variant gets its own Blade view. Text and button labels belong in the views,
while PHP mail classes handle runtime data and delivery.

## Welcome emails

| Email | Edit this view |
| --- | --- |
| Website signup | `welcome/website.blade.php` |
| Web app signup | `welcome/webapp.blade.php` |
| iOS signup | `welcome/ios.blade.php` |

Each variant contains its introduction, platform section, button labels and
screenshot filename. Changes there affect only that version. The hub section is
shared: edit `welcome/hubs.blade.php` to change its heading, introduction, five
hub descriptions, status labels, artwork or final PianoLIT.com button across
all three welcomes. The same views supply HTML and plain text.
Keep `{{ $firstName }}`, URL variables, Blade directives and component arguments
when editing the surrounding wording. Leave `mix(...)` around screenshot paths
so browsers load the correct asset version.

Shared welcome layout parts are here:

| Change | File |
| --- | --- |
| Logo markup and greeting | `components/html/welcome/hero.blade.php` |
| Section markup | `components/html/welcome/section.blade.php` |
| Shared hub copy and image choices | `welcome/hubs.blade.php` |
| Alternating hub section and rows | `components/html/welcome/{hubs,hub}.blade.php` |
| Button markup | `components/html/welcome/button.blade.php` |
| Outer layout, preheader and footer | `components/html/welcome/message.blade.php` |
| Plain-text layout counterparts | `components/text/welcome/` |

Shared component changes affect all three welcomes. If changing shared greeting
or footer wording, update its plain-text counterpart too. Introduction and section
copy are automatically taken from the body views in both formats. The hub rows
alternate text/image, then image/text using `imageLeft => true` on every second
row. Their table columns stay side by side on phones, with images scaling down.
Shop's new hub and Games are labeled as coming soon; update these labels when
their homepage destinations launch.

The hub thumbnails reuse the existing homepage artwork as small JPEGs for email
compatibility. Rebuild only these images without changing the device mockups:

```sh
python3 scripts/build-welcome-email-assets.py --hubs-only
```

For shared colors, font sizes, logo size or spacing, edit
`resources/sass/emails/welcome.scss` and run:

```sh
npm run emails:styles
```

This regenerates `components/html/themes/welcome.css`, which Laravel inlines into
the sent HTML. Keep the logo's HTML width/height attributes in sync with size
changes for email clients that use those attributes. Ordinary wording, label and
markup edits need no asset build.

Regular welcome buttons share the web app's default primary-button dimensions
and finish through `resources/sass/shared/_button-foundations.scss`. That shared
file affects both web UI and emails. The App Store link uses `kind: app-store`
and the badge image in each applicable welcome view; its label supplies the
accessible name and plain-text link. The supplied Apple artwork is exported from
`resources/images/apple/download.svg` for email use with:

```sh
python3 scripts/build-welcome-email-assets.py --badge-only
```

The PNG badge retains its proportions and matches the regular button's 46px
height. Keep its HTML height attribute in sync if shared button dimensions change.

## Preview changes

Save the Blade file, then open the matching **Browser** preview in
**Settings → Emails** and refresh. Send a new test preview to see changes in
Mailtrap; previously received emails retain their original content. Previews use
fictional account data and do not enable automatic sending.

For standalone local HTML/text samples without normal-environment data or email:

```sh
php -d error_reporting=8191 scripts/preview-welcome-emails.php /tmp/pianolit-welcome-preview
```

Open `website.html`, `webapp.html` or `ios.html` in that output folder. Their `.txt`
files show the matching plain-text version. See `docs/WELCOME_EMAILS.md` for
artwork rebuilding and deployment notes.

Existing older emails may still use the shared Laravel components under
`resources/views/vendor/mail/`. New custom components and theme views belong in
this folder's `components/` tree, organized by HTML/plain text and email family.

If a server reports `View [welcome.hero] not found` after the folder move, check
that `components/html/welcome/` and its text counterparts were deployed and
refresh the server's Laravel configuration cache. The application also registers
these current component paths at runtime so older cached mail paths cannot hide
them. Clearing configuration with `php artisan config:clear` from the server's
Laravel root repairs the stale-cache case immediately; use the usual deployment
process to rebuild caches and reload long-running workers.

## Monthly PianoLIT newsletter

Edit `newsletter/monthly-digest.blade.php` for the body, content labels, button
labels and newsletter unsubscribe copy. Edit `newsletter/monthly-subject.blade.php`
for the subject. HTML and plain text share this body. Titles and descriptions
come from each hub's content editor, or `config/newsletter.php` for code-based
feature, game and tool releases. Links use the appropriate public hub hosts.

The PianoLIT News list contains explicit newsletter opt-ins, independently of
Free Pick and other lists. General account signup does not join it. The first
of each month at 08:00 America/New_York, the scheduler collects the previous
calendar month's new public content. Months without news send nothing; a
recipient is skipped if all selected content has been withdrawn before delivery.

Preview through PianoLIT News → Browser preview in Email Lists, or inspect the
next selection without writing or sending:

```sh
php artisan newsletter:send-monthly
```

`--execute` queues a real newsletter. Monthly and recipient guards prevent
ordinary retries from sending duplicates; opt-outs are rechecked immediately
before delivery. See `docs/EMAIL_REPORTS.md` for sources, launch entries,
deployment and retention.
