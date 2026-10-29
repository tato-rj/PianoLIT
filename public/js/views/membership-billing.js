(function () {
    'use strict';

    if (window.PianoLITMembershipBilling) return;

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

    const mounted = new WeakMap();
    function mount(form) {
        if (!form || mounted.has(form)) return;
        let disposed = false;
        const root = form.closest ? form.closest('[data-checkout-surface]') || document : document;
        const button = form.querySelector('button[type="submit"]');
        const errors = form.querySelector('#card-errors');
        let busy = false, stripe, card, wallet, cardComplete = false;
        let operation = form.getAttribute('data-operation');
        const purpose = form.getAttribute('data-purpose');
        const coupon = form.querySelector('input[name="coupon"]');
        const feedback = form.querySelector('[data-coupon-feedback]');
        const total = root.querySelector('[data-membership-total]');
        const due = root.querySelector('[data-membership-due]');
        const renewal = root.querySelector('[data-membership-renewal]');
        const consent = form.querySelector('[data-membership-consent]');
        const charge = form.querySelector('[data-membership-charge]');
        const label = form.querySelector('[data-submit-label]');
        const cancel = form.querySelector('[data-payment-card-cancel]');
        const status = form.querySelector('[data-card-status]');
        const apply = form.querySelector('[data-apply-coupon]');
        const name = form.querySelector('[name="cardholder"]');
        const postal = form.querySelector('[name="postal_code"]');
        const splitCard = form.hasAttribute ? form.hasAttribute('data-split-card') : false;
        const numberMount = form.querySelector('[data-stripe-field="cardNumber"]');
        const savedCard = form.querySelector('[data-checkout-saved-card]');
        const addCard = form.querySelector('[data-payment-card-add]');
        const newFields = form.querySelector('[data-checkout-new-fields]');
        const newPreview = form.querySelector('[data-checkout-new-preview]');
        const savedDetails = form.querySelector('[data-checkout-saved-details]');
        const savedCheck = form.querySelector('[data-checkout-card-check]');
        let usingSavedCard = !!savedCard, fieldsInitialized = false, fieldsFailed = false;
        let focusRequested = false;
        const fields = [], completeFields = {}, fieldErrors = {};
        let cardReady = !splitCard, announcedReady = false;
        let couponValid = !coupon || !coupon.value.trim();
        const preview = form.querySelector('[data-card-preview]');
        const cardName = (preview || form).querySelector('[data-card-name]');
        const brandLabel = (preview || form).querySelector('[data-card-brand-label]');
        const brands = {visa: 'Visa', mastercard: 'Mastercard', amex: 'Amex', discover: 'Discover', diners: 'Diners Club', jcb: 'JCB', unionpay: 'UnionPay'};
        function brand(value) {
            if (!preview) return;
            const supported = Object.prototype.hasOwnProperty.call(brands, value);
            preview.setAttribute('data-card-brand', supported ? value : 'unknown');
            if (brandLabel) brandLabel.textContent = supported ? brands[value] : 'Card';
        }
        function holder() { if (cardName) cardName.textContent = name && name.value.trim() ? name.value.trim().slice(0, 36) : 'Your name'; }
        if (name) name.addEventListener('input', holder);
        holder();
        const idleLabel = label ? label.textContent : '';
        let couponRequest = 0, couponPending = false;
        function message(text) { if (errors) errors.textContent = text; }
        function signal(type) {
            if (form.dispatchEvent && window.CustomEvent) form.dispatchEvent(new window.CustomEvent(type, {bubbles: true}));
        }
        function controls() {
            if (disposed) return;
            button.disabled = busy || couponPending || !stripe || (!usingSavedCard && !cardReady);
            button.setAttribute('aria-busy', busy ? 'true' : 'false');
            form.setAttribute('aria-busy', busy ? 'true' : 'false');
            signal('checkout:busy');
            if (coupon) coupon.disabled = busy;
            if (apply) apply.disabled = busy || couponPending;
            if (name) name.disabled = busy || usingSavedCard;
            if (postal) postal.disabled = busy || usingSavedCard;
            if (cancel) cancel.disabled = busy;
            if (savedCard) savedCard.disabled = busy;
            if (addCard) addCard.disabled = busy;
            if (newFields) newFields.disabled = busy || usingSavedCard;
            fields.forEach(function (field) { if (field.update) field.update({disabled: busy}); });
            if (label) label.textContent = busy ? (purpose === 'card' ? 'Saving your card…' : 'Confirming your membership…') : label.getAttribute('data-idle-label') || idleLabel;
            if (status) status.textContent = busy ? 'Please keep this page open. Your bank may ask you to confirm your card.' : (usingSavedCard || cardReady || !stripe || fieldsFailed ? '' : 'Loading secure card fields…');
            if (stripe && (usingSavedCard || cardReady) && !announcedReady) { announcedReady = true; signal('checkout:loaded'); }
            if (!usingSavedCard && fieldsFailed) message('The secure card form could not load. Please reload the page.');
            focusCardWhenReady();
        }
        function focusCardWhenReady() {
            if (!focusRequested || usingSavedCard || !cardReady) return;
            focusRequested = false;
            // Do not move focus if the user chose another field while Stripe loaded.
            if (document.activeElement === numberMount && card && card.focus) card.focus();
        }
        function paymentMethod() {
            const details = {};
            if (name && name.value.trim()) details.name = name.value.trim();
            if (postal && postal.value.trim()) details.address = {postal_code: postal.value.trim()};
            return {card: card, billing_details: details};
        }
        function price(amount, discounted) {
            const formatted = '$' + (amount / 100).toFixed(2);
            if (total) total.textContent = formatted;
            if (due) due.textContent = formatted;
            const trial = consent ? Number(consent.getAttribute('data-trial-days')) : 0;
            const interval = consent ? consent.getAttribute('data-interval') : '';
            if (renewal) renewal.textContent = discounted
                ? (trial ? 'After your trial: ' : 'Due today: ') + formatted + ' for the discounted billing period. Later renewals follow the coupon terms.'
                : (trial ? 'Then ' + formatted + ' every ' + interval + '. Cancel before your ' + trial + '-day trial ends to avoid the first payment.' : formatted + ' every ' + interval + ', starting today. Cancel anytime to stop future renewals.');
            if (charge) charge.textContent = discounted
                ? (trial ? 'Your ' + trial + '-day free trial starts when checkout is complete. Then you’ll pay ' : 'You authorize a payment of ') + formatted + ' for the discounted billing period. Your subscription renews automatically until canceled; later payments follow the coupon terms. '
                : (trial ? 'Your ' + trial + '-day free trial starts when checkout is complete. Then ' + formatted + ' is charged every ' + interval + ' until you cancel. ' : 'You authorize a payment of ' + formatted + ' today, then every ' + interval + ' until you cancel. Returning members do not receive another introductory trial. ');
            if (label && !trial) label.setAttribute('data-idle-label', 'Subscribe for ' + formatted);
            if (wallet) wallet.update({applePay: recurringWallet(amount)});
        }
        function recurringWallet(amount) {
            const trial = Number(form.getAttribute('data-wallet-trial')) || 0;
            return {recurringPaymentRequest: {paymentDescription: 'PianoLIT Premium', managementURL: form.getAttribute('data-wallet-manage'),
                regularBilling: {amount: Math.round(amount), label: 'PianoLIT Premium',
                    recurringPaymentIntervalUnit: form.getAttribute('data-wallet-interval'), recurringPaymentIntervalCount: 1,
                    ...(trial ? {recurringPaymentStartDate: new Date(Date.now() + trial * 86400000)} : {})},
                ...(trial ? {trialBilling: {amount: 0, label: trial + '-day free trial', recurringPaymentIntervalUnit: 'day', recurringPaymentIntervalCount: trial}} : {}),
                billingAgreement: charge ? charge.textContent.trim() : 'Your subscription renews automatically until canceled.'}};
        }
        function initializeFields() {
            if (fieldsInitialized || !stripe) return;
            fieldsInitialized = true;
            try {
                const elements = stripe.elements();
                if (splitCard) {
                    // Hosted fields use the same resolved values as PianoLIT's native inputs.
                    const inputStyle = window.getComputedStyle(name);
                    const tokens = window.getComputedStyle(form);
                    const style = {base: {color: inputStyle.color, fontFamily: inputStyle.fontFamily,
                        fontSize: inputStyle.fontSize, lineHeight: inputStyle.lineHeight, fontSmoothing: 'antialiased',
                        '::placeholder': {color: tokens.getPropertyValue('--field-muted').trim()}},
                        invalid: {color: tokens.getPropertyValue('--color-danger').trim()}};
                    const readyFields = new Set();
                    form.querySelectorAll('[data-stripe-field]').forEach(function (mount) {
                        const type = mount.getAttribute('data-stripe-field');
                        const field = elements.create(type, {style: style, ...(type === 'cardNumber' ? {showIcon: true} : {})});
                        fields.push(field);
                        if (type === 'cardNumber') card = field;
                        field.on('ready', function () { readyFields.add(type); cardReady = fields.length === 3 && readyFields.size === 3; controls(); });
                        field.on('change', function (event) {
                            if (type === 'cardNumber') brand(event.brand);
                            completeFields[type] = !!event.complete;
                            fieldErrors[type] = event.error ? event.error.message : '';
                            cardComplete = fields.length === 3 && Object.keys(completeFields).filter(key => completeFields[key]).length === 3;
                            message(Object.keys(fieldErrors).map(key => fieldErrors[key]).find(Boolean) || '');
                        });
                        field.mount(mount);
                    });
                } else {
                    card = elements.create('card');
                    card.mount(form.querySelector('#card-element'));
                    card.on('change', function (event) { cardComplete = !!event.complete; message(event.error ? event.error.message : ''); });
                }
            } catch (error) { fieldsFailed = true; message('The secure card form could not load. Please reload the page.'); if (!savedCard) stripe = null; }
        }
        try { stripe = Stripe(form.getAttribute('data-key')); }
        catch (error) { message('The secure card form could not load. Please reload the page.'); }
        if (!usingSavedCard) initializeFields();
        controls();
        if (!stripe) signal('checkout:loaderror');

        function chooseCard(saved) {
            if (busy || disposed) return;
            usingSavedCard = saved;
            savedCard.setAttribute('aria-pressed', saved ? 'true' : 'false');
            savedCheck.hidden = !saved;
            savedDetails.hidden = !saved;
            addCard.hidden = !saved;
            addCard.setAttribute('aria-expanded', saved ? 'false' : 'true');
            newFields.hidden = saved;
            newPreview.hidden = saved;
            message('');
            controls();
            if (!saved) {
                initializeFields();
                numberMount.focus(); focusRequested = true;
            }
            controls();
        }
        if (savedCard) {
            savedCard.addEventListener('click', function () { chooseCard(true); });
            addCard.addEventListener('click', function () { chooseCard(false); });
        }

        async function confirmPayment(result, replacement) {
            if (result.subscription_setup_secret) {
                const setup = await stripe.confirmCardSetup(result.subscription_setup_secret);
                if (setup.error) throw new Error(setup.error.message || 'Please finish confirming your card.');
                result.complete = true;
            }
            if (!result.complete) {
                if (!result.payment_secret) throw new Error('Your payment is still pending. Open My Membership to finish it.');
                const confirmation = await stripe.confirmCardPayment(result.payment_secret, replacement && cardComplete ? {payment_method: paymentMethod()} : {});
                if (confirmation.error) throw new Error(confirmation.error.message || 'Please check your card and try again.');
            }
            const completed = await request(form.getAttribute('data-complete'), {operation_id: operation});
            window.location.assign(completed.redirect);
        }

        form.addEventListener('submit', async function (event) {
            event.preventDefault();
            if (busy || couponPending || !stripe || (!usingSavedCard && !cardReady)) return;
            busy = true; message(''); controls();
            try {
                if (coupon && coupon.value.trim() && !couponValid) {
                    await checkCoupon();
                    if (!couponValid) throw new Error('Please apply a valid coupon or remove the code before continuing.');
                }
                const prepared = await request(form.getAttribute('data-prepare'), {operation_id: operation, coupon: coupon ? coupon.value.trim() : null,
                    ...(savedCard ? {use_saved_card: usingSavedCard, saved_card_reference: usingSavedCard ? form.getAttribute('data-saved-card-reference') : null} : {})});
                operation = prepared.operation_id;
                if (prepared.saved_card) {
                    const result = await request(form.action, {operation_id: operation, use_saved_card: true, saved_card_reference: form.getAttribute('data-saved-card-reference')});
                    await confirmPayment(result, false);
                    return;
                }
                if (purpose === 'purchase' && !prepared.setup_secret) {
                    await confirmPayment(prepared, !usingSavedCard);
                    return;
                }
                if (prepared.complete && purpose === 'card') { window.location.reload(); return; }
                const confirmation = prepared.setup_complete ? {setupIntent: {id: prepared.setup_intent}}
                    : await stripe.confirmCardSetup(prepared.setup_secret, {payment_method: paymentMethod()});
                if (confirmation.error) throw new Error(confirmation.error.message || 'Please check your card and try again.');
                const result = await request(form.action, {operation_id: operation, setup_intent: confirmation.setupIntent.id});
                if (purpose === 'purchase') await confirmPayment(result, false);
                else window.location.assign(result.redirect);
            } catch (error) {
                message(error.message || 'Please check your connection and try again.');
            }
            finally {
                busy = false; controls();
                // Restoring Bootstrap's focus trap first keeps the error focused.
                if (errors && errors.textContent && errors.focus) errors.focus();
            }
        });

        if (purpose === 'purchase' && stripe && window.PianoLITExpressCheckout) {
            wallet = window.PianoLITExpressCheckout.mount(stripe, form, {mode: 'setup',
                returnUrl: form.getAttribute('data-wallet-return'), applePay: recurringWallet(Number(form.getAttribute('data-wallet-amount'))),
                error: function (error) { message(error.message || 'Your wallet payment could not be confirmed.'); }}, async function (payment) {
                if (busy || disposed || couponPending) throw new Error('Please wait for the current checkout request to finish.');
                busy = true; message(''); controls();
                try {
                    if (coupon && coupon.value.trim() && !couponValid) {
                        await checkCoupon();
                        if (!couponValid) throw new Error('Please apply a valid coupon or remove it before continuing.');
                    }
                    const prepared = await request(form.getAttribute('data-prepare'), {operation_id: operation,
                        coupon: coupon ? coupon.value.trim() : null, use_saved_card: false, wallet: true});
                    operation = prepared.operation_id;
                    if (!prepared.setup_secret) throw new Error('This checkout already has a payment in progress. Please finish it with the payment form below.');
                    const confirmed = await payment.confirm(prepared.setup_secret, 'setup');
                    const result = await request(form.action, {operation_id: operation, setup_intent: confirmed.setupIntent.id});
                    await confirmPayment(result, false);
                } finally { busy = false; controls(); }
            });
        }

        if (purpose === 'purchase' && form.getAttribute('data-wallet-resume')) {
            (async function () {
                busy = true; message(''); controls();
                try {
                    const prepared = await request(form.getAttribute('data-prepare'), {operation_id: operation,
                        coupon: coupon ? coupon.value.trim() : null, use_saved_card: false});
                    if (!prepared.setup_complete || !prepared.setup_intent) throw new Error('Please confirm your wallet again to finish checkout.');
                    const result = await request(form.action, {operation_id: operation, setup_intent: prepared.setup_intent});
                    await confirmPayment(result, false);
                } catch (failure) { message(failure.message || 'Please check your payment before trying again.'); }
                finally { busy = false; controls(); }
            }());
        }

        if (coupon) {
            coupon.addEventListener('input', function () {
                couponRequest += 1; couponPending = false; couponValid = !coupon.value.trim(); feedback.textContent = '';
                if (total) price(Number(total.getAttribute('data-base-price')), false);
                controls();
            });
            coupon.addEventListener('blur', function () { if (!busy && !couponPending) return checkCoupon(); });
            if (apply) apply.addEventListener('click', function () { if (!busy && !couponPending) return checkCoupon(); });
            if (coupon.value.trim()) checkCoupon();
        }
        async function checkCoupon() {
            const code = coupon.value.trim(), current = ++couponRequest;
            if (!code) return;
            couponPending = true; couponValid = false; feedback.textContent = 'Checking coupon…'; controls();
            try {
                const result = await request(coupon.getAttribute('data-coupon-url'), {coupon: code});
                if (disposed || current !== couponRequest || coupon.value.trim() !== code) return;
                couponValid = true;
                feedback.textContent = result.message;
                price(result.amount, true);
            } catch (error) {
                if (current === couponRequest) feedback.textContent = error.message || 'We could not check this coupon. Please try again.';
            } finally {
                if (current === couponRequest) { couponPending = false; controls(); }
            }
        }
        mounted.set(form, {focus() { focusRequested = true; focusCardWhenReady(); }, reset() {
            if (busy) return;
            focusRequested = false;
            if (form.reset) form.reset();
            fields.forEach(function (field) { if (field.clear) field.clear(); });
            if (!splitCard && card && card.clear) card.clear();
            cardComplete = false;
            Object.keys(completeFields).forEach(key => { completeFields[key] = false; });
            Object.keys(fieldErrors).forEach(key => { delete fieldErrors[key]; });
            brand('unknown'); holder();
            if (stripe) message('');
            controls();
        }, destroy() {
            disposed = true; couponRequest += 1;
            if (wallet) wallet.destroy();
            fields.forEach(function (field) { if (field.destroy) field.destroy(); });
            if (!splitCard && card && card.destroy) card.destroy();
            mounted.delete(form);
        }});
    }

    window.PianoLITMembershipBilling = {mount};
    function mountCurrent(event) {
        const root = event && event.type === 'checkout:ready' ? event.target : document;
        const form = root.querySelector('[data-membership-payment]');
        if (form && form.hasAttribute && form.hasAttribute('data-deferred-card')) return;
        // The checkout launcher owns mounting after both secure scripts are ready.
        // Ordinary My Membership forms keep their existing automatic setup.
        if (root === document && form && form.closest && form.closest('[data-checkout-modal]')) return;
        mount(form);
    }
    mountCurrent();

    document.querySelectorAll('[data-payment-card-editor]').forEach(function (editor) {
        const form = editor.querySelector('[data-membership-payment]');
        const dialog = window.bootstrap.Modal.getOrCreateInstance(editor);
        function busy() { return form.getAttribute('aria-busy') === 'true'; }
        editor.addEventListener('shown.bs.modal', function () {
            // Stripe mounts at the dialog's final visible dimensions.
            form.querySelector('[data-stripe-field="cardNumber"]').focus();
            mount(form);
            mounted.get(form).focus();
        });
        editor.addEventListener('hide.bs.modal', function (event) {
            if (busy()) event.preventDefault();
        });
        editor.addEventListener('hidden.bs.modal', function () {
            if (mounted.has(form)) mounted.get(form).reset();
        });
        editor.addEventListener('checkout:busy', function () {
            const pending = busy();
            editor.querySelectorAll('[data-bs-dismiss="modal"]').forEach(function (button) { button.disabled = pending; });
            // Bank authentication opens outside this dialog; restore its trap before focusing an error.
            const trap = dialog._focustrap;
            if (trap) { if (pending) trap.deactivate(); else if (editor.classList.contains('show')) trap.activate(); }
        });
    });
    if (document.addEventListener) {
        document.addEventListener('checkout:ready', mountCurrent);
        document.addEventListener('checkout:dispose', function (event) {
            const form = event.target.querySelector('[data-membership-payment]');
            if (form && mounted.has(form)) mounted.get(form).destroy();
        });
    }

    document.querySelectorAll('button[name="plan"]').forEach(function (button) {
        button.addEventListener('click', function () {
            if (button.disabled) return;
            const action = document.querySelector('#update-plan-action form');
            if (!action) return;
            document.querySelectorAll('button[name="plan"]').forEach(function (other) {
                other.setAttribute('aria-pressed', other === button ? 'true' : 'false');
                other.closest('[data-plan-card]').setAttribute('data-selected', other === button ? 'true' : 'false');
            });
            action.querySelector('input[name="plan"]').value = button.getAttribute('data-name');
            action.querySelector('[data-plan-confirmation]').textContent = button.getAttribute('data-confirmation');
            action.style.display = '';
            const description = document.querySelector('#update-plan-action > p');
            if (description) description.style.display = 'none';
        });
    });

    // Summary links activate the real tab trigger, preserving tab and invoice behavior.
    document.querySelectorAll('[data-membership-tab]').forEach(function (link) {
        link.addEventListener('click', function (event) {
            const target = document.querySelector('#membership-tabs a[href="' + link.getAttribute('href') + '"]');
            if (!target || !window.bootstrap) return;
            event.preventDefault();
            window.bootstrap.Tab.getOrCreateInstance(target).show();
            target.focus();
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
    // Also handle a tab restored before this deferred page script was loaded.
    if (invoices && invoices.classList.contains('active')) loadInvoices();
}());
