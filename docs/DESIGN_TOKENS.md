# Shared design settings

Edit `resources/sass/_design-tokens.scss`, then run `npm run production`.
It contains the existing Sass palette and the CSS custom properties used by the
public website, web app and admin. Commit the rebuilt CSS and Mix manifest together.

## Change standard rounded corners

```scss
--radius: .5rem; // Current default: 1rem.
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
TinyMCE and Dropzone containers and hosted Stripe card-field containers use the
same border treatment. Third-party iframe contents are still managed by their
respective editors/payment libraries; field styling does not change their behavior.

## Colors

Change `$brand-primary` at the top of the file to rebuild the primary color and its
hover, active, disabled and soft variants together. Links, primary buttons,
primary text/background/border utilities, accents and pagination use these tokens.
Bootstrap primary outlines and borders now use the same PianoLIT blue.

The existing Primer palette (grey, red, orange, yellow, green, teal, blue, indigo,
purple and pink) remains distinct from Bootstrap status colors (success, danger,
warning, info and secondary). Their base text/background/border utilities use
`--color-*` tokens. Shade utilities, palette buttons, alerts and feature-specific
colors still use their existing styles; further consolidation can happen when
those components are redesigned. The palette's `blue` default is its existing
rendered `#3490dc`; PianoLIT's brand blue is `#0055fe`.

Use `--color-text`, `--color-text-muted`, `--color-surface`,
`--color-surface-subtle` and `--color-border` for new shared styles.

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

`design-system.scss` compiles a shared foundation that Mix appends after Bootstrap
and Primer in both `app.css` and `admin.css`. This order prevents literal utility
styles from overriding the shared variables. `_variables.scss` remains a compatible
Sass import; Bootstrap's original import order is preserved. There is no additional
stylesheet request in page templates.
