(function (root, factory) {
    if (typeof module === 'object' && module.exports) module.exports = factory;
    else root.PianoLITExpressCheckout = factory(root);
}(typeof window !== 'undefined' ? window : this, function (win) {
    'use strict';
    return {mount: function (stripe, form, options, onConfirm) {
        const shell = form.querySelector('[data-checkout-wallets]');
        if (!shell || win.location.protocol !== 'https:') return null;
        try {
            let disposed = false;
            const elements = stripe.elements({mode: options.mode, currency: 'usd', paymentMethodTypes: ['card'],
                ...(options.mode === 'payment' ? {amount: options.amount} : {}),
                appearance: {variables: {fontFamily: win.getComputedStyle(form).fontFamily, fontSizeBase: '16px', borderRadius: '12px'}}});
            const express = elements.create('expressCheckout', {
                buttonHeight: 48, buttonTheme: {applePay: 'black', googlePay: 'black'},
                paymentMethods: {applePay: 'auto', googlePay: 'auto', link: 'never', paypal: 'never', amazonPay: 'never', klarna: 'never'},
                ...(options.applePay ? {applePay: options.applePay} : {})
            });
            shell.hidden = false;
            function availability(event) {
                if (disposed) return;
                shell.hidden = !event.availablePaymentMethods || !Object.values(event.availablePaymentMethods).some(Boolean);
                shell.dataset.walletState = 'ready';
            }
            express.on('ready', availability);
            express.on('availablepaymentmethodschange', availability);
            express.on('loaderror', function () { shell.hidden = true; });
            express.on('confirm', async function (event) {
                if (disposed) return;
                try {
                    await onConfirm({confirm: async function (secret, kind) {
                        const submitted = await elements.submit();
                        if (submitted.error) throw new Error(submitted.error.message);
                        const result = await stripe[kind === 'setup' ? 'confirmSetup' : 'confirmPayment']({elements: elements,
                            clientSecret: secret, confirmParams: {return_url: options.returnUrl}, redirect: 'if_required'});
                        if (result.error) throw new Error(result.error.message || 'Your wallet payment could not be confirmed.');
                        return result;
                    }});
                } catch (error) {
                    if (event.paymentFailed) event.paymentFailed({reason: 'fail', message: error.message || 'Payment could not be confirmed.'});
                    if (!disposed && options.error) options.error(error);
                }
            });
            express.mount(shell.querySelector('[data-wallet-mount]'));
            return {destroy: function () { disposed = true; express.destroy(); shell.hidden = true; },
                update: function (values) { if (values.applePay) express.update({applePay: values.applePay}); }};
        } catch (failure) { shell.hidden = true; return null; }
    }};
}));
