# Welcome emails

The three signup messages have separate editable views under
`resources/views/emails/welcome/`: `website.blade.php`, `webapp.blade.php` and
`ios.blade.php`. Their shared HTML/text components are under
`resources/views/emails/components/{html,text}/welcome/`, resolved through the
existing `mail::welcome` names. Copy, headings, buttons and mockup selection live
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

The layout follows the simple Duolingo reference: white background, a short
welcome and two sections. Colors and body typography come from the web app's
shared Sass foundations; headings use Poppins with the same system-font fallback.
Clients that ignore web fonts use that fallback. Buttons and section composition
are reusable email components, with inline styles, presentation tables, image alt
text and narrow-screen stacking. No JavaScript or CSS transform is needed in the
received email. A cloned Markdown renderer prevents the welcome theme from
carrying into the next ordinary email in a queue worker.

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
rebuilds it; normal development/production builds also do so. It changes only the
welcome messages. ImageMagick 7 rebuilds the static assets with:

```sh
python3 scripts/build-welcome-email-assets.py
```

These are compositions of existing artwork and real screenshots, without AI
illustrations. The approved rounded icon is upright within an image displayed
at 130px wide with proportional height. A thin light-gray outline follows the
icon's own silhouette so the white keys remain visible against the email's white
background. The image retains a small floating gap above a soft floor shadow.
Rebuild only this icon with `python3 scripts/build-welcome-email-assets.py --logo-only`
to preserve the existing screenshot mockups.
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

The resource
mockup uses a browser capture of the current local Circle of Fifths tool; its crop
excludes debug chrome and local test promotions. The source capture is kept in
`resources/email-sources/welcome/theory.png`, outside the published image tree.
The six runtime assets live in `resources/images/emails/welcome/`; production
copies them to `public/images/emails/welcome/` and versions them in the Mix manifest.
Each message loads its matching device composite, the shared logo and resource
image, plus the App Store badge when it offers the iOS app. The welcome hero and
resource section remain the same.

The resource copy promotes retained theory tools, quizzes and the blog through
the main website. It does not restore retired standalone games. URLs use the
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
and renders fictional Jamie samples. It does not query real accounts, send mail,
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
php -d error_reporting=8191 vendor/bin/phpunit -c phpunit-review.xml --filter 'AutomaticEmailSettingsTest|AdminEmailsTest|LocalEmailImagesTest'
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
