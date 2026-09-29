# Icons

PianoLIT uses Lucide SVGs for interface icons. The same small, local icon catalog is rendered by PHP in Blade and by JavaScript for dynamically inserted controls. No CDN or icon-font download is needed for Lucide.

## Blade

```blade
@icon('close')                         {{-- Lucide x, with mr-2 by default --}}
@icon('close', ['mr' => 0])             {{-- no right margin --}}
@icon('search', ['color' => 'muted'])
@icon('heart', ['filled' => $is_favorited, 'color' => 'red'])
@icon('music', ['weight' => 'thin', 'size' => 'lg'])
@icon('check', ['name' => 'success', 'if' => false])
```

Names normally match [Lucide's icon names](https://lucide.dev/icons/), such as `x`, `trash-2`, `circle-check`, `maximize`, and `layers`. `close` is a convenience alias for `x`. Compatibility aliases in `resources/icons/aliases.json` translate legacy model values at the view boundary without changing mobile API responses.

Options: `mr` (default 2), `ml`, `color`, `size` (`xs`, `sm`, `lg`, `xl`, `1x`–`10x`), `weight`, `filled`, `classes`, `styles`, `title`, `label`, `name`, `if`, and `attributes` (data/ARIA attributes, ID, role, tabindex). A false `if` hides the wrapper rather than removing it, preserving show/hide controls. Icons inherit the surrounding text color and size. Decorative icons are hidden from assistive technology; use a label on an icon-only button/link. `label` or `title` on an icon gives it an accessible name.

Within PHP expressions or component strings, use `\App\Support\Icon::render('search', ['mr' => 1])`, concatenated with any label. Do not embed a Blade directive inside a PHP string.

The PianoLIT logo helper is now `@brandIcon` / `@brandIcon(['size' => '80px'])`. It is distinct from interface icons.

## Change stroke width everywhere

Edit `resources/sass/components/_icons.scss`:

```scss
:root {
    --icon-stroke-width: 2;
}
```

Use `1` for thin, `1.5` for light, `2` for regular, or `2.5` for bold, then run `npm run production`. This file is included in the shared design-system CSS for both public/webapp and admin pages. The variable can also be overridden on a section or in a later stylesheet. Explicit `weight` options override the inherited default.

Stroke width changes line thickness; icon dimensions continue to follow font size. `filled` is separate from weight and is used for selected favorites and ratings.

## JavaScript and changing icons

Blade renders SVG immediately. For dynamic HTML, use a wrapper with a Lucide class:

```html
<i class="app-icon icon-circle-play mr-2" aria-hidden="true"></i>
```

The app/admin bundles populate added wrappers automatically. Change `icon-circle-play` to `icon-circle-stop` to swap an icon; the observer updates only its SVG, preserving wrapper events and attributes. Toggle `icon-filled` for favorites/ratings. `window.PianoIcons.refresh(container)` is available for explicit refreshes. Raw wrappers use exactly the margin classes you specify; the `mr-2` default belongs to the Blade/PHP helper.

## Add an icon or update Lucide

1. Use its Lucide name in a view, for example `@icon('sparkles')` or `@button(['icon' => 'sparkles'])`. Literal names in Blade directives, component icon options, and `Icon::render('…')` calls in views are discovered automatically. For names selected dynamically from variables, model values, or JavaScript, add the canonical names to `resources/icons/names.json`.
2. Run `npm run production` (or `npm run development`). The build first regenerates `resources/icons/lucide.json` and its license from the pinned `lucide-static` package, then bundles the same catalog for the browser. `npm run icons` regenerates just the catalog; rebuild JavaScript too so the browser recognizes the new icons.
3. Include generated assets, the catalog, and Mix manifest in deployment. Restart `npm run watch` after introducing a new icon name so its catalog is regenerated.

Only the selected catalog is bundled, rather than Lucide's entire library. Unknown names render `circle-help`; the regression test checks literal Blade names and aliases against the catalog. Add aliases to `resources/icons/aliases.json` if a shorter application name is useful, and include the target in `names.json`.

## Font Awesome compatibility

Font Awesome's dependency and legacy `@fa` component remain installed. App/admin Sass compiles only its brands stylesheet and base brand styling; Webpack emits only the WOFF2/TTF brands fonts. Solid, regular, compatibility fonts, the all-icons CSS, and the redundant webfonts copy have been removed. Use `@icon` for interface icons; `@fa` is retained as a legacy component, with no active view callers. [Lucide does not include brand logos](https://lucide.dev/brand-logo-statement), so `@icon('brand-apple')`, `@icon('brand-youtube')`, etc. intentionally use the existing Font Awesome brand glyphs. Lucide stroke-width settings do not affect those logos.

Legacy icon strings/HTML in shared models and mobile API responses are retained for mobile compatibility. Browser views resolve those names, and the browser adapter converts recognized legacy non-brand HTML into Lucide SVGs. Third-party vendor code and stored rich-text content are not rewritten.

Deploy the PHP helper/provider, views, catalog, and assets together. Clear compiled Blade views during deployment (`php artisan view:clear`) so cached templates pick up the renamed logo helper and new directive.
