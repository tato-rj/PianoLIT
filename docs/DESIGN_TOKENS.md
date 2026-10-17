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

## Change standard rounded corners

```scss
--radius: .5rem; // Current default: 2rem.
```

This updates `.rounded`, directional `.rounded-top/bottom/left/right`, the media
player, the shared modal, pricing badges, admin overview corners, buttons, and unstyled form fields.
Images and divs opt into these rules through their existing rounded classes.
`--radius-button` and `--radius-input` have independent .75rem defaults;
`--radius-modal` inherits `--radius`.

`--radius-sm` controls `.rounded-sm` and its directional variants. Existing pill/circle and
square utility precedence is retained. Custom score tools, Match cards, decorative
shapes, email templates and standalone policy documents keep their own layouts.

## Form controls

Use `@input` (`components.form.input`) for standard text/email fields, including
labels and validation feedback. Supply `id` to associate its label, `autocomplete`
for browser completion and `describedBy` for help text. The component preserves
scalar old input and safely ignores malformed array values. Avoid field-specific
colors, padding, corners and focus outlines; table wrappers must respect the
shared form-control focus style.

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


## Standard confirmation dialogs

Use `components.modal` for ordinary dialogs and confirmations. Its `header`, `body` and `footer` slots retain shared typography, button classes and `--radius-modal`. Pass `centered: true` for a centered confirmation and `descriptionId` for the ID of its explanatory text. The component supplies a focusable dialog root, a title association when a header is present, and an accessible Close button. Bootstrap handles the backdrop, Escape, focus trap and return to the opener. Keep destructive actions behind an explicit submit button, with a separate dismiss button. Web app views can push markup into `page-modals` to render it at body level, outside tab panes and other stacking containers. Settings → Design System’s interactive modal uses this same component.


## Focus appearance

By user preference, PianoLIT has no extra outline or focus halo in any state, across the public website, web app and admin. `shared/_focus.scss` is the final reset for native/vendor outlines and Bootstrap’s focus-ring helper. It stays plain CSS so standalone error layouts can embed the same source. `shared/_bootstrap-theme.scss` disables Bootstrap focus shadows at their source, while controls retain their existing border, background, hover, pressed, validation and selected states. Do not add component-specific `outline`/`outline-offset` rules or focus ring box shadows. Focusability, keyboard navigation, tab semantics and modal focus management remain functional; do not remove `tabindex` or blur controls to hide a ring. The Design System uses these same shared styles.

### Access status select

`components.status-select` reuses the admin table's green/warning/red badges as a native select. Its reusable styles are in `components/_status-select.scss`; the shared script in `views/admin-access-limits.js` updates the selected tone and optional helper note. The Design System table includes the same control. Limited options are supplied by the policy's feature definition. Locked account-owned guest cells show Account required inside a muted/light grey disabled pill, with no repeated note beneath it. Its hidden submitted value remains Blocked. The shared component accepts `selectedLabel` and `tone` for this display without adding another access state.
