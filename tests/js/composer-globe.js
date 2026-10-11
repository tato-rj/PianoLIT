const assert = require('assert');
const api = require('../../resources/js/views/composer-globe');

module.exports = async function () {
    const map = {features: [
        ['DE', 'Germany', 'Europe', 51, 10], ['FR', 'France', 'Europe', 47, 2], ['JP', 'Japan', 'Asia', 36, 138]
    ].map(([code, name, continent, lat, lng]) => ({properties: {code, name, continent, lat, lng}, geometry: {type: 'Polygon', coordinates: [[[0, 0], [1, 0], [1, 1], [0, 0]]]}}))};
    const catalogue = {
        totals: {composers: 5, pieces: 13}, unmapped: {composers: 2, pieces: 4},
        countries: [{id: 1, code: 'DE', name: 'Germany', continent: 'Europe', composers: 2, pieces: 5, url: '/composers?country=1'},
            {id: 2, code: '', name: 'Japan', continent: 'Asia', composers: 1, pieces: 4, url: '/composers?country=2'},
            {id: 3, code: '', name: '<Unmapped>', continent: null, composers: 1, pieces: 2, url: '/composers?country=3'}],
        continents: [{name: 'Europe', composers: 2, pieces: 5, countries: 1, url: '/composers?continent=Europe'},
            {name: 'Asia', composers: 1, pieces: 4, countries: 1, url: '/composers?continent=Asia'}]
    };
    const model = api.buildModel(map, JSON.parse(JSON.stringify(catalogue)));
    assert.strictEqual(model.places['country:DE'].pieces, 5);
    assert.strictEqual(model.places['country:JP'].composers, 1, 'Missing ISO codes match recorded country names');
    assert.strictEqual(model.places['country:FR'].composers, 0, 'Countries without repertoire remain selectable with zero counts');
    assert.strictEqual(model.places['recorded:3'].pieces, 2, 'Unmapped countries keep their recorded counts');
    assert.strictEqual(model.places['continent:Europe'].pieces, 5);
    assert.strictEqual(model.world.pieces, 13, 'World totals include missing geography');
    assert.strictEqual(api.nextMode(1.3, false), true);
    assert.strictEqual(api.nextMode(1.4, true), true);
    assert.strictEqual(api.nextMode(1.4, false), false, 'Hysteresis prevents flicker near the zoom boundary');
    assert.strictEqual(api.nextMode(1.6, true), false);

    function node() {
        let text = '';
        const element = {events: {}, children: [], hidden: false, disabled: false, attrs: {},
            clientWidth: 900, clientHeight: 600,
            addEventListener(key, fn) { this.events[key] = fn; },
            focus(options) { this.focusOptions = options; },
            setAttribute(key, value) { this.attrs[key] = value; }, getAttribute(key) { return this.attrs[key]; },
            removeAttribute(key) { delete this.attrs[key]; }, appendChild(child) { this.children.push(child); }};
        Object.defineProperty(element, 'textContent', {get() { return text; }, set(value) { text = value; this.children = []; }});
        return element;
    }
    function harness(fetch, href = 'https://my.pianolit.com/composers') {
        const elements = {}, modal = node(), doc = node(), stage = node();
        modal.attrs = {'data-globe-map': '/map', 'data-globe-catalogue': '/catalogue'};
        modal.querySelector = selector => elements[selector] || (elements[selector] = node());
        doc.documentElement = node(); doc.documentElement.contains = target => target === modal;
        modal.closest = () => modal.getAttribute('data-bs-theme') ? modal : doc.documentElement.getAttribute('data-bs-theme') ? doc.documentElement : null;
        const canvas = modal.querySelector('[data-globe-canvas]'); canvas.parentNode = stage;
        doc.getElementById = () => modal; doc.createElement = node;
        let instances = 0, paused = 0, resumed = 0, destroyed = 0;
        const calls = {}, callCounts = {}, renderCanvas = node(), pov = {altitude: 2.05, lat: 23, lng: 15};
        const renderer = {setPixelRatio() {}, domElement: renderCanvas, info: {render: {triangles: 100}}};
        let rendered = 0, frameId = 0;
        const frames = new Map(), controls = {}, scene = {onAfterRender() { rendered++; }};
        const material = {color: {set(value) { this.value = value; }}, emissive: {set(value) { this.value = value; }}};
        const globe = new Proxy({}, {get(target, key) {
            if (key === 'pointOfView') return value => { if (value) { Object.assign(pov, value); if (calls.onZoom) calls.onZoom(pov); return globe; } return pov; };
            if (key === 'controls') return () => controls;
            if (key === 'scene') return () => scene;
            if (key === 'getGlobeRadius') return () => 100;
            if (key === 'renderer') return () => renderer;
            if (key === 'globeMaterial') return () => material;
            if (key === 'pauseAnimation') return () => { paused++; };
            if (key === 'resumeAnimation') return () => { resumed++; };
            if (key === '_destructor') return () => { destroyed++; };
            return value => { calls[key] = value; callCounts[key] = (callCounts[key] || 0) + 1; return globe; };
        }});
        let resize, themeChanged;
        const win = {fetch, Globe: function () { instances++; return globe; }, matchMedia: () => ({matches: true}),
            setTimeout, clearTimeout, requestAnimationFrame(callback) { frames.set(++frameId, callback); return frameId; },
            cancelAnimationFrame(id) { frames.delete(id); },
            MutationObserver: class {constructor(callback) { themeChanged = callback; } observe(target, options) {
                assert.strictEqual(target, doc.documentElement);
                assert.deepStrictEqual(options, {attributes: true, subtree: true, attributeFilter: ['data-bs-theme']});
            }},
            addEventListener() {}, ResizeObserver: class {constructor(callback) { resize = callback; } observe() {}}};
        win.location = {href}; win.history = {state: {pianolitCanGoBack: true},
            replaceState(state, title, url) { assert.strictEqual(state, this.state); win.location.href = url; }};
        api.initialize(doc, win);
        return {elements, modal, doc, win, canvas, calls, callCounts, pov, renderCanvas, renderer, controls, scene, frames, material,
            setTheme(value, owner = doc.documentElement) {
                if (value) owner.setAttribute('data-bs-theme', value); else owner.removeAttribute('data-bs-theme');
                themeChanged([{target: owner}]);
            },
            paint() { const callbacks = Array.from(frames.values()); frames.clear(); callbacks.forEach(callback => callback()); },
            get rendered() { return rendered; }, resize() { resize(); },
            get instances() { return instances; }, get paused() { return paused; }, get resumed() { return resumed; }, get destroyed() { return destroyed; }};
    }
    const requests = [];
    const h = harness(url => new Promise(resolve => requests.push({url, resolve})));
    const get = name => h.elements['[data-globe-' + name + ']'];
    assert.strictEqual(requests.length, 0, 'No WebGL or data request before opening the modal');
    assert.strictEqual(get('zoom-in').disabled, true);
    h.modal.events['shown.bs.modal']();
    assert.strictEqual(requests.length, 2);
    h.modal.events['hide.bs.modal']();
    requests.forEach(request => request.resolve({ok: true, json: () => Promise.resolve(request.url === '/map' ? map : JSON.parse(JSON.stringify(catalogue)))}));
    async function settle() { for (let i = 0; i < 15; i++) await Promise.resolve(); }
    await settle();
    assert.strictEqual(h.instances, 0, 'Closing while data loads never starts a hidden renderer');
    h.modal.events['shown.bs.modal']();
    assert.strictEqual(h.instances, 1);
    assert.strictEqual(get('loading').hidden, false, 'Loaded data and a constructed globe must not dismiss the loader');
    h.scene.onAfterRender(); h.paint();
    assert.strictEqual(get('loading').hidden, false, 'An empty frame before globe readiness keeps the loader');
    h.calls.onGlobeReady(); h.paint();
    assert.strictEqual(get('loading').hidden, false, 'Readiness alone does not mean the globe has rendered');
    h.renderer.info.render.triangles = 0; h.scene.onAfterRender(); h.paint();
    assert.strictEqual(get('loading').hidden, false, 'Skip the renderer’s empty startup frame, even after globe readiness');
    h.renderer.info.render.triangles = 100;
    h.scene.onAfterRender();
    assert.strictEqual(get('loading').hidden, false, 'Keep the loader through the first completed WebGL frame');
    assert.strictEqual(get('zoom-in').disabled, true);
    h.paint();
    assert.strictEqual(get('loading').hidden, true, 'Reveal the globe only after its first ready frame can be presented');
    assert.strictEqual(get('zoom-in').disabled, false);
    h.scene.onAfterRender();
    assert.strictEqual(h.frames.size, 0, 'The readiness hook removes itself instead of running throughout exploration');
    assert.strictEqual(h.rendered, 4, 'Preserve the scene’s original render callback');
    assert.strictEqual(get('composers').textContent, '5');
    assert.strictEqual(get('unmapped').hidden, false);
    const shapes = h.calls.polygonsData;
    const germany = shapes.find(place => place.code === 'DE');
    const france = shapes.find(place => place.code === 'FR');
    assert.strictEqual(shapes.length, 3, 'All mapped country geometry is prepared before zooming');
    assert.strictEqual(h.calls.polygonStrokeColor(), 'rgba(255,255,255,0)', 'Transparent borders are prepared even in continent view');
    const landTooltip = h.calls.polygonLabel(germany);
    assert.strictEqual(landTooltip.children[0].textContent, 'Europe');
    h.calls.onPolygonHover(germany);
    assert.strictEqual(h.calls.polygonCapColor(france), '#8fbbea', 'Hovering distant land highlights its whole continent');
    const colorUpdates = h.callCounts.polygonCapColor;
    h.calls.onPolygonHover(france);
    assert.strictEqual(h.callCounts.polygonCapColor, colorUpdates, 'Moving within a continent does not update every mesh again');
    h.calls.onPolygonClick(france);
    assert.strictEqual(get('title').textContent, 'Europe');
    assert.strictEqual(get('pieces').textContent, '5', 'Distant geometry selects continent totals');
    get('home').events.click();
    get('place').value = 'country:DE'; get('place').events.change();
    assert.strictEqual(get('title').textContent, 'Germany');
    assert.strictEqual(get('pieces').textContent, '5');
    assert.strictEqual(get('browse').href, '/composers?country=1');
    assert.strictEqual(get('mode').textContent, 'Countries');
    assert.strictEqual(h.calls.polygonStrokeColor(), 'rgba(255,255,255,0.85)');
    assert.strictEqual(landTooltip.children[0].textContent, 'Germany', 'An existing tooltip changes scope even when the pointer stays on the same mesh');
    assert.strictEqual(h.calls.polygonLabel(germany).children[0].textContent, 'Germany');
    h.calls.onPolygonHover(germany);
    assert.notStrictEqual(h.calls.polygonCapColor(france), '#8fbbea', 'Close hover highlights only one country');
    h.calls.onPolygonClick(france);
    assert.strictEqual(get('title').textContent, 'France', 'Close geometry selects country totals');
    assert.strictEqual(get('composers').textContent, '0');
    get('place').value = 'country:FR'; get('place').events.change();
    assert.strictEqual(get('composers').textContent, '0');
    assert.strictEqual(get('browse').hidden, true, 'Zero-count selections never retain the previous browse link');
    get('home').events.click();
    assert.strictEqual(get('title').textContent, 'A world of music');
    assert.strictEqual(get('mode').textContent, 'Continents');
    get('zoom-in').events.click(); get('zoom-in').events.click();
    assert.strictEqual(get('mode').textContent, 'Countries');
    h.calls.onZoom({altitude: 2}); assert.strictEqual(get('mode').textContent, 'Continents');
    for (let i = 0; i < 4; i++) { h.calls.onZoom({altitude: 1}); h.calls.onZoom({altitude: 2}); }
    assert.strictEqual(h.calls.polygonsData, shapes, 'Both zoom modes retain the original country objects and geometry');
    assert.strictEqual(h.callCounts.polygonsData, 1, 'Crossing zoom thresholds never replaces and retriangulates the polygon layer');
    assert.strictEqual(h.callCounts.pathsData || 0, 0, 'No separate border paths are built when switching views');
    let prevented = false;
    h.canvas.events.keydown({target: h.canvas, key: 'ArrowRight', preventDefault() { prevented = true; }});
    assert.strictEqual(prevented, true); assert.strictEqual(h.pov.lng, 30);
    h.modal.events['hide.bs.modal'](); assert(h.paused > 0);
    h.doc.hidden = true; h.doc.events.visibilitychange();
    const resumed = h.resumed; h.modal.events['shown.bs.modal'](); assert.strictEqual(h.resumed, resumed);
    h.doc.hidden = false; h.doc.events.visibilitychange(); assert(h.resumed > resumed);
    h.renderCanvas.events.webglcontextlost({preventDefault() {}});
    assert.strictEqual(h.destroyed, 1); assert.strictEqual(get('error').hidden, false);
    assert.strictEqual(get('place').disabled, false, 'Place picker remains usable without WebGL');

    for (const [key, title, altitude] of [['country:DE', 'Germany', 0.65], ['continent:Asia', 'Asia', 1.95],
        ['recorded:3', '<Unmapped>', 2.05], ['__proto__', 'A world of music', 2.05], ['bad:place', 'A world of music', 2.05]]) {
        const restored = harness(url => Promise.resolve({ok: true, json: () => Promise.resolve(url === '/map' ? map : catalogue)}),
            'https://my.pianolit.com/composers?source=saved&modal=composer-globe-modal&globe-place=' + encodeURIComponent(key) + '#details');
        assert.strictEqual(restored.instances, 0, 'A place URL alone does not initialize the globe');
        restored.modal.events['shown.bs.modal'](); await settle();
        assert.strictEqual(restored.elements['[data-globe-title]'].textContent, title);
        assert.strictEqual(restored.pov.altitude, altitude);
        let params = new URL(restored.win.location.href).searchParams;
        assert.strictEqual(params.get('globe-place'), title === 'A world of music' ? null : key);
        restored.modal.events['hide.bs.modal'](); restored.modal.events['hidden.bs.modal']();
        assert.strictEqual(new URL(restored.win.location.href).searchParams.has('globe-place'), false);
        restored.modal.events['shown.bs.modal']();
        assert.strictEqual(new URL(restored.win.location.href).searchParams.get('globe-place'), title === 'A world of music' ? null : key);
        restored.elements['[data-globe-home]'].events.click();
        params = new URL(restored.win.location.href).searchParams;
        assert.strictEqual(params.has('globe-place'), false, 'World reset clears the saved country');
        assert.strictEqual(params.get('source'), 'saved');
        assert.strictEqual(params.get('modal'), 'composer-globe-modal');
        assert.strictEqual(new URL(restored.win.location.href).hash, '#details');
        assert.strictEqual(restored.callCounts.polygonsData, 1, 'Restoring a selection keeps one persistent geometry layer');
    }

    const scopeMap = JSON.parse(JSON.stringify(map));
    scopeMap.features.push({properties: {code: 'AM', name: 'Armenia', continent: 'Asia', lat: 40, lng: 45}, geometry: map.features[0].geometry});
    const scopeCatalogue = JSON.parse(JSON.stringify(catalogue));
    scopeCatalogue.countries.push({id: 4, code: 'AM', name: 'Armenia', continent: 'Asia', composers: 1, pieces: 4});
    scopeCatalogue.continents[1] = {name: 'Asia', composers: 2, pieces: 8, countries: 2};
    scopeCatalogue.totals = {composers: 6, pieces: 17};
    const scoped = harness(url => Promise.resolve({ok: true, json: () => Promise.resolve(url === '/map' ? scopeMap : scopeCatalogue)}));
    const scopedGet = name => scoped.elements['[data-globe-' + name + ']'];
    function selectScope(key) { scopedGet('place').value = key; scopedGet('place').events.change(); }
    scoped.modal.events['shown.bs.modal'](); await settle();
    const scopedShapes = scoped.calls.polygonsData;
    const shape = code => scopedShapes.find(place => place.code === code);
    selectScope('continent:Asia');
    assert.strictEqual(scopedGet('pieces').textContent, '8');
    assert.strictEqual(scoped.calls.polygonCapColor(shape('JP')), '#76a7e4', 'The selected continent is highlighted at a distance');
    assert.strictEqual(scoped.calls.polygonCapColor(shape('DE')), '#dfe8e2', 'Other continents remain muted even when they have composers');
    assert.deepStrictEqual(scoped.calls.htmlElementsData.map(place => place.name), ['Asia']);
    scoped.calls.onPolygonHover(shape('DE'));
    assert.strictEqual(scoped.calls.polygonCapColor(shape('DE')), '#dfe8e2', 'Hovering outside the selected continent does not add another highlight');
    assert.strictEqual(scoped.calls.polygonAltitude(shape('DE')), 0.005);
    scopedGet('closer').events.click();
    assert.strictEqual(scopedGet('mode').textContent, 'Countries');
    assert.notStrictEqual(scoped.calls.polygonCapColor(shape('JP')), '#dfe8e2');
    assert.notStrictEqual(scoped.calls.polygonCapColor(shape('AM')), '#dfe8e2');
    assert.strictEqual(scoped.calls.polygonCapColor(shape('DE')), '#dfe8e2', 'Exploring Asian countries keeps European repertoire muted');
    Object.assign(scoped.pov, {lat: 40, lng: 50}); scoped.calls.onZoom(scoped.pov);
    assert.deepStrictEqual(scoped.calls.htmlElementsData.map(place => place.name), ['Armenia'], 'Only labels in the selected continent appear, even when European countries are in view');
    selectScope('country:JP');
    assert.strictEqual(scoped.calls.polygonCapColor(shape('DE')), '#dfe8e2', 'Selecting a country retains its continent scope');
    selectScope('continent:Europe');
    assert.strictEqual(scoped.calls.polygonCapColor(shape('JP')), '#dfe8e2', 'Choosing another continent replaces the scope');
    assert.deepStrictEqual(scoped.calls.htmlElementsData.map(place => place.name), ['Europe']);
    scopedGet('home').events.click();
    assert.deepStrictEqual(scoped.calls.htmlElementsData.map(place => place.name), ['Europe', 'Asia'], 'World reset restores all continent labels');
    scopedGet('zoom-in').events.click(); scopedGet('zoom-in').events.click();
    assert.notStrictEqual(scoped.calls.polygonCapColor(shape('DE')), '#dfe8e2', 'World reset restores worldwide repertoire highlights');
    assert.notStrictEqual(scoped.calls.polygonCapColor(shape('JP')), '#dfe8e2');
    assert.strictEqual(scoped.calls.polygonsData, scopedShapes);
    assert.strictEqual(scoped.callCounts.polygonsData, 1, 'Changing highlight scope reuses all prepared geometry');

    const portraitCatalogue = JSON.parse(JSON.stringify(catalogue));
    portraitCatalogue.countries[0].portraits_url = '/portraits/DE';
    portraitCatalogue.countries[1].portraits_url = '/portraits/JP';
    const portraitRequests = [];
    const portraits = harness(url => {
        if (url === '/map' || url === '/catalogue') return Promise.resolve({ok: true, json: () => Promise.resolve(url === '/map' ? map : portraitCatalogue)});
        return new Promise((resolve, reject) => portraitRequests.push({url, resolve, reject}));
    });
    const portraitGet = name => portraits.elements['[data-globe-' + name + ']'];
    const pick = key => { portraitGet('place').value = key; portraitGet('place').events.change(); };
    const hasPortraits = () => portraits.calls.htmlElementsData.some(place => place.kind === 'Portraits');
    const complete = (request, composers) => request.resolve({ok: true, json: () => Promise.resolve({composers})});
    portraits.modal.events['shown.bs.modal'](); await settle();
    pick('continent:Europe'); portraitGet('closer').events.click();
    assert.strictEqual(portraitRequests.length, 0, 'Continents and zooming into countries never fetch composer portraits');
    assert.strictEqual(hasPortraits(), false);
    pick('country:DE');
    assert.strictEqual(portraitRequests[0].url, '/portraits/DE');
    assert.strictEqual(portraitGet('portrait-status').hidden, false);
    pick('country:JP');
    const germanComposers = ['<Clara & Robert>', 'Second composer', 'Third composer', 'Fourth composer'].map((name, i) =>
        ({id: i + 1, name, pieces: i + 1, image: i === 1 ? null : '/portrait-' + i + '.jpg', url: '/search?search=' + encodeURIComponent(name)}));
    complete(portraitRequests[0], germanComposers); await settle();
    assert.strictEqual(hasPortraits(), false, 'A late response for the previous country cannot replace the current view');
    portraitRequests[1].reject(new Error('offline')); await settle();
    assert.strictEqual(portraitGet('portrait-retry').hidden, false);
    portraitGet('portrait-retry').events.click();
    assert.strictEqual(portraitRequests.length, 3);
    assert.strictEqual(portraitGet('portrait-retry').hidden, true);
    portraits.modal.events['hide.bs.modal']();
    complete(portraitRequests[2], [{id: 5, name: 'Japanese composer', pieces: 4, image: null, url: '/search?search=Japanese'}]); await settle();
    assert.strictEqual(hasPortraits(), false, 'Portrait completion does not change a closed globe');
    portraits.modal.events['shown.bs.modal']();
    assert.strictEqual(hasPortraits(), true, 'Reopening displays the completed current-country request');
    const single = portraits.calls.htmlElement(portraits.calls.htmlElementsData[0]);
    assert.strictEqual(single.children[1].hidden, true, 'A single composer needs no pager');
    pick('country:DE');
    assert.strictEqual(portraitRequests.length, 3, 'Previously selected countries reuse cached portrait data');
    const marker = portraits.calls.htmlElementsData[0];
    const deck = portraits.calls.htmlElement(marker), row = deck.children[0], pager = deck.children[1];
    const portraitGermany = portraits.calls.polygonsData.find(place => place.code === 'DE');
    const portraitFrance = portraits.calls.polygonsData.find(place => place.code === 'FR');
    portraits.calls.onPolygonHover(portraitFrance);
    assert.strictEqual(portraits.calls.polygonCapColor(portraitFrance), '#8fbbea');
    deck.events.pointerenter();
    assert.strictEqual(portraits.calls.enablePointerInteraction, false, 'The entire portrait group disables underlying globe tracking and tooltips');
    assert.strictEqual(portraits.calls.polygonCapColor(portraitFrance), '#dfe8e2', 'Entering portraits removes the underlying hover highlight');
    assert.strictEqual(portraits.calls.polygonCapColor(portraitGermany), '#76a7e4', 'The selected country stays highlighted');
    portraits.calls.onPolygonClick(portraitFrance);
    assert.strictEqual(portraitGet('place').value, 'country:DE', 'A pending map click cannot change selection beneath the overlay');
    deck.events.pointerleave();
    assert.strictEqual(portraits.calls.enablePointerInteraction, true, 'Leaving the group restores map interaction');
    assert.strictEqual(portraits.calls.polygonCapColor(portraitFrance), '#8fbbea');
    deck.events.pointerenter(); deck.events.pointercancel();
    assert.strictEqual(portraits.calls.enablePointerInteraction, true, 'Cancelled touch input cannot leave map tracking disabled');
    deck.events.pointerenter();
    assert.strictEqual(row.children.length, 3, 'Desktop renders at most three portraits at a time');
    assert.strictEqual(row.children[0].children[1].textContent, '<Clara & Robert>', 'Names remain text, never HTML');
    assert.strictEqual(row.children[0].children[2].textContent, '1 piece');
    assert.strictEqual(row.children[1].children[2].textContent, '2 pieces');
    assert.strictEqual(row.children[0].href, germanComposers[0].url);
    const photo = row.children[0].children[0].children[1]; photo.events.error();
    assert.strictEqual(photo.hidden, true, 'Broken portraits leave the initials fallback visible');
    assert.strictEqual(pager.children[0].disabled, true);
    pager.children[2].events.click();
    assert.strictEqual(row.children.length, 1);
    assert.strictEqual(row.children[0].children[1].textContent, 'Fourth composer', 'Paging makes every composer reachable');
    assert.strictEqual(pager.children[2].disabled, true);
    assert.strictEqual(portraits.calls.enablePointerInteraction, false, 'Paging within the group keeps map interaction disabled');
    portraits.canvas.parentNode.clientWidth = 390; portraits.resize();
    assert.strictEqual(row.children.length, 2, 'Small stages render at most two portraits at a time');
    assert.strictEqual(pager.children[1].textContent, '3–4 of 4');
    portraits.calls.onZoom({altitude: 2}); assert.strictEqual(hasPortraits(), false, 'Zooming out hides polaroids');
    assert.strictEqual(portraits.calls.enablePointerInteraction, true, 'Removing the portrait group restores tracking without relying on pointerleave');
    portraits.calls.onZoom({altitude: 1}); assert.strictEqual(hasPortraits(), true);
    assert.strictEqual(portraits.calls.htmlElementsData[0], marker, 'Rotation and zoom reuse the selected-country marker');
    const closeSelection = deck.children[2].children[0], selectedView = Object.assign({}, portraits.pov);
    assert.strictEqual(closeSelection.textContent, 'Close');
    assert.strictEqual(closeSelection.attrs['aria-label'], 'Close Germany selection');
    deck.events.pointerenter();
    let stopped = false;
    closeSelection.events.click({stopPropagation() { stopped = true; }});
    assert.strictEqual(stopped, true, 'Closing portraits does not also click the globe underneath');
    assert.strictEqual(hasPortraits(), false, 'Closing the country hides its portraits');
    assert.strictEqual(portraits.calls.enablePointerInteraction, true, 'Closing a hovered portrait group restores map interaction');
    assert.strictEqual(portraitGet('place').value, 'world');
    assert.strictEqual(new URL(portraits.win.location.href).searchParams.has('globe-place'), false, 'Refresh must not reselect a dismissed country');
    assert.deepStrictEqual(portraits.pov, selectedView, 'Deselecting a country keeps the current rotation and zoom');
    assert.deepStrictEqual(portraits.canvas.focusOptions, {preventScroll: true}, 'Keyboard focus returns to the globe');
    pick('country:DE');
    assert.strictEqual(hasPortraits(), true, 'The dismissed country can be selected again');
    assert.strictEqual(portraitRequests.length, 3, 'Reselection reuses its cached portraits');
    const reopenedDeck = portraits.calls.htmlElement(marker);
    reopenedDeck.events.pointerenter(); deck.events.pointerleave();
    assert.strictEqual(portraits.calls.enablePointerInteraction, false, 'A removed group cannot release a newer group’s hover guard');
    portraits.modal.events['hide.bs.modal']();
    assert.strictEqual(portraits.calls.enablePointerInteraction, true, 'Closing the modal releases the hover guard');
    portraits.modal.events['shown.bs.modal']();
    pick('country:FR'); assert.strictEqual(hasPortraits(), false, 'Empty countries hide the previous country portraits');
    assert.strictEqual(portraitGet('portrait-status').hidden, true);
    pick('continent:Asia'); assert.strictEqual(hasPortraits(), false);
    portraitGet('home').events.click(); assert.strictEqual(hasPortraits(), false);
    assert.strictEqual(portraits.callCounts.polygonsData, 1, 'Portraits do not rebuild globe geometry');

    const themed = harness(url => Promise.resolve({ok: true, json: () => Promise.resolve(url === '/map' ? map : catalogue)}));
    assert.strictEqual(themed.modal.getAttribute('data-globe-theme'), 'light', 'Default is the light app appearance, independent of the OS preference');
    themed.setTheme('dark');
    assert.strictEqual(themed.instances, 0, 'Theme changes never initialize a closed globe');
    themed.modal.events['shown.bs.modal'](); await settle();
    const themedGermany = themed.calls.polygonsData.find(country => country.code === 'DE');
    assert.strictEqual(themed.modal.getAttribute('data-globe-theme'), 'dark');
    assert.strictEqual(themed.material.color.value, '#09283f', 'Dark mode retains the original ocean');
    assert.strictEqual(themed.material.emissive.value, '#061a2b');
    assert.strictEqual(themed.calls.atmosphereColor, '#59b3d4');
    assert.strictEqual(themed.calls.atmosphereAltitude, .14);
    assert.strictEqual(themed.calls.polygonSideColor(), '#173c50');
    assert.strictEqual(themed.calls.polygonCapColor(themedGermany), '#699b9c', 'Original continent colors are retained');
    themed.calls.onPolygonHover(themedGermany);
    assert.strictEqual(themed.calls.polygonCapColor(themedGermany), '#e1bd77');
    const themedPicker = themed.elements['[data-globe-place]'];
    themedPicker.value = 'country:DE'; themedPicker.events.change();
    themed.calls.onPolygonHover(null);
    const themedView = Object.assign({}, themed.pov), themedUrl = themed.win.location.href;
    const themedGeometry = themed.calls.polygonsData;
    assert.strictEqual(themed.calls.polygonCapColor(themedGermany), '#c8a76a');
    assert.strictEqual(themed.calls.polygonStrokeColor(), 'rgba(127,167,187,0.8)');
    themed.setTheme('light');
    assert.strictEqual(themed.material.color.value, '#d5ebf4');
    assert.strictEqual(themed.calls.polygonCapColor(themedGermany), '#76a7e4');
    assert.strictEqual(themedPicker.value, 'country:DE');
    assert.deepStrictEqual(themed.pov, themedView, 'Theme changes preserve camera position and zoom');
    assert.strictEqual(themed.win.location.href, themedUrl, 'Theme changes preserve the selection URL');
    assert.strictEqual(themed.calls.polygonsData, themedGeometry);
    assert.strictEqual(themed.callCounts.polygonsData, 1, 'Changing theme never rebuilds country geometry');
    themed.setTheme('light', themed.modal); themed.setTheme('dark');
    assert.strictEqual(themed.modal.getAttribute('data-globe-theme'), 'light', 'The nearest explicit theme overrides an ancestor theme');
    themed.setTheme(null, themed.modal);
    assert.strictEqual(themed.modal.getAttribute('data-globe-theme'), 'dark');
    themed.setTheme(null);
    assert.strictEqual(themed.modal.getAttribute('data-globe-theme'), 'light', 'Removing the app theme returns to day mode');
    themed.modal.events['hide.bs.modal'](); themed.setTheme('dark'); themed.modal.events['shown.bs.modal']();
    assert.strictEqual(themed.instances, 1, 'Changing the theme while closed reuses the existing renderer');
    assert.strictEqual(themed.material.color.value, '#09283f');

    let failing = true;
    const failure = harness(url => failing ? Promise.reject(new Error('offline')) : Promise.resolve({ok: true, json: () => Promise.resolve(url === '/map' ? map : JSON.parse(JSON.stringify(catalogue)))}));
    failure.modal.events['shown.bs.modal'](); await settle();
    assert.strictEqual(failure.elements['[data-globe-error]'].hidden, false);
    assert.strictEqual(failure.elements['[data-globe-loading]'].hidden, true);
    failing = false; failure.elements['[data-globe-retry]'].events.click(); await settle();
    assert.strictEqual(failure.instances, 1, 'Failed requests can be retried without reloading the page');
    const failureGet = name => failure.elements['[data-globe-' + name + ']'];
    assert.strictEqual(failureGet('loading').hidden, false, 'Retry restores the loader during renderer preparation');
    failure.calls.onGlobeReady(); failure.scene.onAfterRender();
    failure.modal.events['hide.bs.modal'](); failure.paint();
    assert.strictEqual(failureGet('loading').hidden, false, 'Closing during preparation cannot reveal an unfinished globe');
    failure.modal.events['shown.bs.modal']();
    failure.doc.hidden = true; failure.scene.onAfterRender(); failure.paint();
    assert.strictEqual(failureGet('loading').hidden, false, 'A background tab keeps its loading state until a visible frame');
    failure.doc.hidden = false; failure.scene.onAfterRender();
    const staleReveal = Array.from(failure.frames.values())[0];
    failure.renderCanvas.events.webglcontextlost({preventDefault() {}});
    assert.strictEqual(failure.frames.size, 0, 'Context loss cancels a pending reveal');
    failureGet('retry').events.click();
    assert.strictEqual(failureGet('loading').hidden, false, 'A rebuilt renderer starts covered');
    staleReveal();
    assert.strictEqual(failureGet('loading').hidden, false, 'An old renderer’s frame cannot dismiss a new renderer’s loader');
    failure.calls.onGlobeReady(); failure.scene.onAfterRender(); failure.paint();
    assert.strictEqual(failureGet('loading').hidden, true);
    assert.strictEqual(failureGet('error').hidden, true);
    console.log('Passed: composer globe counts, cached geometry, scoped highlights, country-only polaroids, portrait counts/paging/fallbacks, lazy requests/cache/races/retry, keyboard controls and context loss.');
};
