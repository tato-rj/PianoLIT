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
