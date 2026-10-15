(function () {
    'use strict';

    const csrf = document.querySelector('meta[name="csrf-token"]');
    async function request(url, data, method) {
        const options = {method: method || 'POST', credentials: 'same-origin', headers: {'Accept': 'application/json', 'X-CSRF-TOKEN': csrf ? csrf.content : ''}};
        if (options.method !== 'GET') {
            options.headers['Content-Type'] = 'application/json';
            options.body = JSON.stringify(data || {});
        }
        const response = await fetch(url, options);
        let result;
        try { result = await response.json(); }
        catch (error) { throw new Error('Billing is temporarily unavailable. Please try again.'); }
        if (!response.ok) {
            const fields = result.errors ? Object.keys(result.errors) : [];
            throw new Error(fields.length ? result.errors[fields[0]][0] : (result.message || 'We could not complete this billing request. Please try again.'));
        }
        return result;
    }

    const form = document.querySelector('[data-membership-payment]');
    if (form) {
        const button = form.querySelector('button[type="submit"]');
        const errors = form.querySelector('#card-errors');
        let busy = false, stripe, card, cardComplete = false;
        let operation = form.getAttribute('data-operation');
        const purpose = form.getAttribute('data-purpose');
        const coupon = form.querySelector('input[name="coupon"]');
        const feedback = form.querySelector('[data-coupon-feedback]');
        const total = document.querySelector('[data-membership-total]');
        let couponRequest = 0, couponPending = false;
        function message(text) { if (errors) errors.textContent = text; }
        function controls() {
            button.disabled = busy || couponPending || !stripe;
            button.setAttribute('aria-busy', busy ? 'true' : 'false');
            if (coupon) coupon.disabled = busy;
        }
        try {
            stripe = Stripe(form.getAttribute('data-key'));
            card = stripe.elements().create('card');
            card.mount(form.querySelector('#card-element'));
            card.on('change', function (event) { cardComplete = !!event.complete; message(event.error ? event.error.message : ''); });
        } catch (error) { message('The secure card form could not load. Please reload the page.'); stripe = null; }
        controls();

        async function confirmPayment(result, replacement) {
            if (result.subscription_setup_secret) {
                const setup = await stripe.confirmCardSetup(result.subscription_setup_secret);
                if (setup.error) throw new Error(setup.error.message || 'Please finish confirming your card.');
                result.complete = true;
            }
            if (!result.complete) {
                if (!result.payment_secret) throw new Error('Your payment is still pending. Open My Membership to finish it.');
                const confirmation = await stripe.confirmCardPayment(result.payment_secret, replacement && cardComplete ? {payment_method: {card: card}} : {});
                if (confirmation.error) throw new Error(confirmation.error.message || 'Please check your card and try again.');
            }
            const completed = await request(form.getAttribute('data-complete'), {operation_id: operation});
            window.location.assign(completed.redirect);
        }

        form.addEventListener('submit', async function (event) {
            event.preventDefault();
            if (busy || couponPending || !stripe) return;
            busy = true; message(''); controls();
            try {
                const prepared = await request(form.getAttribute('data-prepare'), {operation_id: operation, coupon: coupon ? coupon.value.trim() : null});
                operation = prepared.operation_id;
                if (purpose === 'purchase' && !prepared.setup_secret) {
                    await confirmPayment(prepared, true);
                    return;
                }
                if (prepared.complete && purpose === 'card') { window.location.reload(); return; }
                const confirmation = prepared.setup_complete ? {setupIntent: {id: prepared.setup_intent}}
                    : await stripe.confirmCardSetup(prepared.setup_secret, {payment_method: {card: card}});
                if (confirmation.error) throw new Error(confirmation.error.message || 'Please check your card and try again.');
                const result = await request(form.action, {operation_id: operation, setup_intent: confirmation.setupIntent.id});
                if (purpose === 'purchase') await confirmPayment(result, false);
                else window.location.assign(result.redirect);
            } catch (error) { message(error.message || 'Please check your connection and try again.'); }
            finally { busy = false; controls(); }
        });

        if (coupon) {
            coupon.addEventListener('input', function () {
                couponRequest += 1; couponPending = false; feedback.textContent = '';
                if (total) total.textContent = '$' + (Number(total.getAttribute('data-base-price')) / 100).toFixed(2);
                controls();
            });
            coupon.addEventListener('blur', async function () {
                const code = coupon.value.trim(), current = ++couponRequest;
                if (!code || busy) return;
                couponPending = true; feedback.textContent = 'Checking coupon…'; controls();
                try {
                    const result = await request(coupon.getAttribute('data-coupon-url'), {coupon: code});
                    if (current !== couponRequest || coupon.value.trim() !== code) return;
                    feedback.textContent = result.message;
                    if (total) total.textContent = '$' + (result.amount / 100).toFixed(2);
                } catch (error) {
                    if (current === couponRequest) feedback.textContent = error.message || 'We could not check this coupon. Please try again.';
                } finally {
                    if (current === couponRequest) { couponPending = false; controls(); }
                }
            });
        }
    }

    document.querySelectorAll('button[name="plan"]').forEach(function (button) {
        button.addEventListener('click', function () {
            document.querySelectorAll('button[name="plan"]').forEach(function (other) { other.classList.remove('selected-plan'); });
            button.classList.add('selected-plan');
            const check = document.getElementById('plan-check');
            if (check) button.appendChild(check);
            const action = document.querySelector('#update-plan-action form');
            if (!action) return;
            action.querySelector('input[name="plan"]').value = button.getAttribute('data-name');
            action.style.display = '';
            const description = document.querySelector('#update-plan-action > p');
            if (description) description.style.display = 'none';
        });
    });

    if (window.jQuery) {
        window.jQuery('#faq-accordion').on('show.bs.collapse hide.bs.collapse', function (event) {
            window.jQuery(event.target).siblings('div').find('i').toggleClass('icon-plus', event.type === 'hide').toggleClass('icon-minus', event.type === 'show');
        });
        window.jQuery('a[data-bs-toggle="tab"]').on('shown.bs.tab', function (event) {
            if (event.target.getAttribute('href') === '#invoices-tab') loadInvoices();
        });
    }
    const invoices = document.querySelector('[data-membership-invoices]');
    let invoicesBusy = false, invoicesLoaded = false, after = null;
    async function loadInvoices(next) {
        if (!invoices || invoicesBusy || (invoicesLoaded && !next)) return;
        invoicesBusy = true;
        const status = invoices.querySelector('[data-invoice-status]');
        const content = invoices.querySelector('[data-invoice-content]');
        const more = invoices.querySelector('[data-invoice-more]');
        const retry = invoices.querySelector('[data-invoice-retry]');
        status.textContent = 'Loading invoices…'; more.disabled = true; retry.hidden = true;
        try {
            const url = invoices.getAttribute('data-url') + (next && after ? '?after=' + encodeURIComponent(after) : '');
            const result = await request(url, null, 'GET');
            // HTML comes only from the authenticated, same-origin Blade renderer.
            if (next && invoicesLoaded) {
                const page = new DOMParser().parseFromString(result.html, 'text/html');
                const rows = page.querySelector('#past-invoices-table tbody');
                const existing = content.querySelector('#past-invoices-table tbody');
                if (!rows || !existing) throw new Error('Older invoices could not be displayed. Please try again.');
                existing.insertAdjacentHTML('beforeend', rows.innerHTML);
            } else content.innerHTML = result.html;
            after = result.after; more.hidden = !after; invoicesLoaded = true; status.textContent = '';
        } catch (error) { status.textContent = error.message || 'Invoices could not load.'; retry.hidden = false; }
        finally { invoicesBusy = false; more.disabled = false; }
    }
    if (invoices) {
        invoices.querySelector('[data-invoice-more]').addEventListener('click', function () { loadInvoices(true); });
        invoices.querySelector('[data-invoice-retry]').addEventListener('click', function () { loadInvoices(invoicesLoaded && !!after); });
    }
    if (window.location.hash) {
        const target = document.querySelector('a[data-bs-toggle="tab"][href="' + (['#update-card-tab', '#invoices-tab', '#update-plan-tab'].includes(window.location.hash) ? window.location.hash : '#overview-tab') + '"]');
        if (target && window.bootstrap) window.bootstrap.Tab.getOrCreateInstance(target).show();
    }
}());
