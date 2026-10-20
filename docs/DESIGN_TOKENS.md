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
export. UI previews use the shared `@brandIcon` component and the actual exports.
The two SVG masters live in `resources/images/brand/`; keep their six evenly
spaced black keys, as explicitly requested by Arthur.

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

## Change standard rounded corners

```scss
--radius: .5rem; // Current default: 2rem.
```

This updates `.rounded`, directional `.rounded-top/bottom/left/right`, the media
player, the shared modal, pricing badges, admin overview corners, buttons, and unstyled form fields.
Images and divs opt into these rules through their existing rounded classes.
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

Date and time displays omit timezone names and explanatory timezone captions.
Use the configured application timezone for formatting and calculations.

Use `@input` (`components.form.input`) for standard text/email fields, including
labels and validation feedback. Supply `id` to associate its label, `autocomplete`
for browser completion and `describedBy` for help text. The component preserves
scalar old input and safely ignores malformed array values. Avoid field-specific
colors, padding, corners and focus outlines; table wrappers must respect the
shared form-control focus style.

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

`components.tab-navigation` is a Blade `@component` with its `.tab-content` and panes in the default slot. It provides accessible Bootstrap tab triggers for an `items` array of `target`/`label` pairs, with a unique `id` and accessible `label`. The `segmented` variant is the shared compact account navigation in `components/_tabs.scss`; it uses a compact row on phones and two columns below 360px. Panels use the target as their ID and `aria-labelledby="{id}-{target}"`. Summary shortcuts activate the matching real tab trigger so keyboard focus and lazy loading remain consistent. It encloses the menu and panes in one frame and embeds its small shared controller once, selecting the URL fragment synchronously before revealing that frame. This avoids a flash of the default tab or a Bootstrap fade while the main bundle loads. With JavaScript disabled, the server default remains visible; an interrupted response reveals that default at DOM ready. Keep the panels inside the component slot, rather than after an `@include`. The navigation automatically marks each instance with `data-tab-navigation`. The shared `components/tab-navigation.js` controller, loaded in both app and admin bundles, saves the selected panel as a URL fragment and restores it on refresh or fragment navigation. It uses `history.replaceState` to preserve query strings and existing Back-button state without adding history entries; ordinary page anchors and unrelated tab implementations keep their own behavior. Both components are demonstrated under Settings → Design System → Components.

## Saved payment cards and secure fields

`components.payment-card-preview` is shared by checkout, Billing and the Design System. Its default mode renders the live card preview. Pass `saved: ['brand' => ..., 'last4' => ...]` for the current membership card and its check mark, and optionally `selectable: true` for a checkout selection button, or `add: true` for the monochromatic Add new card button. Add tiles take either `controlsId` for an inline disclosure (checkout), or `modalId` for a Bootstrap modal trigger with the appropriate dialog semantics (Billing and the Design System). The artwork, brand colors, check mark and add-card styling live in `components/_payment-card.scss`, imported by app and admin. Only the currently stored membership method is shown; the card editor explicitly describes replacement after verification.

`components.payment-card-fields` supplies the three Stripe-hosted mounts and native cardholder/postal fields. Pass a unique `prefix` and the initial `cardholder` value. Native fields use `@input` and shared labels; field layout lives in `components/_payment-card-fields.scss`. Checkout and Billing use this same component. Billing's editor uses `components.modal` at body level; its `data-deferred-card` form mounts after the modal is shown, focuses the card number once ready without overriding another user-selected field, and clears its draft after any dismissal while reusing the hosted fields on reopen. The dialog blocks dismissal during verification and temporarily releases its focus trap for Stripe's bank challenge, restoring it on failure. The existing verified SetupIntent flow remains responsible for saving the card.


## Admin breadcrumbs

Every admin page header renders `admin.components.breadcrumb` above its heading.
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
