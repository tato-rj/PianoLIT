# Editing emails

Email bodies live here, grouped by purpose. New emails follow the same convention:
`welcome/`, `memberships/`, `shop/`, `tutorials/`, and so on. Each meaningful
variant gets its own Blade view. Text and button labels belong in the views,
while PHP mail classes handle runtime data and delivery.

Titles and headings never end with a period, in either HTML or plain text.
Keep sentence punctuation in body copy and internal catalogue punctuation.

## Shared background

All email page backgrounds use the web app's `--color-surface-subtle`, resolved
to `#f8f9fa` for inbox compatibility. This covers the editorial theme, older
Laravel mail theme and simple/raw layouts, including their outer wrappers and
main content surfaces. Colored promotional sections and white cards keep their
own surfaces. Keep the `bgcolor` fallbacks on both layout families in sync.

Edit `resources/sass/emails/welcome.scss` for editorial emails and rebuild its
theme. Older emails use `resources/views/vendor/mail/html/themes/default.css`
and `layout-simple.blade.php`. The welcome logo has a transparent outer canvas
so its floor shadow blends into the shared background; publish its matching
resource/public image and manifest hash together.

## Welcome emails

| Email | Edit this view |
| --- | --- |
| Website signup | `welcome/website.blade.php` |
| Web app signup | `welcome/webapp.blade.php` |
| iOS signup | `welcome/ios.blade.php` |

Each variant contains its introduction, platform section, button labels and
screenshot filename. Changes there affect only that version. Everything from
“A world to explore” down is shared through `welcome/shared.blade.php`:
edit `welcome/hubs.blade.php` for the five hubs, `welcome/premium.blade.php` for
the Premium benefits and Go Premium button, and `welcome/pieces.blade.php` for
the next-piece heading, description and Browse the PianoLIT library button.
That last button opens the web app's Explore page. These same views supply
HTML and plain text.
Keep `{{ $firstName }}`, URL variables, Blade directives and component arguments
when editing the surrounding wording. Leave `mix(...)` around screenshot paths
so browsers load the correct asset version.

Shared welcome layout parts are here:

| Change | File |
| --- | --- |
| Shared logo artwork/markup | `components/html/editorial/logo.blade.php` |
| Welcome greeting | `components/html/welcome/hero.blade.php` |
| Section markup | `components/html/welcome/section.blade.php` |
| Shared hub copy and image choices | `welcome/hubs.blade.php` |
| Image-left hub section and rows | `components/html/welcome/{hubs,hub}.blade.php` |
| Premium benefits copy and icon choices | `welcome/premium.blade.php` |
| Centered Premium section and icon rows | `components/html/welcome/{benefits,benefit}.blade.php` |
| Three piece cards and library action | `welcome/pieces.blade.php`, `components/html/welcome/pieces.blade.php` |
| Button markup | `components/html/welcome/button.blade.php` |
| Welcome title/preheader | `components/html/welcome/message.blade.php` |
| Shared email shell and footer | `components/html/editorial/{message,footer}.blade.php` |
| Plain-text layout counterparts | `components/text/welcome/` |

The editorial shell and footer also serve the Freepick email. If changing shared
greeting or footer wording, update its plain-text counterpart too. Introduction and section
copy are automatically taken from the body views in both formats. Hub rows place
128px square images to the left of the existing copy, on one pale lavender
section. Their table columns stay side by side on phones, with images scaling
down. Premium benefits follow as centered blue line icons, headings and text;
the three piece cards also stay side by side on phones.
Shop's new hub and Games are labeled as coming soon; update these labels when
their homepage destinations launch.

Actual welcomes select up to three distinct random past Free Picks through
`App\Services\WelcomePieces`, excluding the current due pick and requiring
artwork, a composer and a tutorial. A small/empty history produces fewer cards;
it never invents or duplicates pieces. These past picks retain normal access
rules. Admin browser/test previews use stable fictional cards with inert links,
so they do not query the live repertoire or accounts.

The reusable footer follows the supplied left-aligned reference: a regular linked
YouTube social icon first, then a delivery-reason note, contact
copy, Unsubscribe / email preferences, Privacy, Help and Forgot password links,
and copyright. The shared outlined PianoLIT icon sits at the lower right without
a floor shadow. There is no postal address or other social channel. The delivery
reason and contact sentence share the same 14px note style. The account-specific or list-specific
delivery-reason copy is supplied by the corresponding message component/view.
The footer has no social buttons or App Store badge/link. The existing App Store
actions in the welcome body remain separate. Free Pick's note says “because you
are subscribed to PianoLIT Free Picks.” in HTML and plain text.
The **Unsubscribe / email preferences** link leads to the signed-in account's existing email-list preferences. It
opens that tab directly; recipients choose which lists to leave there. Rendering
a welcome never subscribes, unsubscribes or changes an automatic-sending switch.

The four Premium icons reuse the project's Lucide artwork as transparent PNGs.
The three preview thumbnails crop existing period artwork and are preview-only.
Rebuild these assets using ImageMagick and `sharp` (available in the bundled
workspace runtime):

```sh
node scripts/build-welcome-email-details.js
```

If `sharp` is outside the project's Node modules, set `PIANOLIT_SHARP_MODULE`
to its absolute module directory for this command. Preserve the accompanying
`LUCIDE-LICENSE.txt` when publishing the raster icons.

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

**Email Lists → Send preview email** also sends its single recipient immediately,
including Free Pick, custom-list and monthly newsletter previews. The page waits
for rendering and mail-server submission, reports failures on that request and
keeps the entered recipient for retry. Success means submitted to the configured
mail server. New previews need no queue worker; subscriber campaigns still do.

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

## Freepick email

Edit `lists/freepick.blade.php` for the title, description placement, feature
copy, primary action and Premium invitation. It uses the same
`mail::editorial.message` shell and `mail::editorial.footer` as all three welcomes.
Keep future redesigned emails on these shared components so their social row,
branding, contact text, legal links and opt-out finish stay consistent. Plain-text counterparts
live under `components/text/editorial/`.

The header, large cover, portrait/level/title row, introduction, resource section
and Premium section live in `components/{html,text}/freepick/`.
`editorial/feature` supplies the reusable icon-left/text-right row. Both email
families use the shared font/button tokens and compiled `welcome.css` theme;
edit `resources/sass/emails/welcome.scss` and rebuild with `npm run emails:styles`.
`EditorialLayout` clones the Markdown renderer per message so other emails keep
their default theme in long-running workers.

All redesigned email logos use `mail::editorial.logo`. Its default preserves the
welcome emails' floating artwork and floor shadow; `shadow => false` selects the
identical outlined icon on transparency, without that shadow/canvas. The visible
icon is 78px wide in welcomes and 48px wide in Freepick. Rebuild both assets with
`python3 scripts/build-welcome-email-assets.py --logo-only`; do not substitute the
separate square app icon. Freepick begins with the smaller centered logo and
**Free pick of the week**, then a shorter cover image (240px desktop / 170px phone)
and the familiar piece identity/description/action. The content panel has
24px rounded top corners overlapping the cover's bottom by 24px. The cover is a
linked table background with a rounded white edge in its last 24px, followed by
the white content table. Its 26px rounded fill paints 2px beyond a 24px frame
with visible overflow to prevent a photo-colored hairline at fractional zoom.
Keep the rounding on the fill, not the cell, and do not clip this overpaint.
Keep this structure instead of negative margins or
positioned layers, which Gmail does not retain; the cover keeps its 240px/170px
responsive heights and a regular image fallback for Word-based Outlook. This panel is
white through the end of the resource features; More music to discover returns
to the shared `#f8f9fa` background with 36px of top padding, matching Keep the
inspiration going. The logo/title
remain above the image. The image uses a centered crop. Native inbox rendering
remains a release check.

`FreePickEmail` derives resource availability from its pinned piece and tutorial
records. Score/annotation copy requires a public-domain PDF; performance,
Synthesia and audio rows require those resources. **Comments as you watch**
promotes commentary synchronized with playback and navigation to passages, only
when comments exist on the performance/Synthesia recording the player uses.
Do not call this feature moments in the email. Lessons are no longer promoted.
Separate-hand audio
is mentioned only when at least one hand is available. The free-account note
describes the weekly selection; Premium describes the wider library plus
public-domain score downloads/printing. These views never change access rules.

Freepick's feature icons stay compact at 24px, while regular copy, captions,
feature headings and button labels use at least the default 16px font. The
piece description is left-aligned; the section heading is **Let's dive in**,
with no period. Account copy omits free, and the Prefer your phone line is
removed from both HTML and plain text. Shared footer contact and delivery-reason
copy use the same 14px note style; copyright retains its smaller type. The **More music to discover** row
reuses the shared three-card component with smaller typography and shows the
latest eligible pieces, excluding the pinned Freepick. Phone cards stack as
compact image/text rows, keeping captions readable. `FreePickEmailPieces`
selects these by creation date and ID, requiring tutorials, art and a composer.
Edit its heading, description and Explore action in `lists/freepick.blade.php`.

Subscriber messages retain their actual Free Pick list unsubscribe URL, labeled
**Unsubscribe** in the shared footer. Previews use the ordinary email-preferences
destination, without a real subscriber's opt-out link. Campaign tracking, pinned
selection and duplicate/opt-out delivery guards stay in their existing classes.

Only **Explore this piece**, **Browse the PianoLIT library** and **Go Premium**
receive first-party campaign tags in subscriber deliveries. Their calls in
`lists/freepick.blade.php` use `FreePickCampaignLinks` with the shared labels
`utm_source=pianolit`, `utm_medium=email`, `utm_campaign=freepick` and the
placements `explore_piece`, `browse_library`, `go_premium`. Keep the helper on
these three buttons only; all other links, including unsubscribe, remain direct
and untagged. Preview messages and excluded recipients stay untagged. HTML/plain
text use the same destinations, and the existing Campaigns report counts
eligible first arrivals with its normal deduplication and visitor choices.
Keep the template's helper fallback: workers started before the mail-class update
can supply the existing `list_id`/`subscription` fields without `campaignLinks`.
The fallback preserves the same preview and recipient exclusions during reloads.

Export standalone previews of all four redesigned emails without normal-environment
data or delivery:

```sh
php -d error_reporting=8191 scripts/preview-freepick-email.php /tmp/pianolit-email-preview
```

This extends the isolated welcome exporter with an unsaved Chaminade sample,
four sample resources, video-commentary availability, three fixed latest-piece
cards and `freepick.html`/`freepick.txt`. Optional local public
piece/portrait artwork is copied when present; a fresh checkout falls back to
existing period artwork. No account/repertoire query, real send or provider call
is performed.

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

## Account email changes

Edit `account/confirm-email-change.blade.php` for the subject-purpose copy and
confirmation button used by `App\Mail\ProfileEmailChange`. It uses the existing
Laravel HTML/plain-text mail components. The new address receives a one-hour
signed confirmation link; no account email or subscription is changed until the
signed-in owner confirms. This is an essential account email, independent of
marketing-list preferences. Profile regressions fake mail delivery.

## Sunday admin snapshot

Edit `admin/report/index.blade.php` for the weekly report's composition, labels
and analytics button. The matching HTML/plain-text section templates live in
`components/{html,text}/snapshot/`. It uses the welcome/Free Pick editorial theme,
outlined logo, primary button, piece artwork and shared footer's optional internal
mode. The old `admin/report/{legacy,template,card,records}.blade.php` views only
serve report jobs queued before this redesign.

The Sunday email now shows new/active/returning accounts, new users exploring
music, a daily chart, web/iOS behavior, current membership status and use, and
compact rankings for active accounts' saved countries, campaign results and music.
Campaigns show weekly tagged arrivals, trial starts and first Stripe payments;
music shows both the biggest increases in exploring accounts and the most explored
pieces. Countries/campaigns/music share `snapshot/panel.blade.php`; edit the
paired `countries`, `campaigns` and `pieces` components for their row layouts.
PHP supplies aggregate numbers, not subscriber email lists.
See `docs/WEEKLY_ADMIN_SNAPSHOT.md` for exact definitions, source limitations and
release notes. Render a clearly labeled fictional preview with:

```sh
npm run emails:styles
php -d error_reporting=8191 scripts/preview-weekly-snapshot.php /tmp/pianolit-weekly-snapshot-preview
```

The exporter uses the welcome preview's isolated boot; it never sends mail or
reads production accounts. Open `weekly-snapshot.html` / `weekly-snapshot.txt`.

Settings → Emails also lists **Weekly admin snapshot** under Administration.
Browser renders the current template; Email uses the shared preview-recipient
field and `[Preview]` subject prefix. Both use the same labeled fictional data
from `App\Services\Admin\WeeklySnapshotPreview`, with existing preview images and
inert report/piece links. The Sending switch controls scheduled and queued admin
reports, defaults on, and does not prevent previews.
