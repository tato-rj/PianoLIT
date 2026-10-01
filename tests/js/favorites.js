const assert = require('assert');
const fs = require('fs');
const path = require('path');
const vm = require('vm');

module.exports = async function () {
    const handlers = {};
    const panelEvents = {};
    const overlayEvents = {};
    const sheet = {addEventListener(name, handler) { panelEvents[name] = handler; }};
    const options = {
        addEventListener(name, handler) { this.hidden = handler; },
        hide() { this.hiding = true; }
    };
    let openPanels = [options];
    const document = {
        getElementById(id) { assert.strictEqual(id, 'save-to-offcanvas'); return sheet; },
        addEventListener(name, handler) { overlayEvents[name] = handler; },
        querySelectorAll() { return openPanels; }
    };
    let getRequests = 0;
    let resolvePost;
    let resolveGet;
    let content = 'before';
    let sheetContent = '';
    let opened = false;
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
    const openButton = {
        data() { return '/piece/save-to'; },
        disable() { this.disabled = true; return this; },
        enable() { this.disabled = false; return this; }
    };
    const sheetBody = {html(value) { sheetContent = value; }};
    const flag = {find() { return {toggleClass() { flagFilled = !flagFilled; }}; }};
    const $ = value => {
        if (value === document) return {on(event, selector, handler) { handlers[selector] = handler; }};
        if (value === button) return button;
        if (value === openButton) return openButton;
        if (value === '#flag-1') return flag;
        if (value === '#save-to-offcanvas-content') return sheetBody;
        throw new Error('Unexpected selector: ' + value);
    };
    const context = vm.createContext({document, $, window: {bootstrap: {Offcanvas: {getOrCreateInstance(element) {
                if (element === options) return options;
                assert.strictEqual(element, sheet);
                return {show(trigger) {
                    assert.strictEqual(trigger, openButton);
                    const event = {target: sheet, relatedTarget: trigger, preventDefault() { this.defaultPrevented = true; }};
                    panelEvents['show.bs.offcanvas'](event);
                    overlayEvents['show.bs.offcanvas'](event);
                    if (!event.defaultPrevented) {
                        opened = true;
                        panelEvents['shown.bs.offcanvas']();
                    }
                }};
            }}}}, axios: {
            get() { getRequests++; return new Promise(resolve => { resolveGet = resolve; }); },
            post() { return new Promise(resolve => { resolvePost = resolve; }); }
        },
            setTimeout() { throw new Error('A successful save must not wait for a timer'); }});
    for (const name of ['offcanvas', 'favorites']) {
        vm.runInContext(fs.readFileSync(path.join(__dirname, '../../resources/js/components/' + name + '.js'), 'utf8'), context);
    }

    const firstShow = {relatedTarget: openButton, preventDefault() { this.prevented = true; }};
    panelEvents['show.bs.offcanvas'](firstShow);
    assert.strictEqual(firstShow.prevented, true);
    assert.strictEqual(opened, false, 'The sheet waits for its folder content');
    resolveGet({data: '<div id="favorite-folders-container"></div>'});
    await new Promise(resolve => setImmediate(resolve));
    assert.strictEqual(opened, false, 'Loaded folders wait for the Options panel to finish closing');
    assert(options.hiding);
    openPanels = [];
    options.hidden();
    assert.strictEqual(opened, true);
    assert.strictEqual(getRequests, 1, 'The Bootstrap handoff reuses the loaded folder content');
    assert(sheetContent.includes('favorite-folders-container'));
    assert.strictEqual(openButton.disabled, false);

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
