const assert = require('assert');
const install = require('../../resources/js/views/explore-transitions');

module.exports = async function () {
    const base = 'http://my.pianolit.test/explore';
    const storage = new Map();
    function page(url, options = {}) {
        const events = {}, attrs = {}, sections = [{open: true}, {open: false}];
        const root = {
            location: {href: url}, scrollY: 240,
            matchMedia: query => ({matches: query.includes('max-width') ? !options.desktop : !!options.reduced}),
            sessionStorage: {
                getItem: key => storage.get(key) || null,
                setItem: (key, value) => { storage.set(key, value); },
                removeItem: key => { storage.delete(key); }
            },
            addEventListener: (name, callback) => { events[name] = callback; },
            scrollTo: (x, y) => { root.scrollY = y; },
            document: {
                documentElement: {
                    setAttribute: (key, value) => { attrs[key] = value; },
                    removeAttribute: key => { delete attrs[key]; }
                },
                querySelectorAll: () => sections,
                addEventListener: (name, callback) => { events[name] = callback; }
            }
        };
        const api = install(root);
        function transition(activation) {
            let finish;
            const result = {activation, skipped: false};
            result.viewTransition = {
                finished: new Promise(resolve => { finish = resolve; }),
                skipTransition: () => { result.skipped = true; }
            };
            result.finish = finish;
            return result;
        }
        return {root, events, attrs, sections, api, transition};
    }
    const directory = page(base);
    const level = base + '?level=elementary';
    assert.strictEqual(directory.api.direction(base, level), 'forward');
    assert.strictEqual(directory.api.direction(level, base), 'back');
    assert.strictEqual(directory.api.direction(level, level + '&mood=gentle'), 'forward');
    assert.strictEqual(directory.api.direction(level + '&mood=gentle', level), 'back');
    [base, base + '?sort=title', 'http://other.test/explore?level=elementary', base + '/other?level=elementary'].forEach(url => {
        assert.strictEqual(directory.api.direction(base, url), null);
    });
    assert.strictEqual(directory.api.direction(level, base + '?mood=gentle'), null);

    directory.sections[0].open = false;
    directory.sections[1].open = true;
    const outgoing = directory.transition({entry: {url: level}});
    directory.events.pageswap(outgoing);
    assert.strictEqual(directory.attrs['data-explore-transition'], 'forward');
    outgoing.finish();
    await Promise.resolve();
    assert.strictEqual(directory.attrs['data-explore-transition'], undefined);

    // Browsers without Navigation API activation use the observed native link.
    const guide = page(level);
    const incoming = guide.transition();
    guide.events.pagereveal(incoming);
    assert.strictEqual(guide.attrs['data-explore-transition'], 'forward');
    incoming.finish();
    await Promise.resolve();
    const link = {href: base, target: '', hasAttribute: () => false};
    guide.events.click({button: 0, target: {closest: () => link}});
    guide.events.pageswap(guide.transition());
    assert.strictEqual(guide.attrs['data-explore-transition'], 'back');
    const restored = page(base);
    restored.root.scrollY = 0;
    restored.events.pagereveal(restored.transition());
    assert.deepStrictEqual(restored.sections.map(section => section.open), [false, true]);
    assert.strictEqual(restored.root.scrollY, 240);
    assert.strictEqual(restored.attrs['data-explore-transition'], 'back');

    // History traversal can determine direction without a preceding click.
    const history = page(level);
    history.root.navigation = {activation: {from: {url: base}}};
    history.events.pagereveal(history.transition());
    assert.strictEqual(history.attrs['data-explore-transition'], 'forward');
    [{desktop: true}, {reduced: true}].forEach(options => {
        const disabled = page(base, options), event = disabled.transition({entry: {url: level}});
        disabled.events.pageswap(event);
        assert.strictEqual(event.skipped, true);
        assert.strictEqual(disabled.attrs['data-explore-transition'], undefined);
    });
    const modified = page(base), modifiedEvent = modified.transition();
    modified.events.click({button: 0, ctrlKey: true, target: {closest: () => ({...link, href: level})}});
    modified.events.pageswap(modifiedEvent);
    assert.strictEqual(modifiedEvent.skipped, true);
    const unavailable = page(base);
    Object.defineProperty(unavailable.root, 'sessionStorage', {get() { throw new Error('Disabled'); }});
    unavailable.events.pageswap(unavailable.transition({entry: {url: level}}));
    unavailable.events.pagereveal({});
    console.log('Passed: native Explore transition direction, cleanup, history, directory restoration and motion fallbacks.');
};
