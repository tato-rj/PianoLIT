const assert = require('assert');
const icons = require('../../resources/js/components/icons');

module.exports = function () {
    let callback;
    const document = {createElementNS: (ns, tag) => element(tag, '')};
    function element(tag, classes) {
        const attrs = {};
        const tokens = new Set(classes.split(' ').filter(Boolean));
        const children = [];
        const node = {
            ownerDocument: document, innerHTML: '', children,
            classList: {
                [Symbol.iterator]: () => tokens.values(), contains: t => tokens.has(t),
                add: (...values) => values.forEach(t => tokens.add(t)), remove: (...values) => values.forEach(t => tokens.delete(t))
            },
            matches: () => tag === 'i' && ['app-icon', 'fas', 'far', 'fa-solid'].some(t => tokens.has(t)),
            setAttribute: (k, v) => { attrs[k] = v; }, getAttribute: k => attrs[k], hasAttribute: k => k in attrs,
            querySelector: () => children.find(child => child.tag === 'svg'),
            querySelectorAll: () => children.filter(child => child.tag === 'i'),
            appendChild(child) { children.push(child); child.remove = () => children.splice(children.indexOf(child), 1); },
            tag
        };
        return node;
    }
    const icon = element('i', 'app-icon icon-circle-play mr-0');
    icons.refreshIcon(icon);
    assert.equal(icon.children[0].getAttribute('data-lucide-name'), 'circle-play');
    assert(icon.children[0].innerHTML.includes('<circle'));
    const oldSvg = icon.children[0];
    icons.refreshIcon(icon);
    assert.strictEqual(icon.children[0], oldSvg, 'Unchanged SVG is retained, avoiding observer loops');
    icon.classList.remove('icon-circle-play'); icon.classList.add('icon-circle-stop');
    icons.refreshIcon(icon);
    assert.equal(icon.children.length, 1);
    assert.equal(icon.children[0].getAttribute('data-lucide-name'), 'circle-stop');
    icon.classList.add('spinner-border'); icons.refreshIcon(icon);
    assert.equal(icon.children.length, 0, 'Loading spinner has no leftover icon');
    icon.classList.remove('spinner-border'); icons.refreshIcon(icon);
    assert.equal(icon.children[0].getAttribute('data-lucide-name'), 'circle-stop');

    const legacy = element('i', 'fas fa-money-bill-wave mr-1');
    icons.refreshIcon(legacy);
    assert(legacy.classList.contains('app-icon')); assert(legacy.classList.contains('mr-1'));
    assert(!legacy.classList.contains('fas')); assert.equal(legacy.children[0].getAttribute('data-lucide-name'), 'banknote');
    const favorite = element('i', 'app-icon icon-heart icon-filled');
    icons.refreshIcon(favorite);
    favorite.classList.remove('icon-filled'); icons.refreshIcon(favorite);
    assert.equal(favorite.children[0].getAttribute('data-lucide-name'), 'heart');

    const previousObserver = global.MutationObserver;
    global.MutationObserver = class { constructor(fn) { callback = fn; } observe() {} };
    try {
        document.documentElement = {};
        icons.install(document);
        const inserted = element('i', 'app-icon icon-close');
        callback([{type: 'childList', addedNodes: [inserted]}]);
        assert.equal(inserted.children[0].getAttribute('data-lucide-name'), 'x');
        inserted.classList.remove('icon-close'); inserted.classList.add('icon-maximize');
        callback([{type: 'attributes', target: inserted}]);
        assert.equal(inserted.children[0].getAttribute('data-lucide-name'), 'maximize');
        callback([{type: 'childList', addedNodes: [inserted.children[0]]}]);
        assert.equal(inserted.children.length, 1);
    } finally { global.MutationObserver = previousObserver; }
    console.log('Passed: Lucide dynamic insertion, state swaps, spinner restoration, legacy HTML, and stable wrappers.');
};
