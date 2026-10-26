// PianoLIT's Laravel 8 runtime uses authenticated HTTP snapshots. Game engines,
// lobby, readiness, countdown and results share the original transport contract.
export class DuelTransport {
    constructor(config = window.__duelConfig) {
        this.config = config;
        this.offset = 0;
        this.subscription = 0;
        this.echo = { disconnect: () => this.disconnect() };
    }

    async request(path = '', data = null, signal = undefined) {
        const began = Date.now();
        const response = await fetch(`${this.config.base}${path}`, {
            method: data === null ? 'GET' : 'POST', credentials: 'same-origin', cache: 'no-store', signal,
            headers: { Accept: 'application/json', 'Content-Type': 'application/json', 'X-Requested-With': 'XMLHttpRequest',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content },
            ...(data === null ? {} : { body: JSON.stringify(data) }),
        });
        const payload = await response.json().catch(() => ({}));
        if (!response.ok) {
            const error = new Error(Object.values(payload.errors || {}).flat()[0] || payload.message || 'Could not reach the Duel server. Please try again.');
            error.status = response.status; throw error;
        }
        if (payload.server_now) this.offset = Date.parse(payload.server_now) - (began + Date.now()) / 2;
        return payload;
    }

    leaveOnExit(id) {
        const body = new FormData();
        body.append('_token', document.querySelector('meta[name="csrf-token"]').content);
        const url = `${this.config.base}/${id}/leave`;
        try { if (navigator.sendBeacon?.(url, body)) return; } catch (_) {}
        fetch(url, { method: 'POST', credentials: 'same-origin', body, keepalive: true }).catch(() => {});
    }

    disconnect() {
        this.subscription++;
        clearTimeout(this.timer);
        this.controller?.abort();
        this.controller = null;
    }

    subscribe(id, { update, answer, connected, disconnected, error }) {
        this.disconnect();
        const subscription = this.subscription;
        const seenAnswers = new Set();
        let online = false;
        let failures = 0;
        const poll = async () => {
            if (subscription !== this.subscription) return;
            const controller = new AbortController();
            this.controller = controller;
            const timeout = setTimeout(() => controller.abort(), 10000);
            try {
                const state = await this.request(`/${id}`, null, controller.signal);
                if (subscription !== this.subscription) return;
                if (!online) { online = true; connected?.(); }
                failures = 0;
                update?.(state);
                for (const player of state.players || []) {
                    const event = player.answer;
                    if (event && !seenAnswers.has(event.id)) {
                        seenAnswers.add(event.id);
                        answer?.(event);
                    }
                }
                // Retain only recent event IDs; server feedback expires after three seconds.
                if (seenAnswers.size > 64) {
                    const recent = [...seenAnswers].slice(-32);
                    seenAnswers.clear(); recent.forEach(value => seenAnswers.add(value));
                }
            } catch (failure) {
                if (subscription !== this.subscription) return;
                if (online) { online = false; disconnected?.(); }
                failures++;
                if ([403, 404, 419].includes(failure.status)) {
                    error?.(failure); this.disconnect(); return;
                }
                if (failures === 3) error?.(failure);
            } finally {
                clearTimeout(timeout);
                if (subscription === this.subscription) {
                    this.timer = setTimeout(poll, failures ? Math.min(5000, failures * 1000) : (this.config.poll_ms || 800));
                }
            }
        };
        poll();
    }
}
