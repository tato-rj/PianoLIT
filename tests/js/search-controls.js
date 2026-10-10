const assert = require('assert');
const mount = require('../../resources/js/components/search-controls');
module.exports = function () {
    function node(attrs = {}) {
        return {attrs, events: {}, style: {setProperty(key, value) { this[key] = value; }}, children: [],
            classList: {toggle(key, value) { this[key] = value; }},
            getAttribute(key) { return this.attrs[key] || null; }, setAttribute(key, value) { this.attrs[key] = value; },
            addEventListener(name, fn) { this.events[name] = fn; }, remove() { this.removed = true; }, appendChild(child) { this.children.push(child); }};
    }
    const saved = {};
    const storage = {getItem: key => saved[key] || null, setItem: (key, value) => saved[key] = value, removeItem: key => delete saved[key]};
    function fixture(href, isResults = true, sessionStorage = storage) {
        const groups = Object.entries({level: ['elementary', 'advanced'], period: ['baroque'], length: [], type: ['pedagogical'], ensemble: ['solo', '4 hands']}).map(([facet, values]) => {
            const group = node({'data-search-facet': facet});
            group.buttons = values.map(value => node({'data-search-value': value, 'aria-pressed': 'false'}));
            group.querySelectorAll = selector => selector === '[aria-pressed=true]' ? group.buttons.filter(button => button.attrs['aria-pressed'] === 'true') : group.buttons;
            group.contains = button => group.buttons.includes(button);
            return group;
        });
        const panel = node(), form = node(), search = node(), toggle = node(), reset = node(), apply = node(), range = node(), summary = node();
        const min = node(), max = node(), video = node(), score = node(), query = {value: 'happy'};
        const sorts = ['relevance', 'title_asc', 'title_desc', 'composer', 'level', 'period'].map(value => Object.assign(node(), {value}));
        form.querySelectorAll = () => sorts;
        form.querySelector = selector => selector === '[name=sort]:checked' ? sorts.find(input => input.checked) : selector === '[name=video_only]' ? video : score;
        const inputs = {'[data-length-min]': min, '[data-length-max]': max, '.search-length': range, '[data-length-summary]': summary, '[data-search-reset]': reset, '[data-search-apply]': apply};
        panel.querySelectorAll = () => groups;
        panel.querySelector = selector => inputs[selector];
        search.action = 'https://my.example.test/search?lazy-load=';
        search.querySelector = selector => selector === '.search-controls-toggle' ? toggle : query;
        search.querySelectorAll = () => search.children.filter(child => !child.removed);
        let hidden = 0, refreshed = 0, clears = 0, filters;
        const root = {URL, sessionStorage, location: {href},
            document: {getElementById: id => ({'search-controls': panel, 'search-controls-form': form, 'search-form': search, 'pieces-list': isResults ? {} : null})[id], createElement: () => node()},
            history: {replaceState(state, title, url) { root.location.href = url; }},
            bootstrap: {Offcanvas: {getOrCreateInstance: () => ({hide() { hidden++; }})}},
            reset() { clears++; }, applyFilters(value) { refreshed++; filters = value; }};
        mount(root);
        function click(facet, value) {
            const group = groups.find(group => group.attrs['data-search-facet'] === facet);
            const button = group.buttons.find(button => button.attrs['data-search-value'] === value);
            group.events.click({target: {closest: () => button}});
        }
        return {root, panel, form, search, toggle, reset, apply, range, summary, min, max, video, score, query, sorts, groups, click,
            counts: () => ({hidden, refreshed, clears, filters}), applyNow: () => apply.events.click(),
            requestUrl: () => new URL(root.searchControlsUrl(root.location.href)), submitQuery(value) { query.value = value; let prevented = false; search.events.submit({preventDefault() { prevented = true; }}); return prevented; }};
    }
    const f = fixture('https://my.example.test/search?search=happy&page=2&sort=title_desc&facets[level][]=advanced');
    assert(f.sorts[2].checked);
    assert.strictEqual(f.groups[0].buttons[1].attrs['aria-pressed'], 'true');
    assert(f.toggle.classList['is-active']);
    assert.strictEqual(new URL(f.root.location.href).searchParams.get('sort'), null, 'Incoming choices are consumed so refresh resets them');
    f.panel.events['show.bs.offcanvas'](); f.click('level', 'elementary'); f.panel.events['show.bs.offcanvas']();
    assert.strictEqual(f.groups[0].buttons[0].attrs['aria-pressed'], 'false', 'Cancellation discards edits');
    f.reset.events.click(); assert(f.sorts[0].checked);
    assert.strictEqual(f.counts().refreshed, 0, 'Pending Reset does not load results');
    f.min.value = '1'; f.min.events.input();
    assert.strictEqual(f.summary.textContent, 'Medium, Long');
    assert.strictEqual(f.range.style['--range-start'], '50%');
    f.max.value = '0'; f.max.events.input();
    assert.strictEqual(Number(f.min.value), 0, 'Crossing endpoints keeps a valid range');
    assert.strictEqual(f.summary.textContent, 'Short');
    f.click('ensemble', 'solo'); f.video.checked = true; f.applyNow();
    const url = f.requestUrl();
    assert.strictEqual(url.searchParams.get('video_only'), '1');
    assert.deepStrictEqual(url.searchParams.getAll('facets[ensemble][]'), ['solo']);
    assert.deepStrictEqual(url.searchParams.getAll('facets[length][]'), ['short']);
    assert.strictEqual(new URL(f.root.location.href).searchParams.get('video_only'), null, 'Applied options stay out of the visible URL');
    assert.deepStrictEqual(f.counts(), {hidden: 1, refreshed: 1, clears: 1, filters: []});
    f.reset.events.click(); f.panel.events['show.bs.offcanvas']();
    assert(f.video.checked);
    assert.strictEqual(f.summary.textContent, 'Short', 'Dismissed Reset preserves applied range');
    assert.strictEqual(Number(f.max.value), 0);
    assert(f.submitQuery('Mozart & sonata'));
    assert.strictEqual(f.counts().refreshed, 2);
    assert.strictEqual(f.requestUrl().searchParams.get('search'), 'Mozart & sonata');
    assert.deepStrictEqual(f.requestUrl().searchParams.getAll('facets[length][]'), ['short'], 'Further searches on this screen keep the range');
    assert.strictEqual(f.requestUrl().searchParams.get('video_only'), '1');
    const reload = fixture(f.root.location.href);
    assert.strictEqual(reload.requestUrl().searchParams.get('video_only'), null, 'Refresh forgets result-page choices');
    assert.strictEqual(reload.requestUrl().searchParams.get('sort'), 'relevance');
    f.min.value = '0'; f.max.value = '2'; f.min.events.input();
    assert.strictEqual(f.summary.textContent, 'Any length');
    f.reset.events.click(); f.applyNow();
    assert.strictEqual(f.toggle.classList['is-active'], false);

    const explore = fixture('https://my.example.test/explore', false);
    explore.click('ensemble', 'solo'); explore.video.checked = true; explore.applyNow();
    assert.strictEqual(explore.root.location.href, 'https://my.example.test/explore');
    assert.strictEqual(explore.counts().refreshed, 0, 'Apply outside results must not trigger a search');
    assert.strictEqual(explore.counts().hidden, 1);
    assert.strictEqual(explore.submitQuery('Bach'), false, 'Ordinary Explore searches keep native form navigation');
    assert(explore.search.children.some(input => input.name === 'video_only' && input.value === '1'));
    const guide = fixture('https://my.example.test/explore?level=elementary', false);
    assert(guide.video.checked, 'Preferences survive intermediate Explore selections');
    assert.strictEqual(guide.counts().refreshed, 0);
    const results = fixture('https://my.example.test/search?catalogue=1&level=elementary&search=Elementary');
    assert.strictEqual(results.requestUrl().searchParams.get('level'), 'elementary');
    assert.strictEqual(results.requestUrl().searchParams.get('video_only'), '1');
    assert.deepStrictEqual(results.requestUrl().searchParams.getAll('facets[ensemble][]'), ['solo']);
    assert.deepStrictEqual(saved, {}, 'Results consume the pending handoff');
    const afterRefresh = fixture(results.root.location.href);
    assert.strictEqual(afterRefresh.requestUrl().searchParams.get('video_only'), null);
    assert.deepStrictEqual(afterRefresh.requestUrl().searchParams.getAll('facets[ensemble][]'), []);
    const unavailable = {getItem() { throw Error('blocked'); }, setItem() { throw Error('blocked'); }, removeItem() { throw Error('blocked'); }};
    const noStorage = fixture('https://my.example.test/explore', false, unavailable);
    noStorage.video.checked = true; noStorage.applyNow();
    assert.strictEqual(noStorage.counts().refreshed, 0);
    console.log('Passed: Apply-only behavior, result-page searches/reset-on-refresh, Explore handoff, cancellation and slider state.');
};
