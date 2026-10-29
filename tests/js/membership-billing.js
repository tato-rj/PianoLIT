const assert = require('assert');
const fs = require('fs');
const path = require('path');
const vm = require('vm');
const source = fs.readFileSync(path.join(__dirname, '../../resources/js/views/membership-billing.js'), 'utf8');
const flush = () => new Promise(resolve => setImmediate(resolve));

function element(attributes = {}) {
    return {attributes, events: {}, disabled: false, textContent: '', style: {}, hidden: true,
        getAttribute(name) {return this.attributes[name];}, setAttribute(name, value) {this.attributes[name] = value;},
        addEventListener(name, callback) {this.events[name] = callback;}, focus() {this.focused = true;}, classList: {contains() {return false;}}};
}
function fixture(purpose = 'purchase', invoiceMode = false, split = false, managed = false, invoiceActive = false, deferred = false, saved = false, wallets = false) {
    const requests = [], redirects = [], confirmations = [], replies = [];
    const button = element(), errors = element(), coupon = element({'data-coupon-url': '/coupon'}), feedback = element(); coupon.value = '';
    const total = element({'data-base-price': '799'}), mount = element();
    const due = element(), renewal = element(), consent = element({'data-trial-days': '0', 'data-interval': 'month'}), charge = element();
    const label = element(), status = element(), apply = element(), name = element(), postal = element();
    const preview = element(), cardName = element(), brandLabel = element();
    preview.querySelector = selector => selector === '[data-card-name]' ? cardName : brandLabel;
    const savedCard = element(), addCard = element(), newFields = element(), newPreview = element(), savedDetails = element(), savedCheck = element();
    savedCard.attributes['aria-pressed'] = 'true'; savedDetails.hidden = false; savedCheck.hidden = false;
    const opener = element(), cancel = element(), close = element(), editor = element();
    editor.querySelector = () => form;
    editor.querySelectorAll = () => [cancel, close];
    editor.classList.contains = name => name === 'show' && !editor.hidden;
    const trap = {active: false, activate() {this.active = true;}, deactivate() {this.active = false;}};
    const dialog = {_focustrap: trap, show() {
        editor.hidden = false; trap.activate(); editor.events['shown.bs.modal']();
    }, hide() {
        let prevented = false;
        editor.events['hide.bs.modal']({preventDefault() {prevented = true;}});
        if (prevented) return;
        editor.hidden = true; trap.deactivate(); editor.events['hidden.bs.modal'](); opener.focus();
    }};
    // Bootstrap's data API owns these controls; the page controller owns modal lifecycle events.
    opener.events.click = () => dialog.show();
    cancel.events.click = close.events.click = () => dialog.hide();
    const documentEvents = {}, signals = [];
    label.textContent = purpose === 'card' ? 'Save card' : 'Subscribe for $7.99'; name.value = 'Review Pianist'; postal.value = '10001';
    const mounts = ['cardNumber', 'cardExpiry', 'cardCvc'].map(type => element({'data-stripe-field': type}));
    mounts[0].focus = () => {document.activeElement = mounts[0];};
    const form = element({'data-operation': 'operation-review', 'data-purpose': purpose, 'data-key': 'pk_review', 'data-prepare': '/prepare', 'data-complete': '/complete',
        'data-wallet-amount': '799', 'data-wallet-interval': 'month', 'data-wallet-trial': '7', 'data-wallet-manage': 'https://my.example.test/membership/edit'});
    form.action = '/submit';
    if (saved) form.attributes['data-saved-card-reference'] = 'reference-review';
    form.closest = selector => managed && selector === '[data-checkout-modal]' ? {} : null;
    form.dispatchEvent = event => {signals.push(event.type); if (deferred && editor.events[event.type]) editor.events[event.type](event);};
    form.hasAttribute = attribute => (split && attribute === 'data-split-card') || (deferred && attribute === 'data-deferred-card');
    form.reset = () => {name.value = 'Review Pianist'; postal.value = '';};
    form.querySelectorAll = () => mounts;
    form.querySelector = selector => ({'button[type="submit"]': button, '#card-errors': errors, '#card-element': mount,
        'input[name="coupon"]': purpose === 'purchase' ? coupon : null, '[data-coupon-feedback]': feedback,
        '[data-membership-consent]': split ? consent : null, '[data-membership-charge]': split ? charge : null,
        '[data-submit-label]': split ? label : null, '[data-card-status]': split ? status : null,
        '[data-apply-coupon]': split ? apply : null, '[name="cardholder"]': split ? name : null,
        '[name="postal_code"]': split ? postal : null, '[data-card-preview]': split ? preview : null,
        '[data-card-name]': split ? cardName : null, '[data-card-brand-label]': split ? brandLabel : null,
        '[data-stripe-field="cardNumber"]': split ? mounts[0] : null,
        '[data-payment-card-cancel]': deferred ? cancel : null,
        '[data-checkout-saved-card]': saved ? savedCard : null, '[data-payment-card-add]': saved ? addCard : null,
        '[data-checkout-new-fields]': saved ? newFields : null, '[data-checkout-new-preview]': saved ? newPreview : null,
        '[data-checkout-saved-details]': saved ? savedDetails : null, '[data-checkout-card-check]': saved ? savedCheck : null}[selector]);
    const card = {mount() {}, on(name, callback) { this.change = callback; }};
    const hosted = {};
    const stripe = {elements() {return {create(type, options) {
        if (!split) return card;
        const field = {events: {}, options, destroyed: false, focus() {this.focused = true;}, destroy() {this.destroyed = true;}, clear() {this.cleared = true; this.events.change({complete: false});}, mount() {}, update(options) {this.disabled = options.disabled;}, on(event, callback) {this.events[event] = callback;}};
        hosted[type] = field; return field;
    }};},
        async confirmCardSetup(secret, details) { confirmations.push(['setup', secret, details]); return stripe.setupResult || {setupIntent: {id: 'seti_review'}}; },
        async confirmCardPayment(secret, details) { confirmations.push(['payment', secret, details]); return stripe.paymentResult || {paymentIntent: {status: 'succeeded'}}; }};
    const fetch = (url, options) => {
        requests.push({url, options});
        const reply = replies.shift();
        if (reply instanceof Error) return Promise.reject(reply);
        if (typeof reply === 'function') return reply(url, options);
        if (!reply) throw new Error('Unexpected request '+url);
        return Promise.resolve({ok: reply.ok !== false, json: async () => reply.data || reply});
    };
    const invoiceStatus = element(), invoiceMore = element(), invoiceRetry = element(), invoiceBody = {innerHTML: '', insertAdjacentHTML(where, html) {this.innerHTML += html;}};
    const invoiceContent = {querySelector() {return invoiceBody;}};
    Object.defineProperty(invoiceContent, 'innerHTML', {set(value) {invoiceBody.innerHTML = value;}, get() {return invoiceBody.innerHTML;}});
    const invoiceRoot = element({'data-url': '/invoices'});
    invoiceRoot.classList.contains = name => name === 'active' && invoiceActive;
    invoiceRoot.querySelector = selector => ({'[data-invoice-status]': invoiceStatus, '[data-invoice-more]': invoiceMore, '[data-invoice-retry]': invoiceRetry, '[data-invoice-content]': invoiceContent}[selector]);
    let shown;
    const jquery = selector => ({on(events, callback) {if (selector.includes('a[')) shown = callback; return this;}});
    const DOMParser = class {parseFromString(html) {return {querySelector() {return {innerHTML: html};}};}};
    const document = {addEventListener(name, handler) {documentEvents[name] = handler;}, querySelector(selector) {return {'meta[name="csrf-token"]': {content: 'csrf-review'}, '[data-membership-payment]': purpose === 'none' ? null : form, '[data-membership-total]': total, '[data-membership-due]': split ? due : null, '[data-membership-renewal]': split ? renewal : null, '[data-membership-invoices]': invoiceMode ? invoiceRoot : null}[selector] || null;}, querySelectorAll(selector) {return deferred && selector === '[data-payment-card-editor]' ? [editor] : [];}};
    if (invoiceActive) replies.push({html: 'restored invoice rows', after: null});
    let walletConfirm;
    const wallet = {destroy() {this.destroyed = true;}, update() {}};
    vm.runInNewContext(source, {document, DOMParser, Stripe: () => stripe, fetch, window: {bootstrap: {Modal: {getOrCreateInstance() {return dialog;}}}, CustomEvent: class {constructor(type) {this.type = type;}}, getComputedStyle() {return {color: '#273244', fontFamily: 'system-ui', fontSize: '16px', lineHeight: '24px', getPropertyValue() {return '#697386';}};},
        PianoLITExpressCheckout: wallets ? {mount(stripe, form, options, confirm) {walletConfirm = confirm; wallet.options = options; return wallet;}} : null,
        jQuery: invoiceMode ? jquery : null, location: {href: 'https://my.example.test/membership/monthly/checkout', hash: '', assign(url) { redirects.push(url); }, reload() { redirects.push('reload'); }}}});
    return {form, button, errors, coupon, invoiceStatus, invoiceMore, invoiceRetry, invoiceBody, openInvoices() {shown({target:{getAttribute() {return '#invoices-tab';}}});}, feedback, total, stripe, card, requests, redirects, confirmations, replies,
        due, renewal, consent, charge, label, status, name, postal, hosted, preview, cardName, brandLabel, documentEvents, signals, opener, editor, cancel, close, dialog, trap, savedCard, addCard, newFields, newPreview, savedDetails, savedCheck,
        ready() {Object.values(hosted).forEach(field => field.events.ready());},
        wallet, confirmWallet() {return walletConfirm({confirm: async function () {confirmations.push(['wallet_setup']); return {setupIntent: {id: 'seti_wallet'}};}});},
        submit() {return form.events.submit({preventDefault() {}});}};
}
function managementFixture() {
    const monthly = element({'data-name': 'monthly', 'data-confirmation': 'Keep your current plan.'});
    const yearly = element({'data-name': 'yearly', 'data-confirmation': 'Yearly billing starts at renewal.'});
    const cards = [element(), element()], input = element(), confirmation = element(), description = element();
    [monthly, yearly].forEach((button, i) => { button.closest = () => cards[i]; });
    const form = {style: {display: 'none'}, querySelector(selector) {return selector === 'input[name="plan"]' ? input : confirmation;}};
    const link = element({'href': '#invoices-tab'}), trigger = element(), shown = [];
    trigger.focus = () => {trigger.focused = true;};
    const document = {
        addEventListener() {},
        querySelectorAll(selector) {return selector === 'button[name="plan"]' ? [monthly, yearly] : selector === '[data-membership-tab]' ? [link] : [];},
        querySelector(selector) {return {'#update-plan-action form': form, '#update-plan-action > p': description, '#membership-tabs a[href="#invoices-tab"]': trigger}[selector] || null;}
    };
    vm.runInNewContext(source, {document, window: {location: {hash: ''}, bootstrap: {Tab: {getOrCreateInstance(target) {return {show() {shown.push(target);}};}}}}});
    return {monthly, yearly, cards, input, confirmation, form, description, link, trigger, shown};
}
module.exports = async function () {
    const saved = fixture('purchase', false, true, false, false, false, true);
    assert.strictEqual(saved.button.disabled, false, 'A saved card can be selected without waiting for new-card fields.');
    assert.deepStrictEqual(Object.keys(saved.hosted), [], 'Do not mount hidden secure inputs until Add new card is requested.');
    saved.replies.push({operation_id: 'saved-operation', saved_card: true}, {payment_secret: 'pi_saved', complete: false}, {redirect: '/welcome'});
    await saved.submit();
    assert.strictEqual(JSON.parse(saved.requests[0].options.body).use_saved_card, true);
    assert.deepStrictEqual(JSON.parse(saved.requests[1].options.body), {operation_id: 'saved-operation', use_saved_card: true, saved_card_reference: 'reference-review'});
    assert.deepStrictEqual(saved.confirmations.map(call => call[0]), ['payment'], 'Saved cards retain bank confirmation without a new-card SetupIntent.');
    assert.strictEqual(Object.keys(saved.confirmations[0][2]).length, 0, 'Hidden card fields never override the selected saved method.');
    assert.deepStrictEqual(saved.redirects, ['/welcome']);

    const choices = fixture('purchase', false, true, false, false, false, true);
    choices.addCard.events.click();
    assert.strictEqual(choices.newFields.hidden, false);
    assert.strictEqual(choices.newFields.disabled, false);
    assert.strictEqual(choices.savedCheck.hidden, true);
    assert.strictEqual(choices.newPreview.hidden, false);
    assert.strictEqual(choices.button.disabled, true, 'A new card waits for its secure fields.');
    choices.ready();
    Object.values(choices.hosted).forEach(field => field.events.change({complete: true, brand: 'mastercard'}));
    choices.savedCard.events.click();
    assert.strictEqual(choices.newFields.hidden, true);
    assert.strictEqual(choices.newFields.disabled, true);
    assert.strictEqual(choices.savedCheck.hidden, false);
    assert.strictEqual(choices.savedCard.attributes['aria-pressed'], 'true');
    choices.replies.push({operation_id: 'existing-payment', payment_secret: 'pi_retry', complete: false}, {redirect: '/welcome'});
    await choices.submit();
    assert.strictEqual(Object.keys(choices.confirmations[0][2]).length, 0, 'Returning to the saved card ignores a completed hidden draft even on a resumed payment.');

    const replacement = fixture('purchase', false, true, false, false, false, true);
    replacement.addCard.events.click(); replacement.ready();
    replacement.replies.push({operation_id: 'new-operation', setup_secret: 'seti_new_secret'}, {complete: true}, {redirect: '/welcome'});
    await replacement.submit();
    assert.strictEqual(JSON.parse(replacement.requests[0].options.body).use_saved_card, false);
    assert.strictEqual(replacement.confirmations[0][0], 'setup', 'Add new card retains the verified SetupIntent flow.');
    assert.strictEqual(JSON.parse(replacement.requests[1].options.body).setup_intent, 'seti_review');

    const failedSaved = fixture('purchase', false, true, false, false, false, true);
    failedSaved.replies.push(new Error('Saved card is no longer available'));
    await failedSaved.submit();
    assert.strictEqual(failedSaved.button.disabled, false);
    assert.strictEqual(failedSaved.savedCard.disabled, false);
    assert.strictEqual(failedSaved.addCard.disabled, false);
    assert.strictEqual(failedSaved.errors.textContent, 'Saved card is no longer available');

    const failedNew = fixture('purchase', false, true, false, false, false, true);
    failedNew.stripe.elements = () => { throw new Error('Secure fields unavailable'); };
    failedNew.addCard.events.click();
    assert(failedNew.errors.textContent.includes('reload'));
    assert.strictEqual(failedNew.button.disabled, true);
    failedNew.savedCard.events.click();
    assert.strictEqual(failedNew.button.disabled, false, 'The saved method remains usable if new-card fields cannot load.');
    failedNew.addCard.events.click();
    assert(failedNew.errors.textContent.includes('reload'), 'Reopening an unavailable new-card editor keeps a useful recovery message.');

    const lockedSaved = fixture('purchase', false, true, false, false, false, true);
    let releaseSaved;
    lockedSaved.replies.push(() => new Promise(resolve => { releaseSaved = resolve; }));
    const pendingSaved = lockedSaved.submit();
    assert.strictEqual(lockedSaved.addCard.disabled, true);
    lockedSaved.addCard.events.click();
    assert.strictEqual(lockedSaved.newFields.hidden, true, 'Payment selection cannot change while confirmation is in progress.');
    releaseSaved({ok: true, json: async () => { throw new Error('Connection interrupted'); }});
    await pendingSaved;
    assert.strictEqual(lockedSaved.addCard.disabled, false);

    const failedEditor = fixture('card', false, true, false, false, true);
    failedEditor.stripe.elements = () => {throw new Error('Secure fields unavailable');};
    failedEditor.opener.events.click();
    assert(failedEditor.errors.textContent.includes('reload'));
    failedEditor.cancel.events.click(); failedEditor.opener.events.click();
    assert(failedEditor.errors.textContent.includes('reload'), 'A failed secure-field load keeps its recovery instruction when the editor reopens.');
    assert.strictEqual(failedEditor.button.disabled, true);
    assert.strictEqual(failedEditor.requests.length, 0);

    const editor = fixture('card', false, true, false, false, true);
    assert.deepStrictEqual(Object.keys(editor.hosted), [], 'Billing does not load secure fields before Add new card is requested.');
    editor.opener.events.click();
    assert.strictEqual(editor.editor.hidden, false);
    editor.ready();
    assert.strictEqual(editor.hosted.cardNumber.focused, true, 'The secure card number field receives focus once ready.');
    const originalFields = Object.values(editor.hosted);
    editor.hosted.cardNumber.events.change({brand: 'visa', complete: true});
    editor.name.value = 'Another name';
    editor.cancel.events.click();
    assert.strictEqual(editor.editor.hidden, true);
    assert(originalFields.every(field => field.cleared));
    assert.strictEqual(editor.preview.attributes['data-card-brand'], 'unknown');
    assert.strictEqual(editor.name.value, 'Review Pianist');
    assert.strictEqual(editor.opener.focused, true);
    editor.opener.events.click();
    assert.strictEqual(editor.hosted.cardNumber, originalFields[0], 'Reopening reuses cleared fields without duplicating submit listeners.');
    let releaseSetup;
    editor.replies.push(() => new Promise(resolve => {releaseSetup = resolve;}), {redirect: '/membership/edit#update-card-tab'});
    const saving = editor.submit();
    assert.strictEqual(editor.label.textContent, 'Saving your card…');
    assert.strictEqual(editor.cancel.disabled, true);
    assert.strictEqual(editor.close.disabled, true);
    assert.strictEqual(editor.trap.active, false, 'Stripe bank authentication can take focus outside the dialog.');
    editor.cancel.events.click();
    editor.dialog.hide();
    assert.strictEqual(editor.editor.hidden, false, 'A card verification in progress cannot be dismissed.');
    releaseSetup({ok: true, json: async () => ({operation_id: 'card-op', setup_secret: 'card-secret'})});
    await saving;
    assert.strictEqual(editor.requests.length, 2, 'One prepare and one update run after close/reopen.');
    assert.strictEqual(editor.cancel.disabled, false);
    assert.strictEqual(editor.close.disabled, false);
    assert.strictEqual(editor.trap.active, true, 'The dialog focus trap is restored after verification.');
    assert.strictEqual(editor.label.textContent, 'Save card');
    assert.deepStrictEqual(editor.redirects, ['/membership/edit#update-card-tab']);

    const dismissed = fixture('card', false, true, false, false, true);
    dismissed.opener.events.click(); dismissed.ready();
    dismissed.name.value = 'Draft name';
    dismissed.close.events.click();
    assert.strictEqual(dismissed.name.value, 'Review Pianist', 'Close clears the draft just like Cancel.');
    dismissed.opener.events.click();
    dismissed.name.value = 'Another draft'; dismissed.dialog.hide();
    assert.strictEqual(dismissed.name.value, 'Review Pianist', 'Escape/backdrop dismissals use the same cleanup.');
    assert.strictEqual(dismissed.requests.length, 0, 'Dismissing never saves a card.');

    const restoredInvoices = fixture('none', true, false, false, true);
    await flush();
    assert.strictEqual(restoredInvoices.invoiceBody.innerHTML, 'restored invoice rows', 'Invoices also load if the shared menu restored them before the page script initialized.');
    assert.strictEqual(restoredInvoices.requests.length, 1);
    const management = managementFixture();
    management.monthly.disabled = true;
    management.monthly.events.click();
    assert.strictEqual(management.form.style.display, 'none', 'An unavailable plan cannot open the confirmation form.');
    management.yearly.events.click();
    assert.strictEqual(management.input.value, 'yearly');
    assert.strictEqual(management.confirmation.textContent, 'Yearly billing starts at renewal.');
    assert.strictEqual(management.form.style.display, '');
    assert.strictEqual(management.yearly.attributes['aria-pressed'], 'true');
    assert.strictEqual(management.cards[1].attributes['data-selected'], 'true');
    management.monthly.disabled = false;
    management.monthly.events.click();
    assert.strictEqual(management.input.value, 'monthly', 'Keeping the current plan can remove a pending change.');
    assert.strictEqual(management.yearly.attributes['aria-pressed'], 'false');
    assert.strictEqual(management.cards[1].attributes['data-selected'], 'false');
    let prevented = false;
    management.link.events.click({preventDefault() {prevented = true;}});
    assert(prevented);
    assert.deepStrictEqual(management.shown, [management.trigger], 'Overview links activate the real navigation trigger.');
    assert(management.trigger.focused, 'Keyboard focus follows the selected section.');

    let managed = fixture('purchase', false, true, true);
    assert.deepStrictEqual(Object.keys(managed.hosted), [], 'A managed checkout waits for the launcher, so parallel script downloads cannot mount Stripe too early.');
    managed.documentEvents['checkout:ready']({type: 'checkout:ready', target: {querySelector() {return managed.form;}}});
    assert.strictEqual(Object.keys(managed.hosted).length, 3);
    managed.hosted.cardNumber.events.ready(); managed.hosted.cardNumber.events.ready(); managed.hosted.cardNumber.events.ready();
    assert.strictEqual(managed.button.disabled, true, 'Repeated readiness from one iframe cannot stand in for the other secure fields.');
    assert(!managed.signals.includes('checkout:loaded'));
    managed.hosted.cardExpiry.events.ready(); assert.strictEqual(managed.button.disabled, true);
    managed.hosted.cardCvc.events.ready(); assert.strictEqual(managed.button.disabled, false);
    managed.ready();
    assert.strictEqual(managed.signals.filter(type => type === 'checkout:loaded').length, 1, 'Reveal only once, when all hosted fields are ready.');
    let f = fixture('none');
    assert.strictEqual(f.requests.length, 0, 'Canceled/ended pages with no card form do not initialize Stripe.');
    f = fixture();
    f.replies.push({operation_id: 'owned-operation', setup_secret: 'setup-secret'}, {complete: false, payment_secret: 'payment-secret'}, {redirect: '/welcome'});
    const first = f.submit(); const second = f.submit();
    await Promise.all([first, second]);
    assert.deepStrictEqual(f.requests.map(r => r.url), ['/prepare', '/submit', '/complete']);
    assert.strictEqual(f.requests[0].options.credentials, 'same-origin');
    assert.strictEqual(f.requests[0].options.headers['X-CSRF-TOKEN'], 'csrf-review');
    assert.strictEqual(JSON.parse(f.requests[1].options.body).operation_id, 'owned-operation');
    assert.strictEqual(JSON.parse(f.requests[1].options.body).setup_intent, 'seti_review');
    assert(!('stripeToken' in JSON.parse(f.requests[1].options.body)));
    assert.deepStrictEqual(f.confirmations.map(c => c[0]), ['setup', 'payment']);
    assert.deepStrictEqual(f.redirects, ['/welcome']);

    f = fixture(); f.stripe.setupResult = {error: {message: 'Authentication declined'}};
    f.replies.push({operation_id: 'op', setup_secret: 'setup-secret'});
    await f.submit();
    assert.strictEqual(f.requests.length, 1); assert.strictEqual(f.errors.textContent, 'Authentication declined');
    assert.strictEqual(f.button.disabled, false); assert.strictEqual(f.button.attributes['aria-busy'], 'false');

    f = fixture(); f.replies.push(new Error('Connection interrupted')); await f.submit();
    assert.strictEqual(f.button.disabled, false); assert.strictEqual(f.errors.textContent, 'Connection interrupted');

    f = fixture(); f.stripe.paymentResult = {error: {message: 'Payment requires another card'}};
    f.replies.push({operation_id: 'op', complete: false, payment_secret: 'existing-payment'});
    await f.submit();
    assert.strictEqual(f.requests.length, 1, 'An existing incomplete subscription is confirmed without creating another subscription.');
    assert.strictEqual(f.errors.textContent, 'Payment requires another card');
    f.stripe.paymentResult = null; f.card.change({complete: true});
    f.replies.push({operation_id: 'op', complete: false, payment_secret: 'existing-payment'}, {redirect: '/welcome'});
    await f.submit();
    assert(f.confirmations[1][2].payment_method.card, 'A retry can supply the replacement card to the existing invoice.');
    assert.deepStrictEqual(f.redirects, ['/welcome']);

    f = fixture();
    f.replies.push({operation_id: 'op', complete: false, subscription_setup_secret: 'trial-setup-secret'}, {redirect: '/welcome'});
    await f.submit(); assert.deepStrictEqual(f.confirmations.map(c => c[0]), ['setup']);
    assert.deepStrictEqual(f.redirects, ['/welcome']);

    f = fixture('card'); f.replies.push({operation_id: 'op', setup_secret: 'setup-secret'}, {redirect: '/membership/edit'});
    await f.submit(); assert.deepStrictEqual(f.redirects, ['/membership/edit']);
    assert.strictEqual(f.requests.length, 2);

    f = fixture('card'); f.replies.push({operation_id: 'op', setup_secret: 'setup-secret', setup_complete: true, setup_intent: 'seti_confirmed'}, {redirect: '/membership/edit'});
    await f.submit(); assert.strictEqual(f.confirmations.length, 0, 'A retry never reconfirms an already successful SetupIntent.');
    assert.strictEqual(JSON.parse(f.requests[1].options.body).setup_intent, 'seti_confirmed');

    f = fixture(); let releaseOld;
    f.coupon.value = 'FIRST';
    f.replies.push(() => new Promise(resolve => {releaseOld = resolve;}));
    const old = f.coupon.events.blur();
    f.coupon.value = 'SECOND'; f.coupon.events.input();
    f.replies.push({message: 'Second coupon applied', amount: 399});
    await f.coupon.events.blur();
    releaseOld({ok: true, json: async () => ({message: 'Stale first coupon', amount: 1})}); await old;
    assert.strictEqual(f.feedback.textContent, 'Second coupon applied'); assert.strictEqual(f.total.textContent, '$3.99');
    assert.strictEqual(f.button.disabled, false);
    f.coupon.value = 'THIRD'; f.coupon.events.input(); f.replies.push(new Error('Coupon check failed'));
    await f.coupon.events.blur(); assert.strictEqual(f.feedback.textContent, 'Coupon check failed'); assert.strictEqual(f.button.disabled, false);
    assert.strictEqual(f.total.textContent, '$7.99');

    f = fixture('purchase', false, true);
    assert(f.button.disabled, 'Hosted fields must be ready before checkout is enabled.');
    await f.submit(); assert.strictEqual(f.requests.length, 0, 'Submitting before hosted fields are ready cannot prepare a purchase.');
    f.ready(); assert.strictEqual(f.button.disabled, false);
    assert.strictEqual(f.hosted.cardNumber.options.style.base.fontSize, '16px');
    assert.strictEqual(f.hosted.cardNumber.options.showIcon, true);
    f.hosted.cardNumber.events.change({brand: 'visa', complete: false});
    assert.strictEqual(f.preview.attributes['data-card-brand'], 'visa'); assert.strictEqual(f.brandLabel.textContent, 'Visa');
    f.hosted.cardNumber.events.change({brand: 'mastercard', complete: false});
    assert.strictEqual(f.preview.attributes['data-card-brand'], 'mastercard');
    f.hosted.cardNumber.events.change({brand: '__proto__', complete: false});
    assert.strictEqual(f.preview.attributes['data-card-brand'], 'unknown');
    f.name.value = 'Another Pianist'; f.name.events.input(); assert.strictEqual(f.cardName.textContent, 'Another Pianist');
    f.name.value = 'Review Pianist';
    f.hosted.cardNumber.events.change({complete: false, error: {message: 'Card number is incomplete'}});
    f.hosted.cardExpiry.events.change({complete: true});
    assert.strictEqual(f.errors.textContent, 'Card number is incomplete', 'A valid expiry cannot hide a card-number error.');
    f.hosted.cardNumber.events.change({complete: true});
    f.hosted.cardCvc.events.change({complete: true});
    assert.strictEqual(f.errors.textContent, '');
    f.replies.push({operation_id: 'op', setup_secret: 'secret'}, {complete: true}, {redirect: '/welcome'});
    await f.submit();
    assert.strictEqual(f.confirmations[0][2].payment_method.card, f.hosted.cardNumber);
    assert.strictEqual(f.confirmations[0][2].payment_method.billing_details.name, 'Review Pianist');
    assert.strictEqual(f.confirmations[0][2].payment_method.billing_details.address.postal_code, '10001');
    assert.strictEqual(f.name.disabled, false); assert.strictEqual(f.hosted.cardNumber.disabled, false);
    assert.deepStrictEqual(f.redirects, ['/welcome']);
    const oldHosted = Object.values(f.hosted);
    f.documentEvents['checkout:ready']();
    assert.strictEqual(f.hosted.cardNumber, oldHosted[0], 'Repeated ready events do not create duplicate Stripe fields.');
    f.documentEvents['checkout:dispose']({target: {querySelector() {return f.form;}}});
    assert(oldHosted.every(field => field.destroyed), 'Closing checkout destroys its Stripe fields.');
    f.documentEvents['checkout:ready']();
    assert.notStrictEqual(f.hosted.cardNumber, oldHosted[0], 'Reopening checkout mounts fresh hosted fields.');

    f = fixture('purchase', false, true); f.ready();
    f.coupon.value = 'SAVE'; f.coupon.events.input();
    f.replies.push({message: 'Coupon applied', amount: 399}); await f.coupon.events.blur();
    assert.strictEqual(f.due.textContent, '$3.99');
    assert.strictEqual(f.label.textContent, 'Subscribe for $3.99');
    assert(f.charge.textContent.includes('$3.99'));
    assert(f.renewal.textContent.includes('Later renewals follow the coupon terms.'));
    f.coupon.value = ''; f.coupon.events.input();
    assert.strictEqual(f.due.textContent, '$7.99'); assert.strictEqual(f.label.textContent, 'Subscribe for $7.99');

    f = fixture('purchase', false, true); f.ready();
    f.coupon.value = 'INVALID'; f.coupon.events.input(); f.replies.push({ok: false, message: 'Invalid coupon'});
    await f.submit(); assert.deepStrictEqual(f.requests.map(r => r.url), ['/coupon']);
    assert(f.errors.textContent.includes('remove the code'));
    assert.strictEqual(f.button.disabled, false);

    f = fixture('purchase', false, true, false, false, false, true, true);
    f.replies.push({operation_id: 'wallet-operation', setup_secret: 'seti_wallet_secret_review'}, {complete: true}, {redirect: '/welcome'});
    await f.confirmWallet();
    assert.strictEqual(JSON.parse(f.requests[0].options.body).use_saved_card, false, 'A wallet never silently charges the selected saved card');
    assert.strictEqual(JSON.parse(f.requests[1].options.body).setup_intent, 'seti_wallet');
    assert.deepStrictEqual(f.redirects, ['/welcome']);
    assert.strictEqual(f.wallet.options.mode, 'setup');
    assert.strictEqual(f.wallet.options.applePay.recurringPaymentRequest.regularBilling.amount, 799);
    f.documentEvents['checkout:dispose']({target: {querySelector() {return f.form;}}});
    assert.strictEqual(f.wallet.destroyed, true);

    f = fixture('none', true);
    assert.strictEqual(f.requests.length, 0, 'Invoice history is lazy.');
    f.replies.push({html: 'first invoice rows', after: 'in_older'});
    f.openInvoices(); await flush();
    assert.strictEqual(f.invoiceBody.innerHTML, 'first invoice rows');
    f.replies.push(new Error('Invoice request interrupted'));
    f.invoiceMore.events.click(); await flush();
    assert.strictEqual(f.invoiceRetry.hidden, false); assert.strictEqual(f.invoiceMore.disabled, false);
    f.replies.push({html: ' plus older invoice rows', after: null});
    f.invoiceRetry.events.click(); await flush();
    assert.strictEqual(f.invoiceBody.innerHTML, 'first invoice rows plus older invoice rows');
    assert.strictEqual(f.invoiceMore.hidden, true);
    assert.strictEqual(f.requests[2].url, '/invoices?after=in_older');
};
if (require.main === module) module.exports().then(() => console.log('Membership billing JavaScript regressions passed.')).catch(error => {console.error(error); process.exitCode = 1;});
