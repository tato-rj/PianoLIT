const assert = require('assert');
const mount = require('../../resources/js/views/explore');

module.exports = function () {
    function node() {
        return {style: {}, children: [], events: {}, value: '',
            appendChild(child) { this.children.push(child); },
            setAttribute() {},
            addEventListener(name, fn) { this.events[name] = fn; },
            focus() { this.focused = true; }
        };
    }
    function setup(signedIn, saved) {
        const input = node(), erase = node(), list = node(), container = node(), form = node();
        container.querySelector = () => list;
        form.querySelector = selector => selector === '[name="search"]' ? input : erase;
        form.requestSubmit = () => { form.submitted = input.value; form.events.submit(); };
        const page = {querySelector: selector => selector === '#search-form' ? form : container};
        let reads = 0, writes = [];
        mount({app: {user: signedIn ? {id: 1} : null},
            document: {getElementById: () => page, createElement: node, createTextNode: text => ({text})},
            getCookie() { reads++; return saved; },
            setCookie(name, value) { writes.push(JSON.parse(value)); }
        });
        return {input, erase, list, container, form, writes, reads};
    }
    const guest = setup(false, '["Chopin"]');
    guest.input.value = 'Bach'; guest.form.events.submit();
    assert.strictEqual(guest.reads, 0);
    assert.strictEqual(guest.list.children.length, 0);
    assert.strictEqual(guest.writes.length, 0);
    for (const cookie of ['{broken', 'null', '{}', '42']) assert.strictEqual(setup(true, cookie).list.children.length, 0);
    const signed = setup(true, JSON.stringify(['<b>Chopin</b>', null, 12, 'Scarlatti']));
    assert.strictEqual(signed.list.children.length, 2);
    assert.strictEqual(signed.list.children[0].children[1].text, '<b>Chopin</b>', 'Recent text is a text node, never markup');
    signed.list.children[1].events.click();
    assert.strictEqual(signed.form.submitted, 'Scarlatti');
    assert.strictEqual(signed.writes[0][0], 'Scarlatti');
    signed.erase.events.click();
    assert.strictEqual(signed.input.value, '');
    assert(signed.input.focused);
    const bounded = setup(true, JSON.stringify(['one', 'two', 'three', 'four', 'five', 'six']));
    bounded.input.value = 'new'; bounded.form.events.submit();
    assert.deepStrictEqual(bounded.writes[0], ['new', 'one', 'two', 'three', 'four']);
    bounded.input.value = ' '; bounded.form.events.submit();
    assert.strictEqual(bounded.writes.length, 1);
    console.log('Passed: Explore guest history isolation, malformed cookies, literal text, keyboard controls and bounded recent searches.');
};
