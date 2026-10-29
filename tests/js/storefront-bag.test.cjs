const assert = require('node:assert/strict');
const fs = require('node:fs');
const vm = require('node:vm');
const path = require('node:path');

module.exports = async function () {
    function element(extra = {}) {
        return Object.assign({events: {}, attrs: {}, dataset: {},
            addEventListener(name, fn) { this.events[name] = fn; },
            setAttribute(name, value) { this.attrs[name] = value; },
            removeAttribute(name) { delete this.attrs[name]; }
        }, extra);
    }
    function product(id, inBag) {
        const options = [['91', 'Mint', 'M', inBag], ['92', 'Mint', 'L', 0], ['93', 'Black', 'L', 1]]
            .map(([value, color, size, quantity]) => ({value, dataset: {color, size, inBag: String(quantity), price: '$28.00'}}));
        const select = element({options, value: '91'});
        Object.defineProperty(select, 'selectedOptions', {get() { return options.filter(option => option.value === this.value); }});
        function group(key) {
            const buttons = [...new Set(options.map(option => option.dataset[key]))].map(value => element({dataset: {optionValue: value}}));
            return element({dataset: {optionGroup: key}, buttons, querySelectorAll: () => buttons});
        }
        const color = group('color'), size = group('size');
        const label = element(), button = element({disabled: false});
        const nodes = {'[name="variant_id"]': select, '[name="product_id"]': {value: id},
            '[data-option-group="color"]': color, '[data-option-group="size"]': size,
            '[data-bag-label]': label, '[type="submit"]': button,
            '[data-variant-fallback]': element(), '[data-option-pickers]': element()};
        const form = element({action: '/bag/items', reportValidity: () => true, querySelector: selector => nodes[selector]});
        return {form, select, options, color, size, label, button};
    }
    const current = product('1', 2), other = product('2', 5);
    const quickButton = element({disabled: false});
    const quick = element({action: '/bag/items', reportValidity: () => true, querySelector(selector) {
        return {'[type="submit"]': quickButton,
            '[name="product_id"]': {value: '1'}, '[name="variant_id"]': {value: '91'}}[selector];
    }});
    const count = element();
    const output = {replaceChildren(content) { this.content = content; }};
    const timers = new Map();
    let timerId = 0;
    function template(type) {
        return {content: {cloneNode() {
            const message = {};
            return {type, message, querySelector: () => message};
        }}};
    }
    const feedback = {'[data-feedback-output]': output,
        '[data-feedback-template="success"]': template('success'),
        '[data-feedback-template="error"]': template('error')};
    const root = element({querySelectorAll(selector) {
        if (selector === '[data-product-options]') return [current.form, other.form];
        if (selector === '[data-add-to-bag]') return [current.form, other.form, quick];
        if (selector === '[data-add-to-bag] [type="submit"]') return [current.button, other.button, quickButton];
        if (selector === '[data-bag-count]') return [count];
        return [];
    }, querySelector: selector => feedback[selector] || null});
    const requests = [];
    vm.runInNewContext(fs.readFileSync(path.join(__dirname, '../../resources/js/views/storefront.js'), 'utf8'), {
        document: {querySelector: () => root},
        FormData: class { constructor(form) { this.variant = form.querySelector('[name="variant_id"]').value; } },
        fetch: (url, options) => new Promise(resolve => requests.push({resolve, options})),
        clearTimeout(id) { timers.delete(id); }, setTimeout(fn) { timers.set(++timerId, fn); return timerId; }
    });
    function choose(group, value) { group.buttons.find(button => button.dataset.optionValue === value).events.click(); }
    async function resolve(request, payload, ok = true) { request.resolve({ok, status: ok ? 200 : 422, json: async () => payload}); }
    const submit = () => current.form.events.submit({preventDefault() {}});

    assert.equal(current.label.textContent, 'In bag (2) · Add more');
    choose(current.size, 'L');
    assert.equal(current.label.textContent, 'Add to bag', 'An unbagged size does not inherit another size count');
    choose(current.color, 'Black');
    assert.equal(current.label.textContent, 'In bag (1) · Add more', 'Color changes use the exact variant');
    choose(current.color, 'Mint');
    let pending = submit();
    assert.equal(current.button.disabled, true);
    assert.equal(requests[0].options.body.variant, '92');
    choose(current.size, 'M');
    await resolve(requests[0], {count: 9, message: 'Added to your bag', item: {product_id: 1, variant_id: 92, quantity: 1}});
    await pending;
    assert.equal(current.label.textContent, 'In bag (2) · Add more', 'A delayed reply must not label the newly selected variant');
    assert.equal(other.label.textContent, 'In bag (5) · Add more', 'Other products with matching variant IDs stay unchanged');
    assert.equal(count.textContent, 9);
    assert.equal(current.button.disabled, false);
    choose(current.size, 'L');
    assert.equal(current.label.textContent, 'In bag (1) · Add more');
    pending = submit();
    await resolve(requests[1], {count: 10, message: 'Added to your bag', item: {product_id: 1, variant_id: 92, quantity: 2}});
    await pending;
    assert.equal(current.label.textContent, 'In bag (2) · Add more', 'Confirmed repeat additions update the count');
    pending = submit();
    await resolve(requests[2], {errors: {quantity: ['You can add up to 10 of each option.']}}, false);
    await pending;
    assert.equal(current.label.textContent, 'In bag (2) · Add more', 'Rejected additions never increment the indication');
    assert.equal(output.content.type, 'error');
    assert.equal(output.content.message.textContent, 'You can add up to 10 of each option.');
    assert.equal(timers.size, 0, 'An earlier success timer cannot remove the current error alert');
    assert.equal(current.button.disabled, false);
    current.select.value = '93'; current.select.events.change();
    assert.equal(current.label.textContent, 'In bag (1) · Add more');
    pending = quick.events.submit({preventDefault() {}});
    await resolve(requests[3], {count: 11, message: 'Added to your bag', item: {product_id: 1, variant_id: 91, quantity: 3}});
    await pending;
    assert.equal(quickButton.disabled, false);
    assert.equal(count.textContent, 11);
    assert.equal(output.content.type, 'success');
    assert.equal(output.content.message.textContent, 'Added to your bag');
    [...timers.values()].forEach(fn => fn());
    assert.equal(output.content, undefined, 'Successful AJAX notices retain their temporary lifetime');
    console.log('Passed: exact Shop variant bag counts, confirmed additions, selection races, failures and product isolation.');
};
