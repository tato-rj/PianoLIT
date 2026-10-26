# Welcome emails

The three signup messages have separate editable views under
`resources/views/emails/welcome/`: `website.blade.php`, `webapp.blade.php` and
`ios.blade.php`. Their shared HTML/text components are under
`resources/views/emails/components/{html,text}/welcome/`, resolved through the
existing `mail::welcome` names. The outer shell and footer now live in
`components/{html,text}/editorial/`, shared with the redesigned Freepick email;
the welcome message components delegate to them. Copy, headings, buttons and mockup selection live
in the individual views; mail classes supply only runtime data and delivery.
See `resources/views/emails/README.md` for the editing map and preview workflow.
Settings → Emails offers Website welcome,
Web app welcome and iOS app welcome with the existing Browser and Email previews.
Their saved signup origins (`web`, `webapp`, `ios`) select the message; request
headers do not. The app paragraph, buttons and device mockup follow that source:

| Signup | App highlight | Device mockup | Sending setting |
| --- | --- | --- | --- |
| pianolit.com | Web app and iOS, using the same account | Original browser and native iPhone app | `account-welcome-website` |
| Web app | iOS, using the same account | Tablet and native iPhone app | `account-welcome` |
| iOS | Web app, using the same account | Browser and mobile web app in an iPhone | `account-welcome-ios` |

All default off. Existing web app/iOS setting values remain authoritative. Website
signup previously shared the web app switch and now has a new, default-off switch.
Nothing is sent retrospectively, and no list subscription is created. No schema
change is required beyond the existing automatic email settings migration.

## Design and assets

The top retains the welcome and signup-specific app section. All email page
backgrounds now use the web app's `--color-surface-subtle` (`#f8f9fa`), including
the editorial theme, older Laravel theme and simple/raw layouts. Their main
content surfaces and HTML `bgcolor` fallbacks match; promotional bands and
individual cards retain their own colors.
From “A world to explore” down, all three share the supplied Turo reference's
layout: a pale section with image-left hub rows, centered Premium benefits with
line icons, then three piece cards above the library button and shared footer.
The hub copy and typography are preserved. Colors and body typography come from the web app's
shared Sass foundations; headings use Poppins with the same system-font fallback.
Clients that ignore web fonts use that fallback. Buttons and section composition
are reusable email components, with inline styles, presentation tables, image alt
text and narrow-screen stacking. No JavaScript or CSS transform is needed in the
received email. A cloned Markdown renderer prevents the welcome theme from
carrying into the next ordinary email in a queue worker.

Titles and headings never end with a period. This includes the welcome greeting,
platform headings, shared section titles and individual benefit headings in HTML
and plain text. Body sentences and internal catalogue punctuation are retained.

`AppServiceProvider` registers the current email component folder when Laravel's
Markdown renderer resolves, in addition to configured paths and the current
legacy vendor-mail fallback. This keeps the moved components and welcome theme
available if a deployed configuration cache still lists only the old folder.
After deployment, refresh configuration caches and long-running workers through
the normal release process. An immediate recovery for the observed
`View [welcome.hero] not found` error is `php artisan config:clear` from the
server's Laravel root, provided the matching component files are deployed.

Regular actions use the same default primary-button dimensions and finish as the
web app via `shared/_button-foundations.scss`: 11px × 21px padding, 22px line
height, 1px border, 12px corners and the primary shadow. Email values are resolved
for inlining rather than depending on browser CSS variables. iOS download links
use the supplied Apple App Store badge, exported from the identical existing
`resources/images/apple/download.svg` artwork as a 4× PNG. Its displayed height
is 46px, matching the regular button. `--badge-only` rebuilds just this asset;
the corresponding editable welcome views select it with `kind: app-store`.

`resources/sass/emails/welcome.scss` compiles to the tracked Laravel theme
`resources/views/emails/components/html/themes/welcome.css`. `npm run emails:styles`
rebuilds it; normal development/production builds also do so. It serves the
welcome and Free pick messages. ImageMagick 7 rebuilds the static assets with:

```sh
python3 scripts/build-welcome-email-assets.py
```

The logo and device assets are compositions of existing artwork and real
screenshots. The hub thumbnails reuse the approved editorial artwork already on
the homepage; this update generates no new illustrations. The approved rounded icon is upright within an image displayed
at 130px wide with proportional height. A thin light-gray outline follows the
icon's own silhouette so the white keys remain clear. The image has a transparent
outer canvas and retains a small floating gap above a soft floor shadow that
blends into the shared email background.
`mail::editorial.logo` supplies this artwork to all three welcomes; its
`shadow => false` variant gives Freepick the identical outlined icon on
transparency, displayed smaller at 48px. Welcomes keep their 78px visible icon
inside the unchanged 130px complete canvas. Rebuild both assets with
`python3 scripts/build-welcome-email-assets.py --logo-only` to preserve the
existing screenshot mockups.
The website's approved `app-devices.jpg` composition retains the Discover browser
and actual iOS Discover screen from `mockup/main-view.png`. The web app email uses
`tablet-iphone.jpg`, pairing the real `piece-tablet.jpg` capture with that native
iPhone artwork. The iOS email uses `webapp-devices.jpg`, keeping the approved
browser and replacing the phone screen with Arthur's supplied mobile Discover
screenshot, saved in `resources/email-sources/welcome/discover-mobile.png`.
Only device frames/masks surround these real screens; no UI is generated.
Rebuild these two variants without touching the website composition using:

```sh
python3 scripts/build-welcome-email-assets.py --platform-mockups-only
```

The shared `welcome/hubs.blade.php` section highlights Learn, Blog, Metronome,
Shop and Games in image-left rows, followed by one Explore PianoLIT.com
button. The existing app section covers the homepage's other two projects.
Learn includes the retained theory tools, courses and quizzes; Shop's new hub and
Games explicitly say coming soon because their root destinations still do.
No retired standalone games are restored or advertised as available.

The five `hub-*.jpg` files are 420px square crops of the existing approved
`resources/images/home/editorial/` artwork, displayed at up to 128px. JPEG avoids
depending on inbox WebP support. Use `--hubs-only` to rebuild these thumbnails.
The original resource-browser asset and source capture are retained, but no
longer appear in the welcomes. Welcome assets live in
`resources/images/emails/welcome/`; production copies them to the matching public
directory and versions them in the Mix manifest. Four transparent blue PNG icons
reuse Lucide video, keyboard-music, headphones and file-music artwork; ship their
`LUCIDE-LICENSE.txt` too. `scripts/build-welcome-email-details.js` rebuilds these
icons with `sharp` and the preview-only period-art thumbnails with ImageMagick.
Set `PIANOLIT_SHARP_MODULE` if using `sharp` from the bundled workspace runtime.
HTML uses inline-styled presentation tables, retaining image/copy columns and
three piece cards side by side on narrow screens. Plain text includes the same
descriptions, availability labels, benefits and destinations.

`welcome/shared.blade.php` includes the hubs, `welcome/premium.blade.php` and
`welcome/pieces.blade.php`. Premium highlights performances, Synthesia, recordings
and public-domain scores; its Go Premium button opens the existing pricing page.
“Ready for your next piece?” shows up to three distinct random historical Free
Picks with artwork, a composer and a tutorial. `WelcomePieces` excludes the current
due schedule row even before history synchronization, and excludes future
highlight dates. It leaves access rules unchanged; the description identifies
these as past picks available with Premium. With fewer eligible pieces, only
those available are shown. Browse the PianoLIT library opens `webapp.explore`.
The cards resolve once per message build for matching HTML and plain text.

The shared footer uses a regular linked YouTube social icon, followed by a
signup-reason note, contact copy, legal/support
links and copyright. The outlined, shadow-free PianoLIT icon sits on the right;
no postal address, social buttons, footer App Store link or other social channels
appear. Body App Store actions are retained. The Free pick counterpart
uses the same component with a subscription-reason note. HTML and plain text
include the same destinations. **Unsubscribe / email preferences** opens the
account's current email-list controls through `users.profile?tab=email-preferences`.
Sign-in is required, and the recipient chooses which lists to leave. No list
subscription, opt-out, delivery setting or account data changes during rendering.

URLs use the
configured public main host, named web app route and existing App Store URL;
assets cannot inherit the admin preview's host. Scheme-less `APP_URL` is normalized
to HTTPS in production. For a `.test` host it uses the current request's scheme,
so HTTP-only local previews can load images. An explicitly configured HTTP/HTTPS
scheme is always preserved.

## Local review

Export self-contained HTML/text samples with copied images:

```sh
php -d error_reporting=8191 scripts/preview-welcome-emails.php /tmp/pianolit-welcome-preview
```

The exporter bypasses `.env` and cached configuration, verifies in-memory SQLite,
and renders fictional Jamie samples. The three piece samples use stable titles,
period artwork and inert `#email-preview` links. It does not query real accounts
or repertoire, send mail,
run workers or use provider credentials. Open `website.html`, `webapp.html` and
`ios.html` from that directory. Browser previews in Settings → Emails use the
same real components/theme and retain their sandbox, with Google Fonts permitted
for the web app's existing Poppins family.

Local Mailtrap sends embed the referenced images, including the App Store badge,
as inline CID attachments when the
environment is `local` and the configured main host ends in `.test`. Mailtrap
therefore does not need network access to the developer's machine. This happens
only at delivery; browser previews and production emails retain their image URLs.
It applies to new messages, including queued welcomes and admin test sends. See
[AUTOMATIC_EMAILS.md](AUTOMATIC_EMAILS.md#local-mailtrap-images) for the shared
local-image handler and worker notes.

Focused checks:

```sh
php -d error_reporting=8191 vendor/bin/phpunit -c phpunit-review.xml --filter 'AutomaticEmailSettingsTest|AdminEmailsTest|LocalEmailImagesTest|EmailViewComponentsTest|WelcomeEmailContentTest'
npm run production
npm run test:build
git diff --check
```

Deploy the mail classes/listener/catalog, Blade components, compiled theme and
versioned images/manifest together; publish assets before serving the new templates
and restart long-running queue workers. Enable each desired sending switch through
Settings → Emails after review. Browser rendering at 320/390 px and desktop is
checked locally; Gmail/Outlook/Apple Mail inbox rendering and physical iOS signup
remain release checks. Existing mobile request/response payloads and media URLs
are unchanged.
