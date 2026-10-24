const assert = require('assert');
const fs = require('fs');
const vm = require('vm');

module.exports = async function () {
    function node() {
        const classes = new Set();
        return {
            hidden: false, disabled: false, textContent: '', innerHTML: '', dataset: {}, style: {}, attributes: {}, events: {},
            classList: {toggle(name, on) { if (on) classes.add(name); else classes.delete(name); }, contains: name => classes.has(name)},
            addEventListener(name, callback) { this.events[name] = callback; },
            setAttribute(name, value) { this.attributes[name] = value; },
            focus() { this.focused = true; },
        };
    }
    const requests = [], alerts = [], panel = node(), form = node(), submit = node(), body = node();
    const summary = node(), error = node(), retry = node(), close = node(), badge = node(), bell = node(), link = node();
    panel.isConnected = true;
    panel.classList.toggle('show', true);
    panel.dataset = {panelUrl: '/notifications/panel', previewLimit: '12'};
    form.action = '/notifications/read';
    form.querySelector = selector => selector === 'button' ? submit : {value: 'session-csrf'};
    panel.querySelector = selector => ({
        '[data-notification-read-all]': form, '[data-notification-body]': body, '[data-notification-summary]': summary,
        '[data-notification-error]': error, '[data-notification-retry]': retry, '[data-bs-dismiss="offcanvas"]': close,
    })[selector];
    link.querySelector = selector => selector === '.admin-notification-dot' ? badge : bell;
    function historyRow(id) {
        const row = node();
        const read = node(), unread = node();
        read.dataset.url = '/notifications/read?ids[]=' + id;
        unread.dataset.url = '/notifications/unread?ids[]=' + id;
        [read, unread].forEach(button => { button.closest = selector => selector === 'tr' ? row : button; });
        return {row, read, unread};
    }
    const first = historyRow('first'), second = historyRow('second');
    const actions = [first.read, first.unread, second.read, second.unread];
    const document = {
        events: {}, getElementById: () => panel,
        querySelectorAll: selector => selector === '.notifications-link' ? [link] : actions,
        addEventListener(name, callback) { this.events[name] = callback; },
    };
    const window = {location: {href: 'http://admin.test/?days=30&platform=web'}, alert: message => alerts.push(message)};
    function fetch(url, options) { return new Promise((resolve, reject) => requests.push({url, options, resolve, reject})); }
    vm.runInNewContext(fs.readFileSync('resources/js/views/admin-notifications.js', 'utf8'), {document, window, fetch, URL});
    const tick = () => new Promise(resolve => setImmediate(resolve));
    const show = () => panel.events['show.bs.offcanvas']();
    const markAll = () => form.events.submit({preventDefault() {}});
    const click = button => document.events.click({target: button, preventDefault() {}});
    function respond(unreadCount, html = '<p>Alerts</p>') {
        requests.at(-1).resolve({ok: true, json: async () => ({unreadCount, html})});
    }

    assert.strictEqual(requests.length, 0, 'Hidden notification panels do no background fetching.');
    show(); show();
    assert.strictEqual(requests.length, 1, 'Repeated opening cannot queue duplicate requests.');
    assert.strictEqual(requests[0].options.method, 'GET');
    assert.strictEqual(submit.disabled, true);
    respond(250); await tick();
    assert.strictEqual(summary.textContent, '250 unread notifications · Showing latest 12');
    assert.strictEqual(badge.textContent, 250);
    assert.strictEqual(badge.hidden, false);
    assert.strictEqual(form.hidden, false);
    assert(bell.classList.contains('icon-filled'));

    markAll(); markAll(); show();
    assert.strictEqual(requests.length, 2, 'A mutation blocks duplicate submits and competing panel loads.');
    assert.strictEqual(requests[1].url, '/notifications/read');
    assert.strictEqual(requests[1].options.method, 'POST');
    assert.strictEqual(requests[1].options.credentials, 'same-origin');
    assert.strictEqual(requests[1].options.headers['X-CSRF-TOKEN'], 'session-csrf');
    requests[1].reject(new Error('Offline')); await tick();
    assert.strictEqual(error.textContent, 'Offline');
    assert.strictEqual(error.hidden, false);
    assert.strictEqual(submit.disabled, false);
    assert.strictEqual(badge.textContent, 250, 'Failed writes preserve the unread state.');
    assert.strictEqual(form.hidden, false);

    markAll(); respond(0, '<p>No new notifications</p>'); await tick();
    assert.strictEqual(body.innerHTML, '<p>No new notifications</p>');
    assert.strictEqual(summary.textContent, "You're all caught up");
    assert.strictEqual(badge.hidden, true);
    assert.strictEqual(form.hidden, true);
    assert.strictEqual(close.focused, true);
    assert(!bell.classList.contains('icon-filled'));
    assert.strictEqual(link.attributes['aria-label'], 'Notifications (0 unread)');
    assert.strictEqual(window.location.href, 'http://admin.test/?days=30&platform=web', 'Marking read never navigates or drops dashboard filters.');
    [first, second].forEach(item => {
        assert(item.row.classList.contains('opacity-4'));
        assert.strictEqual(item.read.style.display, 'none');
        assert.strictEqual(item.unread.style.display, 'inline-block');
    });

    click(first.unread); respond(1); await tick();
    assert.strictEqual(first.unread.disabled, false);
    assert.strictEqual(first.read.style.display, 'inline-block');
    assert(!first.row.classList.contains('opacity-4'));
    assert(second.row.classList.contains('opacity-4'), 'History mutations only change the matching row.');
    assert.strictEqual(summary.textContent, '1 unread notification');
    click(first.read); respond(0); await tick();
    assert(first.row.classList.contains('opacity-4'));

    show(); requests.at(-1).resolve({ok: false, status: 419}); await tick();
    assert.match(error.textContent, /session has expired/);
    assert.strictEqual(retry.hidden, false);
    assert.strictEqual(panel.attributes['aria-busy'], 'false');
    retry.events.click(); respond(2); await tick();
    assert.strictEqual(summary.textContent, '2 unread notifications');
    assert.strictEqual(error.hidden, true);

    click(first.read); requests.at(-1).resolve({ok: false, status: 500}); await tick();
    assert.strictEqual(alerts.length, 1, 'Closed-panel history failures remain visible.');
    assert.strictEqual(first.read.disabled, false);

    show(); requests.at(-1).resolve({ok: true, json: async () => ({unreadCount: 'bad', html: ''})}); await tick();
    assert.strictEqual(error.hidden, false);
    assert.strictEqual(badge.textContent, 2, 'Malformed replies never clear the current state.');
    show(); panel.isConnected = false; respond(99, '<p>Late</p>'); await tick();
    assert.notStrictEqual(body.innerHTML, '<p>Late</p>', 'Responses cannot modify a replaced page.');
    console.log('Passed: lazy notification loading, single-request marking, CSRF, duplicate guards, count/history sync, failures/retry and detached responses.');
};

if (require.main === module) module.exports().catch(error => { console.error(error); process.exitCode = 1; });
