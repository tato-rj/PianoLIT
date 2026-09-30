const assert = require('assert');
const fs = require('fs');
const path = require('path');
const vm = require('vm');

module.exports = function () {
    const document = {};
    let dismiss;
    let outsideClick;
    let visible = true;
    let hasCloseButton = true;
    let closed = 0;
    let closeButtonClicked = 0;
    const closeButton = {length: 1, trigger(event) {
        assert.strictEqual(event, 'click');
        closeButtonClicked++;
        dismiss();
    }};
    const popup = {
        get length() { return visible ? 1 : 0; },
        find(selector) {
            assert.strictEqual(selector, '[data-dismiss=popup]');
            return {first() { return hasCloseButton ? closeButton : {length: 0}; }};
        },
        fadeOut() { visible = false; closed++; }
    };
    const $ = value => {
        if (value === document) return {
            ready() {},
            on(event, selector, handler) {
                if (typeof selector === 'function') outsideClick = selector;
                else if (selector === '[data-dismiss=popup]') dismiss = handler;
            }
        };
        if (value === '#bottom-popup:visible' || value === '#bottom-popup') return popup;
        if (value && typeof value.inside === 'boolean') return {
            closest(selector) {
                assert.strictEqual(selector, '#bottom-popup-content');
                return {length: value.inside ? 1 : 0};
            }
        };
        throw new Error('Unexpected selector: ' + value);
    };
    vm.runInNewContext(
        fs.readFileSync(path.join(__dirname, '../../resources/js/components/popups.js'), 'utf8'),
        {document, $}
    );

    outsideClick({target: {inside: true}});
    assert.strictEqual(closed, 0, 'Clicks inside the popup keep it open');
    outsideClick({target: {inside: false}});
    assert.strictEqual(closeButtonClicked, 1, 'Outside clicks use the existing close action');
    assert.strictEqual(closed, 1);

    visible = true;
    hasCloseButton = false;
    outsideClick({target: {inside: false}});
    assert.strictEqual(closed, 2, 'Popups without a close control also dismiss');
    outsideClick({target: {inside: false}});
    assert.strictEqual(closed, 2, 'Hidden popups are left alone');
};
