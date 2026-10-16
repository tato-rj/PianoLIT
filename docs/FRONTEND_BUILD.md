# Frontend build on Node 24

Use native Node.js 24 on Apple Silicon. `.nvmrc` selects that major version and `package.json` declares it as the supported build runtime. Verification used macOS arm64, Node 24.22.0, npm 12.2.0 and OpenSSL 3.5.8.

```sh
nvm install
nvm use
npm ci
npm run dev
npm run prod
```

Neither build requires `--openssl-legacy-provider`. Remove an old shell-level workaround before verification. `npm ci` uses the committed version 3 lockfile; avoid deleting it or running a blanket dependency update. Existing application libraries retain their previous locked versions.

`dev`/`development`, `prod`/`production`, `watch`, `watch-poll`, `hot`, `icons`, `test:js` and `test:css` remain available. Development, production, watch and hot builds regenerate the Lucide catalog first. Production runs the CSS regressions and `test:build` afterward. The watch-poll command forwards Webpack 5's `--watch-options-poll=1000` option through the Mix CLI. Restart watchers after introducing new Blade icon names, as before.

## Compatibility choices

- Laravel Mix 6.0.49 provides Webpack 5, Babel 7 and PostCSS 8. The old Webpack CLI arguments and Babel 6 Bootstrap override were removed. Terser can minify the existing Bootstrap bundle directly.
- Webpack is pinned to **5.105.4**. Later releases break Mix 6's progress/output reporters. Keep the pin until those integrations are replaced or upstream compatibility is demonstrated. `xxhash64` avoids reliance on OpenSSL's legacy MD4 provider.
- Dart Sass replaces Node Sass. **Sass 1.77.8 is deliberately pinned**: the existing palette tests pass with its legacy color rounding, whereas 1.105.2 changed `.btn-grey` from `#9aa9b7` to `#9aa8b7`. This is a compatibility constraint, not a recommendation to stay on that compiler indefinitely.
- sass-loader 16 uses Sass's modern API and resolves the existing package imports. Version 12's modern importer is unfinished. `resolve-url-loader` 5 preserves URL rewriting; the four Swiper SVG paths now resolve relative to their actual partial. Their generated public paths stay the same.
- `mergeLonghand: false` remains enabled through Mix 6's `cssNano` option. The CSS regressions require warning-free Sass and check all 437 Bootstrap custom properties in each main bundle and the required layout/palette helpers.

The existing six public entry points are unchanged: `public/js/{app,admin,tone}.js` and `public/css/{app,admin,shared}.css`. Blade still uses the same `mix()` paths. Concatenation preserves the vendor/shared ordering and all image, font, GeoJSON, flag, editor and standalone-script copies.

Mix 6 excludes the destination of `styles()`/`scripts()` from their input list. The app/admin compilation therefore uses ignored `public/.mix/` intermediates before producing the original final filenames. Hot mode writes these intermediates to disk so concatenation also works on a fresh build.

`scripts/version-assets.js` registers a small Mix extension that reuses Mix's explicit-file version tasks and watchers. Unlike Mix 6's global `version()`, it leaves copied design binaries unversioned: hashing the 97 MB carousel PSD as a UTF-8 string caused an invalid-string-length failure. The extension refreshes the 39 selected hashes after final concatenation/minification, while retaining standalone-file version updates during watch. It intentionally depends on Mix 6's version component; recheck this extension during a future Mix upgrade.

## Verification

```sh
npm run test:css
npm run test:build
npm run test:js
git diff --check
```

`test:build` checks literal Blade asset references, all six entry paths, final-content manifest hashes, bundle syntax, exact copied-file contents, and CSS font/image URLs. Production invokes it automatically. It expects a completed build and does not modify assets.

For isolated builds, copy `artisan`, `package.json`, `package-lock.json`, `webpack.mix.js`, `resources/`, `scripts/`, `tests/js/`, `tests/css/` and `tests/build/` into a temporary directory. Run `npm ci` there, or symlink the already verified `node_modules`; Mix 6 uses the working directory without the old Mix 2 root-path preload. Copy resources rather than symlinking them so loader matching and source-map paths remain correct. Keep logs outside the repository. This allows verification without overwriting unrelated changes to tracked public assets.

## Remaining work

The 182 warnings shown after the initial upgrade have been resolved in project SCSS: palette/class identifiers are quoted strings, parent declarations precede nested rules to preserve the existing cascade, and opacity helpers use `math.div()`. All three Sass entries compile without warnings and produce byte-for-byte identical CSS to the pre-cleanup sources. `test:css` now fails on any Sass warning from app, admin or shared. No compiler warnings are suppressed.

The Sass pin still preserves legacy color rounding. Before moving to newer Sass, compare palette and cascade output again; newer compilers also require a separately scoped `@use`/color-function migration.

Mix 6 and several retained application/build packages are deprecated or no longer maintained. This task does not upgrade Vue, Axios, flag icons or the other application libraries. Replacing Mix's reporters or migrating bundlers, and reviewing application dependency advisories, require their own compatibility work.

The general JavaScript regression runner already fails on Node 24 with `ReferenceError: alert is not defined` in its existing mocked-browser tests. A diagnostic run allowing unhandled rejections also reaches the previously documented `tests/js/webapp-search.js:39` text mismatch (`We found 24 results` versus `24 results`). Neither application behavior nor those unrelated expectations were changed here. The build/CSS regressions and browser smoke checks are separate and pass.

Sources: [Sass declaration ordering](https://sass-lang.com/documentation/breaking-changes/mixed-decls/), [Mix 6 upgrade guide](https://laravel-mix.com/docs/6.0/upgrade), [Mix/Webpack output reporter issue](https://github.com/laravel-mix/laravel-mix/issues/3413), [Mix/Webpack progress reporter issue](https://github.com/laravel-mix/laravel-mix/issues/3410).
