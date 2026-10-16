const assert = require('assert');
const fs = require('fs');
const path = require('path');
const vm = require('vm');

// Small DOM fixture: exercise the real event handlers without a browser dependency.
function fixture() {
    class Element {
        constructor(tag = 'div', attributes = {}) {
            this.tagName = tag;
            this.attributes = {...attributes};
            this.children = [];
            this.events = {};
            this.value = '';
            this.scrollHeight = 24;
            const classes = new Set();
            this.classList = {
                toggle(name, on) { if (on) classes.add(name); else classes.delete(name); },
                remove(name) { classes.delete(name); }
            };
            this.style = {values: {}, priorities: {}, setProperty: (property, value, priority) => {
                this.style.values[property] = value;
                this.style.priorities[property] = priority;
                this.attributes.style = JSON.stringify(this.style.values);
            }};
            if (attributes.style) this.restoreStyle(attributes.style);
        }
        restoreStyle(value) {
            this.style.values = {};
            this.style.priorities = {};
            value.split(';').forEach(declaration => {
                const [property, raw] = declaration.split(':');
                if (!raw) return;
                this.style.values[property.trim()] = raw.replace('!important', '').trim();
                this.style.priorities[property.trim()] = raw.includes('!important') ? 'important' : '';
            });
        }
        getAttribute(name) { return this.attributes[name] === undefined ? null : this.attributes[name]; }
        hasAttribute(name) { return this.getAttribute(name) !== null; }
        setAttribute(name, value) {
            this.attributes[name] = String(value);
            if (name === 'style') this.restoreStyle(value);
        }
        removeAttribute(name) {
            delete this.attributes[name];
            if (name === 'style') this.restoreStyle('');
        }
        appendChild(child) {
            if (child.tagName === 'fragment') child.children.forEach(node => this.appendChild(node));
            else { this.children.push(child); child.parentNode = this; }
        }
        addEventListener(name, callback) { this.events[name] = callback; }
        emit(name, event = {}) { this.events[name]({...event, target: this}); }
        querySelector(selector) { return this.selectors && this.selectors[selector] || null; }
        closest() { return this.sample || this.parentNode && this.parentNode.closest(); }
    }

    function sample(name, properties, originalStyle) {
        const node = new Element('article', {'data-ds-properties': properties, 'data-ds-label': name});
        node.preview = new Element('div', originalStyle ? {style: originalStyle} : {});
        node.preview.base = {'border-radius': '32px', border: '1px solid silver', padding: '8px'};
        node.output = new Element('dl');
        node.selectors = {'[data-ds-preview]': node.preview, '[data-ds-specs]': node.output};
        node.preview.sample = node;
        node.output.sample = node;
        return node;
    }

    const first = sample('Container', 'border,border-radius,padding', 'padding: 4px !important; color: purple;');
    const second = sample('Other container', 'border-radius');
    const table = sample('Table', 'border-radius');
    const tableWrapper = new Element();
    tableWrapper.selectors = {'.admin-table': table.preview};
    table.selectors['[data-ds-preview]'] = tableWrapper;
    const icon = sample('Icon', 'width');
    icon.preview.base.width = '32px';
    const iconWrapper = new Element('span', {'data-ds-icon': ''});
    iconWrapper.selectors = {svg: icon.preview};
    icon.selectors['[data-ds-preview]'] = iconWrapper;
    const color = sample('Brand color', 'background-color');
    color.preview.setAttribute('data-ds-color', '');
    color.preview.base['background-color'] = 'rgb(12, 34, 56)';
    const samples = [first, second, table, icon, color];
    const resetAll = new Element('button');
    const gallery = new Element();
    gallery.selectors = {'[data-ds-reset-all]': resetAll};
    gallery.querySelectorAll = selector => selector === '[data-ds-sample]' ? samples : [];
    const document = {
        activeElement: null,
        documentElement: new Element(),
        querySelector() { return gallery; },
        createElement(tag) { return new Element(tag); },
        createDocumentFragment() { return new Element('fragment'); }
    };
    const frames = new Map();
    const events = {};
    let frameId = 0;
    const window = {
        CSS: {supports: (property, value) => !value.includes('invalid') && !value.includes(';')},
        getComputedStyle: element => ({getPropertyValue: property => element.style.values[property] || (element.base || {})[property] || ''}),
        addEventListener(name, callback) { events[name] = callback; },
        cancelAnimationFrame(id) { frames.delete(id); },
        requestAnimationFrame(callback) { frames.set(++frameId, callback); return frameId; }
    };
    vm.runInNewContext(fs.readFileSync(path.join(__dirname, '../../resources/js/views/admin-design-system.js'), 'utf8'), {
        window, document, Element
    });

    const fields = node => node.output.children.filter(child => child.tagName === 'dd').map(child => child.children[0]);
    return {
        first, second, table, tableWrapper, icon, iconWrapper, color, resetAll, document, gallery,
        fields,
        edit(node, index, value) { const field = fields(node)[index]; field.value = value; field.emit('input'); return field; },
        reset(node) { node.children[node.children.length - 1].children[1].emit('click'); },
        resize() { events.resize({type: 'resize'}); [...frames.values()].forEach(callback => callback()); frames.clear(); }
    };
}

module.exports = function () {
    const page = fixture();
    const original = page.first.preview.getAttribute('style');
    const radius = page.fields(page.first)[1];
    assert.strictEqual(radius.value, '32px');
    assert.strictEqual(page.fields(page.first)[2].value, '4px');
    assert.strictEqual(page.fields(page.color)[0].value, '#0C2238');
    assert(page.resetAll.disabled);

    page.document.activeElement = radius;
    page.edit(page.first, 1, '12px 24px');
    assert.strictEqual(page.first.preview.style.values['border-radius'], '12px 24px');
    assert.strictEqual(page.first.preview.style.priorities['border-radius'], 'important', 'Preview overrides important utility classes');
    assert.strictEqual(page.second.preview.getAttribute('style'), null, 'Only this sample changes');
    assert.strictEqual(page.first.preview.style.values.color, 'purple', 'Unedited original inline declarations survive');
    assert.strictEqual(page.first.preview.style.priorities.padding, 'important');
    assert(!page.resetAll.disabled);
    page.resize();
    page.gallery.events.focusin({type: 'focusin', target: radius});
    page.resize();
    assert.strictEqual(page.fields(page.first)[1], radius, 'Refresh preserves the editor node and focus');
    assert.strictEqual(page.document.activeElement, radius);
    assert.strictEqual(radius.value, '12px 24px', 'Keep authored values and drafts intact');

    page.edit(page.first, 1, 'invalid radius');
    assert.strictEqual(radius.getAttribute('aria-invalid'), 'true');
    assert.strictEqual(page.first.preview.style.values['border-radius'], '12px 24px', 'Invalid CSS leaves the last valid preview intact');
    page.resize();
    assert.strictEqual(radius.value, 'invalid radius', 'Do not erase invalid drafts during measurement');
    let escaped = false;
    radius.emit('keydown', {key: 'Escape', preventDefault() { escaped = true; }});
    assert(escaped);
    assert.strictEqual(radius.value, '12px 24px');
    assert.strictEqual(radius.getAttribute('aria-invalid'), 'false');

    page.edit(page.first, 0, '2px dashed blue');
    page.edit(page.first, 1, '');
    assert.strictEqual(page.first.preview.style.values['border-radius'], undefined, 'Clearing removes only this override');
    assert.strictEqual(page.first.preview.style.values.border, '2px dashed blue');
    page.document.activeElement = null;
    page.reset(page.first);
    assert.strictEqual(page.first.preview.getAttribute('style'), original, 'Reset restores the exact original style attribute');
    assert.strictEqual(radius.value, '32px');
    assert(page.resetAll.disabled);

    page.edit(page.table, 0, '6px');
    assert.strictEqual(page.table.preview.style.values['border-radius'], '6px');
    assert.strictEqual(page.tableWrapper.getAttribute('style'), null, 'Table edits reach the actual component');
    page.edit(page.icon, 0, '48px');
    assert.strictEqual(page.icon.preview.style.values.width, '48px');
    assert.strictEqual(page.iconWrapper.getAttribute('style'), null, 'Icon edits reach the SVG');
    page.edit(page.second, 0, 'invalid');
    page.resetAll.emit('click');
    [page.second, page.table, page.icon].forEach(node => assert.strictEqual(node.preview.getAttribute('style'), null));
    assert.strictEqual(page.fields(page.second)[0].getAttribute('aria-invalid'), 'false');
    assert.strictEqual(page.color.children[0].children[0].textContent, 'Edit a value to preview', 'Reset all leaves untouched live regions quiet');
    assert(page.resetAll.disabled);

    page.second.preview.base['border-radius'] = '16px';
    page.resize();
    assert.strictEqual(page.fields(page.second)[0].value, '16px', 'Unedited readings still follow responsive and interaction states');
};
