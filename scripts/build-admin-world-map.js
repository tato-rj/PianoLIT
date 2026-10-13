// Generate local SVG geometry; D3 is a pinned build utility, not a runtime dependency.
const fs = require('fs');
const path = require('path');
const vm = require('vm');
const root = path.resolve(__dirname, '..');
const context = vm.createContext({});
for (const library of ['d3-array-3.2.4.min.js', 'd3-geo-3.1.1.min.js']) {
    vm.runInContext(fs.readFileSync(path.join(__dirname, 'vendor', library), 'utf8'), context);
}
const d3 = context.d3;
const source = JSON.parse(fs.readFileSync(path.join(root, 'resources/data/composer-globe.geojson'), 'utf8'));
const features = source.features.filter(feature => feature.properties.code !== 'AQ');
// Normalize winding for D3's spherical polygon convention before projection.
for (const feature of features) {
    if (d3.geoArea(feature) > 2 * Math.PI) {
        const polygons = feature.geometry.type === 'Polygon' ? [feature.geometry.coordinates] : feature.geometry.coordinates;
        polygons.forEach(polygon => polygon.forEach(ring => ring.reverse()));
    }
}
const projection = d3.geoNaturalEarth1().fitExtent([[8, 8], [952, 472]], {type: 'FeatureCollection', features});

function distanceSquared(point, start, end) {
    let x = start[0], y = start[1];
    const dx = end[0] - x, dy = end[1] - y;
    if (dx || dy) {
        const t = ((point[0] - x) * dx + (point[1] - y) * dy) / (dx * dx + dy * dy);
        if (t > 1) { x = end[0]; y = end[1]; }
        else if (t > 0) { x += dx * t; y += dy * t; }
    }
    return (point[0] - x) ** 2 + (point[1] - y) ** 2;
}

function simplify(points) {
    const keep = new Set([0, points.length - 1]);
    const stack = [[0, points.length - 1]];
    while (stack.length) {
        const [start, end] = stack.pop();
        let farthest = -1, distance = .35 ** 2;
        for (let i = start + 1; i < end; i++) {
            const next = distanceSquared(points[i], points[start], points[end]);
            if (next > distance) { farthest = i; distance = next; }
        }
        if (farthest !== -1) {
            keep.add(farthest);
            stack.push([start, farthest], [farthest, end]);
        }
    }
    const result = points.filter((_, index) => keep.has(index));
    return result.length >= 3 ? result : points;
}

const countries = features.map(feature => {
    const rings = [];
    const recorder = {
        moveTo(x, y) { rings.push([[x, y]]); },
        lineTo(x, y) { rings[rings.length - 1].push([x, y]); },
        closePath() {}
    };
    d3.geoPath(projection).context(recorder)(feature);
    const shape = rings.map(ring => {
        const points = simplify(ring);
        return 'M' + points.map(point => point.map(value => +value.toFixed(1)).join(',')).join('L') + 'Z';
    }).join('');
    const center = projection([feature.properties.lng, feature.properties.lat]).map(value => +value.toFixed(1));
    if (!shape || !center.every(Number.isFinite)) throw new Error('Invalid geometry: ' + feature.properties.code);
    return {code: feature.properties.code, name: feature.properties.name, path: shape, center};
}).sort((a, b) => a.code.localeCompare(b.code));
const output = path.join(root, 'resources/data/admin-world-map.json');
fs.writeFileSync(output, JSON.stringify({width: 960, height: 480, countries}) + '\n');
console.log('Generated ' + countries.length + ' country paths (' + fs.statSync(output).size + ' bytes).');
