(function (root, factory) {
    if (typeof module === 'object' && module.exports) module.exports = factory;
    else factory(root);
}(typeof window !== 'undefined' ? window : this, function (root) {
    'use strict';
    const doc = root.document;
    const checkout = doc.querySelector('[data-shop-checkout]');
    if (!checkout) return;
    const forms = Array.from(checkout.querySelectorAll('[data-shop-payment]'));
    if (!forms.length) return;
    const newForm = doc.getElementById('payment-form');
    const methods = Array.from(checkout.querySelectorAll('[data-payment-target]'));
    const cardField = doc.getElementById('card-element');
    const cardErrors = doc.getElementById('card-errors');
    const cardStatus = checkout.querySelector('[data-card-status]');
    const cardRetry = checkout.querySelector('[data-card-retry]');
    const coupon = checkout.querySelector('[data-shop-coupon]');
    const couponInput = coupon.querySelector('input');
    const couponApply = coupon.querySelector('[data-coupon-apply]');
    const couponRemove = coupon.querySelector('[data-coupon-remove]');
    const couponLoading = coupon.querySelector('[data-coupon-loading]');
    const couponFeedback = coupon.querySelector('[data-coupon-feedback]');
    const base = Number(checkout.dataset.orderBase);
    let total = base;
    let busy = false;
    let checking = false;
    let ready = false;
    let complete = false;
    let stripe, card;
    let cardGeneration = 0;
    let submitGeneration = 0;
    let couponGeneration = 0;
    let couponController;
    let cardTimer, submitTimer, couponTimer;

    const money = cents => '$' + (cents / 100).toFixed(2);
    const activeForm = () => forms.find(form => !form.hidden);

    function render() {
        checkout.setAttribute('aria-busy', String(busy));
        forms.forEach(function (form) {
            const button = form.querySelector('[type="submit"]');
            button.disabled = busy || checking || (form === newForm && (!ready || !complete));
            button.textContent = busy && form === activeForm() ? 'Processing…' : total ? 'Pay ' + money(total) : 'Place order';
        });
        methods.forEach(method => { method.disabled = busy; });
        couponInput.disabled = busy;
        couponApply.disabled = busy || checking;
        couponRemove.disabled = busy;
        couponLoading.hidden = !checking;
        checkout.querySelector('[data-order-total]').textContent = money(total);
        checkout.querySelector('[data-order-discount-row]').hidden = total === base;
        checkout.querySelector('[data-order-discount]').textContent = '−' + money(base - total);
    }

    function clearCoupon() {
        total = base;
        forms.forEach(form => { form.querySelector('[data-coupon-value]').value = ''; });
        couponRemove.hidden = true;
    }

    function cancelCoupon() {
        ++couponGeneration;
        if (couponController) couponController.abort();
        root.clearTimeout(couponTimer);
        checking = false;
    }

    couponInput.addEventListener('input', function () {
        cancelCoupon();
        clearCoupon();
        couponFeedback.textContent = '';
        render();
    });
    couponInput.addEventListener('keydown', function (event) {
        if (event.key === 'Enter') { event.preventDefault(); applyCoupon(); }
    });
    couponRemove.addEventListener('click', function () {
        if (busy) return;
        cancelCoupon();
        clearCoupon();
        couponInput.value = '';
        couponFeedback.textContent = 'Promo code removed.';
        couponFeedback.classList.remove('text-danger', 'text-success');
        render();
    });
    couponApply.addEventListener('click', applyCoupon);

    async function applyCoupon() {
        if (busy || checking) return;
        const code = couponInput.value.trim().toUpperCase();
        cancelCoupon();
        clearCoupon();
        couponFeedback.classList.remove('text-danger', 'text-success');
        if (!code) { couponFeedback.textContent = 'Enter a promo code first.'; render(); return; }
        const request = ++couponGeneration;
        couponController = new root.AbortController();
        checking = true;
        couponFeedback.textContent = '';
        render();
        couponTimer = root.setTimeout(() => couponController.abort(), 10000);
        try {
            const url = new root.URL(coupon.dataset.url, root.location.href);
            url.searchParams.set('coupon', code);
            const response = await root.fetch(url.href, {credentials: 'same-origin', headers: {Accept: 'application/json'}, signal: couponController.signal});
            if (!response.ok) throw new Error('Coupon request failed');
            const data = await response.json();
            if (request !== couponGeneration) return;
            const percent = Number(data.percentOff);
            if (data.isValid === true && Number.isFinite(percent) && percent >= 0 && percent <= 100) {
                // Match the existing shop charge calculation; the server rechecks the code.
                total = base - Math.round(base * percent / 100);
                forms.forEach(form => { form.querySelector('[data-coupon-value]').value = code; });
                couponInput.value = code;
                couponRemove.hidden = false;
                couponFeedback.classList.add('text-success');
                couponFeedback.textContent = 'Promo code applied.';
            } else {
                couponFeedback.classList.add('text-danger');
                couponFeedback.textContent = data.isValid === false && typeof data.message === 'string' ? data.message : 'We couldn’t confirm this discount. Please try again.';
            }
        } catch (error) {
            if (request !== couponGeneration) return;
            couponFeedback.classList.add('text-danger');
            couponFeedback.textContent = 'We couldn’t check this code. Please try again.';
        } finally {
            if (request === couponGeneration) {
                root.clearTimeout(couponTimer);
                checking = false;
                render();
            }
        }
    }

    methods.forEach(method => method.addEventListener('change', function () {
        if (busy || !method.checked) return;
        forms.forEach(form => { form.hidden = form.id !== method.dataset.paymentTarget; });
        // Keep the draft, accepted coupon, save-card choice and CSRF tokens intact.
        render();
    }));

    function failCard(message) {
        ++cardGeneration;
        root.clearTimeout(cardTimer);
        ready = false;
        cardStatus.hidden = true;
        cardErrors.textContent = message;
        cardRetry.hidden = false;
        render();
    }

    function initializeCard() {
        const request = ++cardGeneration;
        ready = complete = false;
        cardStatus.hidden = false;
        cardStatus.textContent = 'Loading secure card field…';
        cardErrors.textContent = '';
        cardRetry.hidden = true;
        root.clearTimeout(cardTimer);
        render();
        try {
            if (typeof root.Stripe !== 'function' || !newForm.dataset.key) throw new Error('Stripe unavailable');
            if (card) card.destroy();
            stripe = root.Stripe(newForm.dataset.key);
            const style = root.getComputedStyle(cardField);
            card = stripe.elements().create('card', {hidePostalCode: true, style: {
                base: {fontSize: style.fontSize, fontFamily: style.fontFamily, color: style.color},
                invalid: {color: '#dc3545'}
            }});
            card.addEventListener('ready', function () {
                if (request !== cardGeneration) return;
                root.clearTimeout(cardTimer);
                ready = true;
                cardStatus.hidden = true;
                render();
            });
            card.addEventListener('change', function (event) {
                if (request !== cardGeneration) return;
                complete = event.complete;
                cardErrors.textContent = event.error ? event.error.message : '';
                render();
            });
            cardTimer = root.setTimeout(() => { if (request === cardGeneration) failCard('The secure card field couldn’t load. Please reload it.'); }, 15000);
            card.mount('#card-element');
        } catch (error) {
            failCard('The secure card field couldn’t load. Please reload it.');
        }
    }

    cardRetry.addEventListener('click', function () {
        if (busy) return;
        if (typeof root.Stripe !== 'function') root.location.reload();
        else initializeCard();
    });

    forms.forEach(form => form.addEventListener('submit', async function (event) {
        if (busy || checking || form !== activeForm()) { event.preventDefault(); return; }
        if (form !== newForm) { busy = true; render(); return; }
        event.preventDefault();
        if (!ready || !complete) { if (card) card.focus(); return; }
        const request = ++submitGeneration;
        busy = true;
        cardErrors.textContent = '';
        render();
        submitTimer = root.setTimeout(function () {
            if (request !== submitGeneration) return;
            ++submitGeneration;
            busy = false;
            cardErrors.textContent = 'We couldn’t verify your card. Please try again.';
            render();
        }, 20000);
        try {
            const result = await stripe.createToken(card);
            if (request !== submitGeneration) return;
            root.clearTimeout(submitTimer);
            if (result.error || !result.token) throw new Error(result.error ? result.error.message : 'We couldn’t verify your card. Please try again.');
            form.querySelectorAll('input[name="stripeToken"]').forEach(input => input.remove());
            const token = doc.createElement('input');
            token.type = 'hidden';
            token.name = 'stripeToken';
            token.value = result.token.id;
            form.appendChild(token);
            form.submit();
        } catch (error) {
            if (request !== submitGeneration) return;
            root.clearTimeout(submitTimer);
            busy = false;
            cardErrors.textContent = error.message || 'We couldn’t verify your card. Please try again.';
            render();
        }
    }));

    root.addEventListener('pagehide', function () {
        ++submitGeneration;
        root.clearTimeout(submitTimer);
        cancelCoupon();
    });
    root.addEventListener('pageshow', function (event) {
        if (event.persisted) { busy = false; render(); }
    });
    initializeCard();
}));
