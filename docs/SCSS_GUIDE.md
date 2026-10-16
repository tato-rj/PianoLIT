# Editing PianoLIT styles

Start in `resources/sass/`. It contains three build entry points and five folders:

```text
resources/sass/
├── app.scss       Website and my.* web app
├── admin.scss     Administration
├── shared.scss    Shared utility and component layer, appended to both bundles
├── shared/        Tokens, base styles, typography, buttons, colors and utilities
├── components/    Reusable controls and content components
├── pages/         Website and web app page layouts
├── admin/         Admin shell, tables and admin page layouts
└── vendor/        External styles kept separate from editable project styles
```

The admin **Settings → Design System** page (`admin.design-system.index`) previews
the real typography, palettes, dimensions, corners, shadows, controls and tables.
Measurements follow the browser's actual styles. Its sample content is fictional.

## Where to change a style

| Concern | Source under `resources/sass/` |
| --- | --- |
| Shared fonts, colors, spacing, corners, field dimensions and shadows | `shared/_tokens.scss` |
| Typography and component size adapters | `shared/_typography.scss` |
| Primary, secondary and outline button states | `shared/_buttons.scss` |
| Existing configurable palette button themes | `shared/_palette-buttons.scss` |
| Brand and repertoire level colors | `shared/_colors.scss` |
| Palette shades and light borders | `shared/_palette.scss` |
| Text/background/border/shadow utilities | `shared/_utilities.scss` |
| Additional spacing steps 6–8 | `shared/_large-spacing.scss` |
| Existing cross-page breakpoint compatibility | `shared/_responsive.scss` |
| Positioning, flex alignment, lists and heavier borders | `shared/_layout-utilities.scss` |
| Hover visibility, opacity, overlays and gallery scrollbars | `shared/_effects.scss` |
| Form fields, focus and validation states | `components/_form-fields.scss` |
| Palette alerts and status labels | `components/_palette-alerts.scss` |
| Input icons and special field arrangements | `components/_input-decorations.scss` |
| Drag handles and filter-column layouts | `components/_drag-handles.scss`, `components/_filter-columns.scss` |
| Public page-specific rules | The named file in `pages/` |
| Public-only framework overrides | `pages/_public-overrides.scss` |
| Shared admin sidebar, headings and account menu | `admin/_shell.scss` |
| Floating admin back-to-top button | `admin/_back-to-top.scss` |
| Admin Overview charts, metrics and rankings | `admin/_dashboard.scss` |
| Shared admin table | `admin/_tables.scss` |
| Admin editor tools, uploads and event/video editors | `admin/_text-improver.scss`, `_uploads.scss`, `_timeline-events.scss`, `_video-moments.scss` |
| Older admin template compatibility | `admin/_template-compat.scss` |
| Design System gallery and sample bindings | `admin/_design-system.scss`, `admin/_design-system-specimens.scss` |

## Source and generated files

Edit SCSS sources. `public/css/app.css` and `public/css/admin.css` are generated
browser bundles; `public/css/shared.css` is their generated shared build layer.
There is no extra shared stylesheet request in page templates.

`public/css/vendor/` contains externally maintained flag styles and their relative
SVG assets. Their internal layout stays together so the library's URLs continue
to work. Web fonts emitted by Webpack live under `public/fonts/vendor/`.

## Cascade order

Each main entry point imports framework, compatibility, component and page rules.
`webpack.mix.js` appends the separately compiled `shared.scss` layer afterwards.
It contains the required utility aliases, shared tokens, typography and final
control adapters. The admin bundle also includes its vendor theme/table/upload
styles before its own Sass output.

Keep this order. Some later feature components intentionally retain their existing
cascade positions. Shared compilation remains separate because Sass `@extend`
consumers, including the media player, must not inherit additional final utility
rules. Changing folder names does not require changing public CSS class names.

## Readable source

- Use one declaration per line, four-space indentation, blank lines between groups
  and comments explaining purpose or ordering constraints.
- Put reusable UI in `components/`, public page layouts in `pages/`, admin-only
  rules in `admin/`, and defaults/utilities in `shared/`.
- Change shared values in `shared/_tokens.scss` before adding another literal.
- Keep presentation in SCSS rather than adding inline styles to Blade.
- Keep external vendor files separate. Source formatting settings are in
  `.prettierrc.json` beside the entry points.
- Format a changed source with
  `node node_modules/prettier/bin-prettier.js --write resources/sass/path.scss`.

Run `npm run production`, check affected desktop/phone views and the CSS/JavaScript
regressions, and ship the relevant tracked bundles with `public/mix-manifest.json`.
Generated delivery CSS is minified; editable SCSS remains expanded. See `AGENTS.md`
for isolated builds and application checks.
