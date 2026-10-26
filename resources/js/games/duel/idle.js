import { dialog } from './dialog';

const activityEvents = ['pointerdown', 'pointermove', 'keydown', 'wheel', 'touchstart'];

export class DuelIdle {
    constructor({ onExpire, onResume }) {
        this.onExpire = onExpire;
        this.onResume = onResume;
        this.lastActivity = Date.now();
        this.warning = false;
        this.stopped = false;
        this.seconds = null;
        this.activity = event => {
            if (!event.isTrusted || this.stopped) return;
            this.check();
            if (this.warning && event.type === 'keydown' && String(event.key).startsWith('Arrow')) {
                event.preventDefault(); event.stopImmediatePropagation();
            }
            // Only the explicit confirmation dismisses an already visible warning.
            if (!this.warning && !this.stopped) this.lastActivity = Date.now();
        };
        this.confirm = () => {
            this.check();
            if (!this.warning || this.stopped) return;
            this.lastActivity = Date.now();
            this.warning = false;
            this.seconds = null;
            this.onResume();
        };
        this.checkVisible = () => this.check();
    }

    start() {
        this.stopped = false;
        this.lastActivity = Date.now();
        this.seconds = null;
        for (const event of activityEvents) document.addEventListener(event, this.activity, { capture: true, passive: event !== 'keydown' });
        document.addEventListener('visibilitychange', this.checkVisible);
        this.button = document.querySelector('[data-duel-active]');
        this.button.addEventListener('click', this.confirm);
        this.timer = setInterval(() => this.check(), 250);
        return this;
    }

    check() {
        if (this.stopped) return;
        const idle = Date.now() - this.lastActivity;
        if (idle >= 40000) {
            this.stop();
            this.onExpire();
            return;
        }
        if (idle < 30000) return;
        const seconds = Math.ceil((40000 - idle) / 1000);
        if (seconds === this.seconds) return;
        const first = !this.warning;
        this.warning = true;
        this.seconds = seconds;
        dialog({ message: 'Still playing?', idle: true, seconds });
        if (first) this.button.focus();
    }

    stop() {
        this.stopped = true;
        this.warning = false;
        clearInterval(this.timer);
        for (const event of activityEvents) document.removeEventListener(event, this.activity, true);
        document.removeEventListener('visibilitychange', this.checkVisible);
        this.button?.removeEventListener('click', this.confirm);
    }
}
