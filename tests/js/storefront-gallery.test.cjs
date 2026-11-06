const assert = require('node:assert/strict');
const fs = require('node:fs');
const vm = require('node:vm');
const path = require('node:path');

module.exports = async function () {
    function element(extra = {}) {
        const classes = new Set();
        return Object.assign({events: {}, dataset: {}, attrs: {}, hidden: false,
            classList: {toggle(name, active) { if (active) classes.add(name); else classes.delete(name); }, contains: name => classes.has(name)},
            addEventListener(name, fn) { this.events[name] = fn; }, setAttribute(name, value) { this.attrs[name] = value; },
            focus() { this.focused = true; }, matches(selector) { return selector === '[data-gallery-thumb]' && this.dataset.galleryThumb !== undefined; }
        }, extra);
    }
    function gallery(colors, productId) {
        const slides = colors.map(color => element({dataset: {galleryColor: color}}));
        const thumbnails = colors.map((color, index) => element({dataset: {galleryThumb: String(index)}}));
        const empty = element(), row = element(), count = element();
        const node = element({dataset: productId ? {galleryProduct: productId, gallerySelectedColor: 'Pink'} : {},
            querySelectorAll(selector) { return selector === '[data-gallery-slide]' ? slides : selector === '[data-gallery-thumb]' ? thumbnails : []; },
            querySelector(selector) { return {'[data-gallery-empty]': empty, '[data-gallery-thumbnails]': row, '[data-gallery-count]': count}[selector] || null; }});
        return {node, slides, thumbnails, row, count, empty};
    }
    const colorGallery = gallery(['Pink', 'Blue', 'Pink', 'Ivory'], '1');
    const digital = gallery([undefined, undefined]);
    const variants = [['91', 'Pink', 'M'], ['92', 'Pink', 'L'], ['93', 'Blue', 'M'], ['94', 'Ivory', 'M'], ['95', 'White', 'M']]
        .map(([value, color, size]) => ({value, disabled: color === 'White', dataset: {color, size, price: color === 'Blue' ? '$27.00' : '$25.00', inBag: '0'}}));
    const select = element({options: variants, value: '91'});
    Object.defineProperty(select, 'selectedOptions', {get() { return variants.filter(option => option.value === this.value); }});
    function group(key) {
        const buttons = [...new Set(variants.map(variant => variant.dataset[key]))].map(value => element({dataset: {optionValue: value}}));
        return element({dataset: {optionGroup: key}, buttons, querySelectorAll() { return buttons; }});
    }
    const colors = group('color'), sizes = group('size');
    const formNodes = {'[name="variant_id"]': select, '[name="product_id"]': {value: '1'},
        '[data-option-group="color"]': colors, '[data-option-group="size"]': sizes,
        '[data-bag-label]': element(), '[data-variant-fallback]': element(), '[data-option-pickers]': element()};
    const form = element({querySelector(selector) { return formNodes[selector]; }});
    const price = element();
    const root = element({querySelectorAll(selector) {
        if (selector === '[data-shop-gallery]') return [colorGallery.node, digital.node];
        if (selector === '[data-product-options]') return [form];
        return [];
    }, querySelector(selector) { return selector === '[data-product-price]' ? price : null; }});
    // Each detail page has one gallery; the second fixture exercises the unchanged digital path independently.
    const source = fs.readFileSync(path.join(__dirname, '../../resources/js/views/storefront.js'), 'utf8');
    function run(galleries, forms) {
        root.querySelectorAll = selector => selector === '[data-shop-gallery]' ? galleries : selector === '[data-product-options]' ? forms : [];
        vm.runInNewContext(source, {document: {querySelector: () => root}});
    }
    run([colorGallery.node], [form]);
    const shown = () => colorGallery.slides.flatMap((slide, index) => slide.hidden ? [] : [index]);
    const thumbnail = index => colorGallery.thumbnails[index].events.click();
    function choose(group, value) { group.buttons.find(button => button.dataset.optionValue === value).events.click(); }
    assert.deepEqual(shown(), [0]);
    assert.equal(colorGallery.count.textContent, 'Image 1 of 2');
    assert.deepEqual(colorGallery.thumbnails.map(button => button.hidden), [false, true, false, true]);
    assert.equal(colorGallery.thumbnails[0].attrs['aria-pressed'], 'true');
    thumbnail(2); assert.deepEqual(shown(), [2]);
    assert.equal(colorGallery.thumbnails[2].attrs['aria-pressed'], 'true');
    assert.equal(colorGallery.thumbnails[2].classList.contains('border-dark'), true);
    assert.equal(colorGallery.thumbnails[0].attrs['aria-pressed'], 'false');
    choose(sizes, 'L'); assert.deepEqual(shown(), [2], 'Size changes retain the selected view');
    thumbnail(0); assert.deepEqual(shown(), [0]);
    colorGallery.node.events.keydown({key: 'ArrowLeft', target: colorGallery.thumbnails[0], preventDefault() {}});
    assert.deepEqual(shown(), [2], 'Keyboard navigation also stays within the selected color');
    assert.equal(colorGallery.thumbnails[2].focused, true, 'Keyboard navigation follows the selected thumbnail');
    colorGallery.node.events.keydown({key: 'ArrowDown', target: colorGallery.thumbnails[2], preventDefault() {}});
    assert.deepEqual(shown(), [0], 'Down wraps within the visible thumbnail rail');
    assert.equal(colorGallery.thumbnails[0].focused, true);
    colorGallery.node.events.keydown({key: 'ArrowUp', target: colorGallery.thumbnails[0], preventDefault() {}});
    assert.deepEqual(shown(), [2], 'Up moves to the previous visible thumbnail');
    thumbnail(1); assert.deepEqual(shown(), [2], 'An inactive color thumbnail cannot select another color');
    choose(colors, 'Blue'); assert.deepEqual(shown(), [1]);
    assert.equal(price.textContent, '$27.00', 'The price below the options follows the selected variant');
    assert.equal(colors.buttons.find(button => button.dataset.optionValue === 'Blue').classList.contains('border-primary'), true);
    assert.equal(colors.buttons.find(button => button.dataset.optionValue === 'Pink').classList.contains('border-primary'), false);
    assert.equal(sizes.buttons.find(button => button.dataset.optionValue === 'M').classList.contains('border-primary'), true);
    assert.equal(select.value, '93', 'Color selection still chooses an actual available size/variant');
    assert.equal(colorGallery.row.hidden, true);
    assert.equal(colorGallery.count.hidden, true);
    thumbnail(2); assert.deepEqual(shown(), [1], 'A single view cannot reveal another color');
    assert.equal(colors.buttons.find(button => button.dataset.optionValue === 'White').disabled, true);
    choose(colors, 'White'); assert.deepEqual(shown(), [1], 'A color without uploaded mockups cannot change the selected variant or gallery');
    assert.equal(select.value, '93');
    select.value = '95'; select.events.change();
    assert.equal(select.value, '93', 'The native selection cannot activate an unavailable color');
    choose(colors, 'Pink'); assert.deepEqual(shown(), [0], 'A color change starts at that color’s first view');
    assert.equal(colorGallery.empty.hidden, true);
    assert.equal(colorGallery.row.hidden, false);
    select.value = '94'; select.events.change(); assert.deepEqual(shown(), [3], 'Native select changes also select the color gallery');
    run([digital.node], []);
    assert.equal(digital.slides[0].hidden, false);
    digital.thumbnails[1].events.click();
    assert.equal(digital.slides[1].hidden, false, 'Digital galleries retain their full sequence');
    assert.equal(digital.count.textContent, 'Image 2 of 2');
    assert.equal(colorGallery.count.textContent, 'Image 1 of 1', 'Counters belong to their own galleries');
    const emptyGallery = gallery([], '1');
    variants.forEach(variant => { variant.disabled = true; });
    run([emptyGallery.node], [form]);
    assert.equal(colors.buttons.every(button => button.disabled && button.attrs['aria-pressed'] === 'false'), true);
    assert.equal(sizes.buttons.every(button => button.disabled), true);
    assert.equal(emptyGallery.empty.hidden, false, 'A product without uploaded galleries has no selectable colors');
    console.log('Passed: color-filtered thumbnail selection, active state, keyboard focus, missing/single views, size preservation and digital galleries.');
};
