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

Full-screen public projects use `components.project-watermark`: a white,
transparent SVG with a white rounded-square border, derived from the approved
monochrome silhouette, fixed at the top left with safe-area spacing. It links to
the absolute `home` route, uses 30%
opacity at rest and 65% on hover/keyboard focus, and disables its transition for
reduced motion. The metronome uses this component without adding public
navigation. The Components section previews the same link against blue; the
Website & web app download modal includes `icon-white.svg`. Regenerating brand
icons also regenerates this derivative, without changing the two icon masters.

`components.voice-recorder` supplies the standalone metronome's guided four-count
recorder, named voice library and the Components specimen. Buttons, selects and the live level meter
reuse shared Bootstrap controls. `components.voice-recorder-cue` supplies the
large number, status badge and animated ring using shared status colors: yellow
Wait with a countdown, green Speak now with a listening pulse, a green Recorded
check and red Try again with a brief shake. Text/icons reinforce every color;
reduced motion removes animations and transitions. The Components gallery shows
all four states on this same cue component. Keep the concise recording prompts, per-number replay/redo and explicit
Save and use action on this component. Reuse `@input` for recording names and
renaming. `components.metronome-sound` renders a custom Sound menu whose trigger
uses `form-control` and the shared `form-control-select-trigger` arrow mixin, so it
matches native PianoLIT selects. The popup reuses Bootstrap's shared dropdown
surface/items, with Recorded voices grouped beneath instruments. The shared
`metronome-sound-voice` row has play/stop, edit-name and delete icon buttons; the
footer uses a full-width standard `.btn.btn-primary.btn-sm` New recording button.
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

`components.brand-theme` supplies the shared user/admin browser metadata.
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
unchanged. `components.brand-splash` supplies
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
Native file fields use `components.form.upload.file` with `id`, `name`, `label`,
optional `accept` and `currentUrl` / `currentLabel`. Their IDs and labels remain
unique; existing files open safely in a new tab.

Collapsible lists use `components.accordion` and `components.accordion-item`,
with the native Bootstrap accordion styling and behavior. Pass a unique wrapper
`id`, then each item's unique `id`, `title` and optional `parentId` to keep one
item open at a time. `open: true` sets the initial expanded state. The wrapper
defaults to the flat `accordion-flush` variant; `flush: false` keeps the standard
outer border. The Design System previews these same components. Avoid combining
rounded card headers with separate card borders inside an accordion.

Date and time displays omit timezone names and explanatory timezone captions.
Use the configured application timezone for formatting and calculations.

Use `@input` (`components.form.input`) for standard text/email fields, including
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
`@select` (`components.form.select`), with the same default field dimensions and
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

Multiple-choice filter menus use `components.form.checkbox-menu`. Pass a field
`name`, accessible `label`, an `options` value/label map, the `selected` array and
an optional `allLabel`. The shared admin filter trigger, Bootstrap dropdown and
native checkbox styles stay in sync with the Design System's Checkbox menu
specimen (`designSystemPreview: true`). The menu stays open while checking
options, offers Select all, and updates its summary. Escape returns focus to the
trigger; outside clicks and Tab leaving the menu dismiss it. Choices submit with
the containing form's Apply action; at least one is required. The component sends
an empty array sentinel so an unchecked menu can be rejected on the server.
Consumers must validate allowed values and discard the sentinel before querying.

Small, mutually exclusive choices use `components.segmented-control`: pass a
unique `id` and radio `name`, an accessible `label`, an `options` value/label map
and the selected `value`. The shared component fills its container with equal
segments and uses native radios for a single selection and arrow-key navigation.
Its neutral tray, selected surface and focus border use the shared tokens. The
Design System's Segmented selection specimen renders this same component.

Switches use `@toggle` (`components.form.toggle`) and the shared `.switch` /
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
`--color-surface-subtle` (#f8f9fa), matching the homepage. Keep white cards and
controls on `--color-surface`; page-specific backgrounds remain explicit
exceptions. The mobile article embed retains its white body through `bg-white`.

Discover's horizontal piece links reuse `.discover-compact-card` for their white
`--color-surface`, shared padding and rounded corners, including Recently viewed
and recommendation rails. Compact piece cards also stay white in Past highlights.

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

`components.membership-plan-card` is the single plan card used by Pricing and Change plan. Pass a plan, a unique `headingId`, an optional `monthlyPlan` for yearly savings, and an `action` slot containing a shared button or button link. Optional `trial`, `accessNote`, `context` and `selected` values adapt its content and selection state. Pass `current: true` for the existing membership plan to apply the shared `shadow-none border border-light` utilities. Its gradient panel, shadow and layout live in `components/_membership-plan-card.scss`, imported by both app and admin so the Design System specimen uses the same component and styles. These catalog cards belong to plan selection; the current membership overview uses ordinary `.card.rounded.shadow-light` content cards and saved billing dates, without guessing an existing subscriber’s charge from a catalog price.

`components.tab-navigation` is a Blade `@component` with its `.tab-content` and panes in the default slot. It provides accessible Bootstrap tab triggers for an `items` array of `target`/`label` pairs, with a unique `id` and accessible `label`. The `segmented` variant is the shared compact account navigation in `components/_tabs.scss`; it uses a compact row on phones and two columns below 360px. Panels use the target as their ID and `aria-labelledby="{id}-{target}"`. Items may supply an explicit `id` and a `data` map when an existing tool needs stable trigger IDs or data attributes; the matching panel must reference that explicit ID. Summary shortcuts activate the matching real tab trigger so keyboard focus and lazy loading remain consistent. It encloses the menu and panes in one frame and embeds its small shared controller once, selecting the URL fragment synchronously before revealing that frame. This avoids a flash of the default tab or a Bootstrap fade while the main bundle loads. With JavaScript disabled, the server default remains visible; an interrupted response reveals that default at DOM ready. Keep the panels inside the component slot, rather than after an `@include`. The navigation automatically marks each instance with `data-tab-navigation`. The shared `components/tab-navigation.js` controller, loaded in both app and admin bundles, saves the selected panel as a URL fragment and restores it on refresh or fragment navigation. It uses `history.replaceState` to preserve query strings and existing Back-button state without adding history entries; ordinary page anchors and unrelated tab implementations keep their own behavior. Both components are demonstrated under Settings → Design System → Components.

## Saved payment cards and secure fields

`components.payment-card-preview` is shared by checkout, Billing and the Design System. Its default mode renders the live card preview. Pass `saved: ['brand' => ..., 'last4' => ...]` for the current membership card and its check mark, and optionally `selectable: true` for a checkout selection button, or `add: true` for the monochromatic Add new card button. Add tiles take either `controlsId` for an inline disclosure (checkout), or `modalId` for a Bootstrap modal trigger with the appropriate dialog semantics (Billing and the Design System). The artwork, brand colors, check mark and add-card styling live in `components/_payment-card.scss`, imported by app and admin. Only the currently stored membership method is shown; the card editor explicitly describes replacement after verification.

`components.payment-card-fields` supplies the three Stripe-hosted mounts and native cardholder/postal fields. Pass a unique `prefix` and the initial `cardholder` value. Native fields use `@input` and shared labels; field layout lives in `components/_payment-card-fields.scss`. Checkout and Billing use this same component. Billing's editor uses `components.modal` at body level; its `data-deferred-card` form mounts after the modal is shown, focuses the card number once ready without overriding another user-selected field, and clears its draft after any dismissal while reusing the hosted fields on reopen. The dialog blocks dismissal during verification and temporarily releases its focus trap for Stripe's bank challenge, restoring it on failure. The existing verified SetupIntent flow remains responsible for saving the card.


## Public hub breadcrumbs

Use `components.breadcrumb` above the content of every redesigned public hub
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
Bootstrap breadcrumb, spacing, small-text and wrapping utilities and the shared
link finish. Its specimen is under Settings → Design System → Components.

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

Use `components.modal` for ordinary dialogs and confirmations. Its `header`, `body` and `footer` slots retain shared typography, button classes and `--radius-modal`. Pass `centered: true` for a centered confirmation and `descriptionId` for the ID of its explanatory text. The component supplies a focusable dialog root, a title association when a header is present, and an accessible Close button. Bootstrap handles the backdrop, Escape, focus trap and return to the opener. Keep destructive actions behind an explicit submit button, with a separate dismiss button. Web app views can push markup into `page-modals` to render it at body level, outside tab panes and other stacking containers. Settings → Design System’s interactive modal uses this same component.

Signup and Premium invitations use `components.access-modal`, with `kind`, `id`, `title`, `description` and a string array of up to six `benefits`. It composes the shared modal, `access-prompt-icon`, `access-prompt-body` and green-check `access-benefits` list. Signup uses a user icon, emphasizes **free** in the description and **Sign up free** action, and includes a login link. Premium uses a crown and **Go Premium**. Titles belong in the body; the modal's optional `titleId` points its accessible label there. Default body spacing is `px-4 pb-4 pt-0`; an explicit numeric `options.body.padding` still produces `p-N`.

`App\Services\WebApp\AccessPrompts` supplies concise action copy and benefits derived from the saved Access Limits matrix. Lists describe actual improvements to the next account tier, respect annotation/score dependencies and limited caps, and never promise a paid feature for free. The shared `components/access-prompts.js` updates Premium titles from server-rendered action copy for native modal triggers (`data-access-action`) and media/favorite callbacks (`PianoLITAccessPrompts.show`). Both modal kinds and the reusable `components.signup-card` specimen are demonstrated in Settings → Design System. The live blurred-score overlay stays lightweight: one friendly sentence and the shared Sign up free / Go Premium button, without an embedded card.


## Focus appearance

By user preference, PianoLIT has no extra outline or focus halo in any state, across the public website, web app and admin. `shared/_focus.scss` is the final reset for native/vendor outlines and Bootstrap’s focus-ring helper. It stays plain CSS so standalone error layouts can embed the same source. `shared/_bootstrap-theme.scss` disables Bootstrap focus shadows at their source, while controls retain their existing border, background, hover, pressed, validation and selected states. Do not add component-specific `outline`/`outline-offset` rules or focus ring box shadows. Focusability, keyboard navigation, tab semantics and modal focus management remain functional; do not remove `tabindex` or blur controls to hide a ring. The Design System uses these same shared styles.

### Access status select

`components.status-select` reuses the admin table's green/warning/red badges as a native select. Its reusable styles are in `components/_status-select.scss`; the shared script in `views/admin-access-limits.js` updates the selected tone and optional helper note. The Design System table includes the same control. Limited options are supplied by the policy's feature definition. Locked account-owned guest cells show Account required inside a muted/light grey disabled pill, with no repeated note beneath it. Its hidden submitted value remains Blocked. The shared component accepts `selectedLabel` and `tone` for this display without adding another access state.
## Calendar fields

`components.form.date-picker` is the reusable inline calendar for forms that must
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

The standard public header is `components.public-header`, with shared layout in
`resources/sass/components/_public-header.scss`. Reuse it for public pages:
brand icon left and the circular account control right. The YouTube button is
shown only on the main homepage (`home` route).
Other public pages add `components.public-hub-menu-button` after the account
control. Its Bootstrap offcanvas, `components.public-hub-menu`, links to the
main website, Web app, iOS app, Shop, Games, Learn, Blog and Metronome using named routes
or the configured App Store URL. Its `.hub-menu.offcanvas-end` sets a 360px
width and `max-width: none` to clear Bootstrap's inherited 100% cap, as requested
by Arthur. The hamburger/panel is absent on `home`
and `my.*`; standalone account pages keep their existing layout. The button
uses the shared raw-button and Bootstrap spacing/alignment classes, with its
specimen in the Design System. The hamburger icon is exactly `1.5rem`.
Panel destinations are a plain text-link list without icons or button cards.
The panel is pushed to the body-level script
stack so it does not affect header layout or inherit a page stacking context.
`layouts.header` supplies it to the public layout; the homepage and public
account-security screens use the same component. Web app, admin and mobile
embedded layouts retain their existing navigation.

The public account control is `components.account-circle` with `.btn-raw` and
shared styles in `components/_account-circle.scss`. Its actual button surface
provides a consistent white-bordered light-grey circle using `--color-border`,
with `--color-text-dark` for the icon/initial and `--color-text` on interaction.
Its shape follows the admin avatar; the public control uses neutral site colors.
Guests see a smaller `user-round` icon centered inside; signed-in users see
their first initial, opening the retained account dropdown. Both states use the
shared field-height token for size and are shown in the Design System. Bootstrap
flex alignment on header list items centers YouTube beside the circle without
inline baseline spacing.

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

The homepage footer is also shared across all full public pages, including the
temporary project pages and public account-security screens. Standalone login
and registration are excluded. Use
`components.public-footer` through `layouts.footer`; its `.site-footer` styles
live in `components/_public-footer.scss`. Keep the existing message,
Contact/Privacy/Terms links and copyright. The `.public-site` shell uses Bootstrap
flex/height utilities to keep the footer at the bottom of short pages. Both
shared templates use Bootstrap `container`, `row`, `col-*` and spacing/alignment
classes for layout, so width and responsive behavior can be adjusted directly
in Blade. The footer's three `col-12 col-md-4` groups stack on mobile. Sass
retains typography, colors and link states. Web app, admin and embedded mobile
layouts retain their own footers.

The Shop and Games root placeholders use
`projects.coming-soon`, their matching versioned card SVGs and the shared header.
Their layout lives in `pages/_project-coming-soon.scss`; content URLs below those
roots remain available. Return links use the named main-site route, including
from Games and when `APP_URL` has no scheme.

`components.project-card` renders the homepage's seven destination links and its
Design System specimen. Its gallery finish lives in
`resources/sass/components/_project-card.scss`; the wall, room and responsive
arrangement live in `pages/_home.scss`. Following Arthur's October 22 reference,
cards have a 35:48 aspect ratio, 7px corners, a warm-white surface, dark type and
directional soft shadows. Realistic close-up product mockups fill the card width,
with PianoLIT branding, dark hardware/printing and saturated book covers. The laptop
and iPhone screens use the live web-app capture and Arthur's actual iOS screenshot,
respectively, rather than invented interfaces. Neutral upper space keeps the HTML copy readable; relevant tabletop,
score and piano details give the lower scene depth. Omit abstract shapes, folded
paper and unrelated props. Artwork starts 40px below the top and uses `object-fit:
cover` with top-center alignment; natural side/bottom crops are intentional. General
pastel cards elsewhere retain their existing finish.
Project titles explicitly use Bootstrap's `text-body` utility so they keep the
regular text color regardless of inherited link states; descriptions also keep
their regular color on hover. Each card has a muted top-right `arrow-up-right`
from the shared icon renderer, positioned with Bootstrap utilities 16px inward
from the top and right edges to indicate its destination link. The gallery has
no previous/next arrow controls.
Within the gallery, fine-pointer hover highlights the active card with a 1.12
scale, 3px lift and soft 480ms spring. Other cards dim to 90% brightness and scale
to .95; the cards on each side move 32px inward behind the active card. The active
card stays in its original horizontal position and paints above its neighbors.
Keyboard focus gets the same emphasis when no card is hovered. Dragging disables
the emphasis, and reduced motion disables transforms/transitions while retaining
the brightness contrast. Compensated scrollport padding leaves room for the larger
card/shadow without changing the resting layout or piano position. These rules
live in `_project-card.scss`; Blog and web-app piece cards keep the shared 3px /
180ms `.card-lift` behavior in `components/_card-lift.scss`.

The gallery uses `components.project-deck` and its standalone, versioned
`js/components/project-deck.js`. Cards are 244px wide on desktop and 264px on
phones, retaining the 35:48 ratio, responsive 20–32px gaps (20px on phones) and Web app / iOS / Shop /
Learn / Games / Metronome / Blog order. Titles use `--font-size-h4` (24px); descriptions use
`--font-size-base` (16px) and shared line-height/weight tokens. The hero also uses
standard heading/body sizes. Every card is a direct destination link; Arthur
withdrew the inline expansion request, so there are no open panels or extra
navigation steps.

Native horizontal touch/trackpad scrolling is supplemented by mouse dragging
and Arrow/Home/End keyboard navigation; there are no previous/next buttons.
The finite row gently auto-scrolls forward at 22px/second, then stops at the
last card. Each project appears once; there are no clones, looping or reversal.
Both first and last cards are real boundaries. Arrow keys stay within those
seven links, Home selects Web app, and End selects the last rendered project.
Hover, pointer/touch holds and keyboard focus pause it immediately.
Manual scrolling and native swipe inertia postpone its
return until 2.4 seconds after interaction ends. It also stops while offscreen,
in a hidden tab or when reduced motion is enabled. A drag suppresses only its
release click; ordinary, modified and Enter clicks retain native link behavior.
Vertical mouse-wheel scrolling browses the row and releases to the page
at either boundary. Without JavaScript, the original seven links and native
horizontal scrolling still work as a finite row.
The Design System includes this same gallery component.

The main background keeps `--color-surface-subtle` (#f8f9fa) as its base, with
homepage-only translucent daylight layers: a soft upper-left glow, gently darker
right side and broad diagonal light bands feathered toward the left of the room.
These static gradients live in `pages/_home.scss`; the light overlay is behind
content and never intercepts input. The translucent floor shading lets the same
light continue below the wall. The scene includes an upright
piano and bench at their natural aspect ratio. The image uses `height: auto`,
with a 620px desktop maximum. The piano retains a normal black satin finish with
simplified surfaces, restrained texture, subtle brass trim and a softer shadow; its
top stays bare, with no plant. Cards remain in front of the decorative scene.
`--room-offset: 60px` in `pages/_home.scss` lowers the piano, floor and following
content together. Desktop spacing is `calc(-132px + var(--room-offset))`.
Below 768px, the responsive baseline remains
`calc(-60px - 12vw + var(--room-offset))`, with the piano at 94% of viewport width.
Adjust the shared offset to move the scene without separating it from its floor.
Keep the piano physically grounded: `__instrument::before` supplies tight contact
shadows under both wheels and the bench feet, plus broader soft floor shadows.
Their percentage coordinates follow the transparent image and scale with it
(wheels at 88.8% height, front bench feet at 94.6%). The piano's right/down drop
shadow follows the left-side daylight. Simplifying the piano must not remove
these contact/cast shadows or make it appear to float above the room.
The decorative room has `pointer-events: none` so it does not intercept links
or scrolling underneath. It is never compressed to fit the viewport.
Its lower portion and the shared footer
continue down the page. The floor meets the rear cabinet at 82% of the image
height, excluding the room's bottom padding; the 7px skirting sits above that line.
This keeps the piano's front feet and bench on the floor at every screen size.
Header and footer continue to use their shared Bootstrap
components, with the icon alone at left and YouTube/account controls at right.

Versioned artwork lives under `resources/images/home/editorial/`. Card WebPs are
720 × 960; the piano is a transparent 1402 × 1122 WebP. See
`docs/HOMEPAGE_ARTWORK.md` for source references and the exact built-in imagegen
prompts. The older SVGs under `resources/images/home/projects/` and
`scripts/build-home-project-mockups.js` remain for existing hub illustrations;
the homepage no longer consumes them.

### Decorative homepage piano

Arthur removed the homepage score and playing feature on October 22. The scene
is now only the original piano image, with an empty score rack and no interactive
controls. The homepage no longer queries the Free Pick or loads score PDFs,
audio sources, player scripts or player styles. The dedicated `home-piano.js`,
score partial and SCSS have been removed. Free Pick functionality and media
access in the web app and mobile clients are unchanged. Cards render above the
whole decorative piano/plant scene, which has `pointer-events: none`.

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

`components.editorial-layout` provides the Bootstrap content/sidebar columns,
stacking the sidebar after the content below the desktop breakpoint. Reuse it
as other public directories and editorial pages are redesigned.
Hub detail content sits directly on the page surface and uses the available
content column, without an enclosing white card, shadow or extra inset padding.
Keep cards for individual stories/resources, promotions and purposeful controls.
`components.display.ads` accepts `variant: cards` to render the existing latest
published crash course, eBook and eScore through `components.promo-card`, in the
requested order. Legacy banners on other pages retain their existing layout.
The shared promotion card uses `.pastel-card`, its existing soft action button,
color/radius/type tokens and the original course/shop destinations and media.
Its specimen lives in Settings → Design System → Components. Shared layout/card
styles live in `components/_editorial-layout.scss`; blog-specific typography and
story grid styles remain in `pages/_blog.scss`.

Public article pages use the same subtle surface, fluid headline, shared
`components.editorial-layout` and pastel promotion cards as the directory. The
article has no outer card or inset padding; topic links render shared `@pill`
badges above its heading. The date and reading
time remain visible, with a simple text byline. `.blog-article #blog-content`
fills the content column with the shared blog font token, paragraph and
heading rhythm, and responsive YouTube/Vimeo framing. These rules are scoped to
the public article; mobile embeds retain their existing typography and media.
Stored article HTML, cover credits and references are preserved.

`components.newsletter.subscribe-button` supplies the shared primary mail-icon
opener used in the Blog and Learn introductions and newsletter invitations.
Its modal URL stays on the request host; `label` changes the caption and
`preview: true` makes a Design System sample inert.

`components.newsletter.invitation` provides the reusable pastel newsletter panel
with the standard primary button and existing same-host subscription modal.
Pass a unique `headingId`; `preview: true` renders an inert Design System sample.
The article explicitly passes `preview: false` so editorial previews also retain
the subscription action. Related stories reuse `blog.components.story-card` in
the directory's responsive grid, without Masonry or JavaScript clamping.


## Learn hub landing page

`learn.home` renders `learn.index`, replacing its placeholder with a directory
of six projects grouped by purpose: Interactive tools (Circle of fifths and
Chord finder), Courses & guides (Crash courses and Infographics), and Quizzes &
pianists. Anchor links at the top jump to each group without hiding content or
requiring JavaScript. Each project has a clear description, format label and
action. Scales and arpeggios stay in a smaller More practice tools row.

Reuse `components.resource-card` for project/resource navigation cards. It uses
`.card.rounded.shadow-light`, `.card-lift`, a pastel artwork panel and the shared
color/type/radius tokens; the whole card is one named link and titles keep their
normal color on hover. Pass a unique `id`, `title`, `description`, `format`,
`action`, `url`, `color`, and an `artwork` slot. `preview: true` marks the Design
System sample, which renders this same component and circle artwork. The common
styles in `components/_resource-card.scss` are imported by app and admin.

`learn/components/artwork.blade.php` supplies small decorative, editable SVG
previews of each project. These use the card's palette and the existing PianoLIT
illustration style. They contain no screenshots, invented content totals or
external image requests. Learn retains the familiar public header/footer,
breadcrumb, `components.editorial-layout` and pastel course/shop promotions.
The introduction uses Blog's Subscribe action in place of its decorative image,
with the same Bootstrap alignment and mobile stacking. Both that action and its
newsletter invitation subscribe to PianoLIT-wide news on the Learn host.
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

`components.piano.keyboard` supports an opt-in `responsive: true` layout with
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

`metronome.index` retains Arthur’s original full-screen canvas and animated native dialog sheet. Only this page loads `css/metronome.css` and `js/metronome.js`. The canvas defaults to `--color-primary`, with the matching `#0055fe` reset value in the local preference module. Its tempo numeral retains the original thin system face; the settings inherit PianoLIT body/heading typography and standard sizes from `app.css`. Reuse `components.segmented-control`, `@select`, `@toggle`, `.btn-close`, `.btn.btn-secondary` and `.btn.btn-primary`, including their existing shadows. `components.form.color` supplies the native `.form-control.form-control-color` control, also shown in Settings → Design System. Local settings CSS owns only the sheet geometry, scrolling and animation.
