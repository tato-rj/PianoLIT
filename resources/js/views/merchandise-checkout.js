(function () {
    'use strict';
    if (window.PianoLITMerchandiseCheckout) return;
    const mounted = new WeakMap();
    function mount(root) {
        if (!root || mounted.has(root)) return;
        const modal = root.closest('[data-checkout-modal]');
        const surface = root.closest('[data-checkout-surface]');
        const shipping = root.querySelector('[data-shipping-form]');
        const form = root.querySelector('[data-merchandise-payment]');
        const retryButton = shipping.querySelector('[data-delivery-retry]');
        const payButton = form.querySelector('[type="submit"]');
        const payLabel = form.querySelector('[data-pay-label]');
        const error = form.querySelector('[data-checkout-error]');
        const status = shipping.querySelector('[data-delivery-status]');
        const country = shipping.querySelector('[name="country_code"]');
        const state = shipping.querySelector('[name="state_code"]');
        const rate = shipping.querySelector('[name="shipping_id"]');
        const countries = JSON.parse(root.dataset.countries);
        const demo = root.dataset.mode === 'demo';
        const digital = root.dataset.kind === 'digital';
        const walletOnly = root.dataset.walletOnly === 'true';
        const sections = root.querySelectorAll ? Array.from(root.querySelectorAll('[data-checkout-sections] .accordion-collapse')) : [];
        const sectionWaiters = new Set();
        let checkingAddress = false, invalidTarget = null, reportingInvalid = false;
        function setSection(panel, open) {
            panel.classList.toggle('show', open);
            const toggle = root.querySelector('[data-bs-target="#' + panel.id + '"]');
            toggle.classList.toggle('collapsed', !open);
            toggle.setAttribute('aria-expanded', String(open));
        }
        function initialSections() {
            // Card frames mount at their final width before the loading surface is revealed.
            sections.forEach(panel => setSection(panel, panel.id === root.dataset.checkoutInitialSection));
        }
        function revealSection(node) {
            if (disposed || !sections.length || !node) return Promise.resolve();
            const panel = node.closest('.accordion-collapse');
            if (!panel || panel.classList.contains('show')) return Promise.resolve();
            return new Promise(resolve => {
                function done() {
                    window.clearTimeout(timer);
                    panel.removeEventListener('shown.bs.collapse', done);
                    sectionWaiters.delete(done);
                    resolve();
                }
                const timer = window.setTimeout(done, 500);
                sectionWaiters.add(done);
                panel.addEventListener('shown.bs.collapse', done, {once: true});
                if (window.bootstrap?.Collapse) window.bootstrap.Collapse.getOrCreateInstance(panel, {toggle: false}).show();
                else { sections.forEach(item => setSection(item, item === panel)); done(); }
            });
        }
        root.addEventListener('invalid', function (event) {
            if (!sections.length || checkingAddress || walletOnly || (reportingInvalid && event.target === invalidTarget)) return;
            event.preventDefault();
            if (invalidTarget) return;
            invalidTarget = event.target;
            revealSection(invalidTarget).then(function () {
                if (!disposed) {
                    reportingInvalid = true;
                    invalidTarget.focus(); invalidTarget.reportValidity();
                    reportingInvalid = false;
                }
                invalidTarget = null;
            });
        }, true);
        const bagItems = walletOnly ? root.closest('[data-storefront]')?.querySelector('[data-bag-items]') : null;
        const bagContinue = walletOnly ? root.closest('[data-storefront]')?.querySelector('.shop-bag-checkout') : null;
        const summary = surface.querySelector('[data-checkout-summary]');
        const smallScreen = summary && window.matchMedia ? window.matchMedia('(max-width: 767.98px)') : null;
        function summaryLayout() {
            if (!summary || !smallScreen) return;
            summary.open = !smallScreen.matches;
            summary.querySelector('summary').tabIndex = smallScreen.matches ? 0 : -1;
        }
        summaryLayout();
        if (smallScreen) smallScreen.addEventListener('change', summaryLayout);
        const controllers = new Set(), fields = [], completed = new Set(), ready = new Set();
        let disposed = false, busy = false, quoting = false, quote = null, stripe, card, wallet;
        let walletActive = false, walletConfirming = false, walletPreview = null, walletAddress = null, walletVersion = 0;
        let walletQueue = Promise.resolve();
        const walletAmount = Number(root.dataset.walletAmount);
        const pendingShipping = [{id: 'pending', displayName: 'Delivery calculated after address selection', amount: 0}];
        let quoteTimer = null, pendingAt = 0, inputKey = null, quoteKey = null, destinationKey = null, composing = false;
        let selectedCountry = country.value;
        const quoteDelay = 1000;
        let paymentReady = demo || walletOnly;
        const money = amount => '$' + (amount / 100).toFixed(2);
        function signal(type) { form.dispatchEvent(new window.CustomEvent(type, {bubbles: true})); }
        function controls() {
            if (disposed) return;
            payButton.disabled = busy || walletActive || quoting || !quote || !paymentReady;
            retryButton.disabled = busy || walletActive || quoting;
            shipping.inert = walletActive;
            if (bagItems) bagItems.inert = walletActive || busy;
            if (bagContinue) bagContinue.inert = walletActive || busy;
            shipping.querySelectorAll('input, select').forEach(node => { node.disabled = busy || (!digital && node === state && !countryData().states?.length); });
            const free = quote && quote.total === 0;
            form.querySelectorAll('input').forEach(node => { node.disabled = busy || (free && node.name !== '_token'); });
            const cardFields = form.querySelector('.checkout-card-and-fields');
            if (cardFields) cardFields.hidden = !!free;
            form.setAttribute('aria-busy', String(busy || walletActive));
            fields.forEach(field => field.update({disabled: busy || walletActive || !!free}));
            payLabel.textContent = busy ? 'Confirming your order…' : demo ? 'Complete test order' : quote ? (quote.total ? 'Pay ' + money(quote.total) : 'Get your download') : 'Pay securely';
            signal('checkout:busy');
        }
        async function request(url, data) {
            const controller = new window.AbortController();
            controllers.add(controller);
            const timer = window.setTimeout(() => controller.abort(), 30000);
            try {
                const response = await window.fetch(url, {method: 'POST', credentials: 'same-origin', signal: controller.signal,
                    headers: {'Content-Type': 'application/json', Accept: 'application/json', 'X-CSRF-TOKEN': form.querySelector('[name="_token"]').value}, body: JSON.stringify(data)});
                const result = await response.json();
                if (!response.ok) {
                    const failure = new Error(Object.values(result.errors || {}).flat()[0] || result.message || 'Checkout could not be confirmed. Please try again.');
                    failure.refreshQuote = result.refresh_quote === true;
                    failure.fieldNames = Object.keys(result.errors || {});
                    throw failure;
                }
                return result;
            } finally { window.clearTimeout(timer); controllers.delete(controller); }
        }
        function countryData() { return countries.find(item => item.code === country.value) || {}; }
        function states(preserve = false) {
            if (digital) return;
            const previous = state.value;
            state.replaceChildren();
            state.add(new window.Option('Choose state / province', ''));
            (countryData().states || []).forEach(item => state.add(new window.Option(item.name, item.code)));
            if (preserve && Array.from(state.options).some(item => item.value === previous)) state.value = previous;
            state.required = !!countryData().states?.length;
            root.querySelector('[data-state-field]').hidden = !state.required;
        }
        function invalidate() {
            quote = null; quoteKey = null;
            retryButton.hidden = true;
            error.textContent = '';
            if (wallet) {
                if (digital) { wallet.destroy(); wallet = null; }
                else if (!walletActive) wallet.update({amount: walletAmount, shippingRates: pendingShipping, lineItems: [{name: 'Products', amount: walletAmount}]});
            }
            surface.querySelector('[data-checkout-total]').textContent = '—';
            const mobileTotal = surface.querySelector('[data-checkout-mobile-total]');
            if (mobileTotal) mobileTotal.textContent = money(walletAmount);
            const delivery = surface.querySelector('[data-checkout-shipping]');
            if (delivery) delivery.textContent = 'Calculated at checkout';
            surface.querySelector('[data-checkout-tax]').textContent = 'Calculated at checkout';
            surface.querySelector('[data-checkout-discount-line]').hidden = true;
            status.textContent = digital ? 'Your total updates automatically when your billing address is complete.' : 'Delivery and tax update automatically when your address is complete.';
            controls();
        }
        function details() {
            const data = Object.fromEntries(new window.FormData(shipping));
            Object.keys(data).forEach(key => { if (typeof data[key] === 'string') data[key] = data[key].trim(); });
            return {data, key: JSON.stringify(data)};
        }
        function destination(data) {
            return JSON.stringify(['address1', 'address2', 'country_code', 'state_code', 'city', 'zip'].map(key => data[key] || ''));
        }
        function completeAddress() {
            checkingAddress = true;
            try {
                return !composing && shipping.checkValidity() && Array.from(shipping.querySelectorAll('[required]'))
                    .every(node => node.disabled || String(node.value).trim() !== '');
            } finally { checkingAddress = false; }
        }
        function cancelScheduled() {
            if (quoteTimer !== null) window.clearTimeout(quoteTimer);
            quoteTimer = null;
        }
        function schedule(delay) {
            cancelScheduled();
            quoteTimer = window.setTimeout(function () { quoteTimer = null; calculate(); }, delay);
        }
        function changed(force = false, delay = quoteDelay) {
            if (disposed || busy || walletActive || walletConfirming || walletOnly) return;
            if (selectedCountry !== country.value) { selectedCountry = country.value; states(); }
            let snapshot = details();
            const nextDestination = destination(snapshot.data);
            if (rate && destinationKey !== null && destinationKey !== nextDestination) {
                rate.replaceChildren();
                shipping.querySelector('[data-shipping-options]').hidden = true;
                snapshot = details();
            }
            destinationKey = nextDestination;
            // input/change/autofill can report the same values several times.
            if (!force && inputKey === snapshot.key) return;
            inputKey = snapshot.key;
            cancelScheduled(); invalidate();
            pendingAt = completeAddress() ? Date.now() + delay : 0;
            if (pendingAt) schedule(delay);
        }
        states(true);
        shipping.addEventListener('input', function () { changed(); });
        shipping.addEventListener('change', function () { changed(); });
        shipping.addEventListener('compositionstart', function () { composing = true; changed(true); });
        shipping.addEventListener('compositionend', function () { composing = false; changed(true); });
        retryButton.addEventListener('click', function () { changed(true, 0); });
        async function calculate() {
            if (disposed || busy || walletActive || walletConfirming || quoting || !completeAddress()) return;
            const snapshot = details();
            pendingAt = 0;
            quoting = true; error.textContent = ''; status.textContent = digital ? 'Confirming your total…' : 'Calculating delivery and tax…'; controls();
            try {
                await walletQueue.catch(() => {});
                if (disposed || composing || snapshot.key !== details().key) return;
                const result = await request(root.dataset.quoteUrl, snapshot.data);
                if (disposed || composing || snapshot.key !== details().key) return;
                if (result.redirect) { window.location.assign(result.redirect); return; }
                showQuote(result);
                if (digital && wallet) { wallet.destroy(); wallet = null; }
                if (digital && !demo && result.total && window.PianoLITExpressCheckout) wallet = window.PianoLITExpressCheckout.mount(stripe, form, {
                    mode: 'payment', amount: result.total, returnUrl: window.location.origin + '/orders/' + result.order_id,
                    error: failure => { if (!disposed) error.textContent = failure.message; }
                }, payment => pay(payment));
                else if (wallet && !walletActive) wallet.update(walletValues(result));
            } catch (failure) {
                if (!disposed && snapshot.key === details().key) {
                    quote = null; pendingAt = 0;
                    error.textContent = failure.message || 'Delivery could not be calculated. Please try again.';
                    status.textContent = 'Please check your address and try again.';
                    retryButton.hidden = false;
                    const name = failure.fieldNames?.find(name => /^[a-z_]+$/.test(name));
                    if (name) await revealSection(shipping.querySelector('[name="' + name + '"]'));
                }
            } finally {
                quoting = false;
                controls();
                // Let the existing request finish before quoting the latest settled values.
                // Aborting fetch alone cannot cancel a calculation already running on the server.
                if (!disposed && !quote && pendingAt && quoteTimer === null && completeAddress()) schedule(Math.max(0, pendingAt - Date.now()));
            }
        }
        function showQuote(result) {
            quote = result;
            if (rate) {
                rate.replaceChildren();
                result.rates.forEach(item => rate.add(new window.Option(item.name + ' — ' + money(item.id === result.shipping_id ? result.shipping : item.amount), item.id)));
                rate.value = result.shipping_id;
                shipping.querySelector('[data-shipping-options]').hidden = false;
            }
            quoteKey = inputKey = details().key;
            destinationKey = destination(details().data);
            pendingAt = 0; cancelScheduled();
            surface.querySelector('[data-checkout-total]').textContent = money(result.total);
            const mobileTotal = surface.querySelector('[data-checkout-mobile-total]');
            if (mobileTotal) mobileTotal.textContent = money(result.total);
            surface.querySelector('[data-checkout-subtotal]').textContent = money(result.subtotal + (result.discount || 0));
            surface.querySelector('[data-checkout-discount-line]').hidden = !result.discount;
            surface.querySelector('[data-checkout-discount]').textContent = '−' + money(result.discount || 0);
            const discountLabel = surface.querySelector('[data-checkout-discount-label]');
            if (discountLabel) discountLabel.textContent = result.discount_label || 'Coupon discount';
            const delivery = surface.querySelector('[data-checkout-shipping]');
            if (delivery) delivery.textContent = money(result.shipping);
            surface.querySelector('[data-checkout-tax]').textContent = money(result.tax);
            status.textContent = digital ? 'Your total is confirmed. Your download will be available after payment.' : 'Delivery and tax confirmed. Review your total before paying.';
        }
        function walletValues(result) {
            const rates = result.rates.map(item => ({id: item.id, displayName: item.name,
                amount: item.id === result.shipping_id ? result.shipping : item.amount}));
            // Stripe makes the first rate the default when the rates are replaced.
            rates.sort((a, b) => Number(b.id === result.shipping_id) - Number(a.id === result.shipping_id));
            return {amount: result.total, shippingRates: rates, lineItems: [
                {name: 'Products', amount: result.subtotal}, {name: 'Delivery', amount: result.shipping}, {name: 'Tax', amount: result.tax}
            ]};
        }
        function obsoleteWallet() {
            const failure = new Error('This wallet selection is no longer current.');
            failure.obsolete = true;
            return failure;
        }
        async function previewWallet(address, shippingId) {
            const version = ++walletVersion;
            walletPreview = null;
            walletAddress = address;
            const email = shipping.querySelector('[name="email"]').value.trim();
            const data = {country_code: address.country || '', state_code: address.state || '', city: address.city || '', zip: address.postal_code || '',
                ...(/^[^@\s]+@[^@\s]+\.[^@\s]+$/.test(email) ? {email} : {}), ...(shippingId ? {shipping_id: shippingId} : {})};
            const known = details().data;
            if (['country_code', 'state_code', 'city', 'zip'].every(key => (known[key] || '').toUpperCase() === data[key].toUpperCase()) && known.address1 && known.name) {
                Object.assign(data, {address1: known.address1, address2: known.address2 || '', name: known.name});
            }
            const current = () => !disposed && walletActive && version === walletVersion;
            // Wallet address/rate changes share one request lane; stale results never update the sheet.
            const task = walletQueue.catch(() => {}).then(function () {
                if (!current()) throw obsoleteWallet();
                return request(root.dataset.walletQuoteUrl, data);
            });
            walletQueue = task;
            const result = await task;
            if (!current()) throw obsoleteWallet();
            walletPreview = result;
            return walletValues(result);
        }
        function cancelWallet() {
            walletActive = false; walletPreview = null; walletAddress = null; walletVersion++;
            controls();
            if (!walletConfirming && !quote && completeAddress()) changed(true);
        }
        async function payFromWallet(payment) {
            if (disposed || busy || !walletActive || walletConfirming) throw new Error('Please open the wallet again to continue.');
            walletConfirming = true;
            try {
                const shown = walletPreview;
                const event = payment.event;
                const contact = shipping.querySelector('[name="email"]');
                const email = contact.type === 'hidden' ? contact.value : event.billingDetails?.email;
                if (!shown || !event.shippingAddress?.address || !email) throw new Error('Please select a delivery address and email in your wallet.');
                const selected = event.shippingRate?.id || shown.shipping_id;
                if (selected !== shown.shipping_id) throw new Error('Please review your delivery option in the wallet.');
                const address = event.shippingAddress.address;
                const values = {email, name: event.shippingAddress.name || event.billingDetails?.name || '',
                    address1: address.line1 || '', address2: address.line2 || '', country_code: address.country || '',
                    state_code: address.state || '', city: address.city || '', zip: address.postal_code || ''};
                country.value = values.country_code;
                selectedCountry = country.value; states();
                Object.entries(values).forEach(([name, value]) => { shipping.querySelector('[name="' + name + '"]').value = value; });
                controls();
                // Populate the normal form so cancellation/errors retain the wallet's full address.
                rate.replaceChildren(); rate.add(new window.Option('', selected)); rate.value = selected;
                quote = null; quoteKey = null; pendingAt = 0; cancelScheduled();
                if (!completeAddress()) {
                    const failure = new Error('Please complete your delivery address before paying.');
                    failure.paymentFailureReason = 'invalid_shipping_address';
                    throw failure;
                }
                const snapshot = details();
                const result = await request(root.dataset.quoteUrl, snapshot.data);
                if (disposed || !walletActive) throw obsoleteWallet();
                showQuote(result);
                wallet.update(walletValues(result));
                if (['subtotal', 'shipping', 'tax', 'total', 'currency', 'shipping_id'].some(key => result[key] !== shown[key])) {
                    throw new Error('Your total changed to ' + money(result.total) + ' after checking your full address. Review the updated total and open your wallet again.');
                }
                walletActive = false;
                await pay(payment);
            } finally {
                walletConfirming = false;
                if (!disposed) {
                    walletActive = false; controls();
                    if (!quote && completeAddress()) changed(true);
                }
            }
        }
        shipping.addEventListener('submit', function (event) { event.preventDefault(); changed(); });
        async function pay(walletPayment) {
            if (busy || disposed) return;
            if (!walletPayment && !completeAddress()) { shipping.reportValidity(); return; }
            if (!quote || quoteKey !== details().key || (quote.expires_at && Date.parse(quote.expires_at) <= Date.now())) {
                changed(true, 0);
                if (walletPayment) throw new Error('Your total is being updated. Please review it before paying.');
                return;
            }
            if (!walletPayment && !demo && quote.total) {
                await revealSection(form.querySelector('[name="cardholder"]'));
                if (disposed) return;
                if (completed.size !== 3 || !form.reportValidity()) { if (card) card.focus(); return; }
            }
            const selectedQuote = quote;
            const orderLink = form.querySelector('[data-checkout-order-link]');
            orderLink.href = window.location.origin + '/orders/' + selectedQuote.order_id;
            orderLink.hidden = false;
            busy = true; error.textContent = ''; controls();
            let refreshQuote = false;
            try {
                const prepared = await request(root.dataset.prepareUrl, {order_id: selectedQuote.order_id});
                if (prepared.paid) { window.location.assign(prepared.redirect); return; }
                if (!demo) {
                    if (disposed) return;
                    if (walletPayment) await walletPayment.confirm(prepared.client_secret, 'payment', prepared.return_url || window.location.origin + '/orders/' + selectedQuote.order_id);
                    else {
                        const result = await stripe.confirmCardPayment(prepared.client_secret, {payment_method: {card: card, billing_details: {
                            name: form.querySelector('[name="cardholder"]').value,
                            address: {postal_code: form.querySelector('[name="postal_code"]').value}
                        }}});
                        if (result.error) throw new Error(result.error.message || 'Your payment could not be confirmed.');
                    }
                }
                const completedOrder = await request(root.dataset.completeUrl, {order_id: selectedQuote.order_id});
                if (!disposed) window.location.assign(completedOrder.redirect);
            } catch (failure) {
                refreshQuote = failure.refreshQuote === true;
                if (!disposed) error.textContent = failure.message || 'We could not confirm your order. Please try again.';
                if (!walletPayment) await revealSection(form.querySelector('[name="cardholder"]'));
                if (walletPayment) throw failure;
            } finally {
                busy = false;
                if (refreshQuote) { quote = null; quoteKey = null; changed(true, 0); }
                controls();
            }
        }
        form.addEventListener('submit', function (event) { event.preventDefault(); pay(null); });
        if (!demo) {
            try {
                stripe = window.Stripe(root.dataset.key);
                if (!walletOnly) {
                    const elements = stripe.elements();
                    const name = form.querySelector('[name="cardholder"]');
                    const inputStyle = window.getComputedStyle(name);
                    const preview = form.querySelector('[data-card-preview]');
                    function holder() { if (preview) preview.querySelector('[data-card-name]').textContent = name.value.trim() || 'Your name'; }
                    name.addEventListener('input', holder); holder();
                    form.querySelectorAll('[data-stripe-field]').forEach(function (node) {
                        const type = node.dataset.stripeField;
                        const field = elements.create(type, {style: {base: {color: inputStyle.color, fontFamily: inputStyle.fontFamily, fontSize: inputStyle.fontSize, lineHeight: inputStyle.lineHeight}}, ...(type === 'cardNumber' ? {showIcon: true} : {})});
                        fields.push(field);
                        if (type === 'cardNumber') card = field;
                        field.on('ready', function () {
                            if (disposed) return;
                            ready.add(type);
                            if (ready.size === 3) { paymentReady = true; form.querySelector('[data-card-status]').textContent = ''; initialSections(); controls(); signal('checkout:loaded'); }
                        });
                        field.on('change', function (event) {
                            if (event.complete) completed.add(type); else completed.delete(type);
                            if (type === 'cardNumber' && preview) {
                                const brands = {visa: 'Visa', mastercard: 'Mastercard', amex: 'Amex', discover: 'Discover', diners: 'Diners Club', jcb: 'JCB', unionpay: 'UnionPay'};
                                preview.setAttribute('data-card-brand', brands[event.brand] ? event.brand : 'unknown');
                                preview.querySelector('[data-card-brand-label]').textContent = brands[event.brand] || 'Card';
                            }
                            error.textContent = event.error ? event.error.message : '';
                        });
                        field.mount(node);
                    });
                }
                if (!digital && window.PianoLITExpressCheckout) wallet = window.PianoLITExpressCheckout.mount(stripe, form, {
                    shell: root.querySelector('[data-checkout-wallets]'), mode: 'payment', amount: walletAmount,
                    returnUrl: window.location.origin + '/checkout',
                    collection: {emailRequired: shipping.querySelector('[name="email"]').type !== 'hidden', billingAddressRequired: false, shippingAddressRequired: true,
                        allowedShippingCountries: countries.map(item => item.code), shippingRates: pendingShipping,
                        lineItems: [{name: 'Products', amount: walletAmount}]},
                    click: function (event) {
                        if (busy || quoting || walletActive || walletConfirming) return;
                        cancelScheduled(); pendingAt = 0; walletActive = true; walletPreview = null; walletAddress = null; walletVersion++;
                        wallet.update({amount: walletAmount});
                        controls();
                        event.resolve({shippingRates: pendingShipping, lineItems: [{name: 'Products', amount: walletAmount}]});
                    },
                    shippingAddressChange: event => previewWallet(event.address),
                    shippingRateChange: event => {
                        if (!walletAddress) throw new Error('Please select a delivery address in your wallet.');
                        return previewWallet(walletAddress, event.shippingRate.id);
                    },
                    cancel: cancelWallet,
                    error: failure => { if (!disposed && !failure.obsolete) error.textContent = failure.message; }
                }, payFromWallet);
                if (walletOnly) signal('checkout:loaded');
            } catch (failure) { error.textContent = 'The secure payment form could not load. Please try again.'; signal('checkout:loaderror'); }
        } else { initialSections(); signal('checkout:loaded'); }
        controls();
        mounted.set(root, {destroy: function () {
            disposed = true;
            walletVersion++;
            sectionWaiters.forEach(done => done());
            if (smallScreen) smallScreen.removeEventListener('change', summaryLayout);
            if (bagItems) bagItems.inert = false;
            if (bagContinue) bagContinue.inert = false;
            cancelScheduled();
            controllers.forEach(controller => controller.abort());
            fields.forEach(field => field.destroy());
            if (wallet) wallet.destroy();
            mounted.delete(root);
        }});
        changed(true);
    }
    window.PianoLITMerchandiseCheckout = {mount: mount};
    document.addEventListener('checkout:ready', event => mount(event.target.querySelector('[data-merchandise-checkout]')));
    document.addEventListener('checkout:dispose', function (event) {
        const root = event.target.querySelector('[data-merchandise-checkout]');
        if (root && mounted.has(root)) mounted.get(root).destroy();
    });
    function inlineWallets() {
        if (!document.querySelectorAll) return;
        const roots = Array.from(document.querySelectorAll('[data-merchandise-checkout][data-wallet-only="true"]'));
        roots.forEach(mount);
        if (roots.length) window.addEventListener('pagehide', function () {
            roots.forEach(root => { if (mounted.has(root)) mounted.get(root).destroy(); });
        }, {once: true});
    }
    if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', inlineWallets, {once: true});
    else inlineWallets();
}());
