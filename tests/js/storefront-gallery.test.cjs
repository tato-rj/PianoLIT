const assert = require('node:assert/strict');
const fs = require('node:fs');
const vm = require('node:vm');
const path = require('node:path');

module.exports = async function () {
    function element(extra = {}) {
        return Object.assign({events: {}, dataset: {}, hidden: false,
            classList: {toggle() {}}, addEventListener(name, fn) { this.events[name] = fn; }, setAttribute() {}
        }, extra);
    }
    function gallery(colors, productId) {
        const slides = colors.map(color => element({dataset: {galleryColor: color}}));
        const arrows = [-1, 1].map(step => element({dataset: {galleryStep: String(step)}}));
        const empty = element();
        const node = element({dataset: productId ? {galleryProduct: productId, gallerySelectedColor: 'Pink'} : {},
            querySelectorAll(selector) { return selector === '[data-gallery-slide]' ? slides : arrows; },
            querySelector() { return empty; }});
        return {node, slides, arrows, empty};
    }
    const colorGallery = gallery(['Pink', 'Blue', 'Pink', 'Ivory'], '1');
    const digital = gallery([undefined, undefined]);
    const variants = [['91', 'Pink', 'M'], ['92', 'Pink', 'L'], ['93', 'Blue', 'M'], ['94', 'Ivory', 'M'], ['95', 'White', 'M']]
        .map(([value, color, size]) => ({value, dataset: {color, size, price: '$25.00', inBag: '0'}}));
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
    const count = element();
    const root = element({querySelectorAll(selector) {
        if (selector === '[data-shop-gallery]') return [colorGallery.node, digital.node];
        if (selector === '[data-product-options]') return [form];
        return [];
    }, querySelector(selector) { return selector === '[data-gallery-count]' ? count : null; }});
    // Each detail page has one gallery; the second fixture exercises the unchanged digital path independently.
    const source = fs.readFileSync(path.join(__dirname, '../../resources/js/views/storefront.js'), 'utf8');
    function run(galleries, forms) {
        root.querySelectorAll = selector => selector === '[data-shop-gallery]' ? galleries : selector === '[data-product-options]' ? forms : [];
        vm.runInNewContext(source, {document: {querySelector: () => root}});
    }
    run([colorGallery.node], [form]);
    const shown = () => colorGallery.slides.flatMap((slide, index) => slide.hidden ? [] : [index]);
    const next = () => colorGallery.arrows[1].events.click();
    function choose(group, value) { group.buttons.find(button => button.dataset.optionValue === value).events.click(); }
    assert.deepEqual(shown(), [0]);
    assert.equal(count.textContent, '1 / 2');
    next(); assert.deepEqual(shown(), [2]);
    choose(sizes, 'L'); assert.deepEqual(shown(), [2], 'Size changes retain the selected view');
    next(); assert.deepEqual(shown(), [0], 'Next wraps inside Pink only');
    colorGallery.node.events.keydown({key: 'ArrowLeft', preventDefault() {}});
    assert.deepEqual(shown(), [2], 'Keyboard navigation also stays within the selected color');
    choose(colors, 'Blue'); assert.deepEqual(shown(), [1]);
    assert.equal(select.value, '93', 'Color selection still chooses an actual available size/variant');
    assert.equal(colorGallery.arrows[0].hidden, true);
    assert.equal(count.hidden, true);
    next(); assert.deepEqual(shown(), [1], 'A single view cannot reveal another color');
    choose(colors, 'White'); assert.deepEqual(shown(), []);
    assert.equal(colorGallery.empty.hidden, false, 'A missing color gallery never falls back to another color');
    assert.equal(count.textContent, '0 / 0');
    choose(colors, 'Pink'); assert.deepEqual(shown(), [0], 'A color change starts at that color’s first view');
    assert.equal(colorGallery.empty.hidden, true);
    assert.equal(colorGallery.arrows[1].hidden, false);
    select.value = '94'; select.events.change(); assert.deepEqual(shown(), [3], 'Native select changes also select the color gallery');
    run([digital.node], []);
    assert.equal(digital.slides[0].hidden, false);
    digital.arrows[1].events.click();
    assert.equal(digital.slides[1].hidden, false, 'Digital galleries retain their full sequence');
    console.log('Passed: manual Shop color galleries, isolated image navigation, missing colors, size preservation and digital galleries.');
};
