// Fetch the server-rendered checkout on demand. The shell can host other orders later.
module.exports = function (win) {
    const doc = win.document;
    const scripts = new Map();
    function sameOrigin(value) {
        const url = new URL(value, win.location.href);
        if (url.origin !== new URL(win.location.href).origin) throw new Error('Checkout must stay on PianoLIT.');
        return url.href;
    }
    function script(url) {
        if (scripts.has(url)) return scripts.get(url);
        const promise = new Promise((resolve, reject) => {
            const node = doc.createElement('script');
            const timer = win.setTimeout(() => fail(), 20000);
            function fail() {
                win.clearTimeout(timer); node.remove(); scripts.delete(url);
                reject(new Error('The secure payment form could not load. Please try again.'));
            }
            node.src = url; node.async = true;
            node.onload = () => { win.clearTimeout(timer); resolve(); };
            node.onerror = fail;
            doc.head.appendChild(node);
        });
        scripts.set(url, promise);
        return promise;
    }
    class Launcher {
        constructor(modal) {
            this.modal = modal;
            this.content = modal.querySelector('.modal-content');
            this.loadingMarkup = modal.querySelector('[data-checkout-loading]').outerHTML;
            this.direct = modal.dataset.checkoutDirect === 'true';
            this.cancelUrl = sameOrigin(modal.dataset.checkoutCancelUrl);
            this.generation = 0;
            this.dialog = win.bootstrap.Modal.getOrCreateInstance(modal);
            this.visible = false;
            // Keep the fullscreen overlay outside page containers and their stacking contexts.
            doc.body.appendChild(modal);
            modal.addEventListener('show.bs.modal', () => {
                this.visible = false;
                this.background = doc.getElementById('webapp');
                if (this.background) { this.previousInert = this.background.inert; this.background.inert = true; }
            });
            modal.addEventListener('shown.bs.modal', () => {
                this.visible = true;
                if (this.closeRequested) { this.dialog.hide(); return; }
                this.reveal();
            });
            modal.addEventListener('hide.bs.modal', event => {
                if (this.busy()) { event.preventDefault(); return; }
                this.visible = false;
                this.dispose();
            });
            modal.addEventListener('hidden.bs.modal', () => {
                if (this.background) this.background.inert = this.previousInert;
                this.urlState(null);
                if (this.direct) { win.location.assign(this.cancelUrl); return; }
                this.loading();
                if (this.opener && this.opener.isConnected) this.opener.focus({preventScroll: true});
            });
            modal.addEventListener('checkout:busy', () => {
                const close = modal.querySelector('.fullscreen-modal-close');
                if (close) close.disabled = this.busy();
                // Stripe's bank challenge lives outside Bootstrap's dialog. Let Stripe own
                // focus during confirmation, then restore the dialog trap after an error.
                const trap = this.dialog._focustrap;
                if (trap) { if (this.busy()) trap.deactivate(); else if (modal.classList.contains('show')) trap.activate(); }
            });
            modal.addEventListener('click', event => {
                if (event.target.closest('[data-checkout-cancel]')) {
                    event.preventDefault();
                    if (!this.busy()) {
                        this.closeRequested = true;
                        if (!this.visible) this.dispose();
                        this.dialog.hide();
                    }
                }
                if (event.target.closest('[data-checkout-retry]') && this.url) this.open(this.url, this.opener);
            });
            doc.addEventListener('click', event => {
                const link = event.target.closest('[data-checkout-open]');
                if (!link || event.defaultPrevented || event.button !== 0 || event.metaKey || event.ctrlKey || event.shiftKey || event.altKey) return;
                event.preventDefault(); this.open(link.href, link);
            });
        }
        busy() { return !!this.modal.querySelector('[data-checkout-payment][aria-busy="true"]'); }
        urlState(key) {
            const url = new URL(win.location.href);
            if (key && !this.direct) url.searchParams.set('checkout', key);
            else url.searchParams.delete('checkout');
            if (url.href !== win.location.href) win.history.replaceState(win.history.state, '', url.href);
        }
        dispose() {
            this.generation += 1;
            if (this.abort) this.abort.abort();
            if (this.cancelReady) this.cancelReady();
            this.modal.dispatchEvent(new win.CustomEvent('checkout:dispose', {bubbles: true}));
        }
        state(value) {
            this.modal.dataset.checkoutState = value;
            this.modal.setAttribute('aria-busy', value === 'loading' ? 'true' : 'false');
            this.modal.setAttribute('aria-labelledby', value === 'ready' ? 'checkout-heading' : 'checkout-loading-heading');
        }
        loading() {
            this.prepared = false;
            this.content.innerHTML = this.loadingMarkup;
            this.state('loading');
        }
        stripeScript() {
            // Start alongside the HTML request. Handle rejection immediately even if
            // the dialog closes before the server responds.
            return (win.Stripe ? Promise.resolve() : script('https://js.stripe.com/v3/'))
                .then(() => null, error => error);
        }
        reveal() {
            if (!this.prepared || !this.visible || this.closeRequested) return;
            const stage = this.modal.querySelector('[data-checkout-stage]');
            const loading = this.modal.querySelector('[data-checkout-loading]');
            if (!stage) return;
            stage.inert = false; stage.removeAttribute('aria-hidden');
            loading.inert = true; loading.setAttribute('aria-hidden', 'true');
            this.state('ready');
            const heading = this.modal.querySelector('#checkout-heading');
            if (heading) { heading.setAttribute('tabindex', '-1'); heading.focus({preventScroll: true}); }
        }
        async ready(generation, stripeReady) {
            const controller = this.modal.dataset.checkoutController;
            const controllerUrl = new URL(sameOrigin(controller));
            if (!controllerUrl.pathname.startsWith('/js/views/')) throw new Error('The payment form could not be opened.');
            await Promise.all([
                (stripeReady || this.stripeScript()).then(error => { if (error) throw error; }),
                script(controllerUrl.href)
            ]);
            if (generation !== this.generation) return;
            // Mount at the final dimensions behind the placeholder. Card iframes
            // must all report ready before users see or focus the payment form.
            await new Promise((resolve, reject) => {
                let timer;
                const cleanup = () => {
                    win.clearTimeout(timer);
                    this.modal.removeEventListener('checkout:loaded', loaded);
                    this.modal.removeEventListener('checkout:loaderror', failed);
                    this.cancelReady = null;
                };
                const loaded = () => { cleanup(); resolve(); };
                const failed = () => { cleanup(); reject(new Error('The secure payment form could not load. Please try again.')); };
                this.cancelReady = () => { cleanup(); resolve(); };
                this.modal.addEventListener('checkout:loaded', loaded);
                this.modal.addEventListener('checkout:loaderror', failed);
                timer = win.setTimeout(failed, 20000);
                this.modal.dispatchEvent(new win.CustomEvent('checkout:ready', {bubbles: true}));
            });
            if (generation !== this.generation) return;
            this.prepared = true;
            this.reveal();
        }
        failure(error, generation) {
            if (generation !== this.generation) return;
            this.dispose(); this.loading(); this.state('error');
            this.modal.querySelector('[data-checkout-load-status]').textContent = error.message === 'The secure payment form could not load. Please try again.' ? error.message : 'Checkout could not open. Please check your connection and try again.';
            this.modal.querySelector('[data-checkout-retry]').hidden = false;
        }
        async open(url, opener) {
            if (this.busy()) return;
            try { url = sameOrigin(url); } catch (error) { return; }
            this.dispose();
            this.closeRequested = false;
            this.url = url; this.opener = opener || this.opener;
            this.loading(); this.dialog.show(opener);
            const stripeReady = this.stripeScript();
            const generation = this.generation;
            const abort = this.abort = new win.AbortController();
            const timer = win.setTimeout(() => abort.abort(), 20000);
            try {
                const response = await win.fetch(url, {credentials: 'same-origin', signal: abort.signal,
                    headers: {Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest'}, cache: 'no-store'});
                if (generation !== this.generation) return;
                if (response.status === 401) { win.location.assign(url); return; }
                const result = await response.json();
                if (!response.ok) throw new Error(result.message || 'Checkout could not open. Please try again.');
                if (generation !== this.generation) return;
                const page = new win.DOMParser().parseFromString(result.html, 'text/html');
                const incoming = page.querySelector('#payment-checkout-modal[data-checkout-modal]');
                const stage = incoming && incoming.querySelector('[data-checkout-stage]');
                if (!stage) throw new Error('Checkout could not open. Please try again.');
                // Leave the existing placeholder in place throughout fetch/mount.
                this.content.insertAdjacentHTML('beforeend', stage.outerHTML);
                this.modal.dataset.checkoutController = incoming.dataset.checkoutController;
                this.urlState(incoming.dataset.checkoutKey);
                await this.ready(generation, stripeReady);
            } catch (error) {
                this.failure(error, generation);
            } finally { win.clearTimeout(timer); }
        }
        async start() {
            if (this.direct) {
                this.url = sameOrigin(win.location.href);
                this.state('loading');
                this.dialog.show();
                const generation = this.generation;
                try { await this.ready(generation); }
                catch (error) { this.failure(error, generation); }
            } else if (this.modal.dataset.checkoutAutoUrl) this.open(this.modal.dataset.checkoutAutoUrl);
            else {
                // A copied/invalid modal parameter without a recognized order must
                // not reopen an empty loading shell through the shared restorer.
                const url = new URL(win.location.href);
                if (url.searchParams.get('modal') === this.modal.id) {
                    url.searchParams.delete('modal');
                    win.history.replaceState(win.history.state, '', url.href);
                }
            }
        }
    }
    function initialize() {
        const modal = doc.querySelector('[data-checkout-modal]');
        if (modal) { const launcher = new Launcher(modal); launcher.start(); }
    }
    if (doc.readyState === 'loading') doc.addEventListener('DOMContentLoaded', initialize, {once: true});
    else initialize();
    return {Launcher, sameOrigin};
};
