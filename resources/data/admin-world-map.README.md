# Admin world map

The map is generated from the existing composer-globe.geojson country geometry.
See composer-globe.README.md for the Natural Earth v5.1.2 source, public-domain
terms, country-code conventions and limitations of the generalized boundaries.

Regenerate with:

    node scripts/build-admin-world-map.js

The generator uses D3's Natural Earth projection, with antimeridian clipping,
a 960 × 480 viewport, 0.35-pixel Douglas–Peucker simplification and one decimal
place in the projected SVG coordinates. It retains all 239 non-Antarctic country
features, including the largest outlines of small island countries. Account
locations whose country has no separate shape remain available in the ranking,
selector and complete counts table; they are never reassigned to a neighboring
country. Shapes are a geographic overview, not a statement about boundaries.

Pinned build utilities in scripts/vendor/:

- d3-geo 3.1.1, https://registry.npmjs.org/d3-geo/-/d3-geo-3.1.1.tgz
  (archive SHA-256 fe1f2dd81a87d942790c5a7786f4aab03df2b357d46d8af56b613fb8a1bb9be5).
- d3-array 3.2.4, https://registry.npmjs.org/d3-array/-/d3-array-3.2.4.tgz
  (archive SHA-256 6dbf3f2be39e48f86775be6e166a5d3e5a585ca7144453cd6ad1912f5ae4417f).

Their unchanged distributions and ISC licenses are retained together. Neither
library is loaded by the browser or added to the installed application packages.
The SVG is rendered by Blade using aggregated account counts. The small standalone
admin-user-map.js controller handles selection, tooltips, keyboard access and
bounded zoom/pan without fetching maps, account data or third-party services.
