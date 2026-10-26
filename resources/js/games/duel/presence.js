const ended = state => ['cancelled', 'expired', 'finished'].includes(state.status);

export class DuelPresence {
    constructor(transport, id, onState, onError) {
        this.transport = transport;
        this.id = id;
        this.onState = onState;
        this.onError = onError;
        this.stopped = false;
        this.hidden = false;
        this.claimed = false;
        this.onHide = () => this.depart();
        this.onShow = event => { if (event.persisted && !this.stopped) return this.claim(); };
    }

    start() {
        window.addEventListener('pagehide', this.onHide);
        window.addEventListener('pageshow', this.onShow);
        return this.claim();
    }

    async claim() {
        const bytes = window.crypto.getRandomValues(new Uint8Array(16));
        this.connectionId = Array.from(bytes, byte => byte.toString(16).padStart(2, '0')).join('');
        this.hidden = false;
        this.claimed = false;
        clearInterval(this.timer);
        try {
            const state = await this.transport.request(`/${this.id}/connect`, { connection_id: this.connectionId });
            this.claimed = true;
            this.accept(state);
        } catch (error) { if (!this.stopped && !this.hidden) this.onError(error); }
        if (!this.stopped && !this.hidden) this.timer = setInterval(() => this.heartbeat(), 15000);
    }

    async heartbeat() {
        if (this.stopped || this.hidden) return;
        if (!this.claimed) return this.claim();
        try {
            this.accept(await this.transport.request(`/${this.id}/heartbeat`, { connection_id: this.connectionId }));
        } catch (error) { if (!this.stopped && !this.hidden) this.onError(error); }
    }

    accept(state) {
        if (this.stopped || this.hidden) return;
        this.onState(state);
        if (ended(state)) this.stop();
    }

    depart() {
        if (this.stopped || this.hidden) return;
        this.hidden = true;
        clearInterval(this.timer);
        // Keep the anonymous session and CSRF protections on unload requests too.
        const body = new FormData();
        body.append('_token', document.querySelector('meta[name="csrf-token"]').content);
        body.append('connection_id', this.connectionId);
        const url = `${this.transport.config.base}/${this.id}/depart`;
        try { if (navigator.sendBeacon?.(url, body)) return; } catch (_) {}
        fetch(url, { method: 'POST', credentials: 'same-origin', body, keepalive: true }).catch(() => {});
    }

    stop() {
        this.stopped = true;
        clearInterval(this.timer);
        window.removeEventListener('pagehide', this.onHide);
        window.removeEventListener('pageshow', this.onShow);
    }
}
