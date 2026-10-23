import Dropdown from 'bootstrap/js/dist/dropdown';

export class SoundMenu {
    constructor({ root, input, onSelect, onPlay, onEdit, onDelete, onRecord, onClose }) {
        Object.assign(this, { root, input, onSelect, onPlay, onEdit, onDelete, onRecord, onClose });
        this.trigger = root.querySelector('[data-sound-toggle]');
        this.label = root.querySelector('[data-sound-label]');
        this.list = root.querySelector('[data-sound-voices]');
        this.template = root.querySelector('[data-sound-template]');
        this.empty = root.querySelector('[data-sound-empty]');
        this.rows = new Map();
        this.list?.querySelectorAll('[data-sound-row]').forEach(row => this.rows.set(row.dataset.voiceId, row));
        this.dropdown = new Dropdown(this.trigger, { autoClose: 'outside' });
        this.trigger.addEventListener('shown.bs.dropdown', () => this.size());
        window.addEventListener('resize', () => { if (this.trigger.getAttribute('aria-expanded') === 'true') this.size(); });
        this.trigger.addEventListener('hidden.bs.dropdown', () => this.onClose());
        root.addEventListener('keydown', event => {
            if (event.key !== 'Escape' || this.trigger.getAttribute('aria-expanded') !== 'true') return;
            event.preventDefault();
            event.stopPropagation();
            this.close();
            this.trigger.focus();
        });
        root.addEventListener('click', event => {
            if (event.target.closest('[data-voice-start]')) this.close();
        }, true);
        root.addEventListener('click', event => {
            const button = event.target.closest('button');
            if (!button || button.disabled) return;
            if (button.matches('[data-sound-empty]')) { this.close(); this.onRecord(); return; }
            if (button.dataset.soundValue) { this.close(); this.onSelect(button.dataset.soundValue); this.trigger.focus(); return; }
            const id = button.closest('[data-sound-row]')?.dataset.voiceId;
            if (!id) return;
            if (button.matches('[data-sound-play]')) this.onPlay(id);
            if (button.matches('[data-sound-edit]')) { this.close(); this.onEdit(id); }
            if (button.matches('[data-sound-delete]')) { this.close(); this.onDelete(id); }
        });
    }

    close() { this.dropdown.hide(); }

    size() {
        const options = this.root.querySelector('[data-sound-options]');
        const trigger = this.trigger.getBoundingClientRect();
        const bounds = this.trigger.closest('[data-settings-scroll], .offcanvas')?.getBoundingClientRect();
        const below = Math.min(window.innerHeight, bounds?.bottom ?? window.innerHeight) - trigger.bottom - 12;
        const above = trigger.top - Math.max(0, bounds?.top ?? 0) - 12;
        options.style.maxHeight = `${Math.max(80, Math.min(256, Math.max(above, below) - 16))}px`;
        this.dropdown.update();
    }

    render(recorder) {
        const options = [...this.input.options];
        this.label.textContent = options.find(option => option.value === this.input.value)?.text || 'Classic click';
        const voices = options.filter(option => option.value.startsWith('voice:'));
        if (this.list) {
            const ids = new Set(voices.map(option => option.value.slice(6)));
            this.rows.forEach((row, id) => { if (!ids.has(id)) { row.remove(); this.rows.delete(id); } });
            voices.forEach(option => {
                const id = option.value.slice(6);
                let row = this.rows.get(id);
                if (!row) {
                    row = this.template.content.firstElementChild.cloneNode(true);
                    row.dataset.voiceId = id;
                    this.rows.set(id, row);
                    this.list.append(row);
                }
                const choice = row.querySelector('[data-sound-choice]');
                choice.textContent = option.text;
                choice.dataset.soundValue = option.value;
                row.querySelectorAll('button').forEach(button => { button.disabled = !recorder?.ready || !!recorder?.saving; });
                const playing = recorder?.previewingId === id;
                const play = row.querySelector('[data-sound-play]');
                play.setAttribute('aria-label', `${playing ? 'Stop' : 'Play'} ${option.text}`);
                play.setAttribute('aria-pressed', String(playing));
                play.title = playing ? 'Stop preview' : 'Play recording';
                row.querySelector('[data-sound-play-icon]').hidden = playing;
                row.querySelector('[data-sound-stop-icon]').hidden = !playing;
                row.querySelector('[data-sound-edit]').setAttribute('aria-label', `Rename ${option.text}`);
                row.querySelector('[data-sound-delete]').setAttribute('aria-label', `Delete ${option.text}`);
            });
            this.empty.hidden = !!voices.length || !recorder?.ready;
            this.empty.disabled = !recorder?.ready || !recorder?.supported || !!recorder?.saving;
        }
        this.root.querySelectorAll('[data-sound-value]').forEach(button => {
            const selected = button.dataset.soundValue === this.input.value;
            button.classList.toggle('active', selected);
            button.setAttribute('aria-pressed', String(selected));
        });
        this.dropdown.update();
    }
}
