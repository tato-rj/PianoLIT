const assert = require('assert');
const fs = require('fs');
const path = require('path');
const vm = require('vm');

module.exports = async function () {
    const window = {};
    const bodyClasses = new Set();
    const body = {children: [], classList: {add: name => bodyClasses.add(name), remove: name => bodyClasses.delete(name)},
        appendChild(element) { this.children.push(element); element.parent = this; }};
    const element = tag => ({tag, style: {}, children: [], attributes: {},
        appendChild(child) { this.children.push(child); child.parent = this; },
        remove() { if (this.parent) this.parent.children.splice(this.parent.children.indexOf(this), 1); },
        setAttribute(name, value) { this.attributes[name] = String(value); },
        getContext: () => ({})});
    const head = {children: [], appendChild(child) { this.children.push(child); child.parent = this; }};
    const document = {body, head, createElement: element, createElementNS: (namespace, tag) => element(tag)};
    vm.runInNewContext(fs.readFileSync(path.join(__dirname, '../../resources/js/views/score-editor.js'), 'utf8'), {window, document, Blob: class { constructor(parts) { this.size = Buffer.byteLength(parts.join("")); } }});
    const {Markings, Editor, point, roundPoint} = window.ScoreEditor;
    const a = {id: 'a', page: 1, type: 'text', text: '1 2 3'};
    const b = {id: 'b', page: 2, type: 'stroke', points: [{x: .3, y: .4}]};
    let database = {revision: 0, marks: []};
    const store = new Markings(async data => { database = {revision: data.revision + 1, marks: data.marks}; return {revision: database.revision}; });
    store.load(database);
    assert.strictEqual(store.dirty, false);
    store.replace([a]); store.replace([a, b]); store.undo();
    assert.strictEqual(store.marks.length, 1);
    store.redo(); assert.strictEqual(store.marks[1].page, 2);
    await store.flush(); assert.strictEqual(store.dirty, false);
    const reopened = new Markings(() => {});
    reopened.load(database); assert.strictEqual(reopened.marks[0].text, '1 2 3');
    store.replace([]); await store.flush(); assert.strictEqual(database.marks.length, 0, 'Erasing every mark must persist');
    assert.throws(() => reopened.load('<html>sign in</html>'));

    const textStore = new Markings(async data => ({revision: data.revision + 1}));
    textStore.load({revision: 0, marks: []});
    const editor = Object.create(Editor.prototype);
    editor.store = textStore;
    editor.paint = () => {};
    editor.message = () => {};
    let removed = false;
    const input = {value: '   ', remove: () => { removed = true; }};
    editor.textDraft = {mark: a, input, changed: false};
    editor.syncText();
    assert.strictEqual(textStore.dirty, false, 'Empty text must not create a save');
    input.value = '1'; editor.syncText();
    input.value = '1 2'; editor.syncText();
    assert.strictEqual(textStore.marks[0].text, '1 2', 'Typing updates the same annotation in place');
    assert.strictEqual(textStore.undoStack.length, 1, 'One typing session is one undo action');
    await textStore.flush();
    assert.strictEqual(textStore.dirty, false, 'Text saves while the cursor is still active');
    input.value = ''; editor.syncText(); editor.finishText();
    assert.strictEqual(textStore.marks.length, 0, 'Clearing saved text removes its annotation');
    assert.strictEqual(editor.textDraft, null); assert.strictEqual(removed, true);
    textStore.undo(); assert.strictEqual(textStore.marks.length, 0);
    textStore.load({revision: 2, marks: [a]});
    editor.textDraft = {mark: a, input: {value: '5'}, changed: false};
    editor.syncText();
    assert.strictEqual(textStore.marks.length, 1, 'Editing existing text must not duplicate it');
    textStore.undo(); assert.strictEqual(textStore.marks[0].text, a.text);

    const pen = Object.create(Editor.prototype);
    pen.ready = true; pen.rendering = false; pen.tool = 'pen'; pen.pointerId = null; pen.page = 1;
    pen.store = new Markings(async data => ({revision: data.revision + 1}));
    pen.store.load({revision: 0, marks: []});
    pen.find = selector => selector === '[data-color]' ? {value: '#20252b'} : null;
    pen.finishText = () => {}; pen.paint = () => {};
    pen.svg = {getBoundingClientRect: () => ({left: 0, top: 0, width: 500, height: 800}),
        setPointerCapture: () => {}, hasPointerCapture: () => false};
    pen.down({clientX: 50, clientY: 80, button: 0, isPrimary: true, pointerId: 1, preventDefault: () => {}});
    pen.move({clientX: 100, clientY: 160, pointerId: 1, preventDefault: () => {}});
    pen.finishStroke();
    assert.strictEqual(pen.store.marks.length, 1, 'Pen draws when the width selector is absent');
    assert.strictEqual(pen.store.marks[0].width, .004);
    assert.strictEqual(pen.store.marks[0].points.length, 2);
    pen.page = 2; pen.tool = 'highlight'; pen.find = selector => selector === '[data-color]' ? {value: '#ffe066'} : null;
    pen.down({clientX: 50, clientY: 80, button: 0, isPrimary: true, pointerId: 2, preventDefault: () => {}});
    pen.move({clientX: 100, clientY: 160, pointerId: 2, preventDefault: () => {}});
    pen.finishStroke();
    assert.strictEqual(pen.store.marks[1].type, 'highlight');
    assert.strictEqual(pen.store.marks[1].width, .02);
    assert.strictEqual(pen.store.marks[1].color, '#ffe066');

    pen.clearAll();
    assert.strictEqual(pen.store.marks.length, 0, 'Clear all immediately removes markings across the score');
    pen.store.undo();
    assert.strictEqual(pen.store.marks.length, 2, 'Clear all can be undone');
    assert.strictEqual(pen.store.marks[1].page, 2, 'Undo restores markings on other score pages');

    let newText;
    const textTool = Object.create(Editor.prototype);
    textTool.ready = true; textTool.rendering = false; textTool.tool = 'text'; textTool.pointerId = null; textTool.page = 1;
    textTool.store = new Markings(async data => ({revision: data.revision + 1}));
    textTool.store.load({revision: 0, marks: []});
    textTool.find = selector => selector === '[data-color]' ? {value: '#20252b'} : null;
    textTool.finishText = () => {};
    textTool.beginText = mark => { newText = mark; };
    textTool.svg = {getBoundingClientRect: () => ({left: 0, top: 0, width: 500, height: 800})};
    textTool.down({clientX: 50, clientY: 80, button: 0, isPrimary: true, pointerId: 1,
        target: {closest: () => null}, preventDefault: () => {}});
    assert.strictEqual(newText.size, .02, 'Text defaults to Small without a size selector');

    const dragEditor = Object.create(Editor.prototype);
    const savedText = Object.assign({}, a, {x: .25, y: .3, size: .02, color: '#20252b'});
    dragEditor.ready = true; dragEditor.rendering = false; dragEditor.tool = 'read'; dragEditor.pointerId = null; dragEditor.page = 1;
    dragEditor.store = new Markings(async data => ({revision: data.revision + 1}));
    dragEditor.store.load({revision: 0, marks: [savedText]});
    dragEditor.paint = () => {};
    dragEditor.svg = {getBoundingClientRect: () => ({left: 0, top: 0, width: 500, height: 800}),
        setPointerCapture: () => {}, hasPointerCapture: () => false, setAttribute: () => {}, removeAttribute: () => {}};
    const textHit = {closest: () => ({getAttribute: () => savedText.id})};
    dragEditor.down({target: textHit, clientX: 100, clientY: 100, button: 0, isPrimary: true, pointerId: 4, preventDefault: () => {}});
    dragEditor.move({clientX: 101, clientY: 101, pointerId: 4, preventDefault: () => {}});
    dragEditor.finishTextDrag();
    assert.strictEqual(dragEditor.store.dirty, false, 'Tapping saved text must not create an edit');
    dragEditor.down({target: textHit, clientX: 100, clientY: 100, button: 0, isPrimary: true, pointerId: 5, preventDefault: () => {}});
    let dragPrevented = false;
    dragEditor.preventTouchScroll({preventDefault: () => { dragPrevented = true; }});
    assert.strictEqual(dragPrevented, true, 'Moving text in Read mode blocks mobile scrolling');
    dragEditor.move({clientX: 150, clientY: 180, pointerId: 5, preventDefault: () => {}});
    assert.strictEqual(dragEditor.store.marks[0].x, .25, 'Dragging previews without changing the saved mark');
    dragEditor.finishTextDrag();
    assert.strictEqual(dragEditor.store.marks[0].x, .35);
    assert.strictEqual(dragEditor.store.marks[0].y, .4);
    dragEditor.store.undo();
    assert.strictEqual(dragEditor.store.marks[0].x, .25, 'Moved text remains undoable');

    const zoomEditor = Object.create(Editor.prototype);
    zoomEditor.zoom = .75; zoomEditor.page = 1; zoomEditor.render = () => Promise.resolve();
    zoomEditor.adjustZoom(-.25); assert.strictEqual(zoomEditor.zoom, .5);
    zoomEditor.adjustZoom(-.25); assert.strictEqual(zoomEditor.zoom, .5, 'Zoom stops at 50%');

    const printEditor = Object.create(Editor.prototype);
    const printedPages = [], printMessages = [];
    printEditor.pdf = {numPages: 3, getPage: async number => ({
        getViewport: ({scale}) => ({width: 612 * scale, height: 792 * scale}),
        render: () => { printedPages.push(number); return {promise: Promise.resolve()}; }
    })};
    printEditor.store = new Markings(async data => ({revision: data.revision + 1}));
    printEditor.store.load({revision: 0, marks: [
        {id: 'first', page: 1, type: 'text', text: '1', x: .2, y: .3, size: .02, color: '#20252b'},
        {id: 'last', page: 3, type: 'stroke', points: [{x: .1, y: .2}], width: .004, color: '#20252b'}
    ]});
    printEditor.page = 2; printEditor.ready = true; printEditor.rendering = false; printEditor.printing = false;
    printEditor.finishTextDrag = () => {}; printEditor.finishStroke = () => {}; printEditor.finishText = () => {};
    printEditor.controls = () => {}; printEditor.message = value => printMessages.push(value);
    printEditor.changed = () => {};
    let afterPrint, printCalls = 0;
    window.addEventListener = (event, callback) => { if (event === 'afterprint') afterPrint = callback; };
    window.print = () => { printCalls++; assert(bodyClasses.has('score-printing')); };
    await printEditor.printScore();
    assert.deepStrictEqual(printedPages, [1, 2, 3], 'Print prepares every PDF page in order');
    assert.strictEqual(printCalls, 1);
    assert.strictEqual(printEditor.page, 2, 'Printing must leave the open score page unchanged');
    assert.strictEqual(body.children.length, 1);
    assert.strictEqual(body.children[0].children.length, 3);
    assert.strictEqual(head.children[0].textContent, '@page { size: 612pt 792pt; margin: 0; }', 'Print paper matches the PDF instead of the browser default');
    assert.strictEqual(body.children[0].children[0].style.width, '98%', 'Print page nearly fills the paper with room for pagination rounding');
    assert.strictEqual(body.children[0].children[0].style.aspectRatio, '612 / 792', 'Print preserves the original PDF aspect ratio');
    assert.strictEqual(body.children[0].children[0].children[0].style.width, '100%', 'Canvas fits its print page');
    assert.strictEqual(body.children[0].children[0].children[1].children[0].tag, 'text');
    assert.strictEqual(body.children[0].children[1].children[1].children.length, 0);
    assert.strictEqual(body.children[0].children[2].children[1].children[0].tag, 'path');
    afterPrint();
    assert.strictEqual(body.children.length, 0, 'Print pages are removed after the dialog closes');
    assert.strictEqual(head.children.length, 0, 'Temporary paper size is removed after printing');
    assert.strictEqual(bodyClasses.has('score-printing'), false);
    printEditor.pdf.getPage = async () => { throw new Error('PDF page failed'); };
    await printEditor.printScore();
    assert.strictEqual(printCalls, 1, 'A missing PDF page must not print an incomplete document');
    assert.strictEqual(body.children.length, 0);
    assert(printMessages.pop().includes('Could not prepare every score page'));

    const touchEditor = Object.create(Editor.prototype);
    let blockedTouches = 0;
    const touchMove = {preventDefault: () => { blockedTouches++; }};
    for (const tool of ['pen', 'text', 'erase']) {
        touchEditor.tool = tool;
        touchEditor.preventTouchScroll(touchMove);
    }
    assert.strictEqual(blockedTouches, 3, 'Touch drags on the score cannot scroll while an editing tool is selected');
    touchEditor.tool = 'read'; touchEditor.preventTouchScroll(touchMove);
    assert.strictEqual(blockedTouches, 3, 'Read mode still permits normal touch scrolling');

    const requests = [];
    const concurrent = new Markings(data => new Promise((resolve, reject) => requests.push({data, resolve, reject})));
    concurrent.load({revision: 0, marks: []}); concurrent.replace([a]);
    const firstSave = concurrent.flush();
    concurrent.replace([a, b]); await concurrent.flush();
    assert.strictEqual(requests.length, 1, 'Only one save may be in flight');
    requests[0].resolve({revision: 1});
    for (let i = 0; i < 5; i++) await Promise.resolve();
    assert.strictEqual(requests.length, 2);
    assert.strictEqual(requests[1].data.revision, 1);
    assert.strictEqual(requests[1].data.marks.length, 2);
    requests[1].resolve({revision: 2}); await firstSave;
    assert.strictEqual(concurrent.dirty, false);

    concurrent.replace([b]);
    const failed = concurrent.flush(); requests[2].reject(new Error('offline')); await failed;
    assert.strictEqual(concurrent.state, 'error'); assert.strictEqual(concurrent.dirty, true);
    concurrent.replace([a]);
    const retry = concurrent.flush();
    assert.strictEqual(requests[3].data.marks[0], b, 'Retry the original snapshot if its response was lost');
    assert.strictEqual(requests[3].data.revision, 2);
    requests[3].resolve({revision: 3});
    for (let i = 0; i < 5; i++) await Promise.resolve();
    requests[4].resolve({revision: 4}); await retry;
    assert.strictEqual(concurrent.marks[0], a); assert.strictEqual(concurrent.dirty, false);

    concurrent.replace([b]);
    const conflict = concurrent.flush(); requests[5].reject({response: {status: 409}}); await conflict;
    assert.strictEqual(concurrent.conflict, true); assert.strictEqual(concurrent.dirty, true);
    await concurrent.flush(); assert.strictEqual(requests.length, 6, 'A conflict cannot silently overwrite another device');

    const invalid = new Markings(async () => '<html>login</html>');
    invalid.load({revision: 0, marks: []}); invalid.replace([a]); await invalid.flush();
    assert.strictEqual(invalid.state, 'error'); assert.strictEqual(invalid.dirty, true);
    const p = point({clientX: 350, clientY: 500}, {left: 100, top: 100, width: 500, height: 800});
    assert.strictEqual(p.x, .5); assert.strictEqual(p.y, .5);
    const zoomed = point({clientX: 600, clientY: 900}, {left: 100, top: 100, width: 1000, height: 1600});
    assert.strictEqual(zoomed.x, p.x); assert.strictEqual(zoomed.y, p.y);
    assert.strictEqual(roundPoint({x: 0.333333333, y: 0}).x, .33333);
    console.log('Passed: score annotation persistence, full-document printing, text dragging, zoom limits, highlighting, clear/undo, touch scrolling, page coordinates, autosave, retries and conflicts.');
};
