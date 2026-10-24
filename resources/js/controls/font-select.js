export class FontSelect {
    // Reuse the host bundle's Bootstrap instance and its existing data API.
    constructor(root, Dropdown) {
        this.root = root;
        this.input = root.querySelector('select');
        this.trigger = root.querySelector('[data-font-toggle]');
        this.label = root.querySelector('[data-font-label]');
        this.current = root.querySelector('[data-font-current]');
        this.options = root.querySelector('[data-font-options]');
        this.choices = [...root.querySelectorAll('[data-font-value]')];
        this.dropdown = new Dropdown(this.trigger, { boundary: root.closest('[data-settings-scroll]') || 'clippingParents' });
        this.trigger.addEventListener('shown.bs.dropdown', () => {
            this.render();
            this.size();
            const selected = this.choices.find(choice => choice.dataset.fontValue === this.input.value);
            selected?.focus({ preventScroll: true });
            if (selected) this.options.scrollTop = selected.offsetTop - this.options.offsetTop;
        });
        this.input.addEventListener('change', () => this.render());
        root.addEventListener('click', event => {
            const choice = event.target.closest('[data-font-value]');
            if (!choice) return;
            this.input.value = choice.dataset.fontValue;
            this.input.dispatchEvent(new Event('change', { bubbles: true }));
            this.close();
            this.trigger.focus();
        });
        root.addEventListener('keydown', event => {
            if (this.trigger.getAttribute('aria-expanded') !== 'true') return;
            if (event.key === 'Escape') {
                event.preventDefault();
                event.stopPropagation();
                this.close();
                this.trigger.focus();
            } else if (event.key === 'Home' || event.key === 'End') {
                event.preventDefault();
                this.choices[event.key === 'Home' ? 0 : this.choices.length - 1].focus();
            }
        });
        root.closest('.offcanvas')?.addEventListener('hide.bs.offcanvas', () => this.close());
        window.addEventListener('resize', () => {
            if (this.trigger.getAttribute('aria-expanded') === 'true') this.size();
        });
        this.render();
    }

    close() { this.dropdown.hide(); }

    size() {
        const trigger = this.trigger.getBoundingClientRect();
        const bounds = this.root.closest('[data-settings-scroll], .offcanvas')?.getBoundingClientRect();
        const below = Math.min(window.innerHeight, bounds?.bottom ?? window.innerHeight) - trigger.bottom - 12;
        const above = trigger.top - Math.max(0, bounds?.top ?? 0) - 12;
        this.options.style.maxHeight = `${Math.max(80, Math.min(352, Math.max(above, below) - 16))}px`;
        this.dropdown.update();
    }

    render() {
        const selected = this.choices.find(choice => choice.dataset.fontValue === this.input.value);
        if (!selected) return;
        this.label.textContent = selected.querySelector('[data-font-name]').textContent;
        this.current.dataset.numberFont = this.input.value;
        const source = this.root.dataset.fontPreviewSource;
        const sample = (source ? document.querySelector(source)?.textContent : null) || '120';
        this.current.textContent = sample;
        this.root.querySelectorAll('[data-font-sample]').forEach(preview => { preview.textContent = sample; });
        this.choices.forEach(choice => {
            const active = choice === selected;
            choice.classList.toggle('active', active);
            choice.setAttribute('aria-checked', String(active));
        });
    }
}
