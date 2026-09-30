const assert = require('assert');
const fs = require('fs');
const path = require('path');
const vm = require('vm');

module.exports = async function () {
    const handlers = {};
    const document = {};
    let resolvePost;
    let content = 'before';
    let scroll = 72;
    let flagFilled = false;
    let disabled = false;

    const list = {scrollTop(value) {
        if (value === undefined) return scroll;
        scroll = value;
        return this;
    }};
    const container = {
        length: 1,
        find(selector) { assert.strictEqual(selector, '.save-to-panel__list'); return list; },
        html(value) { content = value; scroll = 0; return this; }
    };
    const buttons = {
        disable() { disabled = true; return this; },
        enable() { disabled = false; return this; }
    };
    const button = {
        data(key) { return key === 'url' ? '/favorite/1' : '#flag-1'; },
        parent() { return {find() { return buttons; }}; },
        closest() { return container; }
    };
    const flag = {find() { return {toggleClass() { flagFilled = !flagFilled; }}; }};
    const $ = value => {
        if (value === document) return {on(event, selector, handler) { handlers[selector] = handler; }};
        if (value === button) return button;
        if (value === '#flag-1') return flag;
        if (value === 'a.toggle-favorite span') return {click() {}};
        throw new Error('Unexpected selector: ' + value);
    };
    vm.runInNewContext(
        fs.readFileSync(path.join(__dirname, '../../resources/js/components/favorites.js'), 'utf8'),
        {document, $, axios: {post() { return new Promise(resolve => { resolvePost = resolve; }); }},
            setTimeout() { throw new Error('A successful save must not wait for a timer'); }}
    );

    const toggle = handlers['button[data-submit=favorite]'];
    toggle.call(button);
    assert.strictEqual(disabled, true);
    assert.strictEqual(content, 'before', 'The folder state waits for the server response');
    resolvePost({data: {html: {list: 'removed'}}});
    await new Promise(resolve => setImmediate(resolve));
    assert.strictEqual(content, 'removed', 'The folder state updates as soon as the request succeeds');
    assert.strictEqual(scroll, 72, 'The folder list keeps its scroll position');
    assert.strictEqual(flagFilled, true);

    toggle.call(button);
    resolvePost({data: {html: {list: 'saved'}}});
    await new Promise(resolve => setImmediate(resolve));
    assert.strictEqual(content, 'saved');
    assert.strictEqual(flagFilled, false);
};
