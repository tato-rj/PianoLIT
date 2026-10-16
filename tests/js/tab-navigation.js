const assert = require('assert');
const initialize = require('../../resources/js/components/tab-navigation');
const fs = require('fs');
const vm = require('vm');

module.exports = function () {
    function fixture(hash = '', readyState = 'loading', restricted = false, initialTab = null) {
        const events = {}, windowEvents = {}, writes = [], shown = [], panels = {}, navs = [];
        const state = {pianolitCanGoBack: true, anotherFeature: 'retained'};
        let url = new URL('https://my.pianolit.com/membership/edit?source=settings' + hash);
        const doc = {readyState, addEventListener(name, callback) {events[name] = callback;},
            getElementById(id) {return panels[id] || null;},
            querySelectorAll(selector) {
                if (selector === '[data-tab-navigation]') return navs;
                assert.strictEqual(selector, '[data-tab-navigation] [data-bs-toggle="tab"]', 'Hashes never become CSS selectors.');
                return navs.flatMap(nav => nav.tabs);
            }};
        const win = {document: doc, location: {get hash() {return url.hash;}},
            addEventListener(name, callback) {windowEvents[name] = callback;},
            history: {state, replaceState(value, title, target) {
                if (restricted) throw new Error('History is unavailable');
                assert.strictEqual(value, state, 'Preserve existing history metadata.');
                url = new URL(target, url); writes.push(url.href);
            }},
            bootstrap: {Tab: {getOrCreateInstance(tab) {return {show() {
                if (tab.active || tab.disabled || tab.ariaDisabled) return;
                tab.nav.tabs.forEach(other => {other.active = other === tab;});
                shown.push(tab.id); events['shown.bs.tab']({target: tab});
            }};}}}};
        function nav(ids, managed = true) {
            const root = {tabs: [], getAttribute() {return ids[0];}, querySelectorAll() {return this.tabs;}, querySelector(selector) {
                return selector.endsWith('.active') ? this.tabs.find(tab => tab.active) : this.tabs[0];
            }};
            root.tabs = ids.map((id, i) => ({id, nav: root, active: i === 0,
                getAttribute(name) {return name === 'href' ? '#' + id : name === 'aria-disabled' && this.ariaDisabled ? 'true' : null;},
                classList: {contains() {return false;}},
                matches(selector) {return selector === '[data-bs-toggle="tab"]';},
                closest() {return managed ? root : null;}}));
            ids.forEach(id => {panels[id] = {};});
            if (managed) navs.push(root);
            return root.tabs;
        }
        const tabs = nav(['overview-tab', 'update-plan-tab', 'invoices-tab', 'details["safe"]']);
        const other = nav(['other-overview', 'other-billing']);
        const unmanaged = nav(['legacy-overview', 'legacy-score'], false);
        if (initialTab) tabs.forEach(tab => {tab.active = tab.id === initialTab;});
        initialize(win);
        return {events, win, tabs, other, unmanaged, writes, shown, panels, state,
            href: () => url.href,
            ready() {if (events.DOMContentLoaded) events.DOMContentLoaded();},
            select(tab) {win.bootstrap.Tab.getOrCreateInstance(tab).show();},
            changeHash(hash) {url.hash = hash; windowEvents.hashchange();}};
    }

    // Execute the exact inline browser entry with no Bootstrap or application bundle available.
    const browser = {window: {}};
    vm.runInNewContext(fs.readFileSync(require.resolve('../../resources/js/components/tab-navigation'), 'utf8'), browser);
    const early = browser.window.PianoLITTabNavigation;
    function firstRender(hash, options = {}) {
        const listeners = {}, writes = [], panels = {};
        function element(classes) {
            const values = new Set(classes.split(' '));
            return {style: {transition: '', visibility: '', removeProperty(name) {this[name] = '';}},
                attrs: {}, setAttribute(name, value) {this.attrs[name] = value;},
                classList: {contains(name) {return values.has(name);}, toggle(name, on) {on ? values.add(name) : values.delete(name);}},
                get offsetHeight() {writes.push('commit'); return 100;}};
        }
        const tabs = ['first', 'saved'].map((id, i) => {
            const tab = element(i === 0 ? 'active' : '');
            tab.getAttribute = name => name === 'href' ? '#' + id : name === 'aria-disabled' && options.disabled && i === 1 ? 'true' : null;
            panels[id] = element('tab-pane fade' + (i === 0 ? ' active show' : ''));
            return tab;
        });
        const container = {children: Object.values(panels)};
        Object.values(panels).forEach(panel => {panel.parentElement = container;});
        const win = {location: {hash}, document: {
            getElementById(id) {return options.missing && id === 'saved' ? null : panels[id];},
            addEventListener(name, fn) {listeners[name] = fn;},
            removeEventListener(name, fn) {if (listeners[name] === fn) delete listeners[name];}
        }};
        const frame = element('');
        frame.querySelector = () => ({querySelectorAll: () => tabs});
        frame.contains = () => !options.outside;
        early.prepare(frame, win);
        return {frame, tabs, panels, listeners, writes, render: () => early.initial(frame, win)};
    }
    let first = firstRender('#saved');
    assert.strictEqual(first.frame.style.visibility, 'hidden', 'Streaming markup cannot paint the default selection.');
    first.render();
    assert.strictEqual(first.tabs[1].classList.contains('active'), true);
    assert.strictEqual(first.tabs[0].classList.contains('active'), false);
    assert.strictEqual(first.tabs[1].attrs['aria-selected'], 'true');
    assert.strictEqual(first.tabs[0].attrs.tabindex, '-1');
    assert.strictEqual(first.tabs[1].attrs.tabindex, '0');
    assert.strictEqual(first.panels.saved.classList.contains('show'), true);
    assert.strictEqual(first.panels.saved.classList.contains('active'), true);
    assert.strictEqual(first.panels.first.classList.contains('active'), false);
    assert.strictEqual(first.frame.style.visibility, '', 'Reveal the menu and matching pane together before loading the bundle.');
    assert.strictEqual(first.panels.saved.style.transition, '', 'Later user transitions retain their normal styling.');
    assert.deepStrictEqual(first.writes, ['commit'], 'Commit the initial selection without a fade.');
    assert.strictEqual(first.listeners.DOMContentLoaded, undefined);
    for (const hash of ['', '#ordinary-heading', '#%E0%A4%A']) {
        first = firstRender(hash); first.render();
        assert.strictEqual(first.panels.first.classList.contains('active'), true);
        assert.strictEqual(first.frame.style.visibility, '');
    }
    for (const options of [{disabled: true}, {missing: true}, {outside: true}]) {
        first = firstRender('#saved', options); first.render();
        assert.strictEqual(first.tabs[0].classList.contains('active'), true);
        assert.strictEqual(first.frame.style.visibility, '', 'Unavailable targets leave the default usable.');
    }
    first = firstRender('#saved');
    first.listeners.DOMContentLoaded();
    assert.strictEqual(first.frame.style.visibility, '', 'An interrupted response cannot leave the component hidden.');

    let f = fixture('#update-plan-tab');
    assert.deepStrictEqual(f.shown, [], 'Restoration waits for deferred page listeners.');
    f.ready();
    assert.deepStrictEqual(f.shown, ['update-plan-tab']);
    assert.strictEqual(f.writes.length, 0, 'Opening a saved URL does not rewrite it.');
    f.select(f.tabs[2]);
    assert(f.href().endsWith('/membership/edit?source=settings#invoices-tab'));
    assert.strictEqual(f.win.history.state, f.state);
    f.select(f.tabs[0]);
    assert(f.href().endsWith('#overview-tab'));
    assert.strictEqual(f.writes.length, 2, 'Selections replace the current entry without adding history entries.');

    const reload = fixture(new URL(f.href()).hash, 'complete');
    assert.strictEqual(reload.tabs[0].active, true, 'Refreshing Overview retains Overview.');
    const preselected = fixture('#update-plan-tab', 'complete', false, 'update-plan-tab');
    assert.deepStrictEqual(preselected.shown, [], 'Loading the app bundle does not reselect or animate an already restored tab.');
    preselected.changeHash('');
    assert.strictEqual(preselected.tabs[0].active, true, 'Clearing the hash uses the server default, not the prepaint selection.');
    const invoiceReload = fixture('#invoices-tab', 'complete');
    assert.strictEqual(invoiceReload.tabs[2].active, true, 'Refreshing a non-default tab restores it.');
    f.select(f.other[1]);
    assert(f.href().endsWith('#other-billing'), 'Every instance of the shared menu participates.');
    f.changeHash('#update-plan-tab');
    assert.strictEqual(f.tabs[1].active, true, 'Fragment navigation restores the matching tab.');
    f.changeHash('');
    assert.strictEqual(f.tabs[0].active, true, 'Clearing the fragment restores the rendered default.');
    assert.strictEqual(f.other[0].active, true);

    f = fixture('#' + encodeURIComponent('details["safe"]'), 'complete');
    assert.strictEqual(f.tabs[3].active, true, 'Encoded fragment IDs are matched without selector interpolation.');
    f.select(f.tabs[0]); f.select(f.tabs[3]);
    assert.strictEqual(new URL(f.href()).hash, '#' + encodeURIComponent('details["safe"]'));

    for (const hash of ['#ordinary-heading', '#%E0%A4%A', '#%22%5D%5Bdata-other']) {
        f = fixture(hash); f.ready();
        assert.deepStrictEqual(f.shown, [], 'Unknown and malformed fragments leave the default tab usable.');
        assert.strictEqual(f.writes.length, 0);
    }
    f = fixture('#invoices-tab');
    f.tabs[2].ariaDisabled = true; f.ready();
    assert.deepStrictEqual(f.shown, []);
    f = fixture('#invoices-tab'); delete f.panels['invoices-tab']; f.ready();
    assert.deepStrictEqual(f.shown, [], 'Missing panels are ignored.');
    f = fixture(); f.ready();
    f.select(f.unmanaged[1]);
    assert.strictEqual(f.writes.length, 0, 'Unrelated legacy tab menus keep their existing URL behavior.');
    f = fixture('', 'complete', true);
    assert.doesNotThrow(() => f.select(f.tabs[1]));
    assert.strictEqual(f.tabs[1].active, true, 'Unavailable browser history cannot block tab selection.');
    console.log('Passed: first-render selection without the app bundle, accessible state, animation-free restore/fallbacks; shared tab URLs, refresh/deep links, queries/history state, keyboard/programmatic selections, fragment changes and invalid targets.');
};
