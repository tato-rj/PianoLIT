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
        const controllers = new Set(), fields = [], completed = new Set(), ready = new Set();
        let disposed = false, busy = false, quoting = false, quote = null, stripe, card, wallet;
        let quoteTimer = null, pendingAt = 0, inputKey = null, quoteKey = null, destinationKey = null, composing = false;
        let selectedCountry = country.value;
        const quoteDelay = 1000;
        let paymentReady = demo;
        const money = amount => '$' + (amount / 100).toFixed(2);
        function signal(type) { form.dispatchEvent(new window.CustomEvent(type, {bubbles: true})); }
        function controls() {
            if (disposed) return;
            payButton.disabled = busy || quoting || !quote || !paymentReady;
            retryButton.disabled = busy || quoting;
            shipping.querySelectorAll('input, select').forEach(node => { node.disabled = busy || (!digital && node === state && !countryData().states?.length); });
            const free = quote && quote.total === 0;
            form.querySelectorAll('input').forEach(node => { node.disabled = busy || (free && node.name !== '_token'); });
            const cardFields = form.querySelector('.checkout-card-and-fields');
            if (cardFields) cardFields.hidden = !!free;
            form.setAttribute('aria-busy', String(busy));
            fields.forEach(field => field.update({disabled: busy || !!free}));
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
            if (wallet) { wallet.destroy(); wallet = null; }
            surface.querySelector('[data-checkout-total]').textContent = '—';
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
        function completeAddress() {
            return !composing && shipping.checkValidity() && Array.from(shipping.querySelectorAll('[required]'))
                .every(node => node.disabled || String(node.value).trim() !== '');
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
            if (disposed || busy) return;
            if (selectedCountry !== country.value) { selectedCountry = country.value; states(); }
            let snapshot = details();
            const destination = JSON.stringify(['address1', 'address2', 'country_code', 'state_code', 'city', 'zip'].map(key => snapshot.data[key] || ''));
            if (rate && destinationKey !== null && destinationKey !== destination) {
                rate.replaceChildren();
                shipping.querySelector('[data-shipping-options]').hidden = true;
                snapshot = details();
            }
            destinationKey = destination;
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
            if (disposed || busy || quoting || !completeAddress()) return;
            const snapshot = details();
            pendingAt = 0;
            quoting = true; error.textContent = ''; status.textContent = digital ? 'Confirming your total…' : 'Calculating delivery and tax…'; controls();
            try {
                const result = await request(root.dataset.quoteUrl, snapshot.data);
                if (disposed || composing || snapshot.key !== details().key) return;
                if (result.redirect) { window.location.assign(result.redirect); return; }
                quote = result;
                if (rate) {
                    rate.replaceChildren();
                    result.rates.forEach(item => rate.add(new window.Option(item.name + ' — ' + money(item.id === result.shipping_id ? result.shipping : item.amount), item.id)));
                    rate.value = result.shipping_id;
                    shipping.querySelector('[data-shipping-options]').hidden = false;
                }
                quoteKey = inputKey = details().key;
                pendingAt = 0; cancelScheduled();
                surface.querySelector('[data-checkout-total]').textContent = money(result.total);
                surface.querySelector('[data-checkout-subtotal]').textContent = money(result.subtotal + (result.discount || 0));
                surface.querySelector('[data-checkout-discount-line]').hidden = !result.discount;
                surface.querySelector('[data-checkout-discount]').textContent = '−' + money(result.discount || 0);
                const delivery = surface.querySelector('[data-checkout-shipping]');
                if (delivery) delivery.textContent = money(result.shipping);
                surface.querySelector('[data-checkout-tax]').textContent = money(result.tax);
                status.textContent = digital ? 'Your total is confirmed. Your download will be available after payment.' : 'Delivery and tax confirmed. Review your total before paying.';
                if (wallet) wallet.destroy();
                if (!demo && result.total && window.PianoLITExpressCheckout) wallet = window.PianoLITExpressCheckout.mount(stripe, form, {
                    mode: 'payment', amount: result.total, returnUrl: window.location.origin + '/orders/' + result.order_id,
                    error: failure => { if (!disposed) error.textContent = failure.message; }
                }, payment => pay(payment));
            } catch (failure) {
                if (!disposed && snapshot.key === details().key) {
                    quote = null; pendingAt = 0;
                    error.textContent = failure.message || 'Delivery could not be calculated. Please try again.';
                    status.textContent = 'Please check your address and try again.';
                    retryButton.hidden = false;
                }
            } finally {
                quoting = false;
                controls();
                // Let the existing request finish before quoting the latest settled values.
                // Aborting fetch alone cannot cancel a calculation already running on the server.
                if (!disposed && !quote && pendingAt && quoteTimer === null && completeAddress()) schedule(Math.max(0, pendingAt - Date.now()));
            }
        }
        shipping.addEventListener('submit', function (event) { event.preventDefault(); changed(); });
        async function pay(walletPayment) {
            if (busy || disposed) return;
            if (!quote || quoteKey !== details().key || (quote.expires_at && Date.parse(quote.expires_at) <= Date.now())) {
                changed(true, 0);
                if (walletPayment) throw new Error('Your total is being updated. Please review it before paying.');
                return;
            }
            if (!walletPayment && !demo && quote.total && (completed.size !== 3 || !form.reportValidity())) { if (card) card.focus(); return; }
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
                    if (walletPayment) await walletPayment.confirm(prepared.client_secret, 'payment');
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
                if (walletPayment) throw failure;
            } finally { busy = false; if (refreshQuote) changed(true, 0); controls(); }
        }
        form.addEventListener('submit', function (event) { event.preventDefault(); pay(null); });
        if (!demo) {
            try {
                stripe = window.Stripe(root.dataset.key);
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
                        if (ready.size === 3) { paymentReady = true; form.querySelector('[data-card-status]').textContent = ''; controls(); signal('checkout:loaded'); }
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
            } catch (failure) { error.textContent = 'The secure payment form could not load. Please try again.'; signal('checkout:loaderror'); }
        } else signal('checkout:loaded');
        controls();
        mounted.set(root, {destroy: function () {
            disposed = true;
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
}());
