# Editing emails

Email bodies live here, grouped by purpose. New emails follow the same convention:
`welcome/`, `memberships/`, `shop/`, `synthesia-requests/`, and so on. Each meaningful
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

Edit `resources/sass/emails/welcome.scss` for editorial content. All email
branding styles live once in `resources/sass/emails/_branding.scss`; both the
welcome and default themes import them. `npm run emails:styles` rebuilds both
generated CSS files. The default theme preserves the existing transactional body
styles from `resources/sass/emails/legacy.css` without reformatting them.

## Shared width and spacing

Every HTML email, public or admin, uses a centered **600px** frame, with **48px**
horizontal body/footer insets on desktop and **24px** at viewport widths of
620px or less. Below 600px the frame fills the available width. This includes
Laravel verification/recovery notifications, editorial emails, course/raw
messages, current and queued legacy admin reports, and the retained simple layout.

`App\Support\EmailLayout` owns these dimensions. Reuse
`mail::layout.container` for the frame and Outlook fallback, and include
`mail::layout.styles` in the email head. Laravel inlines the desktop rules while
retaining the mobile media query. Never set a separate shell width or outer
horizontal padding in a theme or individual email. Full-width artwork and
internal card spacing remain part of their content components.

## Shared customer branding

Every customer email uses `components/html/editorial/logo.blade.php` with the
same outlined, shadow-free 48px `logo.png` used in the weekly admin snapshot.
Welcome emails also use this plain icon. Keep the artwork and markup here;
never copy them into individual email bodies or use the separate square app icon.

All customer emails reuse `components/{html,text}/editorial/footer.blade.php`.
The footer contains text and links only, with no repeated PianoLIT logo; the
shared logo remains in the email header.
Standard `mail::message` emails (including verification, recovery and provider
notifications) reach it through `editorial/customer-footer`. `mail::raw` course
emails delegate to that same message component and retain their lesson HTML.
`App\Support\EmailLayout` supplies absolute main-site links and footer metadata:
list campaigns retain their recipient/list unsubscribe destination, newsletters
retain their newsletter-only link, and course lessons keep contact-based
cancellation. Other messages link to account email preferences. Rendering never
changes a subscription or automatic-sending switch.

Admin reports retain their own footers. The weekly snapshot keeps its existing
internal footer mode; queued legacy reports keep their original template.

User performance submission/approval emails are retired with the community
upload feature. Their templates, mail classes, event listeners, admin previews
and sending switches are removed; the old email keys cannot be enabled or sent.
See `docs/USER_PERFORMANCES_RETIREMENT.md` for the scope and deployment notes.

The old piece-sharing email, event/listener, notification, sending switch and
preview are also retired. The piece-page Share modal now opens the visitor's
own messaging apps or copies a canonical page link; PianoLIT sends no email.
See `docs/LINK_SHARING.md` for its component and deployment notes.

Export fictional samples of every automatic customer email plus course, gift,
newsletter, email-change and Free Pick messages without sending mail:

```sh
php -d error_reporting=8191 scripts/preview-customer-emails.php /tmp/pianolit-customer-email-preview
```

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

The three platform mockups use transparent PNGs (`app-devices.png`,
`tablet-iphone.png`, `webapp-devices.png`), without a white outer image canvas.
The same browser/native-phone image is reused in Free Pick. Run
`python3 scripts/build-welcome-email-assets.py --mockups-only` to rebuild them;
the legacy JPEG masters preserve approved screen pixels while native silhouettes
provide transparent device edges. Publish their resource/public copies and
manifest hashes together. Keep the actual screens and device frames opaque.

Shared welcome layout parts are here:

| Change | File |
| --- | --- |
| Shared logo artwork/markup | `components/html/editorial/logo.blade.php` |
| Welcome greeting | `components/html/welcome/hero.blade.php` |
| Section markup | `components/html/welcome/section.blade.php` |
| Shared hub copy and image choices | `welcome/hubs.blade.php` |
| Image-left hub section and rows | `components/html/welcome/{hubs,hub}.blade.php` |
| Premium benefits copy and icon choices | `welcome/premium.blade.php` |
| Compact Premium section and icon rows | `components/html/welcome/{benefits,benefit}.blade.php` |
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
down. Premium benefits follow as compact rows with 24px blue icons beside
left-aligned 16px headings and 14px body text;
the three piece cards also stay side by side on phones.
Shop's new hub and Games are labeled as coming soon; update these labels when
their homepage destinations launch.

Actual welcomes select up to three distinct random past Free Picks through
`App\Services\WelcomePieces`, excluding the current due pick and requiring
artwork, a composer and a tutorial. A small/empty history produces fewer cards;
it never invents or duplicates pieces. These past picks retain normal access
rules. Admin browser/test previews use stable fictional cards with inert links,
so they do not query the live repertoire or accounts.

The reusable footer follows the supplied left-aligned reference: a small secondary
Visit us on youtube button first, then a delivery-reason note, contact
copy, Unsubscribe / email preferences, Privacy, Help and Forgot password links,
and copyright. The shared outlined PianoLIT icon sits at the lower right without
a floor shadow. There is no postal address or other social channel. The delivery
reason and contact sentence share the same 14px note style. The account-specific or list-specific
delivery-reason copy is supplied by the corresponding message component/view.
The YouTube button uses the shared welcome button with `kind: secondary` and
`size: sm`: the website's white surface, dark text, pale border and light shadow,
with 14px text and 4px by 12px padding. Its destination
and label also appear in plain text. The footer has no other social controls or
App Store badge/link. The existing App Store
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

Every primary email button uses one standard in
`resources/sass/emails/_branding.scss`: the web-app action's body font at 14.4px,
400 weight, 0.4px letter spacing, white text on PianoLIT `#0055fe`, 11px × 21px
padding, 22px line height, 46px total height and the verification action's 4px
corners. The welcome `.welcome-button` and Laravel `.button` renderers share this
rule, including Free Pick and admin snapshot actions; do not add family-specific
font overrides. Dimensions and primary/secondary surfaces reuse
`resources/sass/shared/_button-foundations.scss`. Website corners remain 12px.
Pass `kind: secondary` for the secondary finish and `size: sm` for compact buttons.
The App Store link uses `kind: app-store`
and the badge image in each applicable welcome view; its label supplies the
accessible name and plain-text link. The supplied Apple artwork is exported from
`resources/images/apple/download.svg` for email use with:

```sh
python3 scripts/build-welcome-email-assets.py --badge-only
```

The Open the web app buttons pass an optional `icon` URL to the same component.
Their decorative white PNG is exported from the shared compact PianoLIT mark,
displayed at 20px beside the label and omitted from plain text. Rebuild it with
`node scripts/build-welcome-email-details.js --button-icon-only` (with the
`PIANOLIT_SHARP_MODULE` override above when needed), publishing
the resource/public image and its manifest hash together.

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

All customer email logos use the single shadow-free 48px
`mail::editorial.logo`, matching the weekly snapshot. Rebuild its artwork with
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
feature headings use at least the default 16px font. Buttons follow the shared
14.4px email standard above. The
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

## Membership activation

Edit `memberships/confirmed.blade.php` for the Premium welcome headline, brief
introduction, app image, action and billing copy. It uses the same editorial
shell, `welcome.hero` and `welcome.section` components as the welcome emails,
in HTML and plain text. Arthur removed the entire Yours to explore benefits
section; the app image/action now leads directly to the plan and billing details.
Keep its open neutral background and shared typography, without pastel panels.
Both actions use regular-size shared buttons with 4px corners.

The approved feature-device mockups remain available for future use, but are not
displayed in this email. Source captures and provenance live in
`resources/email-sources/membership/`; rebuild their transparent PNGs with
`scripts/build-email-feature-mockups.js`. Preserve the intact live app screens
inside their frames. The shared benefit component retains its optional mockup
variant, and other welcome callers keep their existing icons.

The hero accepts an optional `title`. Sections may omit the heading/divider or
image, and may supply a short `note` slot for billing footnotes. The benefits
component can omit its action. Existing welcome callers retain their defaults.
Styles remain in the welcome theme; rebuild with `npm run emails:styles`.
`MembershipConfirmed` uses `EditorialLayout` so its theme cannot leak into other
messages sent by the same worker. The plan section uses the original saved
plan/trial/date snapshot: trials show the first billing date and no-payment
note, without a cancellation prompt; paid memberships show the next renewal.
Missing dates
are omitted, never invented. Keep the real Discover and My Membership links,
and leave payment, delivery and queue behavior unchanged.
The customer preview exporter above writes `membership-confirmation.html` and
its plain-text counterpart using fictional data.

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

As of 2026-10-27, the weekly snapshot's campaign block follows the GA/internal
split: conversion mode shows only consented trials and first payments, omitting
arrival totals/columns and traffic-only rankings. The shared HTML/text campaign
partials retain compatibility with historical snapshots lacking this mode flag.
General acquisition/traffic reporting belongs to GA; see `docs/CAMPAIGN_ATTRIBUTION.md`.


## Merchandise orders

`shop/orders/confirmed.blade.php` thanks the customer after verified payment and lists the purchased items and total. `shop/orders/shipped.blade.php` describes one shipped package, its carrier, tracking number/link and delivery estimate when present. `shop/orders/delivered.blade.php` sends “Your order arrived!” once the whole order is reported delivered, listing its items and linking to the private order page. Edit their wording and labels directly in those views; all three reuse the existing transactional `mail::message` and button components and produce HTML/plain text.

All three show product thumbnails through `mail::shop.products`. Its editable
HTML layout is `components/html/shop/products.blade.php`; the plain-text
counterpart is `components/text/shop/products.blade.php`. Merchandise uses the
image saved at checkout. Historical shipping rows resolve that image only when
their exact name/option matches one saved order variant, retaining the package's
own quantity and excluding items from other packages. Missing/invalid images
leave readable product details without an empty image cell. Rendering never
fetches Printful or current catalogue records. Only public HTTP(S) image URLs
without embedded credentials are included; plain-text emails omit image URLs.

`shop/confirm.blade.php` also uses this component for eBook/eScore purchase
confirmations, selecting the product's existing mockup or cover. Fictional
admin previews and newly created local sample orders include sample artwork.

Administration → Emails lists **Merchandise purchase confirmation**, **Merchandise shipped** and **Merchandise delivered** with independent automatic-sending switches and fictional previews. A split shipment receives one shipping email per package; one arrival email follows only after the last package is delivered. All three have duplicate protection, and historical delivered orders are not automatically emailed. Local checkout never sends them. Managers review delivery states in Shop → Orders; an uncertain send must be checked with the mail provider before a manual retry. See `docs/SHOP_CHECKOUT.md` for signed Printful events, setup and durable delivery semantics.

Local merchandise email previews for actual test-order data are available in Admin → Shop → Orders after creating a sample order or simulating shipment. `LocalShopShipmentsController` renders the same `emails/shop/orders` HTML/plain-text templates without sending through a mail provider. See `docs/SHOP_CHECKOUT.md` for the local-only simulator and tracking workflow.

### Synthesia Requests

Edit `synthesia-requests/new.blade.php` for receipt and `synthesia-requests/published.blade.php` for readiness. Their mailables live in `App\Mail\SynthesiaRequests`; the admin switches/previews are `synthesia-request` and `synthesia-published`. The table-rename migration preserves saved switches. The old `App\Mail\Tutorials` classes remain only for queued messages serialized before this rename and inherit the same delivery switches. See `docs/SYNTHESIA_REQUESTS.md` for the coordinated schema/worker rollout and permanent legacy API boundary.
