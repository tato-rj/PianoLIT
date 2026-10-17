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
    const accessModal = {};
    let shownModal = null;
    const document = {
        getElementById(id) { assert.strictEqual(id, 'save-to-offcanvas'); return sheet; },
        addEventListener(name, handler) { overlayEvents[name] = handler; },
        querySelectorAll(selector) {
            if (selector === '[data-favorite-piece="1"]') return hearts;
            return openPanels;
        },
        querySelector(selector) { assert.strictEqual(selector, '#piece-upgrade-modal'); return accessModal; }
    };
    let getRequests = 0;
    let resolvePost, rejectPost;
    let resolveGet, rejectGet;
    let content = 'before';
    let sheetContent = '';
    let opened = false;
    let scroll = 72;
    let disabled = false;
    let postRequests = 0;
    const hearts = [0, 1, 2].map(() => ({
        attributes: {}, filled: false,
        setAttribute(name, value) { this.attributes[name] = value; },
        querySelector() { return {classList: {toggle: (name, value) => { this.filled = value; }}}; }
    }));
    function assertHearts(saved) {
        hearts.forEach(heart => {
            assert.strictEqual(heart.filled, saved);
            assert.strictEqual(heart.attributes['aria-pressed'], String(saved));
            assert.strictEqual(heart.attributes['data-favorited'], String(saved));
        });
    }

    const list = {scrollTop(value) {
        if (value === undefined) return scroll;
        scroll = value;
        return this;
    }};
    const container = {
        length: 1,
        find(selector) {
            if (selector === 'button') return buttons;
            if (selector === '.invalid-feedback') return {text() { return {hide() {}, show() {}}; }};
            assert.strictEqual(selector, '.save-to-panel__list'); return list;
        },
        html(value) { content = value; scroll = 0; return this; }
    };
    const buttons = {
        disable() { disabled = true; return this; },
        enable() { disabled = false; return this; }
    };
    const button = {
        data() { return '/favorite/1'; },
        closest() { return container; }
    };
    const nameInput = {val() { return 'Recital'; }};
    const folderButton = {
        data(key) { return key === 'url' ? '/folders' : '#folder-name-1'; },
        disable() { return this; }, enable() { return this; },
        addLoader() { return this; }, removeLoader() { return this; },
        siblings() { return {hide() {}, show() {}}; }
    };
    const openButton = {
        data() { return '/piece/save-to'; },
        disable() { this.disabled = true; return this; },
        enable() { this.disabled = false; return this; }
    };
    const otherOpenButton = Object.assign({}, openButton, {data() { return '/another-piece/save-to'; }});
    let expectedTrigger = openButton;
    const sheetBody = {html(value) { sheetContent = value; }};
    const $ = value => {
        if (value === document) return {on(event, selector, handler) { handlers[selector] = handler; }};
        if (value === button) return button;
        if (value === folderButton) return folderButton;
        if (value === '#folder-name-1' || value === nameInput) return nameInput;
        if (value === '#favorite-folders-container') return container;
        if (value === openButton || value === otherOpenButton) return value;
        if (value === '#save-to-offcanvas-content') return sheetBody;
        throw new Error('Unexpected selector: ' + value);
    };
    const context = vm.createContext({document, $, window: {bootstrap: {Modal: {getOrCreateInstance(element) { return {show() {shownModal = element;}}; }}, Offcanvas: {getOrCreateInstance(element) {
                if (element === options) return options;
                assert.strictEqual(element, sheet);
                return {show(trigger) {
                    assert.strictEqual(trigger, expectedTrigger);
                    const event = {target: sheet, relatedTarget: trigger, preventDefault() { this.defaultPrevented = true; }};
                    panelEvents['show.bs.offcanvas'](event);
                    overlayEvents['show.bs.offcanvas'](event);
                    if (!event.defaultPrevented) {
                        opened = true;
                        panelEvents['shown.bs.offcanvas']();
                    }
                }};
            }}}}, axios: {
            get() { getRequests++; return new Promise((resolve, reject) => { resolveGet = resolve; rejectGet = reject; }); },
            post() { postRequests++; return new Promise((resolve, reject) => { resolvePost = resolve; rejectPost = reject; }); }
        },
            objFirst(object) { return Object.values(object)[0]; },
            alert(message) { throw new Error(message); },
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
    toggle.call(button);
    assert.strictEqual(postRequests, 1, 'Repeated clicks cannot race a pending save');
    assert.strictEqual(content, 'before', 'The folder state waits for the server response');
    resolvePost({data: {status: false, favorite: {piece_id: 1, is_favorited: true}, html: {list: 'removed'}}});
    await new Promise(resolve => setImmediate(resolve));
    assert.strictEqual(content, 'removed', 'The folder state updates as soon as the request succeeds');
    assert.strictEqual(scroll, 72, 'The folder list keeps its scroll position');
    assertHearts(true);

    toggle.call(button);
    resolvePost({data: {status: true, favorite: {piece_id: 1, is_favorited: true}, html: {list: 'saved'}}});
    await new Promise(resolve => setImmediate(resolve));
    assert.strictEqual(content, 'saved');
    assertHearts(true);
    toggle.call(button);
    resolvePost({data: {status: false, favorite: {piece_id: 1, is_favorited: false}, html: {list: 'unsaved'}}});
    await new Promise(resolve => setImmediate(resolve));
    assertHearts(false);
    const createFolder = handlers['button[data-submit=folder]'];
    createFolder.call(folderButton);
    toggle.call(button);
    assert.strictEqual(postRequests, 4, 'Folder creation also serializes favorite mutations');
    resolvePost({data: {favorite: {piece_id: 1, is_favorited: true}, html: {list: 'new folder saved'}}});
    await new Promise(resolve => setImmediate(resolve));
    assertHearts(true);
    assert.strictEqual(disabled, false);
    toggle.call(button);
    rejectPost({response: {data: {access_prompt: '#piece-upgrade-modal'}}});
    await new Promise(resolve => setImmediate(resolve));
    assert.strictEqual(shownModal, accessModal, 'The cap response opens the supplied Premium dialog');
    assert.strictEqual(disabled, false, 'A capped favorite restores its controls');
    assert.strictEqual(content, 'new folder saved', 'A rejected addition leaves the saved folder list intact');
    assertHearts(true);
    createFolder.call(folderButton);
    rejectPost({response: {data: {errors: {name: ['Choose a different name.']}}}});
    await new Promise(resolve => setImmediate(resolve));
    assert.strictEqual(disabled, false, 'Failed folder creation restores controls');
    assertHearts(true);
    shownModal = null;
    panelEvents['show.bs.offcanvas']({relatedTarget: openButton, preventDefault() {}});
    rejectGet({response: {data: {access_prompt: '#piece-upgrade-modal'}}});
    await new Promise(resolve => setImmediate(resolve));
    assert.strictEqual(shownModal, accessModal, 'Blocked folder access opens the supplied dialog');
    assert.strictEqual(openButton.disabled, false);

    panelEvents['show.bs.offcanvas']({relatedTarget: openButton, preventDefault() {}});
    const resolveOlderPiece = resolveGet;
    panelEvents['show.bs.offcanvas']({relatedTarget: otherOpenButton, preventDefault() {}});
    expectedTrigger = otherOpenButton;
    resolveGet({data: 'current piece folders'});
    await new Promise(resolve => setImmediate(resolve));
    resolveOlderPiece({data: 'older piece folders'});
    await new Promise(resolve => setImmediate(resolve));
    assert.strictEqual(sheetContent, 'current piece folders', 'A slow earlier request cannot replace the selected piece folders');
    assert.strictEqual(openButton.disabled, false);
    assert.strictEqual(otherOpenButton.disabled, false);
};
