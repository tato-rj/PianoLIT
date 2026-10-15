# Editing PianoLIT styles

The admin **Settings → Design System** page (`admin.design-system.index`) shows
the shared typography, colors, spacing, corners, shadows, buttons, fields,
cards, feedback, tables and icons. Its labels read computed browser styles,
including responsive field sizes and interaction states. It uses fictional
content and has no sample submission or account writes.

## Where to change a style

| Concern | Source |
| --- | --- |
| Shared values: fonts, colors, spacing, corners, field dimensions and shadows | `resources/sass/_design-tokens.scss` |
| Final typography and control size adapters | `resources/sass/foundation/_typography.scss` |
| Final color, border, shadow and spacing utilities | `resources/sass/foundation/_utilities.scss` |
| Shared link, outline button and pagination states | `resources/sass/foundation/_interactions.scss` |
| Button variants and editorial exceptions | `resources/sass/brand/` |
| Form finish and states | `resources/sass/components/_form-fields.scss` |
| Reusable component layouts | `resources/sass/components/` |
| Shared admin sidebar, headings and account menu, including phone layouts | `resources/sass/admin/_shell.scss` |
| Floating admin back-to-top button | `resources/sass/admin/_back-to-top.scss` |
| Overview-specific charts, metrics and rankings | `resources/sass/admin/_dashboard.scss` |
| Shared admin table | `resources/sass/admin/_tables.scss` |
| Older admin template compatibility rules | `resources/sass/admin/_legacy.scss` |
| Public-only framework overrides | `resources/sass/app/_overrides.scss` |
| Page layouts | `resources/sass/views/` and the named files in `resources/sass/admin/` |
| Design System gallery layout and token sample bindings | `resources/sass/admin/_design-system.scss` and `_design-system-specimens.scss` |

`_variables.scss` remains a compatibility import for the shared tokens.
Keep public-only and admin-only component differences in their respective
sources. Gallery measurements represent the admin bundle; the public feature
card is explicitly imported from its existing component source inside the
gallery scope.

## Cascade order

`app.scss` and `admin.scss` load their framework, compatibility, component,
brand and page rules. Some later feature components intentionally retain their
existing cascade positions. `webpack.mix.js` then appends Primer followed by
`design-system.scss`, the final shared foundation entry point. That entry point
emits tokens, typography, utilities, interactions and the shared icon/form/menu
adapters in a deliberate order.

Do not alphabetize imports or move final adapters earlier: equal-specificity
rules and legacy Sass `@extend` consumers depend on this order. In particular,
the older shadow definitions in `_bootstrap-custom.scss` also supply the media
player's extended styles; they are not interchangeable with the final shadow
utility overrides.

## Readable source

- Write one declaration per line, with four-space indentation and a blank line
  between logical groups. Add comments that explain a group's purpose or an
  ordering constraint.
- Keep component presentation in SCSS rather than adding inline styles to Blade.
  Token sample bindings in the gallery use data attributes and Sass loops.
- Change a shared value in the token file before adding another literal override.
- Keep vendor and generated Primer files separate from project-owned SCSS.
  The source formatting settings are in `resources/sass/.prettierrc.json`.
- Existing Prettier can format a changed source with
  `node node_modules/prettier/bin-prettier.js --write resources/sass/path.scss`.

Build with `npm run production`, inspect the affected pages at desktop and phone
widths, run the CSS/JavaScript regression checks, and include relevant tracked
outputs and `public/mix-manifest.json`. Generated public CSS is minified for
delivery; the editable SCSS remains expanded. Follow `AGENTS.md` for isolated
builds and application checks.
