# Shared design settings

The admin **Settings → Design System** page previews the shared UI and displays
its computed dimensions and colors. See [SCSS_GUIDE.md](SCSS_GUIDE.md) for the
source map, readable formatting conventions and final stylesheet order.

Always check this page before adding or redesigning a UI element. Reuse its shared
Blade components, classes and tokens instead of copying the appearance into a
page-specific control. This rule applies across the public website, web app and
admin. When a common element has no component, extract one before using it. Design
system specimens should render the same components used by application pages.

Edit `resources/sass/shared/_tokens.scss`, then run `npm run production`.
It contains the existing Sass palette and the CSS custom properties used by the
public website, web app and admin. Commit the rebuilt CSS and Mix manifest together.

## Account menu

`components.account.account-circle` opens `components.account.menu`, a shared
Bootstrap offcanvas on the right on the homepage, public hubs, Shop and `my.*`.
There is no separate hamburger. Guests use the same opener and get a same-host
Sign in link inside the panel. Standalone login/registration/password pages and
focused games remain free of this navigation.

The panel keeps the shared account menu's 17rem width; the live reveal leaves at
least 3rem of the page visible for the outside close control. Do not widen it.
Its avatar sits above the name and wrapping email, without an internal close button. The
entire panel scrolls naturally, including its header and lower Support/Log out
area; no footer stays fixed over the content. Help & Support is immediately
above the same-host CSRF logout form, below a divider. Profile, downloads and
orders return to main.

Account actions retain 16px labels, 20px icons and 48px rows.
`navigation.hub-menu.links` renders PianoLIT Hubs as a normal icon row using a
native details/summary disclosure with a trailing chevron. It starts closed,
works without JavaScript and resets closed when the offcanvas is dismissed.
Its expanded destinations use 14px labels, compact 36px rows and a 2rem left margin to make their hierarchy clear. Keep them icon-free with trailing arrows.
Only iOS opens another tab, with safe external-link attributes. Only one menu
link is current: an account action suppresses its host hub highlight, and Home
is current only on the homepage. Nested hub content retains its hub highlight.

`Services\Navigation\AccountAvatar` uses a saved, verified Google image in
both the header account circle and the menu avatar; the Unicode initial stays
underneath and returns on image failure. The photo remains circular, uses
`object-fit: cover` and sends no referrer. Apple provides no profile photo, so an
Apple-only account keeps its initial. Provider images live in the provider and
pending-proof tables, never User attributes, loaded relationships or mobile JSON.
See `GOOGLE_LOGIN.md` for the additive migration and capture rules.

`Services\Navigation\AccountMenu` controls browser-only shortcut visibility.
My profile and Help & Support are always available for signed-in accounts.
My membership appears on `my.*`, My downloads on Learn and Track your orders on
Shop. An account's membership record (including expired), full-access override,
owned purchase history or owned paid merchandise order respectively makes that
shortcut available across all hubs. Order existence reuses `OrderTracking::visible`
so test/unpaid/digital orders and another account's records do not qualify.
Membership links use management for an existing membership, pricing for a new
account, and Settings for a full-access account without a billing record. These
checks do not call billing/Printful or load relationships into shared user JSON.

Only this universal menu reveals underneath the page: the shared
`components.account.page` frame moves left without changing the content width,
adds 20px right corners, a soft edge shadow and an 80% white veil. The menu stays
clear. Its original header circle becomes the close X and moves continuously to
the shifted page's top-right corner (16px inset, constrained to fit narrow strips),
then travels back and restores its photo/initial on dismissal. Keep this same
button, rather than hiding it and creating a second close control.
The close state shows only the X, with transparent background and border for
both initial and profile-photo accounts; retain its full 44px hit area.
Crossfade the background, border and avatar/X over the same 240ms as the movement
in both directions. Start restoring the avatar when the return movement begins,
so it reaches the header fully restored instead of switching at the end.

`components/_account-menu.scss`, `components/_hub-menu.scss` and
`components/_account-reveal.scss` own the shared finish. The versioned
`components/account-menu.js` synchronizes expanded state; `account-reveal.js`
freezes/restores scroll and sticky positions without reparenting media or changing
fixed-player geometry. The covered page is inert. Bootstrap retains scroll
locking, Escape/backdrop dismissal and overlay handoffs; the controller includes
the external close control in dialog ownership and the keyboard focus cycle.
Page/control motion is 240ms and respects reduced motion. Other offcanvas panels
retain their existing presentation. `layouts.menu.user` pushes one panel to the
body-level script stack. Design System specimens reuse these same components
with fictitious details and inert actions.

Games' yellow trophy menu remains an independent dropdown using
`components.account.menu-panel` and `games.challenges.profile.menu`. Its existing
positioning, animation, avatar, best scores and Clear records behavior stay intact.
The shared script loads once even when both controls appear.

## PianoLIT brand icons

The first Design System section, **PianoLIT icons**, previews and downloads the
approved blue user and white admin artwork, browser icons, Apple/Android
home-screen icons, Windows tiles, Safari masks, rounded/QR artwork and the iOS
export. Only the two main icon cards appear on the page, each with a centered
Download button. Each opens the shared modal with its formats, actual file
dimensions, specifications and download links; brand cards have no CSS dimension
editors. UI previews use the shared `@brandIcon` component and the actual exports.
The two SVG masters live in `resources/images/brand/`; keep their six evenly
spaced black keys, as explicitly requested by Arthur.

For compact web-app references, use `@icon('pianolit')` instead of a laptop or
other generic platform symbol. The monochrome open score and six-key mark inherits
text color and shared icon sizing/spacing, and preserves transparent key cutouts.
For example: `@icon('pianolit', ['color' => 'black', 'size' => 'lg', 'mr' => 2])`.
Its fixed brand geometry is separate from Lucide stroke-weight settings.
Edit `resources/icons/custom/pianolit.svg`, then run `npm run production` to
regenerate `resources/icons/custom.json` and the browser bundles. The PHP renderer
and dynamic icon handler use this same catalog; the Icons specimen previews it.
Keep the full app/admin artwork and model/API origin values unchanged.

Full-screen public projects use `components.branding.watermark`: a white,
transparent SVG with a white rounded-square border, fixed at the top left with
safe-area spacing. Its hand-tuned small-size drawing retains the open score and
six evenly spaced keys, with two stronger lines per page, an open gap above the
keyboard and transparent key cutouts. Edit `resources/images/brand/icon-white.svg`
directly; the website/admin icon masters and Safari mask remain separate. It links to
the absolute `home` route, uses 30%
opacity at rest and 65% on hover/keyboard focus, and disables its transition for
reduced motion. The metronome instead uses the shared public header’s transparent overlay variant,
with the account control omitted. The Components section previews the same link against blue; the
Website & web app download modal includes `icon-white.svg`. Regenerating brand
icons copies this artwork unchanged instead of recreating it from the Safari mask.

`components.dialogs.bottom-sheet.panel` and `components.dialogs.bottom-sheet.header` provide the shared
bottom offcanvas used by the web-app Save to panel and standalone metronome
settings. The favorites panel keeps its 1040px content cap; metronome settings
center their content at a maximum of 560px. Reuse the viewport height cap, heading and
`components.dialogs.bottom-sheet.handle`; Bootstrap supplies the backdrop, focus trap,
transitions and dismissal. Keep content scrolling inside the panel. The
Components section includes an interactive specimen of these same components.

`metronome.components.voice-recorder` supplies the standalone metronome's guided four-count
recorder, named voice library and the Components specimen. Buttons, selects and the live level meter
reuse shared Bootstrap controls. `metronome.components.voice-recorder-cue` supplies the
large number, status badge and animated ring using shared status colors: yellow
Wait with a countdown, green Speak now with a listening pulse, a green Recorded
check and red Try again with a brief shake. Text/icons reinforce every color;
reduced motion removes animations and transitions. The Components gallery shows
all four states on this same cue component. Keep the concise recording prompts, per-number replay/redo and explicit
Save and use action on this component. Reuse `@input` for recording names and
renaming. `metronome.components.sound-menu` renders a custom Sound menu whose trigger
uses `form-control` and the shared `form-control-select-trigger` arrow mixin, so it
matches native PianoLIT selects. The popup reuses Bootstrap's shared dropdown
surface/items, with Recorded voices grouped beneath instruments. The shared
`metronome.components.sound-voice` row has play/stop, edit-name and delete icon buttons.
Deleting a voice uses the browser’s native `window.confirm` with its name;
there is no inline delete message or custom modal. A
full-width standard `.btn.btn-sm` New recording button stays visible
directly beneath the closed Sound control, outside the popup.
A hidden shared `@select` retains the existing value contract (`choiceGroups`
supports value-keyed groups beside `choices`). Only the empty library offers the
generic My voice entry. The recorder
specimen includes this same Sound component. The specimen makes no microphone or account
requests. See `METRONOME.md` for capture, private storage and deployment details.

Run `npm run brand:icons` with ImageMagick 7 installed after changing either
master, then run `npm run production`. The generator retains the legacy email
and QR image dimensions. `app-icon-ios-1024.png` is a 1024 × 1024 RGB PNG with
no transparency or baked corner mask; installing it in the native iOS app is a
separate release task. Apple/Android/Windows exports are also opaque squares so
their platform can apply its own mask. Browser PNG/ICO exports retain rounding.

`components.branding.theme` supplies the shared user/admin browser metadata.
All runtime artwork uses content-versioned URLs from `scripts/brand-assets.js`;
the web manifests and Windows configs also version their nested icon URLs.
Regenerate and deploy the images, metadata, views and Mix manifest together.
An existing installed home-screen shortcut may require removal and re-adding
because its icon refresh is controlled by the device.

### Home-screen splash screens

The public website/web app uses a white launch background; administration uses
`#181e22`. Both center the approved icon at 80 CSS pixels. The blue icon has a
thin, light `#dee2e6` outline (one CSS pixel) so its white keys remain distinct
from the launch background. These splash treatments leave the icon masters
unchanged. `components.branding.splash` supplies
content-versioned iOS/iPadOS startup-image links from the shared
`resources/data/brand-splash-screens.json` catalog: 23 screen profiles with both
portrait and landscape files. The shared brand head also declares standalone
capability and the appropriate installed-app name. Android uses the existing
manifest name, icon and launch background color to generate its own launch
screen; custom startup PNGs apply to Apple web apps. Browser theme colors stay
independent of launch backgrounds: blue for the website/web app and white for
administration.

Run `npm run brand:splashes` to regenerate just the splash artwork, or
`npm run brand:icons` to regenerate icons and splashes together. ImageMagick 7
is required only for manual generation. Outputs live in
`resources/images/brand/splash/` and matching public copies. Each main icon's
Download modal includes a scalable splash SVG, a phone PNG preview and a ZIP
with all 46 opaque RGB PNGs plus a README of exact sizes and media profiles.
There are still only two main cards; no extra size boxes or CSS editors.

Deploy the catalog, shared head/partial, generated assets and Mix manifest
together. Existing installed shortcuts can retain cached launch artwork and
may need to be removed and re-added. Unlisted screen/zoom profiles use the
browser's normal launch fallback. These assets do not change the native iOS
application or its APIs. Physical-device cold launch remains a release check.

Platform references: [Apple startup-image and standalone configuration](https://developer.apple.com/library/archive/documentation/AppleApplications/Reference/SafariWebContent/ConfiguringWebApplications/ConfiguringWebApplications.html),
[Android manifest launch screens](https://web.dev/learn/pwa/web-app-manifest),
[iOS/iPadOS startup-image media queries](https://web.dev/learn/pwa/enhancements).

## Change standard rounded corners

```scss
--radius: .5rem; // Current default: 2rem.
```

This updates `.rounded`, directional `.rounded-top/bottom/left/right`, the media
player, the shared modal, pricing badges, admin overview corners, buttons, and unstyled form fields.
Images and divs opt into these rules through their existing rounded classes.
The score toolbar uses `.rounded-top` in regular mode; its fullscreen toggle
removes the class while fullscreen is open and restores it on exit.
`--radius-button` and `--radius-input` have independent .75rem defaults;
`--radius-modal` inherits `--radius-sm`.

`--radius-sm` controls `.rounded-sm` and its directional variants. Existing pill/circle and
square utility precedence is retained. Custom score tools, Match cards, decorative
shapes, email templates and standalone policy documents keep their own layouts.

## Admin table actions

Render row actions through `admin.components.table-action`. Edit uses the shared
small yellow button with a square-pen icon and Edit label. Delete uses red with
a trash icon; other actions use black. The Users directory and the Design
System's Table actions specimen use the same component. Preserve accessible
names, forms, modal confirmation, disabled states and route parameters. Legacy
DataTables action partials delegate to the same renderer. See `ADMIN_TABLES.md`
for its options and compatibility rules.

## Form controls

Admin editors group related fields with `admin.components.editor.section`.
Pass a unique `id`, `title`, optional `description` and an optional `actions` slot.
An optional `sectionId` supplies an anchor on the section itself.
Use `.admin-editor-grid` for a wide main column and narrower sidebar on desktop;
it stacks on smaller screens. `.admin-editor-stack`, `.admin-editor-options` and
`.admin-editor-save` supply the shared layout. Controls keep their standard
components and finishes. The Design System previews the same section component.
Native file fields use `components.forms.upload.file` with `id`, `name`, `label`,
optional `accept` and `currentUrl` / `currentLabel`. Their IDs and labels remain
unique; existing files open safely in a new tab.

Collapsible lists use `components.layout.accordion` and `components.layout.accordion-item`,
with the native Bootstrap accordion styling and behavior. Pass a unique wrapper
`id`, then each item's unique `id`, `title` and optional `parentId` to keep one
item open at a time. `open: true` sets the initial expanded state. The wrapper
defaults to the flat `accordion-flush` variant; `flush: false` keeps the standard
outer border. The Design System previews these same components. Avoid combining
rounded card headers with separate card borders inside an accordion.

Support uses the wrapper's `variant: minimal` option, matching the membership
FAQ's quiet finish: semibold questions, plus/close icons, thin bottom dividers,
muted answers and no colored expanded header. Shared `components/_accordion.scss`
owns this option; native Bootstrap collapse behavior and accessible buttons remain.
The Design System includes both closed and expanded minimal items. Other callers
retain the default Bootstrap finish.

Date and time displays omit timezone names and explanatory timezone captions.
Use the configured application timezone for formatting and calculations.

Use `@input` (`components.forms.input`) for standard text/email fields, including
labels and validation feedback. Supply `id` to associate its label, `autocomplete`
for browser completion and `describedBy` for help text. The component preserves
scalar old input and safely ignores malformed array values. Avoid field-specific
colors, padding, corners and focus outlines; table wrappers must respect the
shared form-control focus style.

For repeated record forms with identical field names, inputs and textareas accept
`oldInputKey` to choose the flashed-value key independently of `name`. Use a named
validation bag for that record; field errors honor the supplied `bag`. Timeline
event editors use this to restore the submitted record without filling every
other event with its unsaved values.

`@textarea` also accepts `id` for its label and preserves scalar old input while
ignoring malformed array values. Pass `textImprover: false` for structured data
such as subscriber email lists, where the prose-improvement action is unsuitable.

Admin table search fields use `@input` and their filter/sort/page-size fields use
`@select` (`components.forms.select`), with the same default field dimensions and
states as the Design System. `@select` accepts a `choices` value/label map and
selected `value`; its existing `options`, `select` and grouped-option API remains
available for older forms. Pass `required: false` for optional GET filters and a
unique `id` for the shared label. Table wrappers contain layout/width rules only.
Legacy DataTables use the same default field classes through the admin-only
`admin-table-fields.js` adapter; keep their initialization skeleton dimensions in
sync without introducing table-specific field styles.

All admin table and directory filters use `admin.components.filters.form`,
`filters.fields` and `filters.button`, including visit sorting, analytics,
composer/folder directories and the shared table component. Use the shared `.btn.btn-dark` for **Apply
filters** and page-header actions such as Add a new piece; Reverse order and Clear
use `.btn.btn-secondary`. Their normal, hover, focus, pressed and disabled states
come from the same button rules. Filter styling supplies only layout and sizing
to align with fields, without separate colors, borders or corner styles.
Do not define page-specific filter markup or button/field finishes. The form
composes `@input` / `@select`, optional custom widgets and shared actions.
`field-control-sizing` from `shared/_field-sizing.scss` aligns actions with fields:
45px on desktop and 48px with 16px touch text. The Design System's Admin filter
specimen renders this same form in safe preview mode.

Standard `.form-control`, `.form-select`, shared input/textarea components, file
uploads and input-group labels use the same `--radius-input` and `--field-*`
tokens. The default finish is a `#f8f9fb` background, `#dfe5ed` border, `#273244`
text, `#697386` placeholder/focus border, 12 px corners and 11 px by 14 px padding.
Small and large fields inherit matching size settings through `.form-control-sm`,
`.form-select-sm`, `.form-control-lg`, `.form-select-lg` and input-group sizes.
Touch-screen text entry uses 16 px type. Textareas retain their row counts and resizing;
multiple selects retain their native list rows. Joined controls round only their
outside edges. Avoid local field colors, heights, corner utilities or padding overrides;
keep page-specific widths, layout and room for icons local.

Checkboxes, radios, switches and ranges use the shared brand and field colors.

Native analytics selects use `admin.components.filters.select`, which delegates
to `@select`. Date-range and checkbox-menu triggers use the same `.form-control`
field finish, shared label component and `pianolit-select-arrow` mixin as native
selects. Their colors, 12px corners, chevrons, padding, focus treatment and touch
text stay aligned. Keep only panel/menu layout in their component styles; no
white/translucent filter-control variant remains.

Multiple-choice filter menus use `components.forms.checkbox-menu`. Pass a field
`name`, accessible `label`, an `options` value/label map, the `selected` array and
an optional `allLabel`. The shared admin filter trigger, Bootstrap dropdown and
native checkbox styles stay in sync with the Design System's Checkbox menu
specimen (`designSystemPreview: true`). The menu stays open while checking
options, offers Select all, and updates its summary. Escape returns focus to the
trigger; outside clicks and Tab leaving the menu dismiss it. Choices submit with
the containing form's Apply action; at least one is required. The component sends
an empty array sentinel so an unchecked menu can be rejected on the server.
Consumers must validate allowed values and discard the sentinel before querying.

Small, mutually exclusive choices use `components.forms.segmented-control`: pass a
unique `id` and radio `name`, an accessible `label`, an `options` value/label map
and the selected `value`. The shared component fills its container with equal
segments and uses native radios for a single selection and arrow-key navigation.
Its neutral tray, selected surface and focus border use the shared tokens. The
Design System's Segmented selection specimen renders this same component.

Switches use `@toggle` (`components.forms.toggle`) and the shared `.switch` /
`.slider.round` classes. Supply `toggle` and `ariaLabel` for the state and accessible
name. Switches have no visible On/Off label by default; add visible labels only
when explicitly needed. Always retain an accessible name. `autoToggle: true`
retains the existing endpoint-toggle AJAX behavior. For
a normal form, set `autoToggle: false`, `name` and optional `inputValue`;
`submitOnChange: true` submits the containing form on change. Include a preceding
hidden input with the same name and value `0` when the server requires an explicit
off state, plus a no-JavaScript submit button. Keep labels and layout outside the
shared switch rather than restyling it.

TinyMCE and Dropzone containers and hosted Stripe card-field containers use the
same border treatment. Third-party iframe contents are still managed by their
respective editors/payment libraries; field styling does not change their behavior.

## Colors

Change `$brand-primary` at the top of the file to rebuild the primary color and its
hover, active, disabled and soft variants together. Links, primary buttons,
primary text/background/border utilities, accents and pagination use these tokens.
Bootstrap primary outlines and borders now use the same PianoLIT blue.

The utility palette (grey, red, orange, yellow, green, teal, blue, indigo,
purple and pink) remains distinct from Bootstrap status colors (success, danger,
warning, info and secondary). Their base text/background/border utilities use
`--color-*` tokens. Shade utilities, palette buttons, alerts and feature-specific
colors still use their existing styles; further consolidation can happen when
those components are redesigned. The palette's `blue` default is its existing
rendered `#3490dc`; PianoLIT's brand blue is `#0055fe`.

Use `--color-text`, `--color-text-muted`, `--color-surface`,
`--color-surface-subtle` and `--color-border` for new shared styles.

The default body background across the public website, every hub and `my.*` is
white. The public bundle defines `--color-page: var(--color-surface)`; shared
body styles consume it, retaining the subtle-surface fallback for administration.
Use this page surface for full-page game results and theory-tool areas that must
blend into the page. Keep `--color-surface-subtle` (#f8f9fa) for controls, hover
states and inset panels. Public account screens and legacy footer/video-credit
wrappers also use white. Colored artwork, game controls and the standalone
metronome canvas retain their intentional colors; email and admin surfaces are
unchanged. The mobile article embed retains its white body through `bg-white`.

Discover's horizontal piece links reuse `.discover-compact-card` for their white
`--color-surface`, shared padding and rounded corners, including Recently viewed
and recommendation rails. Compact piece cards also stay white in Past highlights.

Discover's level cards use one flat pastel accent per system: sky blue for RCM,
mint green for ABRSM and butter yellow for Suzuki. Their decorative shapes use
the matching action shade; levels within a system share the same color.

The ordered `$pastel-palette` provides 12 soft tones: sky, cream, mint, lavender,
peach, rose, aqua, butter, sage, lilac, periwinkle and terracotta. Each exposes
`--pastel-{name}-surface` for light panels, `-accent` for tabs or soft actions,
`-border` for outlines, and `-ink` for readable secondary text. Reuse these tokens
when a feature needs a pastel background; Discover's For you panel uses
`--pastel-lavender-surface`. My Pieces stores each folder's selected palette name
in `favorite_folders.web_color` and uses `data-folder-color` to select these tokens.
The create/edit dialogs offer only these 12 tones. A new folder starts with a
random unused tone; once every tone is used, defaults repeat among the least-used
tones. Saved choices can repeat. Filtering, renaming and sorting preserve saved
colors. The color migration freezes the old alphabetical palette assignments
without changing folder timestamps. Folders subsequently created by mobile retain
the alphabetical fallback until a browser create/update preserves their colors;
`web_color` is hidden from existing mobile JSON. Deploy the migration before the
updated browser templates/controllers and app CSS.
Soft actions use `-action` for a darker text/icon shade of the same hue,
`-action-hover` for its darker hover/focus shade, and `-accent-hover` for a subtly
deeper tinted background. Icons inherit the action's text color.

For web app promotional panels, add `pastel-card` and
`data-pastel-color="sky"` (or another palette name). The component uses the same
light surface, subtle white shading and border as folder faces. Put labels in
`pastel-card__tag` pills and links/buttons in `pastel-card__action` pills; their
backgrounds, text, arrows and hover/focus states follow the panel's selected tone.
Use `pastel-card__tag--quiet` for short status labels. Collections uses sky for
the PianoLIT Path and books, cream for Featured Collection, and mint/lavender/peach
for inspiration cards. Discover uses lavender for For you and cream/mint for
composer features. Artwork, routes and layout remain owned by each component.

## Typography, spacing and shadows

Body text, headings, small text, common buttons/inputs and blog text consume the
font tokens. Existing responsive or feature-specific font overrides are retained.
The existing `h1` element (2.75rem) and `.h1` utility (2.5rem) have separate defaults.
`--font-size-base` controls body/control text; it does not change the browser's root
font size or all rem-based layout dimensions.

Spacing tokens provide `--space-1` through `--space-5` and opt-in `.gap-1` through
`.gap-5` utilities. Existing Bootstrap margin/padding utilities and custom layout
spacing are unchanged. Shared shadow utilities use the shadow tokens.

All `.dropdown-menu` surfaces share `--radius-dropdown` (9 px),
`--color-dropdown-border` (`#e6eaf0`, 1 px solid) and `--shadow-dropdown`
(`0 8px 24px rgba(26, 48, 85, .1)`) on desktop and mobile. Their background uses
`--color-surface`. Keep menu-specific font sizes, widths and positioning local;
avoid `.rounded` on menus so its general container radius does not override the
dropdown standard.

For a temporary browser experiment, override tokens in DevTools:

```css
:root {
    --radius: .5rem;
    --font-size-base: 1.05rem;
}
```

A runtime change to `--color-primary` does not recalculate its separate interaction
state tokens; edit the Sass brand setting and rebuild for a complete palette change.

## Build order

`shared.scss` compiles the utility aliases, tokens and shared component adapters
that Mix appends after the framework/page styles in both `app.css` and `admin.css`.
The shared defaults live in `shared/_tokens.scss`; the main entry points import
them directly. This separate compilation preserves existing Sass extensions and
stylesheet precedence. Page templates request only their main bundle.

## Membership cards and account tabs

`components.billing.membership-plan-card` is the single plan card used by Pricing and Change plan. Pass a plan, a unique `headingId`, an optional `monthlyPlan` for yearly savings, and an `action` slot containing a shared button or button link. Optional `trial`, `accessNote`, `context` and `selected` values adapt its content and selection state. Pass `current: true` for the existing membership plan to apply the shared `shadow-none border border-light` utilities. Its gradient panel, shadow and layout live in `components/_membership-plan-card.scss`, imported by both app and admin so the Design System specimen uses the same component and styles. These catalog cards belong to plan selection; the current membership overview uses ordinary `.card.rounded.shadow-light` content cards and saved billing dates, without guessing an existing subscriber’s charge from a catalog price.

`components.navigation.tab-navigation` is a Blade `@component` with its `.tab-content` and panes in the default slot. It provides accessible Bootstrap tab triggers for an `items` array of `target`/`label` pairs, with a unique `id` and accessible `label`. The `segmented` variant is the shared compact account navigation in `components/_tabs.scss`; it uses a compact row on phones and two columns below 360px. Panels use the target as their ID and `aria-labelledby="{id}-{target}"`. Items may supply an explicit `id` and a `data` map when an existing tool needs stable trigger IDs or data attributes; the matching panel must reference that explicit ID. Summary shortcuts activate the matching real tab trigger so keyboard focus and lazy loading remain consistent. It encloses the menu and panes in one frame and embeds its small shared controller once, selecting the URL fragment synchronously before revealing that frame. This avoids a flash of the default tab or a Bootstrap fade while the main bundle loads. With JavaScript disabled, the server default remains visible; an interrupted response reveals that default at DOM ready. Keep the panels inside the component slot, rather than after an `@include`. The navigation automatically marks each instance with `data-tab-navigation`. The shared `components/tab-navigation.js` controller, loaded in both app and admin bundles, saves the selected panel as a URL fragment and restores it on refresh or fragment navigation. It uses `history.replaceState` to preserve query strings and existing Back-button state without adding history entries; ordinary page anchors and unrelated tab implementations keep their own behavior. Both components are demonstrated under Settings → Design System → Components.

## Saved payment cards and secure fields

`components.billing.payment-card-preview` is shared by checkout, Billing and the Design System. Its default mode renders the live card preview. Pass `saved: ['brand' => ..., 'last4' => ...]` for the current membership card and its check mark, and optionally `selectable: true` for a checkout selection button, or `add: true` for the monochromatic Add new card button. Add tiles take either `controlsId` for an inline disclosure (checkout), or `modalId` for a Bootstrap modal trigger with the appropriate dialog semantics (Billing and the Design System). The artwork, brand colors, check mark and add-card styling live in `components/_payment-card.scss`, imported by app and admin. Only the currently stored membership method is shown; the card editor explicitly describes replacement after verification.

`components.billing.payment-card-fields` supplies the three Stripe-hosted mounts and native cardholder/postal fields. Pass a unique `prefix` and the initial `cardholder` value. Native fields use `@input` and shared labels; field layout lives in `components/_payment-card-fields.scss`. Checkout and Billing use this same component. Billing's editor uses `components.dialogs.modal` at body level; its `data-deferred-card` form mounts after the modal is shown, focuses the card number once ready without overriding another user-selected field, and clears its draft after any dismissal while reusing the hosted fields on reopen. The dialog blocks dismissal during verification and temporarily releases its focus trap for Stripe's bank challenge, restoring it on failure. The existing verified SetupIntent flow remains responsible for saving the card.


`components.billing.checkout.modal` is the common full-screen payment surface for merchandise, paid eBooks/eScores and membership. The order summary and PianoLIT logo sit in the white rounded panel on the left; payment details sit directly on the open background to the right. Keep the shared layout centered at a maximum of 1080px, with a naturally sized summary card, paired contact fields and one set of grid gutters between address rows. Preserve standard control sizes and typography. Keep this order in the loading state and on phones, where the panels stack. Reuse its payment/summary slots, loading state, secure card fields, shared primary/secondary buttons and cancel/focus lifecycle. `components.billing.checkout.wallets` mounts the shared `express-checkout.js` Stripe controller above card details; only real available Apple Pay/Google Pay buttons appear on valid HTTPS domains. Unsupported devices retain cards without an empty wallet separator. Never draw fake wallet buttons in a preview. Shop's checkout uses this common payment design, including its rounded shared controls, rather than overriding it with the storefront's outlined skin. Shipping/billing fields and priced product rows extend the same shell. See `docs/SHOP_CHECKOUT.md` for behavior and activation.


## Public hub breadcrumbs

Use `components.navigation.breadcrumb` above the content of every redesigned public hub
page. Blog now uses Home → Blog on its directory and Home → Blog → Article title
on articles. Apply the same component automatically when Shop, Games and Learn
and their nested pages are redesigned; their temporary and legacy pages can wait
for that work. The main homepage, `my.*`, standalone account screens and mobile
embeds do not receive public breadcrumbs.

Pass an ordered `items` array of `label` / `url` pairs inside the page's Bootstrap
container. Start with `['label' => 'Home', 'url' => route('home')]`, add the hub and
any actual parents using named routes, then the current page's label. The last
item is unlinked with `aria-current="page"`; labels are escaped and long titles
wrap. Topic filters and pagination keep the directory trail. The component reuses
Bootstrap breadcrumb, spacing and wrapping utilities. All public hubs share the
Shop reference finish: regular 16px body text, normal letter spacing, dark links,
muted slash separators and a muted current page, with an underline on hover or
keyboard focus. `components/_breadcrumb.scss`, included by `shared.scss`, owns
this finish; do not add hub-specific breadcrumb overrides. Its specimen is under
Settings → Design System → Components.

## Admin breadcrumbs

Every admin page header renders `admin.components.breadcrumb` above its heading.
The date, notification button and account menu share that upper row, aligned to
the right; the title and subtitle use the full width below. Long breadcrumb
trails wrap beside the controls, which stay at the top of the row. This layout
comes from the shared page-title component and applies to the Design System too.
The shared `admin.components.page.title` component adds the Overview link and the
current page automatically. Supply `breadcrumbs` as an ordered array of parent
`label` / `url` items, for example:

```blade
'breadcrumbs' => [['label' => 'Email lists', 'url' => route('admin.subscriptions.lists.index')]]
```

Nested editors include their directory and owning record before the current page.
Use `breadcrumbLabel` when a tab or subpage needs a navigation label different
from its heading. Breadcrumbs replace return/back buttons; create, export, delete
and other actions remain in the header's action row. Use explicit named routes,
never the previous URL. The last item is plain text with `aria-current="page"`;
an ordered list, labeled navigation and hidden separators preserve its semantics.
Labels are escaped, and long titles wrap on narrow screens. Shared styling lives
in `admin/_shell.scss` and consumes the typography/color tokens. The Design
System's Breadcrumbs specimen uses this same component.

## Admin page loading

The admin layout embeds `admin.components.page-loading` first in the body so its small standalone controller runs before the page content and main bundle. The shared 3px bar uses `--color-primary`, advances below completion while loading, then fills and fades on the native window load event. Native navigation observation covers links, forms, refresh and history without intercepting them, ignores same-document changes/downloads, and resets on cancellation/failure or cached-page restoration. Older browsers use `beforeunload`, preserving unsaved-change prompts, with Escape to reset a stopped departure. This is estimated activity, not a byte percentage or a tracker for background AJAX work. Reduced-motion settings remove transitions; without JavaScript the bar remains hidden. The Design System Components specimen renders this same Blade component with `preview: true` in normal flow.

## Standard confirmation dialogs

Use `components.dialogs.modal` for ordinary dialogs and confirmations. Its `header`, `body` and `footer` slots retain shared typography, button classes and `--radius-modal`. Pass `centered: true` for a centered confirmation and `descriptionId` for the ID of its explanatory text. The component supplies a focusable dialog root, a title association when a header is present, and an accessible Close button. Bootstrap handles the backdrop, Escape, focus trap and return to the opener. Keep destructive actions behind an explicit submit button, with a separate dismiss button. Web app views can push markup into `page-modals` to render it at body level, outside tab panes and other stacking containers. Settings → Design System’s interactive modal uses this same component.

`components.promotions.download-offer` supplies the free-poster popup and article-specific blog gift dialog. It composes the shared modal, `@input` and primary button, with a cream poster preview, explicit email-delivery copy and a Maybe later dismissal. Desktop uses two Bootstrap columns; phone previews are capped at 150px. Pass a unique `id`, image/alt text, heading, description, `giftUrl` and `actionLabel`; the automatic poster keeps its existing cookie and timing. Its Design System specimen renders the same component with `preview: true`, replacing the form with a non-submitting container. Only preview layout lives in `components/_download-offer.scss`; input and button finishes stay shared.

Signup and Premium invitations use `components.account.access-modal`, with `kind`, `id`, `title`, `description` and a string array of up to six `benefits`. It composes the shared modal, `access-prompt-icon`, `access-prompt-body` and green-check `access-benefits` list. Signup uses a user icon, emphasizes **free** in the description and **Sign up free** action, and includes a login link. Premium uses a crown and **Go Premium**. Titles belong in the body; the modal's optional `titleId` points its accessible label there. Default body spacing is `px-4 pb-4 pt-0`; an explicit numeric `options.body.padding` still produces `p-N`.

`App\Services\WebApp\AccessPrompts` supplies concise action copy and benefits derived from the saved Access Limits matrix. Lists describe actual improvements to the next account tier, respect annotation/score dependencies and limited caps, and never promise a paid feature for free. The shared `components/access-prompts.js` updates Premium titles from server-rendered action copy for native modal triggers (`data-access-action`) and media/favorite callbacks (`PianoLITAccessPrompts.show`). Both modal kinds and the reusable `components.account.signup-card` specimen are demonstrated in Settings → Design System. The live blurred-score overlay stays lightweight: one friendly sentence and the shared Sign up free / Go Premium button, without an embedded card.


## Focus appearance

By user preference, PianoLIT has no extra outline or focus halo in any state, across the public website, web app and admin. `shared/_focus.scss` is the final reset for native/vendor outlines and Bootstrap’s focus-ring helper. It stays plain CSS so standalone error layouts can embed the same source. `shared/_bootstrap-theme.scss` disables Bootstrap focus shadows at their source, while controls retain their existing border, background, hover, pressed, validation and selected states. Do not add component-specific `outline`/`outline-offset` rules or focus ring box shadows. Focusability, keyboard navigation, tab semantics and modal focus management remain functional; do not remove `tabindex` or blur controls to hide a ring. The Design System uses these same shared styles.

### Access status select

`admin.components.filters.status-select` reuses the admin table's green/warning/red badges as a native select. Its reusable styles are in `components/_status-select.scss`; the shared script in `views/admin-access-limits.js` updates the selected tone and optional helper note. The Design System table includes the same control. Limited options are supplied by the policy's feature definition. Locked account-owned guest cells show Account required inside a muted/light grey disabled pill, with no repeated note beneath it. Its hidden submitted value remains Blocked. The shared component accepts `selectedLabel` and `tone` for this display without adding another access state.
## Calendar fields

`components.forms.date-picker` is the reusable inline calendar for forms that must
disable individual dates. It wraps the shared `@input` field and the already
installed jQuery UI calendar. Pass `name`, `id`, `label`, `bag`, `value`,
`minDate` (`YYYY-MM-DD`) and `blockedDates` (date strings). Its input is read-only;
selection happens through available calendar days. Navigation and day controls
use the shared button classes. Styles live in `components/_date-picker.scss`,
emitted by the shared stylesheet, and the component includes its versioned
`js/components/date-picker.js` once. The Design System's calendar specimen uses
this same component. Server-side validation remains authoritative when another
administrator changes availability after a page was loaded.

## Homepage project cards

The standard public header is `components.layout.public.header`, with shared layout in
`resources/sass/components/_public-header.scss`. Reuse it for public pages:
brand icon left and the circular account control right. The YouTube button is
shown only on the main homepage (`home` route).
It reuses `components.navigation.youtube-button`: a black pill with a white
YouTube mark, the label “Youtube” and a white diagonal arrow, using the shared dark button, pill radius
and Bootstrap sizing/alignment. The Design System uses this same component.
The standalone metronome passes `transparentHeader => true` and retains the
shared account circle. Its transparent logo follows the selected background's
luminance; the neutral account circle opens the same account/hub panel. Existing
metronome controls remain isolated while navigation is open.

Public, storefront and web-app headers reuse the shared account/hub navigation
described above. Hub links use named routes or the configured App Store URL.
The panel is pushed to the body-level script stack so it does not affect header
layout or inherit a page stacking context. Administration and mobile embedded
layouts retain their existing navigation. Piece Options keeps its existing
`components.layout.side-menu-header`, styles and Design System specimen.

The public account control is `components.account.account-circle` with `.btn-raw` and
shared styles in `components/_account-circle.scss`. Its actual button surface
provides a consistent white-bordered light-grey circle using `--color-border`,
with `--color-text-dark` for the icon/initial and `--color-text` on interaction.
Its shape follows the admin avatar; the public control uses neutral site colors.
Guests see a smaller `user-round` icon centered inside; signed-in users see
their first initial; both open the shared account/hub panel. Both states use the
shared field-height token for size and are shown in the Design System. Bootstrap
flex alignment on header list items centers YouTube beside the circle without
inline baseline spacing.

On the signed-in Games hub, a second `.account-circle` control opens the separate Game player dropdown. `games.challenges.profile.dropdown` keeps its 21px trophy icon, shared field-height circle size and white border; the Games stylesheet supplies the reference primary yellow (`#ffe54c`) and dark icon color. Keep account links in the shared offcanvas and game records in this separate menu. Guests sign in through the shared offcanvas.

All public and web-app login/register URLs share `auth.webapp.login`,
`auth.webapp.register` and their layout; there are no separate website versions.
Forms and account links stay on the current host, with `web` / `webapp` signup
origin and existing provider availability. Login and registration are
standalone on every host, without the public header or footer.
Login, registration and password recovery/reset fields use the shared `@input`
component and field tokens. Their submit buttons use `.btn.btn-primary` and its
standard shadow, without `input-light` or an extra `shadow` utility overriding
the shared controls. The retained account modal uses the same fields/buttons.
The reciprocal login/register prompts share `.auth-secondary` muted, centered
text and `.auth-secondary-link` primary semibold links, with an underline only
on hover/focus. Place both prompts after the provider options.
Registration's entire terms notice uses that same muted, centered 14px paragraph
style; its Terms of Service and Privacy Policy links inherit the same size and
shared link styles, including their hover/focus behavior. Avoid nesting `small`
text that makes the notice inconsistent with the account prompt.
Forgot-password and reset-password views (`auth.passwords.email` and
`auth.passwords.reset`) use this same standalone layout and return-link styling,
with `showCarousel => false`: a shadowed panel centered horizontally and vertically
using the Bootstrap grid, without the onboarding carousel. Login/register retain it.
Their forms retain the existing same-host endpoints, CSRF fields and reset token.

The standard footer is shared across all full public pages, including the
temporary project pages and public account-security screens. Standalone login
and registration are excluded. Use
`components.layout.public.footer` through `layouts.footer`; its `.site-footer` styles
live in `components/_public-footer.scss`. Keep the existing message,
Support/Privacy/Terms links and copyright. Arthur’s October 28 reference is the
standard across every hub using this footer: regular 12px shared body text,
muted gray, no added letter spacing, and blue underlined links on hover or
keyboard focus. A centered, wrapping row of all seven hubs (Web app, iOS app,
Learn, Games, Shop, Metronome and Blog) sits above the centered Support/Privacy/Terms
links, using the same link finish and Bootstrap grid. The homepage's alternate
two-caption footer retains its own layout. Footer destinations without this shared
navigation (Web app, iOS app and Metronome) open in new tabs with
`target="_blank" rel="noopener noreferrer"`; Learn, Games, Shop, Blog and links
back to the main homepage stay in the same tab.
Keep typography, spacing and link behavior in the shared
component/styles, without hub-specific overrides. The `.public-site` shell uses Bootstrap
flex/height utilities to keep the footer at the bottom of short pages. Both
shared templates use Bootstrap `container`, `row`, `col-*` and spacing/alignment
classes for layout, so width and responsive behavior can be adjusted directly
in Blade. The footer's three `col-12 col-md-4` groups stack on mobile. Sass
retains typography, colors and link states. Web app, admin and embedded mobile
layouts retain their own footers.

The Support center's canonical main-site URL is `/support`, preserving the
`contact` route name so shared links update automatically. Legacy `/contact-us`
GET/HEAD requests redirect permanently to the main `/support` URL with their raw
query strings intact.

The Shop and Games root placeholders use
`projects.coming-soon` with the shared header/footer, centered Coming up soon
message and return link. Arthur removed all illustrations from these placeholders.
Their layout lives in `pages/_project-coming-soon.scss`; content URLs below those
roots remain available. Return links use the named main-site route, including
from Games and when `APP_URL` has no scheme.

`home.components.project-card` renders the homepage’s featured and compact hub cards,
and the Design System previews both variants. The October 26 “Many worlds”
reference replaces the scrolling gallery and decorative piano room. The desktop
Bootstrap grid uses four large cards (Web app, iOS app, Learn, Games), followed by
three horizontal cards (Shop, Metronome, Blog), restoring the original reference order. Every card remains one native
link with its existing destination and accessible title/description; iOS opens
safely in a new tab. There is no auto-scroll or extra click step. Hub headings
reuse the main-menu icons: PianoLIT for Web app, Apple for iOS, graduation cap
for Learn, trophy for Games, shopping basket for Shop, metronome and newspaper
for Blog. Use the shared icon/brand renderers with the existing 20px utility and
Bootstrap alignment/gap classes; icons are decorative beside the accessible title.

Below 768px, the versioned `project-deck.js` progressively enhances all seven hubs
into a scroll-driven accordion. As a card leaves the reading position, it becomes
compact while the adjacent card expands; scrolling upward reverses the handoff.
Mobile compact cards are clean colored text/arrow rows. Each photograph stays at
the full card width and its measured full photo height, anchored below the copy;
the card's changing clip reveals that stationary image with a brief fade.
Never resize or translate the image window with scroll progress: that causes
`object-fit: cover` to zoom and reframe the artwork during the handoff. Copy keeps
the full card width, and action labels fade in near full expansion to avoid
showing fragments of button text. Desktop compact cards retain their thumbnails.
The Bootstrap columns reserve fixed compact slots plus one shared expansion.
Translate the slots and clip full-size cards instead of changing layout heights;
heading/button geometry is also fixed, with transforms and fades supplying the
compact appearance. Only changed card/slot properties are written. Passive native
scrolling feeds a short, elapsed-time-based animation-frame ease (45ms response),
so sparse events and 60Hz/120Hz displays follow the same timing. It stops once
settled and never intercepts wheel/touch input. Copy/tag/heading measurements happen
at initialization or a layout-width change, never inside scroll frames;
viewport-height changes from mobile
browser chrome retain stable anchors, and a small measured bottom allowance lets
the final card expand fully. Keyboard-focused links are brought into the expanded
position. Desktop, reduced-motion preference and no-JavaScript views keep the
static grid. The same card markup supplies tags/actions for every hub, hiding
those additions in the desktop compact variant; the Design System specimens
continue to use the shared card component.

The homepage's `home.components.scroll-hint` places a shared secondary circular
button with a black arrow at the bottom center of the viewport, above the device safe area.
It gently sways over 3.2 seconds and scrolls down when activated. The versioned
`home-scroll-hint.js` hides it permanently for the current document after the
first downward scroll (16px tolerance) or activation; returning to the top or
restoring that document from browser history does not show it again. Fresh
navigation/reload at the top resets it without cookies or browser storage.
Do not show it on a non-scrollable page or an already-restored scroll position.
Reduced motion disables the sway and uses instant click scrolling.

The header keeps the shared logo, YouTube action and account control. Its optional
`showBrandName` adds PianoLIT beside the logo only on the homepage, hidden below
768px with Bootstrap `d-none d-md-inline` so mobile header controls share one row. All public
pages and hubs share the 1360px container limit through `.public-site .container`
in `_public-overrides.scss`. This shell excludes `my.*`, admin and mobile embeds;
the full-screen metronome and individual games keep their focused layouts.
Header/footer rows and the homepage hero use Bootstrap `mx-lg-4` to restore the
desktop text inset. The logo, title and footer share its left edge, with menus
and footer captions equally inset on the right. Homepage cards retain their
wider container and extend beyond those text edges. Other public pages use
`components.layout.public.content` through `layouts.app`: a container, inset row
and unpadded column around the existing page containers. Their content stays
within the logo/menu edges. Homepage, `my.*` and raw views bypass that wrapper;
the centered Shop placeholder retains `mx-auto`. Below `lg`, standard gutters
keep mobile alignment unchanged.
The web-app header uses the same `clamp(48px, 7vw, 64px)` logo size as the public
header, retaining its own existing container and position as the viewport narrows.
The gap from the header to public breadcrumbs matches the homepage eyebrow:
36px from 768px upward and 30px on phones. The shared header's following main
adds 12px/6px above the page container's Bootstrap `py-4` padding; keep this
spacing shared instead of adding breadcrumb-specific margins to each page.
The hero uses six-column blocks from `lg` and a five/seven-column split from
`xxl`, keeping the introduction close to the title without squeezing it on
smaller screens. “Choose a world to begin” stays left-aligned immediately below
the subtitle on desktop and mobile.
Edit these columns and spacing in Blade. The homepage passes
`homeWorlds` to the shared footer
for the reference’s “Made for the love of piano” / “PianoLIT · Come back curious”
layout; other pages retain Support, Privacy, Terms and copyright.

The homepage uses a full-window white page surface, without an outer surround,
inset frame or rounded page corners. Content retains its responsive container.
Its title keeps `--font-size-h1` (44px), switching to `--font-size-h2` (32px) below
768px, with normal letter spacing. The explicit line break and Bootstrap
`text-nowrap` keep the headline on two lines without compressing the type.
Homepage cards, shared hub titles, Learn introductions, Blog article titles,
article subheadings and story-card titles also use normal letter spacing.
The project palette is lime,
lavender, butter yellow, coral, sky blue, mint and rose, scoped to `_project-card.scss`.
Featured titles use the shared 32px heading token, compact titles use 24px, and
body copy uses 16px. Tags, link actions and arrow icons reuse shared components,
Bootstrap controls and the common card lift, including reduced motion.

All featured photo windows, including iOS, clip to the same rounded upper corners.
Compact artwork has a soft left fade behind the HTML text. Source artwork lives
in `resources/images/home/worlds/` and is copied/versioned by Mix: four 720×900
portrait images and three 960×640 landscape images. Image dimensions reflect
the artwork file independently of the card’s featured/compact layout. These are decorative images
extracted/reconstructed from the user’s approved design with the built-in imagegen
tool; they are not live product screenshots. Exact prompts and source paths are
in `HOMEPAGE_ARTWORK.md`. Older editorial images and SVG mockups remain available.

The homepage still does not query a Free Pick or load audio, PDF or score-editor
scripts. Web-app/mobile Free Pick media and access behavior remain unchanged.

## Public editorial pages and promotions

The restored Blog directory uses the shared public header/footer, topic buttons
and a server-paginated, uniform story-card grid: three columns on wide screens,
two on tablets and one on phones, without a featured story.
`blog.components.story-card` keeps titles visible and limits descriptions in CSS,
without Masonry or JavaScript clamping. Cards show every story topic with the shared
`@pill` component above the date, with no repeated author icon/byline. Titles keep
their normal color on hover; `.card-lift` shares the piece cards' 3px upward hover
movement and reduced-motion behavior. The existing 12-story page size, published
content filter and topic queries are retained; pagination preserves the query.

`components.layout.editorial` provides the Bootstrap content/sidebar columns,
stacking the sidebar after the content below the desktop breakpoint. Reuse it
as other public directories and editorial pages are redesigned.
Hub detail content sits directly on the page surface and uses the available
content column, without an enclosing white card, shadow or extra inset padding.
Keep cards for individual stories/resources, promotions and purposeful controls.
`components.promotions.ads` accepts `variant: cards` to render the existing latest
published crash course, eBook and eScore through `components.cards.promotion`, in the
requested order. Legacy banners on other pages retain their existing layout.
The shared promotion card uses `.pastel-card`, its existing soft action button,
color/radius/type tokens and the original course/shop destinations and media.
Its specimen lives in Settings → Design System → Components. Shared layout/card
styles live in `components/_editorial-layout.scss`; blog-specific typography and
story grid styles remain in `pages/_blog.scss`.

Public article pages use the same subtle surface, fluid headline, shared
`components.layout.editorial` and pastel promotion cards as the directory. The
article has no outer card or inset padding; topic links render shared `@pill`
badges above its heading. The date and reading
time remain visible, with a simple text byline. `.blog-article #blog-content`
fills the content column with the shared blog font token, paragraph and
heading rhythm, and responsive YouTube/Vimeo framing. These rules are scoped to
the public article; mobile embeds retain their existing typography and media.
Stored article HTML, cover credits and references are preserved.

`components.newsletter.subscribe-button` supplies the shared brand-blue Subscribe
pill with a white mail icon, used in the Blog and Learn site headers and newsletter
invitations. It matches the homepage YouTube button's compact size, semibold type,
pill radius and shadow-free finish through shared button tokens and Bootstrap utilities.
Its modal URL stays on the request host; `label` changes the caption and
`preview: true` makes a Design System sample inert.

`components.layout.hub-header` owns the shared landing-page heading and description.
Blog and Learn pass a plain `heading`
(`PianoLIT Blog` / `PianoLIT Learn`) and their description as the slot. Every
redesigned hub root uses this same simple title treatment, matching Blog's
responsive 32–48px size, line height and letter spacing; the description uses
the shared 16px body token and muted text. The shared styles live in
`components/_hub-header.scss`, including the Design System specimen.
The shared header uses Bootstrap `mb-5` (48px) to separate it from page content.
The component opts into `public-header-newsletter`, placing Subscribe in
`components.layout.public.header` immediately before the account circle, in the
same position as the homepage's YouTube button. It no longer renders an action
beside or below the introduction. `showNewsletter: false` suppresses this opt-in;
Design System previews do not opt in. Reuse this component as other public hub roots are
redesigned. Do not add a newsletter invitation card at the bottom of any hub root;
use the shared header action. Its Design System specimen uses `preview: true`.

`components.newsletter.invitation` provides the reusable pastel newsletter panel
for article and tool detail pages, with the same blue Subscribe pill and existing
same-host subscription modal. These pages also opt into the top-header action;
their contextual newsletter invitation remains available within the content.
Pass a unique `headingId`; `preview: true` renders an inert Design System sample.
The article explicitly passes `preview: false` so editorial previews also retain
the subscription action. Related stories reuse `blog.components.story-card` in
the directory's responsive grid, without Masonry or JavaScript clamping.


## Learn hub landing page

`learn.home` renders `learn.index`, replacing its placeholder with a directory
of six projects grouped by purpose: Interactive tools (Circle of fifths and
Chord finder), Courses & guides (Crash courses and Infographics), and Quizzes &
pianists. The introduction leads directly into those groups, without an
additional section-navigation strip. Each project has a clear description, format label and
action. Scales and arpeggios stay in a smaller More practice tools row.

Reuse `components.cards.resource` for project/resource navigation cards. It uses
a white surface, fine border, shared small radius and `.card-lift`, with full-width
2:1 artwork instead of an inset pastel panel. The whole card is one named link
and titles keep their normal color on hover. Pass a unique `id`, `title`,
`description`, `format`, `action`, `url`, and an `artwork` slot. `preview: true` marks the Design
System sample, which renders this same component and circle artwork. The common
styles in `components/_resource-card.scss` are imported by app and admin.

`learn/components/artwork.blade.php` supplies editable 480×240 SVG previews:
a blue key wheel, ink-black chord keyboard, vermilion lesson sheet, neutral
dynamics reference, yellow quiz and typographic pianist poster. These illustration
pigments are local to the artwork; shared brand/surface/type tokens remain in use.
They contain no screenshots, invented content totals or external image requests.
The introduction leads with the plain PianoLIT Learn title and names concrete
activities in its description. It uses the same `components.layout.hub-header`, title
and description styles and newsletter placement as Blog.
Learn retains the public header/footer, breadcrumb and `components.layout.editorial`.
Its sidebar ads always use `components.promotions.ads` with `variant: cards`, which
renders the same `components.cards.promotion` as Blog, including the existing pastel
surfaces, category tags, spacing and buttons. Do not add a hub-specific ads design,
appearance variant or introductory heading. This reusable ads area stays consistent
across public hubs. Learn has no bottom newsletter invitation card. Its introduction
uses the shared newsletter action on the Learn host.
The Circle of Fifths and Chord Finder also use this shell; other deep resource and tool layouts
remain for later work. Keep their routes, calculations, signup/download/quiz
requests and mobile contracts intact.

The Circle of Fifths workspace places its SVG wheel directly on the page, without
a pastel wrapper or inset padding. White sectors contrast with the page surface;
the center matches that surface. It pairs with the shared segmented tabs,
key/relative-key panels, `@pill` chord labels and theory help dialogs. The
wheel has broad tap sectors, upright major/minor labels, a selected-key sector,
roving keyboard focus, arrow navigation and Reset to C. Respect reduced motion.
Its key, scale, chord and signature data still comes from `CircleOfFifths`.
The separate Hear the scale section offers cancellable playback through the existing
piano sampler. Changing key/mode, playing a note, opening a dialog or leaving the
page stops the sequence. Theoretical spellings missing from the keyboard aliases
are matched locally by pitch; shared note data and media URLs remain unchanged.

`components.music.piano.keyboard` supports an opt-in `responsive: true` layout with
proportional key widths, responsive height/dots and solid white keys, including
after a played note is released. Put the tool keyboard directly on the page,
without a padded, rounded pastel wrapper. Existing callers retain their fixed key
sizes and finish. App/admin import `components/_piano.scss`; the Design System's
responsive keyboard specimen renders this same component without loading audio.
The circle's standalone script uses `mix()` and an explicit versioned asset entry.

The Chord Finder keeps its editor and results on this same open page surface.
Use the shared segmented tabs for piano-keyboard versus note-name entry, standard
buttons for note/accidental selection, shared `@pill` labels, and `@select` fields
inside the optional spelling/root accordion. The piano uses the shared responsive
keyboard. Selecting the same pitch in another octave moves that selection rather
than adding a duplicate. For common chord shapes the keyboard suggests conventional
enharmonic spellings (for example C–E♭–G), preferring fewer accidentals. It uses
only existing keyboard aliases and leaves manual spelling choices intact. These
suggestions do not change the server’s ranking rules. The editor remains available after analysis, with Clear
notes, an example, inline errors and an Edit notes link from the results.

Results reuse the shared accordion components for interval explanations; each
inversion/step must have a unique target. Result buttons retain their `data-notes`
and legacy `href` attributes alongside accessible state/controls. Displaying a
result does not start audio: selecting a chord plays its ascending notes and
then the full chord through the existing sampler. Slash-chord bass notes affect
the voicing. Cancel playback on a new choice, editing, navigation/dialog opening,
manual piano input or leaving the page. Protect against stale analysis responses
when notes change. The standalone Chord Finder script also uses versioned `mix()`.

### Standalone metronome settings

`metronome.index` retains Arthur’s original full-screen canvas and uses the shared Bootstrap bottom sheet for settings. Only this page loads `css/metronome.css` and `js/metronome.js`. The canvas defaults to `--color-primary`, with the matching `#0055fe` reset value in the local preference module. Its tempo numeral retains the original thin system face; the settings inherit PianoLIT body/heading typography and standard sizes from `app.css`. Reuse `components.forms.segmented-control`, `@select`, `@toggle`, `.btn-close`, `.btn.btn-secondary` and `.btn.btn-primary`, including their existing shadows. `components.forms.color` supplies the native `.form-control.form-control-color` control, also shown in Settings → Design System. Local settings CSS caps the centered content at 560px and hides unrelated fields during recording; shared bottom-sheet styles and Bootstrap own the panel geometry, scrolling and transitions.


### Section navigation

`components.navigation.section-navigation` renders the shared Support center and My profile menus. Pass a unique `id`, an accessible `label`, and ordered `items` with `target` (section ID), `label`, and optional `icon`; optional `heading` adds the muted introduction. The single `_section-navigation.scss` in `shared.scss` owns wrapping mobile links, sticky desktop placement, and the primary-color/semibold current link. Neither page defines its own menu markup or finish.

The component includes the versioned `components/section-navigation.js` once. It highlights the visible section on initial load, manual scrolling, resizing, page restoration and FAQ expansion/collapse, with a bottom-of-page fallback for short final sections. Exactly one valid link per menu receives `aria-current="location"`; ordinary fragment links and keyboard navigation remain native. Keep scroll behavior here rather than in page scripts. The Design System renders this same component with `preview: true`, which shows a static current item without attaching scroll listeners.


## Infographics

`infographics.index` uses the shared breadcrumb (`Home → Learn → Infographics`),
`components.layout.hub-header`, topic links, `components.layout.editorial` and the same
sidebar promotions as Blog. `components.navigation.topic-navigation` supplies the common
link buttons for Blog and Infographics, preserving each directory's query names
and active state. Its items accept `label`, `url`, `active` and an optional
Bootstrap button `variant`. Both shared components have Design System specimens.
There is no bottom newsletter invitation.

`infographics.card` reuses `components.cards.resource`; its optional `artworkClass`
adds `resource-card__art--poster`. That variant fits the entire poster inside a
neutral 4:3 preview area, with a one-rem inset. Default SVG resource cards keep
their existing 2:1 artwork area. The directory uses a responsive Bootstrap grid,
12-item native pagination with preserved filters, and an explicit empty state.

The detail page retains its preview and download information, with shared buttons,
file dimensions, license, related resource cards and accessible feedback status.
On phones, the download information precedes the poster. A guest uses the existing
account prompt; a first download keeps the current confirmation flow. Download
again uses the infographic's `autoDownload()` URL and streams the original file
without navigating to My downloads or creating another purchase. The infographic
confirmation page retains its familiar layout and adds the breadcrumb trail and
shared primary download action; other product confirmation flows stay intact.


## Crash Courses

`crashcourses.index` follows the Infographics/Learn directory pattern: shared
breadcrumb, `components.layout.hub-header`, `components.navigation.topic-navigation`, resource
cards, native pagination and `components.layout.editorial` with standard sidebar
promotions. Topic filters survive pagination; draft-only topics are omitted.
`crashcourses.course` renders `components.cards.resource` with its existing cover
image, actual lesson count and a normal same-tab course link. Bootstrap positioning
and object-fit utilities keep covers inside the standard 2:1 artwork area. Missing
covers reuse the native Learn course illustration. There is no footer newsletter
invitation.

The course detail page uses the public shell and breadcrumb, plain heading,
course overview and a signup panel. On phones, signup precedes the cover and
course facts. `crashcourses.card` owns the signup panel and is reused in the
signup popup. It accepts `formId`, `framed` and an inert `preview` mode; its
Design System specimen renders the same form. Name/email labels, autocomplete,
errors and fields use shared `@input` controls; the action is the shared primary
button. Keep CSRF, the existing anti-bot fields, reCAPTCHA and submission lock.

Validation uses the `crashcourse` error bag, preserving submitted values without
marking login fields invalid or opening the account modal. Canonical popup
validation returns to the course page; legacy posts keep their return host. The
course delivery, existing signup/cancellation URLs and video/media URLs are
unchanged. Course enrollment does not opt someone into the PianoLIT newsletter.
Course emails currently offer a contact link to stop lessons, so the signup panel
uses that instruction rather than promising an automatic unsubscribe control.
The other funnel and lesson-video views retain their existing styles and behavior.

### Quiz pages and covered resource artwork

Public quizzes use Learn's shared breadcrumb, hub header, topic navigation,
editorial layout and unchanged promo-card sidebar. `games.quizzes.card` delegates
to `components.cards.resource` with `resource-card__art--cover`, a 2:1 crop of the
existing cover. This image variant is shared by app/admin styles and represented
in the Design System; the SVG and full-poster variants retain their behavior.
Question panels use white surfaces, shared controls and the standard small radius.
Correct/incorrect answer borders use the shared success/danger tokens, with
visible answer labels and live feedback so color is not the only indicator.
`pages/_quizzes.scss` scopes these states to `.quiz-page`; it does not restyle
promotions or other tools. See `QUIZZES.md` for the view/script map.

### Great Pianists

Public artist pages reuse Learn's breadcrumb, hub header, editorial layout,
resource cards and unchanged card promotions. `pianists.card` supplies the saved
nationality, name and biography excerpt to the shared card. Its
`resource-card__art--portrait` variant uses a 4:3 image crop with top-biased face
positioning; the Design System includes this same variant. Profile portraits use
4:5 framing beside the name/dates on both desktop and phones. Recording cards use
the standard white surface, border, small radius and lift, with 80px square album
art and shared type/links. Layout-specific rules stay in `pages/_pianists.scss`.
See `PIANISTS.md` for controls, data states and the editing map.


## Shop storefront exception

Digital catalog cards inset existing eScore/eBook covers and mockups within the square artwork area, leaving space on every side. Use mixed-case 600-weight body-family titles, regular muted subtitles from the existing product record when available (at most two visible lines), and compact yellow outlined price labels aligned at the bottom of the copy. Keep full titles readable and omit redundant category labels. The shared `shop.storefront.components.card` and its Digital cards Design System specimen own this treatment.

Color options include a 22px square swatch beside the readable name. The swatch uses saved Printful catalog hex codes, including a diagonal split for two-tone variants, and keeps its color through button hover/selection. The button retains ordinary contrast and keyboard feedback; missing or conflicting provider color metadata stays text-only. Never add named-color CSS mappings for products.

Arthur’s October 28 video reference explicitly gives Shop its own component finish: white page surface, square black outlines, mint actions, yellow price tags and a four/two-column product grid. Arthur subsequently removed flat offset shadows from all Shop UI elements, including hover/pressed states, reserving that treatment for Games. Cards and buttons no longer shift to simulate shadow depth. Product-card actions reveal with a 180ms fade and 12px upward slide on card hover or keyboard focus; retain the shared color transition too. On touch devices the action stays visible, and reduced-motion preferences disable the transition. White buttons/options/gallery controls turn black with white text on hover or keyboard focus; mint buttons darken from `#b8efda` to `#96d9be` while retaining dark text, and already-dark controls turn charcoal. Disabled controls do not react. No decorative star. `resources/sass/storefront.scss` is a separate scoped entry, loaded by the Shop storefront shell and its Design System specimen; legacy digital catalog directories now share the same category view. Fonts remain the shared body/Poppins families, 16px body/control text, 44px desktop and 32px phone headings. The reference’s giant monospace titles and textured background are not used. Reuse `shop.storefront.components` for product cards, branding, gallery, price, Details, options and quantity controls.

Shop’s header uses the standard responsive PianoLIT logo size (`clamp(48px, 7vw, 64px)`) beside plain Shop text, without a highlighted label or an extra PianoLIT wordmark. Its header row uses `mx-lg-4`, and all storefront pages use `components.layout.public.content` plus the usual inner container, matching the other hubs’ content edges. The right side contains Bag and the shared account circle/menu. All, T-shirts, Stickers, eBooks and eScores are outlined category links to the root or `/t-shirts`, `/stickers`, `/ebooks` and `/escores`; published downloads share its product cards rather than separate header links. All Products groups T-shirts across the first two desktop columns and Stickers across the next two, followed by full-width eScores and eBooks sections. Small 16px category headings with right arrows link to the same category destinations as the top navigation, preserving the search query; category-filtered pages omit group headings and redundant card category labels. Each section has one horizontal row with 44px previous/next controls at the heading’s right edge, using `shop.storefront.components.category-row` (also shown in the Design System). Physical sections show two cards, full-width digital sections four. On phones sections stack with two cards visible. Controls disable at scroll boundaries; native touch/keyboard scrolling and reduced-motion preferences are retained. Selected category pages show the complete matching product grid without row arrows or pagination. Merchandise, eBook and eScore details all render `shop.storefront.show` through the web-only `Shop\ProductPage` service, sharing the gallery, price tag, title and open, unboxed Details section. Product-specific partials add options or purchase/download actions; digital editions add their full description, topics, preview/audio, reviews and related cards. Preserve paid/free/member/owned actions and existing digital checkout/download behavior. Paid digital editions use the shared full-screen checkout shell and remain separate from the merchandise bag; free/member/owned actions retain their existing behavior. The Design System uses the actual shared gallery and price partials.


Customer merchandise tracking reuses the shared account shell, `profile-panel`, standard headings/buttons and eight-item pagination at `/profile/orders`. Keep My downloads separate and cross-link the two pages. All account menus reuse `components.account.menu`, whose Track your orders entry always links to main. The private Shop order view reuses the storefront shell and `shop.storefront.components.order-tracking` for carrier details, package items, safe external tracking links, delivery estimates and recent carrier events, without a second tracking app design. Receipt and email links lead to that same view; external carrier links open safely in a new tab.
