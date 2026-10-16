# PianoLIT styles

Edit sources here; `public/css/` contains generated output.

- **`shared/`** — start with `_tokens.scss` for fonts, colors, dimensions and shadows.
  Also contains base styles, brand buttons, typography and reusable utilities.
- **`components/`** — reusable UI such as fields, cards, tabs and media controls.
- **`pages/`** — website and web app page layouts.
- **`admin/`** — admin navigation, tables and page layouts.
- **`vendor/`** — external styles, kept separate from project-owned SCSS.

Entry points: `app.scss` for the website/web app, `admin.scss` for administration,
and `shared.scss` for the shared layer appended to both. Keep their import order.

See [the editing guide](../../docs/SCSS_GUIDE.md) for the source map and build details.
