const assert = require('assert');
const mount = require('../../resources/js/components/search-controls');
module.exports = function () {
    function node(attrs = {}) {
        return {attrs, events: {}, style: {setProperty(key, value) { this[key] = value; }}, children: [],
            classList: {toggle(key, value) { this[key] = value; }},
            getAttribute(key) { return this.attrs[key] || null; }, setAttribute(key, value) { this.attrs[key] = value; },
            addEventListener(name, fn) { this.events[name] = fn; }, remove() { this.removed = true; }, appendChild(child) { this.children.push(child); }};
    }
    const groups = Object.entries({level: ['elementary', 'advanced'], period: ['baroque'], length: ['short', 'medium', 'long'], type: ['pedagogical'], ensemble: ['solo', '4 hands']}).map(([facet, values]) => {
        const group = node({'data-search-facet': facet});
        group.buttons = values.map(value => node({'data-search-value': value, 'aria-pressed': 'false'}));
        group.querySelectorAll = selector => selector === '[aria-pressed=true]' ? group.buttons.filter(button => button.attrs['aria-pressed'] === 'true') : group.buttons;
        group.contains = button => group.buttons.includes(button);
        return group;
    });
    const panel = node(), form = node(), search = node(), toggle = node(), reset = node(), range = node(), summary = node();
    const min = node(), max = node(), audio = node(), score = node();
    const sorts = ['relevance', 'title_asc', 'title_desc', 'composer', 'level', 'period'].map(value => Object.assign(node(), {value}));
    form.querySelectorAll = () => sorts;
    form.querySelector = selector => selector === '[name=sort]:checked' ? sorts.find(input => input.checked) : selector === '[name=audio_only]' ? audio : score;
    const inputs = {'[data-length-min]': min, '[data-length-max]': max, '.search-length': range, '[data-length-summary]': summary, '[data-search-reset]': reset};
    panel.querySelectorAll = () => groups;
    panel.querySelector = selector => inputs[selector];
    search.querySelector = selector => selector === '.search-controls-toggle' ? toggle : {value: 'happy'};
    search.querySelectorAll = () => search.children.filter(child => !child.removed);
    let hidden = 0, refreshed = 0, clears = 0, filters;
    const root = {URL, location: {href: 'https://my.example.test/search?search=happy&page=2&sort=title_desc&facets[level][]=advanced'},
        document: {getElementById: id => ({'search-controls': panel, 'search-controls-form': form, 'search-form': search, 'pieces-list': {}})[id], createElement: () => node()},
        history: {replaceState(state, title, url) { root.location.href = url; }},
        bootstrap: {Offcanvas: {getOrCreateInstance: () => ({hide() { hidden++; }})}},
        reset() { clears++; }, applyFilters(value) { refreshed++; filters = value; }};
    // The browser provides URL globally; this module uses the same Node global.
    mount(root);
    assert(sorts[2].checked);
    assert.strictEqual(groups[0].buttons[1].attrs['aria-pressed'], 'true');
    assert(toggle.classList['is-active']);
    function click(group, value) {
        const button = group.buttons.find(button => button.attrs['data-search-value'] === value);
        group.events.click({target: {closest: () => button}});
    }
    panel.events['show.bs.offcanvas']();
    click(groups[0], 'elementary');
    panel.events['show.bs.offcanvas']();
    assert.strictEqual(groups[0].buttons[0].attrs['aria-pressed'], 'false', 'Reopening after cancellation discards edits');
    assert.strictEqual(refreshed, 0);
    reset.events.click();
    assert(sorts[0].checked);
    assert.strictEqual(refreshed, 0, 'Reset is pending until Show results');
    min.value = '1'; min.events.input();
    assert.strictEqual(groups[2].buttons[0].attrs['aria-pressed'], 'false');
    assert.strictEqual(groups[2].buttons[1].attrs['aria-pressed'], 'true');
    assert.strictEqual(range.style['--range-start'], '50%');
    max.value = '0'; max.events.input();
    assert.strictEqual(Number(min.value), 0, 'Crossing endpoints keeps a valid range');
    assert.strictEqual(summary.textContent, 'Short');
    click(groups[4], 'solo'); audio.checked = true;
    form.events.submit({preventDefault() {}});
    const url = new URL(root.location.href);
    assert.strictEqual(url.searchParams.get('page'), null);
    assert.strictEqual(url.searchParams.get('audio_only'), '1');
    assert.deepStrictEqual(url.searchParams.getAll('facets[ensemble][]'), ['solo']);
    assert.strictEqual(hidden, 1); assert.strictEqual(refreshed, 1); assert.strictEqual(clears, 1); assert.deepStrictEqual(filters, []);
    reset.events.click(); panel.events['show.bs.offcanvas']();
    assert(audio.checked, 'Dismissed Reset must preserve applied choices');
    assert.strictEqual(groups[4].buttons[0].attrs['aria-pressed'], 'true');
    reset.events.click(); form.events.submit({preventDefault() {}});
    assert.strictEqual(new URL(root.location.href).searchParams.get('audio_only'), null);
    assert.strictEqual(toggle.classList['is-active'], false);
    assert.strictEqual(refreshed, 2);
    console.log('Passed: search sheet draft/cancel/reset/apply, URL state, length range crossing and Solo/media choices.');
};
