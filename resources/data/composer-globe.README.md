# Composer globe assets

`composer-globe.geojson` is derived from Natural Earth v5.1.2, 1:50m admin-0 countries:
https://github.com/nvkelso/natural-earth-vector/blob/v5.1.2/geojson/ne_50m_admin_0_countries.geojson

Natural Earth is public domain: https://www.naturalearthdata.com/about/terms-of-use/
Only geometry, ISO code, country name, continent and label coordinates are retained;
coordinates are rounded to three decimals, and geometries sharing an ISO code
are combined into one selectable country. In the detailed layer, island polygons
below 0.05 square degrees are omitted unless they are the country's largest polygon;
every selectable country is retained. Coastline coordinates on retained polygons
are otherwise unchanged. Country geometry is stored in `features`. Both zoom
levels reuse these same country meshes, with continent interaction and transparent
borders at a distance. Borders are prepared with the meshes when the globe opens,
so zooming never has to triangulate a new layer. Names and continent assignments use
`country-continents.json` where available, matching the catalogue convention.
The geometry is a generalized exploration map, not a statement about boundaries.

`resources/js/vendor/globe.gl.min.js` is the unchanged UMD distribution from
`globe.gl@2.46.2` (https://registry.npmjs.org/globe.gl/-/globe.gl-2.46.2.tgz).
Its MIT license is retained beside it in `globe.gl.LICENSE`. This prebuilt bundle
includes its rendering dependencies and bypasses the legacy Webpack 3 parser;
it is loaded locally only when the composer modal opens. No CDN is used at runtime.
Mix copies and versions the library, map and controller; their source files are
the authoritative inputs. Update the pinned library as a separate verified change.
