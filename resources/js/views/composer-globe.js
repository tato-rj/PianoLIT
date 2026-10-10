(function (root, factory) {
    var api = factory();
    if (typeof module === 'object' && module.exports) module.exports = api;
    else api.initialize(root.document, root);
}(typeof window !== 'undefined' ? window : this, function () {
    'use strict';
    var positions = {
        'Africa': [5, 20], 'Antarctica': [-76, 0], 'Asia': [35, 95], 'Europe': [51, 15],
        'North America': [40, -105], 'Oceania': [-25, 135], 'South America': [-18, -60]
    };
    var colors = {
        'Africa': '#417e8b', 'Antarctica': '#698394', 'Asia': '#577f94', 'Europe': '#699b9c',
        'North America': '#527f95', 'Oceania': '#4c9292', 'South America': '#438c88'
    };
    function normalize(value) {
        return String(value || '').normalize('NFD').replace(/[\u0300-\u036f]/g, '').toLowerCase().replace(/[^a-z0-9]/g, '');
    }
    function nextMode(altitude, countryMode) {
        return countryMode ? altitude < 1.5 : altitude < 1.35;
    }
    function buildModel(map, catalogue) {
        var byCode = {}, byName = {}, countries = [], continents = [], matched = [], borders = [];
        catalogue.countries.forEach(function (country) {
            if (country.code) byCode[country.code.toUpperCase()] = country;
            byName[normalize(country.name)] = country;
        });
        map.features.forEach(function (feature) {
            var p = feature.properties, counts = byCode[p.code] || byName[normalize(p.name)];
            var country = {
                key: 'country:' + p.code, kind: 'Country', code: p.code,
                name: counts ? counts.name : p.name,
                continent: counts && counts.continent ? counts.continent : p.continent,
                lat: p.lat, lng: p.lng, geometry: feature.geometry,
                composers: counts ? counts.composers : 0, pieces: counts ? counts.pieces : 0,
                url: counts ? counts.url : null
            };
            if (counts) matched.push(counts);
            countries.push(country);
            var polygons = feature.geometry.type === 'Polygon' ? [feature.geometry.coordinates] : feature.geometry.coordinates;
            polygons.forEach(function (polygon) {
                polygon.forEach(function (ring) { borders.push({border: true, points: ring}); });
            });
        });
        // Keep recorded countries without geometry reachable through the place picker.
        catalogue.countries.filter(function (country) { return matched.indexOf(country) === -1; }).forEach(function (country) {
            countries.push({key: 'recorded:' + country.id, kind: 'Country', code: country.code,
                name: country.name, continent: country.continent, composers: country.composers,
                pieces: country.pieces, url: country.url});
        });
        countries.sort(function (a, b) { return a.name.localeCompare(b.name); });
        // Coarser coastlines keep the initial world view light; countries retain detail.
        var worldShapes = (map.worldFeatures || map.features).map(function (feature) {
            var p = feature.properties, counts = byCode[p.code] || byName[normalize(p.name)];
            return {geometry: feature.geometry, continent: counts && counts.continent ? counts.continent : p.continent};
        });
        catalogue.continents.forEach(function (counts) {
            var coordinates = [];
            worldShapes.filter(function (country) { return country.continent === counts.name; }).forEach(function (country) {
                coordinates = coordinates.concat(country.geometry.type === 'Polygon' ? [country.geometry.coordinates] : country.geometry.coordinates);
            });
            continents.push({key: 'continent:' + counts.name, kind: 'Continent', name: counts.name,
                continent: counts.name, lat: positions[counts.name][0], lng: positions[counts.name][1],
                composers: counts.composers, pieces: counts.pieces, countries: counts.countries, url: counts.url,
                geometry: {type: 'MultiPolygon', coordinates: coordinates}});
        });
        var world = {key: 'world', kind: 'World', name: 'A world of music',
            composers: catalogue.totals.composers, pieces: catalogue.totals.pieces};
        var places = {world: world};
        countries.concat(continents).forEach(function (place) { places[place.key] = place; });
        return {countries: countries, continents: continents, borders: borders, places: places, world: world, unmapped: catalogue.unmapped};
    }

    function initialize(doc, win) {
        var modal = doc.getElementById('composer-globe-modal');
        if (!modal) return;
        function element(name) { return modal.querySelector('[data-globe-' + name + ']'); }
        var canvas = element('canvas'), stage = canvas.parentNode, picker = element('place');
        var loading = element('loading'), error = element('error'), retry = element('retry');
        var regionList = element('regions'), zoomIn = element('zoom-in'), zoomOut = element('zoom-out');
        var home = element('home'), closer = element('closer');
        var globe = null, model = null, selection = null, hovered = null;
        var countryMode = false, open = false, pending = null, libraryPromise = null;
        var reducedMotion = win.matchMedia && win.matchMedia('(prefers-reduced-motion: reduce)').matches;
        var duration = reducedMotion ? 0 : 650;

        function loadLibrary() {
            if (win.Globe) return Promise.resolve();
            if (libraryPromise) return libraryPromise;
            libraryPromise = new Promise(function (resolve, reject) {
                var script = doc.createElement('script');
                var timer = win.setTimeout(function () { failed(); }, 20000);
                function failed() {
                    win.clearTimeout(timer);
                    script.remove(); libraryPromise = null;
                    reject(new Error('The globe library could not load.'));
                }
                script.src = modal.getAttribute('data-globe-library'); script.async = true;
                script.onload = function () {
                    win.clearTimeout(timer);
                    if (win.Globe) resolve(); else failed();
                };
                script.onerror = failed;
                doc.head.appendChild(script);
            });
            return libraryPromise;
        }
        function fetchJson(url) {
            var controller = win.AbortController ? new win.AbortController() : null;
            var timer;
            var timeout = new Promise(function (resolve, reject) {
                timer = win.setTimeout(function () {
                    if (controller) controller.abort();
                    reject(new Error('The globe request timed out.'));
                }, 20000);
            });
            var request = win.fetch(url, {credentials: 'same-origin', signal: controller ? controller.signal : undefined})
                .then(function (response) {
                    if (!response.ok) throw new Error('The globe data could not load.');
                    return response.json();
                });
            return Promise.race([request, timeout]).then(function (value) {
                win.clearTimeout(timer); return value;
            }, function (reason) { win.clearTimeout(timer); throw reason; });
        }
        function number(value) { return Number(value).toLocaleString(); }
        function controlsEnabled(enabled) {
            [zoomIn, zoomOut, home, closer].forEach(function (button) { button.disabled = !enabled; });
        }
        function updateDetails(place) {
            selection = place;
            picker.value = place.key;
            element('kind').textContent = place.kind === 'World' ? 'OUR LIBRARY, WORLDWIDE' : place.kind === 'Country' ? (place.continent || 'Country') : 'CONTINENT';
            element('title').textContent = place.name;
            element('composers').textContent = number(place.composers);
            element('pieces').textContent = number(place.pieces);
            element('composer-label').textContent = place.composers === 1 ? 'composer' : 'composers';
            element('piece-label').textContent = place.pieces === 1 ? 'piece' : 'pieces';
            element('description').textContent = place.kind === 'World' ? 'Select a continent, then zoom closer to discover its countries.' :
                !place.composers ? 'No composers from here in our library yet. There is still a whole world to explore.' :
                    place.kind === 'Continent' ? 'Discover piano music from ' + place.countries + (place.countries === 1 ? ' country' : ' countries') + ' in our library.' : 'Explore the composers and piano pieces from ' + place.name + '.';
            var browse = element('browse');
            browse.hidden = !place.composers || !place.url;
            if (place.url) browse.href = place.url; else browse.removeAttribute('href');
            closer.hidden = place.kind !== 'Continent';
            renderRegions(place);
            if (globe) refreshColors();
        }
        function renderRegions(place) {
            regionList.textContent = '';
            var items = model.continents;
            element('list-title').textContent = 'Choose a continent';
            if (place.kind === 'Continent') {
                items = model.countries.filter(function (country) { return country.continent === place.name && country.composers; })
                    .sort(function (a, b) { return b.pieces - a.pieces || a.name.localeCompare(b.name); });
                element('list-title').textContent = 'Countries in our library';
            }
            items.forEach(function (item) {
                var button = doc.createElement('button'); button.type = 'button';
                button.className = 'composer-globe-region';
                button.setAttribute('aria-pressed', String(place.key === item.key));
                var name = doc.createElement('span'); name.textContent = item.name;
                var total = doc.createElement('span'); total.textContent = number(item.composers) + (item.composers === 1 ? ' composer' : ' composers');
                button.appendChild(name); button.appendChild(total);
                button.addEventListener('click', function () { choose(item); });
                regionList.appendChild(button);
            });
            if (!items.length) {
                var empty = doc.createElement('p'); empty.className = 'composer-globe-description';
                empty.textContent = 'Try another continent to keep exploring.'; regionList.appendChild(empty);
            }
        }
        function populatePicker() {
            picker.textContent = '';
            function option(place, parent) {
                var node = doc.createElement('option'); node.value = place.key; node.textContent = place.kind === 'World' ? 'The whole world' : place.name;
                parent.appendChild(node);
            }
            option(model.world, picker);
            var group = doc.createElement('optgroup'); group.label = 'Continents';
            model.continents.forEach(function (place) { option(place, group); }); picker.appendChild(group);
            model.continents.forEach(function (continent) {
                var group = doc.createElement('optgroup'); group.label = continent.name;
                model.countries.filter(function (country) { return country.continent === continent.name; }).forEach(function (place) { option(place, group); });
                picker.appendChild(group);
            });
            var other = doc.createElement('optgroup'); other.label = 'Other recorded countries';
            model.countries.filter(function (country) { return !positions[country.continent]; }).forEach(function (place) { option(place, other); });
            if (other.children.length) picker.appendChild(other);
            picker.disabled = false;
            element('unmapped').hidden = !model.unmapped.composers;
            updateDetails(model.world);
        }
        function selected(place, target) {
            return target && (target.key === place.key || (!countryMode && target.kind === 'Continent' && target.name === place.continent));
        }
        function capColor(place) {
            if (selected(place, hovered)) return '#e1bd77';
            if (selected(place, selection)) return '#c8a76a';
            if (countryMode && !place.composers) return '#264b60';
            return colors[place.continent] || '#457b88';
        }
        function refreshColors() {
            globe.polygonCapColor(capColor).polygonAltitude(function (place) {
                return selected(place, hovered) || selected(place, selection) ? 0.007 : 0.005;
            });
        }
        function tooltip(place) {
            var box = doc.createElement('div'); box.className = 'composer-globe-tooltip';
            var title = doc.createElement('strong'); title.textContent = place.name;
            var detail = doc.createElement('span'); detail.textContent = number(place.composers) + (place.composers === 1 ? ' composer · ' : ' composers · ') + number(place.pieces) + (place.pieces === 1 ? ' piece' : ' pieces');
            box.appendChild(title); box.appendChild(detail); return box;
        }
        function hover(place) {
            if (hovered === place) return;
            hovered = place; refreshColors();
        }
        function updateLabels() {
            var labels = model.continents;
            if (countryMode) {
                var pov = globe.pointOfView();
                labels = model.countries.filter(function (country) {
                    if (!country.geometry || !country.composers) return false;
                    var delta = Math.abs(country.lng - pov.lng) % 360;
                    delta = Math.min(delta, 360 - delta);
                    return Math.abs(country.lat - pov.lat) < 32 && delta < 45;
                }).sort(function (a, b) { return b.pieces - a.pieces; }).slice(0, 10);
            }
            // Reuse the same objects so CSS2D labels need not be rebuilt on rotation.
            var key = labels.map(function (place) { return place.key; }).join('|');
            if (key !== updateLabels.key) { updateLabels.key = key; globe.htmlElementsData(labels); }
        }
        function setMode(next) {
            if (countryMode === next) return;
            countryMode = next; hovered = null;
            element('mode').textContent = next ? 'Countries' : 'Continents';
            element('legend').hidden = !next;
            globe.polygonsData(next ? model.countries.filter(function (country) { return country.geometry; }) : model.continents)
                .pathsData(next ? model.borders : []);
            updateLabels(); refreshColors();
        }
        function zoom(factor) {
            if (!globe) return;
            var altitude = Math.max(0.22, Math.min(3.3, globe.pointOfView().altitude * factor));
            globe.pointOfView({altitude: altitude}, duration);
        }
        function choose(place) {
            if (!place) return;
            updateDetails(place);
            if (!globe) return;
            if (place.kind === 'World') { reset(); return; }
            if (typeof place.lat !== 'number') return;
            var altitude = place.kind === 'Continent' ? 1.95 : 0.65;
            globe.pointOfView({lat: place.lat, lng: place.lng, altitude: altitude}, duration);
        }
        function reset() {
            if (model) updateDetails(model.world);
            if (!globe) return;
            hovered = null;
            globe.pointOfView({lat: 23, lng: 15, altitude: 2.05}, duration);
        }
        function resize() {
            if (globe && open) globe.width(stage.clientWidth).height(stage.clientHeight);
        }
        function createGlobe() {
            try {
                globe = new win.Globe(canvas, {animateIn: false, rendererConfig: {antialias: true, alpha: true}})
                    .width(stage.clientWidth).height(stage.clientHeight).backgroundColor('rgba(0,0,0,0)')
                    .showAtmosphere(true).atmosphereColor('#59b3d4').atmosphereAltitude(0.14)
                    .polygonsData(model.continents).polygonCapColor(capColor).polygonSideColor(function () { return '#173c50'; })
                    .polygonAltitude(0.005).polygonCapCurvatureResolution(4).polygonsTransitionDuration(reducedMotion ? 0 : 180)
                    .polygonLabel(tooltip).onPolygonHover(hover).onPolygonClick(function (place) { choose(place); })
                    .pathPoints('points').pathPointLat(function (point) { return point[1]; }).pathPointLng(function (point) { return point[0]; })
                    .pathPointAlt(0.008).pathColor(function () { return '#7fa7bb'; }).pathResolution(4).pathTransitionDuration(0)
                    .pointerEventsFilter(function (object, data) { return !data || !data.border; })
                    .htmlAltitude(0.023).htmlTransitionDuration(0).htmlElement(function (place) {
                        var label = doc.createElement('button'); label.type = 'button';
                        label.className = 'composer-globe-label' + (place.kind === 'Country' ? ' composer-globe-label-country' : '');
                        label.textContent = place.name;
                        label.setAttribute('aria-label', place.name + ', ' + place.composers + ' composers, ' + place.pieces + ' pieces');
                        label.addEventListener('click', function (event) { event.stopPropagation(); choose(place); });
                        return label;
                    }).onZoom(function (pov) { setMode(nextMode(pov.altitude, countryMode)); updateLabels(); });
                var material = globe.globeMaterial();
                material.color.set('#09283f'); material.emissive.set('#061a2b'); material.shininess = 12;
                var controls = globe.controls();
                controls.enablePan = false; controls.minDistance = globe.getGlobeRadius() * 1.22;
                controls.maxDistance = globe.getGlobeRadius() * 4.3;
                controls.zoomSpeed = 0.8; controls.rotateSpeed = 0.65; controls.enableDamping = true;
                globe.renderer().setPixelRatio(Math.min(win.devicePixelRatio || 1, 1.5));
                globe.renderer().domElement.addEventListener('webglcontextlost', function (event) {
                    event.preventDefault(); disposeGlobe(); showRenderError();
                });
                controlsEnabled(true); updateLabels.key = null; reset(); updateLabels();
                if (doc.hidden) globe.pauseAnimation();
            } catch (reason) { disposeGlobe(); showRenderError(); }
        }
        function disposeGlobe() {
            if (globe) {
                globe.pauseAnimation();
                globe._destructor();
                globe = null;
            }
            canvas.textContent = ''; controlsEnabled(false);
        }
        function showRenderError() {
            loading.hidden = true; error.hidden = false;
            element('error-message').textContent = 'The 3D globe is unavailable in this browser. You can still explore every place using the list.';
        }
        function start() {
            error.hidden = true;
            if (globe) { resize(); if (!doc.hidden) globe.resumeAnimation(); return; }
            if (model) { createGlobe(); return; }
            if (pending) return;
            loading.hidden = false;
            pending = Promise.all([loadLibrary(), fetchJson(modal.getAttribute('data-globe-map')), fetchJson(modal.getAttribute('data-globe-catalogue'))])
                .then(function (results) {
                    model = buildModel(results[1], results[2]); populatePicker();
                    loading.hidden = true; pending = null;
                    if (open) createGlobe();
                }).catch(function () {
                    loading.hidden = true; pending = null; error.hidden = false;
                    element('error-message').textContent = 'The globe could not load. Check your connection and try again.';
                });
        }
        controlsEnabled(false);
        modal.addEventListener('shown.bs.modal', function () { open = true; start(); });
        modal.addEventListener('hide.bs.modal', function () { open = false; if (globe) globe.pauseAnimation(); });
        doc.addEventListener('visibilitychange', function () {
            if (globe) { if (doc.hidden || !open) globe.pauseAnimation(); else globe.resumeAnimation(); }
        });
        if (win.ResizeObserver) new win.ResizeObserver(resize).observe(stage);
        else win.addEventListener('resize', resize);
        retry.addEventListener('click', start);
        picker.addEventListener('change', function () { if (model) choose(model.places[picker.value]); });
        zoomIn.addEventListener('click', function () { zoom(0.7); });
        zoomOut.addEventListener('click', function () { zoom(1.4); });
        home.addEventListener('click', reset);
        closer.addEventListener('click', function () {
            if (!globe || !selection) return;
            globe.pointOfView({lat: selection.lat, lng: selection.lng, altitude: 0.8}, duration);
        });
        canvas.addEventListener('keydown', function (event) {
            if (!globe || event.target !== canvas) return;
            var pov = globe.pointOfView(), key = event.key;
            if (key === '+' || key === '=') zoom(0.7);
            else if (key === '-') zoom(1.4);
            else if (key === 'Home') reset();
            else if (['ArrowUp', 'ArrowDown', 'ArrowLeft', 'ArrowRight'].indexOf(key) !== -1) {
                globe.pointOfView({lat: Math.max(-85, Math.min(85, pov.lat + (key === 'ArrowUp' ? 12 : key === 'ArrowDown' ? -12 : 0))),
                    lng: pov.lng + (key === 'ArrowLeft' ? -15 : key === 'ArrowRight' ? 15 : 0)}, duration);
            } else return;
            event.preventDefault();
        });
    }
    return {initialize: initialize, buildModel: buildModel, nextMode: nextMode};
}));
