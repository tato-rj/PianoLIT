const assert = require('assert');
const initialize = require('../../resources/js/views/composers');

module.exports = function () {
    function node(attrs = {}) {
        return {
            attrs, hidden: true, events: {}, style: {}, value: '', textContent: '',
            classList: {values: {}, toggle(key, value) { this.values[key] = value; }},
            getAttribute(key) { return this.attrs[key]; },
            setAttribute(key, value) { this.attrs[key] = value; },
            addEventListener(key, handler) { this.events[key] = handler; },
            focus() { this.focused = true; }
        };
    }
    const cards = [
        ['Bach', 'Johann Sebastian Bach Germany Europe Prelude and Fugue The Well-Tempered Clavier', true, 1, 20, 'Europe'],
        ['Chopin', 'Frédéric Chopin Poland Europe Étude Douze Études', true, 2, 15, 'Europe'],
        ['Price', 'Florence Price United States North America Fantasie', false, 3, 2, 'North America'],
    ].map(([name, search, popular, created, pieces, regions]) => node({
        'data-composer-name': name, 'data-composer-search': search,
        'data-composer-regions': regions,
        'data-composer-continent': regions,
        'data-composer-period': {Bach: 'Baroque', Chopin: 'Romantic', Price: 'Modern'}[name],
        'data-composer-gender': name === 'Price' ? 'female' : 'male',
        'data-composer-popular': String(popular), 'data-composer-created': created, 'data-composer-pieces': pieces
    }));
    const filters = ['popular', 'recent'].map(value => node({'data-composer-filter': value}));
    const [popular, recent] = filters;
    const letters = ['all', 'b', 'c', 'p'].map(value => node({'data-composer-letter': value}));
    const sorts = ['pieces', 'name', 'recent', 'period'].map(value => Object.assign(node(), {value, checked: value === 'pieces'}));
    const facetOptions = {
        period: ['baroque', 'classical', 'romantic', 'impressionist', 'modern', 'contemporary'],
        continent: ['europe', 'north america', 'asia'],
        gender: ['female', 'male']
    };
    const groups = Object.entries(facetOptions).map(([facet, values]) => {
        const buttons = values.map(value => node({'data-composer-value': value, 'aria-pressed': 'false'}));
        return Object.assign(node({'data-composer-facet': facet}), {buttons, querySelectorAll() { return buttons; }});
    });
    const panelForm = node(), panelReset = node(), apply = node(), toggle = node();
    const panel = Object.assign(node(), {
        querySelector(selector) { return {'form': panelForm, '[data-composer-panel-reset]': panelReset, '[data-composer-apply]': apply}[selector]; },
        querySelectorAll(selector) { return {'[name="composer_sort"]': sorts, '[data-composer-facet]': groups, '[data-composer-facet="period"] [data-composer-value]': groups[0].buttons}[selector]; }
    });
    function chooseSort(value) { sorts.forEach(input => { input.checked = input.value === value; }); }
    function facetButton(facet, value) { return groups.find(group => group.attrs['data-composer-facet'] === facet).buttons.find(button => button.attrs['data-composer-value'] === value); }
    const document = {getElementById(id) { return id === 'composer-controls' ? panel : page; }};
    const search = node(), erase = node(), empty = node(), status = node(), reset = node(), control = node();
    const order = [];
    const list = {querySelectorAll() { return cards; }, appendChild(card) { const index = order.indexOf(card); if (index >= 0) order.splice(index, 1); order.push(card); }};
    const form = Object.assign(node(), {querySelector(selector) { return selector === '[data-erase]' ? erase : selector === '.search-controls-toggle' ? toggle : search; }});
    const page = {
        querySelector(selector) { return {'#composers-list': list, '#search-form': form, '[data-composer-empty]': empty, '[data-composer-status]': status, '[data-composer-reset]': reset}[selector]; },
        querySelectorAll(selector) { return {'[data-composer-filter]': filters, '[data-composer-letter]': letters, '[data-composer-controls]': [control]}[selector]; }
    };
    initialize(document);
    assert.strictEqual(status.textContent, '3 composers shown');
    assert.strictEqual(control.hidden, false);
    search.value = '  EUROPE  '; search.events.input();
    assert.deepStrictEqual(cards.map(card => card.hidden), [false, false, true], 'Continent search ignores case and whitespace');
    letters[2].events.click();
    assert.deepStrictEqual(cards.map(card => card.hidden), [true, false, true], 'Continent search combines with surname filters');
    reset.events.click();
    search.value = 'europe prelude'; search.events.input();
    assert.deepStrictEqual(cards.map(card => card.hidden), [false, true, true], 'Continent and work terms combine');
    search.value = 'north america'; search.events.input();
    assert.deepStrictEqual(cards.map(card => card.hidden), [true, true, false], 'Multiword continents find their composers');
    popular.events.click();
    assert.strictEqual(empty.hidden, false, 'Continent search respects the popular filter');
    reset.events.click();
    search.value = 'well tempered clavier'; search.events.input();
    assert.deepStrictEqual(cards.map(card => card.hidden), [false, true, true], 'Collection names find their composer');
    letters[2].events.click();
    assert.strictEqual(empty.hidden, false, 'Collection search combines with surname filters');
    reset.events.click();
    search.value = 'douze etudes'; search.events.input();
    assert.deepStrictEqual(cards.map(card => card.hidden), [true, false, true], 'Collection searches ignore accents');
    reset.events.click();
    search.value = 'poland etude'; search.events.input();
    assert.deepStrictEqual(cards.map(card => card.hidden), [true, false, true], 'Country/work search ignores accents');
    letters[1].events.click();
    assert.strictEqual(empty.hidden, false, 'Search and surname letter combine');
    assert.strictEqual(status.textContent, '0 composers shown');
    reset.events.click();
    popular.events.click();
    assert.deepStrictEqual(cards.map(card => card.hidden), [false, false, true]);
    search.value = 'fugue'; search.events.input();
    assert.deepStrictEqual(cards.map(card => card.hidden), [false, true, true], 'Popular combines with work search');
    erase.events.click();
    assert.strictEqual(search.value, '');
    assert.deepStrictEqual(cards.map(card => card.hidden), [false, false, true], 'Clear search preserves the selected filter');
    assert.strictEqual(search.focused, true);
    letters[0].events.click();
    assert(cards.every(card => !card.hidden), 'All clears Popular without needing a separate category button');
    assert(filters.every(button => button.attrs['aria-pressed'] === 'false'));
    recent.events.click();
    assert.deepStrictEqual(order, [cards[2], cards[1], cards[0]], 'Recently added uses catalog creation date');
    assert(cards.every(card => !card.hidden), 'Recent ordering retains the whole directory');
    letters[3].events.click();
    letters[0].events.click();
    assert(cards.every(card => !card.hidden), 'All clears surname filtering');
    assert.deepStrictEqual(order, cards, 'All restores most-pieces ordering after Recently added');
    assert.strictEqual(recent.attrs['aria-pressed'], 'false');
    assert.strictEqual(letters[0].attrs['aria-pressed'], 'true');
    recent.events.click();
    panel.events['show.bs.offcanvas']();
    chooseSort('name');
    assert.deepStrictEqual(order, [cards[2], cards[1], cards[0]], 'Draft sorting waits for Apply');
    apply.events.click();
    assert.deepStrictEqual(order, cards, 'Surname sorting is alphabetical');
    assert(filters.every(button => button.attrs['aria-pressed'] === 'false'));
    letters[3].events.click();
    assert.deepStrictEqual(cards.map(card => card.hidden), [true, true, false]);
    search.value = 'nobody'; search.events.input(); reset.events.click();
    assert(cards.every(card => !card.hidden));
    assert.strictEqual(empty.hidden, true);
    let prevented = false;
    form.events.submit({preventDefault() { prevented = true; }});
    assert.strictEqual(prevented, true, 'Directory search never submits to mobile or global search');

    recent.events.click();
    panel.events['show.bs.offcanvas']();
    chooseSort('period');
    apply.events.click();
    assert.deepStrictEqual(order, cards, 'Period sorts chronologically from Baroque to Modern');
    assert.strictEqual(toggle.classList.values['is-active'], true);
    panel.events['show.bs.offcanvas']();
    facetButton('period', 'romantic').events.click();
    facetButton('period', 'modern').events.click();
    assert(cards.every(card => !card.hidden), 'Draft facets leave the current list alone');
    apply.events.click();
    assert.deepStrictEqual(cards.map(card => card.hidden), [true, false, false], 'Multiple periods match with OR');
    panel.events['show.bs.offcanvas']();
    facetButton('continent', 'europe').events.click();
    apply.events.click();
    assert.deepStrictEqual(cards.map(card => card.hidden), [true, false, true], 'Period and continent combine with AND');
    panel.events['show.bs.offcanvas']();
    facetButton('gender', 'female').events.click();
    apply.events.click();
    assert(cards.every(card => card.hidden), 'Gender combines with the other groups');
    assert.strictEqual(empty.hidden, false);
    panel.events['show.bs.offcanvas']();
    panelReset.events.click();
    assert(cards.every(card => card.hidden), 'Panel Reset edits only the draft until Apply');
    panel.events['show.bs.offcanvas']();
    assert.strictEqual(facetButton('gender', 'female').attrs['aria-pressed'], 'true', 'Reopening after Cancel restores applied options');
    panelReset.events.click();
    apply.events.click();
    assert(cards.every(card => !card.hidden));
    assert.strictEqual(toggle.classList.values['is-active'], false);
    panel.events['show.bs.offcanvas']();
    facetButton('gender', 'female').events.click();
    apply.events.click();
    assert.deepStrictEqual(cards.map(card => card.hidden), [true, true, false]);
    letters[3].events.click();
    search.value = 'europe'; search.events.input();
    letters[0].events.click();
    assert.deepStrictEqual(cards.map(card => card.hidden), [false, false, true], 'All clears panel facets and letters while retaining search');
    reset.events.click();
    cards[0].attrs['data-composer-period'] = '';
    cards[0].attrs['data-composer-gender'] = '';
    cards[0].attrs['data-composer-continent'] = '';
    initialize(document);
    panel.events['show.bs.offcanvas']();
    chooseSort('period');
    apply.events.click();
    assert.deepStrictEqual(order, [cards[1], cards[2], cards[0]], 'Unknown periods sort after the recognized periods');
    panel.events['show.bs.offcanvas']();
    facetButton('gender', 'male').events.click();
    apply.events.click();
    assert.deepStrictEqual(cards.map(card => card.hidden), [true, false, true], 'Missing metadata cannot satisfy a selected facet');
    reset.events.click();
    let panelPrevented = false;
    panelForm.events.submit({preventDefault() { panelPrevented = true; }});
    assert(panelPrevented, 'The dedicated panel never submits the shared piece search');

    // Reuse the directory harness with Americas fixtures to exercise its real
    // word matching and filter behavior with the server-generated region alias.
    cards[0].attrs['data-composer-search'] = 'Mexican Composer Mexico North America Study Latin America';
    cards[1].attrs['data-composer-search'] = 'Canadian Composer Canada North America Study';
    cards[2].attrs['data-composer-search'] = 'American Composer United States North America Study';
    cards[0].attrs['data-composer-regions'] = 'North America|Latin America';
    cards[1].attrs['data-composer-regions'] = 'North America';
    cards[2].attrs['data-composer-regions'] = 'North America';
    initialize(document);
    search.value = ' LATIN AMERICA '; search.events.input();
    assert.deepStrictEqual(cards.map(card => card.hidden), [false, true, true], 'Latin America matches the regional alias and excludes US/Canada');
    search.value = 'latin america study'; search.events.input();
    assert.deepStrictEqual(cards.map(card => card.hidden), [false, true, true], 'Regional alias combines with work searches');
    popular.events.click();
    assert.deepStrictEqual(cards.map(card => card.hidden), [false, true, true], 'Regional alias respects popular filtering');
    search.value = 'america'; search.events.input();
    letters[0].events.click();
    assert(cards.every(card => !card.hidden), 'America alone still includes the US and Canada');

    reset.events.click();
    cards[0].attrs['data-composer-search'] = 'Dianne Goolkasian Rahbee United States North America Fantasia Latin America';
    cards[0].attrs['data-composer-regions'] = 'North America';
    cards[1].attrs['data-composer-search'] = 'Carl Philipp Emanuel Bach Germany Europe Fantasia';
    cards[1].attrs['data-composer-regions'] = 'Europe';
    cards[2].attrs['data-composer-search'] = 'Naoko Ikeda Japan Asia Study';
    cards[2].attrs['data-composer-regions'] = 'Asia';
    search.value = 'asia';
    initialize(document);
    assert.deepStrictEqual(cards.map(card => card.hidden), [true, true, false], 'Initial Asia search excludes Goolkasian and Fantasia substring matches');
    search.value = ' ASIA   study '; search.events.input();
    assert.deepStrictEqual(cards.map(card => card.hidden), [true, true, false], 'Geographic filter combines with remaining work terms');
    search.value = 'study asia'; search.events.input();
    assert.deepStrictEqual(cards.map(card => card.hidden), [true, true, false], 'Geographic terms work after ordinary terms');
    search.value = 'fantasia'; search.events.input();
    assert.deepStrictEqual(cards.map(card => card.hidden), [false, false, true], 'Fantasia remains an ordinary work search');
    search.value = 'goolkas'; search.events.input();
    assert.deepStrictEqual(cards.map(card => card.hidden), [false, true, true], 'Partial name searches remain supported');
    search.value = 'latin america'; search.events.input();
    assert(cards.every(card => card.hidden), 'A US work title containing Latin America cannot bypass the geographic exclusion');
    initialize({getElementById() { return null; }});
};
