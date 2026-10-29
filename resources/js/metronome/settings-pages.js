// Keep a single settings panel and focus trap, with one task visible at a time.
export class SettingsPages {
    constructor(root, onLeave = () => {}) {
        this.root = root;
        this.onLeave = onLeave;
        this.pages = [...root.querySelectorAll('[data-settings-page]')];
        this.title = root.querySelector('#settings-title');
        this.back = root.querySelector('[data-settings-back]');
        this.current = 'home';
        this.pages.forEach(page => { page.id = `settings-page-${page.dataset.settingsPage}`; });
        root.querySelectorAll('[data-settings-go]').forEach(button => {
            button.addEventListener('click', () => this.show(button.dataset.settingsGo));
        });
        this.back.addEventListener('click', () => this.show(this.current === 'effects' ? 'sound' : 'home'));
        root.addEventListener('hidden.bs.offcanvas', () => this.show('home', false));
    }

    show(name, focus = true) {
        if (!this.pages.some(page => page.dataset.settingsPage === name)) return;
        const previous = this.current;
        this.onLeave();
        this.current = name;
        this.pages.forEach(page => { page.hidden = page.dataset.settingsPage !== name; });
        this.back.hidden = name === 'home';
        this.back.setAttribute('aria-label', name === 'effects' ? 'Back to sound' : 'Back to settings');
        this.title.textContent = { home: 'Settings', sound: 'Sound', rhythm: 'Rhythm', appearance: 'Appearance', effects: 'Voice effect' }[name];
        this.root.querySelector('[data-settings-scroll]').scrollTop = 0;
        if (focus) {
            const returning = name === 'home' || (name === 'sound' && previous === 'effects');
            const target = returning ? this.root.querySelector(`[data-settings-go="${previous}"]`) : this.back;
            target?.focus();
        }
    }

    summary(name, text) {
        this.root.querySelector(`[data-settings-summary="${name}"]`).textContent = text;
    }
}
