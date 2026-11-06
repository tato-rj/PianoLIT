(function (root, factory) {
    if (typeof module === 'object' && module.exports) module.exports = factory;
    else root.PianoLITExpressCheckout = factory(root);
}(typeof window !== 'undefined' ? window : this, function (win) {
    'use strict';
    return {mount: function (stripe, form, options, onConfirm) {
        const shell = options.shell || form.querySelector('[data-checkout-wallets]');
        if (!shell || win.location.protocol !== 'https:') return null;
        try {
            let disposed = false, interaction = 0;
            const elements = stripe.elements({mode: options.mode, currency: 'usd', paymentMethodTypes: ['card'],
                ...(options.mode === 'payment' ? {amount: options.amount} : {}),
                appearance: {variables: {fontFamily: win.getComputedStyle(form).fontFamily, fontSizeBase: '16px', borderRadius: '12px'}}});
            const express = elements.create('expressCheckout', {
                buttonHeight: 48, buttonTheme: {applePay: 'black', googlePay: 'black'},
                paymentMethods: {applePay: 'auto', googlePay: 'auto', link: 'never', paypal: 'never', amazonPay: 'never', klarna: 'never'},
                ...(options.collection || {}),
                ...(options.applePay ? {applePay: options.applePay} : {})
            });
            function update(values) {
                if (disposed) return;
                const {amount, ...settings} = values;
                if (amount !== undefined) elements.update({amount});
                if (Object.keys(settings).length) express.update(settings);
            }
            shell.hidden = false;
            function availability(event) {
                if (disposed) return;
                shell.hidden = !event.availablePaymentMethods || !Object.values(event.availablePaymentMethods).some(Boolean);
                shell.dataset.walletState = 'ready';
            }
            express.on('ready', availability);
            express.on('availablepaymentmethodschange', availability);
            express.on('loaderror', function () { shell.hidden = true; });
            if (options.click) express.on('click', function (event) { if (!disposed) options.click(event); });
            express.on('cancel', function () { interaction++; if (!disposed && options.cancel) options.cancel(); });
            [['shippingaddresschange', options.shippingAddressChange], ['shippingratechange', options.shippingRateChange]].forEach(function ([name, handler]) {
                if (!handler) return;
                express.on(name, async function (event) {
                    if (disposed) { event.reject(); return; }
                    try {
                        const values = await handler(event);
                        if (disposed) { event.reject(); return; }
                        const {amount, ...settings} = values;
                        update({amount});
                        event.resolve(settings);
                    } catch (error) {
                        event.reject();
                        if (!disposed && options.error && !error.obsolete) options.error(error);
                    }
                });
            });
            express.on('confirm', async function (event) {
                if (disposed) return;
                const version = interaction;
                try {
                    await onConfirm({event, confirm: async function (secret, kind, returnUrl) {
                        if (disposed || version !== interaction) throw new Error('Checkout was canceled. Please open it again.');
                        const submitted = await elements.submit();
                        if (submitted.error) throw new Error(submitted.error.message);
                        if (disposed || version !== interaction) throw new Error('Checkout was canceled. Please open it again.');
                        const result = await stripe[kind === 'setup' ? 'confirmSetup' : 'confirmPayment']({elements: elements,
                            clientSecret: secret, confirmParams: {return_url: returnUrl || options.returnUrl}, redirect: 'if_required'});
                        if (result.error) throw new Error(result.error.message || 'Your wallet payment could not be confirmed.');
                        return result;
                    }});
                } catch (error) {
                    if (event.paymentFailed) event.paymentFailed({reason: error.paymentFailureReason || 'fail', message: error.message || 'Payment could not be confirmed.'});
                    if (!disposed && options.error) options.error(error);
                }
            });
            express.mount(shell.querySelector('[data-wallet-mount]'));
            return {destroy: function () { disposed = true; express.destroy(); shell.hidden = true; },
                update};
        } catch (failure) { shell.hidden = true; return null; }
    }};
}));
