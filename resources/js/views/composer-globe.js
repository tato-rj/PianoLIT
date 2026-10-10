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
        var byCode = {}, byName = {}, countries = [], continents = [], matched = [];
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
                url: counts ? counts.url : null, portraitsUrl: counts ? counts.portraits_url : null
            };
            if (counts) matched.push(counts);
            countries.push(country);
        });
        // Keep recorded countries without geometry reachable through the place picker.
        catalogue.countries.filter(function (country) { return matched.indexOf(country) === -1; }).forEach(function (country) {
            countries.push({key: 'recorded:' + country.id, kind: 'Country', code: country.code,
                name: country.name, continent: country.continent, composers: country.composers,
                pieces: country.pieces, url: country.url});
        });
        countries.sort(function (a, b) { return a.name.localeCompare(b.name); });
        catalogue.continents.forEach(function (counts) {
            continents.push({key: 'continent:' + counts.name, kind: 'Continent', name: counts.name,
                continent: counts.name, lat: positions[counts.name][0], lng: positions[counts.name][1],
                composers: counts.composers, pieces: counts.pieces, countries: counts.countries, url: counts.url});
        });
        var world = {key: 'world', kind: 'World', name: 'A world of music',
            composers: catalogue.totals.composers, pieces: catalogue.totals.pieces};
        var places = {world: world};
        countries.concat(continents).forEach(function (place) { places[place.key] = place; });
        return {countries: countries, continents: continents, places: places, world: world, unmapped: catalogue.unmapped};
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
        var hoveredCountry = null, tooltipCountry = null, tooltipTitle = null, tooltipDetail = null;
        var portraitCache = {}, renderPortraitPage = null, hoveredPortrait = null;
        var stopWaitingForRender = null;
        var countryMode = false, open = false, pending = null, libraryPromise = null;
        var reducedMotion = win.matchMedia && win.matchMedia('(prefers-reduced-motion: reduce)').matches;
        var duration = reducedMotion ? 0 : 650;
        var initialPlace = null;
        try {
            var initialUrl = new URL(win.location.href);
            if (initialUrl.searchParams.get('modal') === 'composer-globe-modal') initialPlace = initialUrl.searchParams.get('globe-place');
        } catch (reason) { /* URL state is optional when history is unavailable. */ }

        function rememberPlace() {
            try {
                var url = new URL(win.location.href);
                if (open && selection && selection.kind !== 'World') url.searchParams.set('globe-place', selection.key);
                else url.searchParams.delete('globe-place');
                if (url.href !== win.location.href) win.history.replaceState(win.history.state, '', url.href);
            } catch (reason) { /* Exploration still works without URL updates. */ }
        }

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
            if (!selection || selection.key !== place.key) renderPortraitPage = null;
            selection = place;
            if (open) rememberPlace();
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
            loadPortraits(place);
            updatePortraitStatus();
            if (globe) { refreshColors(); updateLabels(); }
        }
        function loadPortraits(place, retrying) {
            if (place.kind !== 'Country' || !place.composers || !place.portraitsUrl || typeof place.lat !== 'number') return;
            if (portraitCache[place.key] && !retrying) return;
            var entry = portraitCache[place.key] = {status: 'loading', page: 0};
            function refresh() {
                // A late response can be cached, but only the current selection may
                // change the visible cards or status (including after modal closure).
                if (open && selection && selection.key === place.key) {
                    updatePortraitStatus();
                    if (globe) updateLabels();
                }
            }
            fetchJson(place.portraitsUrl).then(function (data) {
                if (!Array.isArray(data.composers)) throw new Error('Invalid portrait data.');
                entry.status = 'ready'; entry.composers = data.composers;
                entry.marker = {key: 'portraits:' + place.key, kind: 'Portraits', countryKey: place.key,
                    name: place.name, lat: place.lat, lng: place.lng};
                refresh();
            }).catch(function () { entry.status = 'error'; refresh(); });
        }
        function updatePortraitStatus() {
            var entry = selection && selection.kind === 'Country' ? portraitCache[selection.key] : null;
            var failed = entry && entry.status === 'error';
            element('portrait-status').hidden = !entry || entry.status === 'ready';
            element('portrait-message').textContent = failed ? 'Composer portraits could not load.' : 'Loading composer portraits…';
            element('portrait-retry').hidden = !failed;
        }
        function setPortraitHover(group) {
            if (hoveredPortrait === group) return;
            hoveredPortrait = group;
            if (globe) {
                // HTML overlays sit above the canvas, but globe.gl raycasts beneath
                // them independently. Disabling tracking also clears its open tooltip.
                globe.enablePointerInteraction(!group);
                refreshColors();
            }
        }
        function portraitElement(marker) {
            var entry = portraitCache[marker.countryKey];
            var group = doc.createElement('div'); group.className = 'composer-globe-portraits';
            group.setAttribute('role', 'group'); group.setAttribute('aria-label', 'Composers from ' + marker.name);
            var row = doc.createElement('div'); row.className = 'composer-globe-portrait-row';
            var navigation = doc.createElement('div'); navigation.className = 'composer-globe-portrait-nav';
            var previous = doc.createElement('button'), next = doc.createElement('button'), range = doc.createElement('span');
            previous.type = next.type = 'button'; previous.textContent = '‹'; next.textContent = '›';
            previous.setAttribute('aria-label', 'Previous composers from ' + marker.name);
            next.setAttribute('aria-label', 'Next composers from ' + marker.name);
            range.setAttribute('aria-live', 'polite'); range.setAttribute('aria-atomic', 'true');
            navigation.appendChild(previous); navigation.appendChild(range); navigation.appendChild(next);
            var pin = doc.createElement('span'); pin.className = 'composer-globe-portrait-pin'; pin.textContent = marker.name;
            var close = doc.createElement('button'); close.type = 'button'; close.className = 'composer-globe-portrait-close';
            close.textContent = 'Close'; close.setAttribute('aria-label', 'Close ' + marker.name + ' selection');
            close.addEventListener('click', function (event) {
                event.stopPropagation();
                hovered = null; hoveredCountry = null;
                updateDetails(model.world);
                canvas.focus({preventScroll: true});
            });
            pin.appendChild(close);
            group.appendChild(row); group.appendChild(navigation); group.appendChild(pin);
            group.addEventListener('pointerenter', function () { setPortraitHover(group); });
            ['pointerleave', 'pointercancel'].forEach(function (type) {
                group.addEventListener(type, function () { if (hoveredPortrait === group) setPortraitHover(null); });
            });
            ['pointerdown', 'mousedown', 'touchstart', 'click', 'dblclick'].forEach(function (type) {
                group.addEventListener(type, function (event) { event.stopPropagation(); });
            });
            var lastSize = 0, lastPage = -1;
            function render() {
                var size = stage.clientWidth < 520 ? 2 : 3;
                var pages = Math.ceil(entry.composers.length / size);
                entry.page = Math.max(0, Math.min(entry.page, pages - 1));
                if (lastSize === size && lastPage === entry.page) return;
                lastSize = size; lastPage = entry.page;
                var start = entry.page * size;
                row.textContent = '';
                entry.composers.slice(start, start + size).forEach(function (composer) {
                    var card = doc.createElement('a'); card.className = 'composer-globe-polaroid';
                    card.href = composer.url;
                    var pieceCount = number(composer.pieces) + (composer.pieces === 1 ? ' piece' : ' pieces');
                    card.setAttribute('aria-label', composer.name + ', ' + pieceCount + '. Browse pieces');
                    var photo = doc.createElement('span'); photo.className = 'composer-globe-polaroid-photo';
                    var initials = doc.createElement('span'); initials.className = 'composer-globe-polaroid-initials';
                    initials.textContent = composer.name.trim().split(/\s+/).map(function (name) { return name.charAt(0); }).slice(0, 2).join('');
                    initials.setAttribute('aria-hidden', 'true'); photo.appendChild(initials);
                    if (composer.image) {
                        var image = doc.createElement('img'); image.alt = ''; image.decoding = 'async'; image.draggable = false;
                        image.addEventListener('error', function () { image.hidden = true; });
                        image.src = composer.image; photo.appendChild(image);
                    }
                    var name = doc.createElement('span'); name.className = 'composer-globe-polaroid-name'; name.textContent = composer.name;
                    var pieces = doc.createElement('small'); pieces.className = 'composer-globe-polaroid-count'; pieces.textContent = pieceCount;
                    card.appendChild(photo); card.appendChild(name); card.appendChild(pieces); row.appendChild(card);
                });
                navigation.hidden = pages < 2;
                range.textContent = (start + 1) + '–' + Math.min(start + size, entry.composers.length) + ' of ' + entry.composers.length;
                previous.disabled = entry.page === 0; next.disabled = entry.page === pages - 1;
            }
            previous.addEventListener('click', function () { entry.page--; render(); });
            next.addEventListener('click', function () { entry.page++; render(); });
            renderPortraitPage = render; render();
            return group;
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
            updateDetails(Object.prototype.hasOwnProperty.call(model.places, initialPlace) ? model.places[initialPlace] : model.world);
        }
        function selected(place, target) {
            return target && (target.key === place.key || (!countryMode && target.kind === 'Continent' && target.name === place.continent));
        }
        function inScope(place) {
            return !selection || !selection.continent || place.continent === selection.continent;
        }
        function capColor(place) {
            if (!inScope(place)) return '#264b60';
            if (!hoveredPortrait && selected(place, hovered)) return '#e1bd77';
            if (selected(place, selection)) return '#c8a76a';
            if (countryMode && !place.composers) return '#264b60';
            return colors[place.continent] || '#457b88';
        }
        function strokeColor() {
            // A transparent color prebuilds the stroke too. Returning null would defer
            // border geometry creation until the first zoom into country view.
            return countryMode ? 'rgba(127,167,187,0.8)' : 'rgba(127,167,187,0)';
        }
        function interactivePlace(country) {
            return country && !countryMode ? model.places['continent:' + country.continent] || country : country;
        }
        function refreshColors() {
            globe.polygonCapColor(capColor).polygonStrokeColor(strokeColor).polygonAltitude(function (place) {
                return inScope(place) && ((!hoveredPortrait && selected(place, hovered)) || selected(place, selection)) ? 0.007 : 0.005;
            });
        }
        function tooltip(place) {
            tooltipCountry = place;
            var box = doc.createElement('div'); box.className = 'composer-globe-tooltip';
            tooltipTitle = doc.createElement('strong'); tooltipDetail = doc.createElement('span');
            updateTooltip();
            box.appendChild(tooltipTitle); box.appendChild(tooltipDetail); return box;
        }
        function updateTooltip() {
            if (!tooltipCountry) return;
            var place = interactivePlace(tooltipCountry);
            tooltipTitle.textContent = place.name;
            tooltipDetail.textContent = number(place.composers) + (place.composers === 1 ? ' composer · ' : ' composers · ') + number(place.pieces) + (place.pieces === 1 ? ' piece' : ' pieces');
        }
        function hover(place) {
            hoveredCountry = place;
            place = interactivePlace(place);
            if (hovered === place) return;
            hovered = place; refreshColors();
        }
        function updateLabels() {
            var labels = model.continents.filter(inScope);
            if (countryMode) {
                var pov = globe.pointOfView();
                labels = model.countries.filter(function (country) {
                    if (!inScope(country) || !country.geometry || !country.composers) return false;
                    var delta = Math.abs(country.lng - pov.lng) % 360;
                    delta = Math.min(delta, 360 - delta);
                    return Math.abs(country.lat - pov.lat) < 32 && delta < 45;
                }).sort(function (a, b) { return b.pieces - a.pieces; }).slice(0, 10);
                var portraits = selection && selection.kind === 'Country' ? portraitCache[selection.key] : null;
                if (portraits && portraits.status === 'ready' && portraits.composers.length) labels = [portraits.marker];
            }
            // Reuse the same objects so CSS2D labels need not be rebuilt on rotation.
            var key = labels.map(function (place) { return place.key; }).join('|');
            if (key !== updateLabels.key) {
                setPortraitHover(null);
                updateLabels.key = key; globe.htmlElementsData(labels);
            }
        }
        function setMode(next) {
            if (countryMode === next) return;
            countryMode = next; hovered = interactivePlace(hoveredCountry);
            element('mode').textContent = next ? 'Countries' : 'Continents';
            element('legend').hidden = !next;
            // Keep the same polygon objects and coordinates for both views. Globe.gl
            // can reuse their triangulated meshes and borders throughout the zoom.
            // The pointer may still be over the same mesh across the threshold.
            updateTooltip(); updateLabels(); refreshColors();
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
            hovered = null; hoveredCountry = null;
            if (model) updateDetails(model.world);
            if (!globe) return;
            globe.pointOfView({lat: 23, lng: 15, altitude: 2.05}, duration);
        }
        function resize() {
            if (globe && open) {
                globe.width(stage.clientWidth).height(stage.clientHeight);
                if (renderPortraitPage) renderPortraitPage();
            }
        }
        function createGlobe() {
            loading.hidden = false;
            try {
                globe = new win.Globe(canvas, {animateIn: false, rendererConfig: {antialias: true, alpha: true}})
                    .width(stage.clientWidth).height(stage.clientHeight).backgroundColor('rgba(0,0,0,0)')
                    .showAtmosphere(true).atmosphereColor('#59b3d4').atmosphereAltitude(0.14)
                    .polygonsData(model.countries.filter(function (country) { return country.geometry; }))
                    .polygonCapColor(capColor).polygonSideColor(function () { return '#173c50'; }).polygonStrokeColor(strokeColor)
                    .polygonAltitude(0.005).polygonCapCurvatureResolution(4).polygonsTransitionDuration(reducedMotion ? 0 : 180)
                    .polygonLabel(tooltip).onPolygonHover(hover).onPolygonClick(function (place) {
                        if (!hoveredPortrait) choose(interactivePlace(place));
                    })
                    .htmlAltitude(0.023).htmlTransitionDuration(0).htmlElement(function (place) {
                        if (place.kind === 'Portraits') return portraitElement(place);
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
                waitForFirstRender();
                updateLabels.key = null; choose(selection || model.world); updateLabels();
                if (doc.hidden) globe.pauseAnimation();
            } catch (reason) { disposeGlobe(); showRenderError(); }
        }
        function waitForFirstRender() {
            var scene = globe.scene(), renderer = globe.renderer(), previous = scene.onAfterRender;
            var ready = false, cancelled = false, frame = null;
            globe.onGlobeReady(function () { ready = true; });
            stopWaitingForRender = function () {
                cancelled = true;
                scene.onAfterRender = previous;
                if (frame !== null) win.cancelAnimationFrame(frame);
                stopWaitingForRender = null;
            };
            scene.onAfterRender = function () {
                if (previous) previous.apply(this, arguments);
                if (!ready || cancelled || !open || doc.hidden || frame !== null || !renderer.info.render.triangles) return;
                // Readiness precedes drawing. Keep the overlay through the completed
                // WebGL frame, then let the browser present it before revealing it.
                frame = win.requestAnimationFrame(function () {
                    frame = null;
                    if (cancelled || !open || doc.hidden) return;
                    loading.hidden = true; controlsEnabled(true);
                    stopWaitingForRender();
                });
            };
        }
        function disposeGlobe() {
            if (stopWaitingForRender) stopWaitingForRender();
            if (globe) {
                globe.pauseAnimation();
                globe._destructor();
                globe = null;
            }
            canvas.textContent = ''; controlsEnabled(false);
            hovered = null; hoveredCountry = null; tooltipCountry = null; tooltipTitle = null; tooltipDetail = null;
            renderPortraitPage = null; hoveredPortrait = null;
        }
        function showRenderError() {
            loading.hidden = true; error.hidden = false;
            element('error-message').textContent = 'The 3D globe is unavailable in this browser. You can still explore every place using the list.';
        }
        function start() {
            error.hidden = true;
            if (globe) { resize(); updatePortraitStatus(); updateLabels(); if (!doc.hidden) globe.resumeAnimation(); return; }
            if (model) { createGlobe(); return; }
            if (pending) return;
            loading.hidden = false;
            pending = Promise.all([loadLibrary(), fetchJson(modal.getAttribute('data-globe-map')), fetchJson(modal.getAttribute('data-globe-catalogue'))])
                .then(function (results) {
                    model = buildModel(results[1], results[2]); populatePicker();
                    pending = null;
                    if (open) createGlobe();
                }).catch(function () {
                    loading.hidden = true; pending = null; error.hidden = false;
                    element('error-message').textContent = 'The globe could not load. Check your connection and try again.';
                });
        }
        controlsEnabled(false);
        modal.addEventListener('shown.bs.modal', function () { open = true; start(); if (selection) rememberPlace(); });
        modal.addEventListener('hide.bs.modal', function () { open = false; setPortraitHover(null); if (globe) globe.pauseAnimation(); });
        modal.addEventListener('hidden.bs.modal', rememberPlace);
        doc.addEventListener('visibilitychange', function () {
            if (globe) { if (doc.hidden || !open) globe.pauseAnimation(); else globe.resumeAnimation(); }
        });
        if (win.ResizeObserver) new win.ResizeObserver(resize).observe(stage);
        else win.addEventListener('resize', resize);
        retry.addEventListener('click', start);
        element('portrait-retry').addEventListener('click', function () {
            if (selection) { loadPortraits(selection, true); updatePortraitStatus(); }
        });
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
