const { test } = require('node:test');
const assert = require('node:assert/strict');
const fs = require('node:fs');
const path = require('node:path');
const vm = require('node:vm');

function setup() {
    const elements = new Map();
    const handlers = new Map();
    let hides = 0;
    const dismiss = () => {
        let prevented = false;
        handlers.get('hide.bs.modal.duelExit')?.({ preventDefault() { prevented = true; } });
        if (!prevented) hides++;
        return prevented;
    };
    const element = selector => {
        if (!elements.has(selector)) elements.set(selector, { hidden: false, textContent: '', dataset: {}, querySelector: child => element(`${selector} ${child}`) });
        return elements.get(selector);
    };
    const root = { dataset: {}, querySelector: element };
    const context = vm.createContext({ document: { getElementById: () => root }, $: node => ({
        text(value) { node.textContent = value; return this; }, toggle(value) { node.hidden = !value; },
        off(name) { handlers.delete(name); return this; }, on(name, handler) { handlers.set(name, handler); return this; },
        modal(action) { if (action === 'hide') dismiss(); },
    }) });
    const source = fs.readFileSync(path.join(__dirname, '../../../resources/js/games/duel/dialog.js'), 'utf8').replace(/^export /gm, '');
    vm.runInContext(source + '\nglobalThis.show = dialog; globalThis.close = closeDialog;', context);
    return { show: context.show, close: context.close, element, root, dismiss, hides: () => hides };
}

test('ready and waiting screens explain the next step and show each player’s actual readiness', () => {
    const s = setup(); s.show({ phase: 'ready', ready: true, leave: true, opponentReady: true });
    assert.match(s.element('[data-duel-description]').textContent, /both ready.*countdown/);
    assert.equal(s.element('[data-duel-participant="you"]').dataset.ready, 'false');
    assert.equal(s.element('[data-duel-participant="opponent"]').dataset.ready, 'true');
    assert.equal(s.element('[data-duel-ready]').hidden, false);
    s.show({ phase: 'waiting-ready', leave: true });
    assert.match(s.element('[data-duel-description]').textContent, /automatically/);
    assert.equal(s.element('[data-duel-ready]').hidden, true);
    assert.equal(s.element('[data-duel-leave]').hidden, false);
    assert.equal(s.element('[data-duel-exit]').hidden, true);
});

test('ended duels cannot be dismissed accidentally and retain the way back to games', () => {
    const s = setup();
    for (const phase of ['opponent-left', 'you-left', 'cancelled', 'expired']) {
        s.show({ phase, exit: true });
        assert.equal(s.dismiss(), true);
        assert.equal(s.element('.btn-close').hidden, true);
        assert.equal(s.element('[data-duel-exit]').hidden, false);
    }
    assert.equal(s.hides(), 0);
    s.close(); // Intentional transitions (such as cancelling an invitation) still work.
    assert.equal(s.hides(), 1);
    assert.equal(s.root.dataset.duelClosing, undefined);
    assert.equal(s.dismiss(), true);
    s.show({ join: true });
    assert.equal(s.dismiss(), false);
    assert.equal(s.element('.btn-close').hidden, false);
});

test('countdown and departure replace prior content and expose only relevant actions', () => {
    const s = setup(); s.show({ phase: 'countdown', message: '2', leave: true });
    assert.equal(s.element('[data-duel-countdown]').hidden, false);
    assert.equal(s.element('[data-duel-countdown-value]').textContent, '2');
    s.show({ phase: 'opponent-left', exit: true });
    assert.equal(s.element('[data-duel-message]').textContent, 'Your opponent left');
    assert.match(s.element('[data-duel-description]').textContent, /duel has ended/);
    for (const selector of ['[data-duel-countdown]', '[data-duel-participants]', '[data-duel-leave]', '[data-duel-idle]', '[data-duel-ready]', '[data-duel-error]']) assert.equal(s.element(selector).hidden, true);
    assert.equal(s.element('[data-duel-exit]').hidden, false);
    assert.equal(s.element('[data-duel-actions]').hidden, false);
    s.show({ phase: 'go' });
    assert.equal(s.element('[data-duel-actions]').hidden, true);
});

test('idle and error screens keep the recovery instruction visible without empty error panels', () => {
    const s = setup(); s.show({ idle: true, seconds: 7 });
    assert.equal(s.element('[data-duel-idle-count]').textContent, '7');
    assert.equal(s.element('[data-duel-active]').hidden, false);
    s.show({ phase: 'error', message: 'Refresh to restore your last saved round.', error: 'Connection lost', exit: true });
    assert.match(s.element('[data-duel-description]').textContent, /Refresh/);
    assert.equal(s.element('[data-duel-error]').hidden, false);
    s.show({ join: true });
    assert.equal(s.element('[data-duel-error]').hidden, true);
    assert.equal(s.element('[data-duel-join-form]').hidden, false);
    assert.equal(s.element('[data-duel-actions]').hidden, true);
});
