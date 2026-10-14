(function (root, factory) {
    if (typeof module === 'object' && module.exports) module.exports = factory();
    else {
        const Feedback = factory();
        const initialize = () => {
            const modal = root.document.getElementById('feedback-modal');
            if (modal) new Feedback.Controller(modal, root.axios, root);
        };
        if (root.document.readyState === 'loading') root.document.addEventListener('DOMContentLoaded', initialize, {once: true});
        else initialize();
    }
}(typeof window !== 'undefined' ? window : this, function () {
    const ratings = ['bad', 'not_bad', 'good'];
    const labels = ['Bad', 'Not bad', 'Good'];
    const themes = [
        {background: [255, 119, 99], ink: [166, 34, 19], eye: [54, 202, 28, 64, 64, 32], mouth: [119, 93, 0]},
        {background: [255, 211, 103], ink: [121, 81, 11], eye: [26, 180, 44, 114, 56, 28], mouth: [140, 114, 90]},
        {background: [198, 233, 121], ink: [57, 89, 13], eye: [12, 173, 14, 135, 135, 67.5], mouth: [155, 183, 0]}
    ];
    const clamp = value => Math.max(0, Math.min(2, Number(value) || 0));
    const mix = (a, b, t) => a.map((value, i) => value + (b[i] - value) * t);
    function expression(value) {
        value = clamp(value);
        const from = Math.min(1, Math.floor(value)), t = value - from;
        const result = {};
        ['background', 'ink', 'eye', 'mouth'].forEach(key => { result[key] = mix(themes[from][key], themes[from + 1][key], t); });
        return result;
    }
    const color = rgb => 'rgb(' + rgb.map(Math.round).join(',') + ')';

    class Controller {
        constructor(modal, http, win) {
            this.modal = modal; this.http = http; this.win = win;
            this.form = modal.querySelector('[data-feedback-form]');
            this.range = modal.querySelector('[data-feedback-range]');
            this.message = this.form.querySelector('[name="message"]');
            this.rating = this.form.querySelector('[name="rating"]');
            this.buttons = Array.from(modal.querySelectorAll('[data-feedback-rating]'));
            this.faces = Array.from(modal.querySelectorAll('.feedback-face'));
            this.labelTrack = modal.querySelector('[data-feedback-labels]');
            this.thumb = modal.querySelector('[data-feedback-thumb]');
            this.note = modal.querySelector('[data-feedback-note]');
            this.error = modal.querySelector('[data-feedback-error]');
            this.thanks = modal.querySelector('[data-feedback-thanks]');
            this.submitButton = modal.querySelector('.feedback-submit');
            this.submitLabel = modal.querySelector('[data-feedback-submit-label]');
            this.value = 2; this.frame = null; this.request = 0; this.busy = false; this.sent = false; this.drag = null;
            this.reducedMotion = win.matchMedia && win.matchMedia('(prefers-reduced-motion: reduce)').matches;
            this.bind(); this.paint(2); this.commit(2);
        }
        paint(value) {
            this.value = clamp(value);
            const face = expression(this.value);
            this.modal.style.setProperty('--feedback-background', color(face.background));
            this.modal.style.setProperty('--feedback-ink', color(face.ink));
            this.thumb.style.left = (this.value * 50) + '%';
            this.labelTrack.style.transform = 'translateX(' + (-this.value * 100 / 3) + '%)';
            this.faces.forEach(svg => {
                ['left', 'right'].forEach((side, i) => {
                    const eye = svg.querySelector('[data-face-eye="' + side + '"]');
                    const values = [face.eye[i], face.eye[2], face.eye[3], face.eye[4], face.eye[5]];
                    ['x', 'y', 'width', 'height', 'rx'].forEach((key, j) => eye.setAttribute(key, values[j]));
                });
                const mouth = svg.querySelector('[data-face-mouth]');
                mouth.setAttribute('d', 'M 139 ' + face.mouth[0] + ' Q 160 ' + face.mouth[1] + ' 181 ' + face.mouth[0]);
                mouth.setAttribute('transform', 'rotate(' + face.mouth[2] + ' 160 ' + face.mouth[0] + ')');
            });
        }
        commit(value) {
            const index = Math.round(clamp(value));
            this.rating.value = ratings[index];
            this.range.value = index;
            this.range.setAttribute('aria-valuetext', labels[index]);
            this.buttons.forEach((button, i) => button.setAttribute('aria-pressed', String(i === index)));
        }
        cancelAnimation() {
            if (this.frame !== null) this.win.cancelAnimationFrame(this.frame);
            this.frame = null;
        }
        select(value) {
            if (this.busy || this.sent) return;
            this.cancelAnimation();
            const target = Math.round(clamp(value)), from = this.value;
            this.commit(target);
            if (this.reducedMotion || from === target) { this.paint(target); return; }
            let start = null;
            const tick = time => {
                if (start === null) start = time;
                const elapsed = Math.min(1, (time - start) / 440);
                const eased = 1 - Math.pow(1 - elapsed, 3);
                this.paint(from + (target - from) * eased);
                if (elapsed < 1) this.frame = this.win.requestAnimationFrame(tick);
                else this.frame = null;
            };
            this.frame = this.win.requestAnimationFrame(tick);
        }
        setNote(open) {
            if (this.busy || this.sent) return;
            this.note.hidden = !open;
            this.modal.classList.toggle('is-noting', open);
            if (open) this.message.focus({preventScroll: true});
            else {
                this.message.blur();
                const button = this.modal.querySelector('[data-feedback-add-note]');
                button.textContent = this.message.value.trim() ? 'Edit note' : 'Add note';
                button.focus({preventScroll: true});
            }
            this.fitViewport();
        }
        fitViewport() {
            if (!this.modal.classList.contains('show')) return;
            const viewport = this.win.visualViewport;
            if (viewport) {
                this.modal.style.setProperty('--feedback-viewport-height', viewport.height + 'px');
                this.modal.querySelector('.modal-content').style.marginTop = viewport.offsetTop + 'px';
                this.modal.classList.toggle('is-compact', viewport.height <= 480);
            }
        }
        setBusy(busy) {
            this.busy = busy;
            this.form.setAttribute('aria-busy', String(busy));
            Array.from(this.form.querySelectorAll('button, input, textarea')).forEach(control => { control.disabled = busy; });
            this.submitLabel.textContent = busy ? 'Sending…' : 'Submit';
        }
        async submit(event) {
            event.preventDefault();
            if (this.busy || this.sent) return;
            this.cancelAnimation();
            const selected = ratings.indexOf(this.rating.value);
            this.paint(selected); this.commit(selected);
            this.error.hidden = true; this.setBusy(true);
            const request = ++this.request;
            try {
                const response = await this.http.post(this.form.action, {
                    rating: this.rating.value, message: this.message.value.trim() || null,
                    _token: this.form.querySelector('[name="_token"]').value
                }, {timeout: 15000});
                if (request !== this.request) return;
                if (!response.data || response.data.saved !== true) throw new Error('Feedback was not confirmed');
                this.sent = true; this.setBusy(false); this.message.blur();
                this.modal.classList.remove('is-noting');
                this.form.hidden = true; this.thanks.hidden = false;
                this.modal.querySelector('.feedback-done').focus({preventScroll: true});
                this.fitViewport();
            } catch (error) {
                if (request !== this.request) return;
                this.setBusy(false);
                const response = error.response || {}, errors = response.data && response.data.errors;
                this.error.textContent = response.status === 419 ? 'Your session has expired. Refresh the page to send your feedback.' :
                    response.status === 429 ? 'Please wait a minute before sending more feedback.' :
                    errors && (errors.message || errors.rating) ? (errors.message || errors.rating)[0] :
                    'Your feedback could not be sent. Please try again.';
                this.error.hidden = false;
            }
        }
        reset() {
            // A response from a dismissed form must never change a reopened one.
            ++this.request; this.cancelAnimation(); this.drag = null;
            this.sent = false; this.setBusy(false); this.form.reset();
            this.form.hidden = false; this.thanks.hidden = true; this.note.hidden = true; this.error.hidden = true;
            this.modal.classList.remove('is-noting'); this.modal.classList.remove('is-compact');
            this.modal.querySelector('[data-feedback-add-note]').textContent = 'Add note';
            this.paint(2); this.commit(2);
            this.modal.style.removeProperty('--feedback-viewport-height');
            this.modal.querySelector('.modal-content').style.marginTop = '';
        }
        bind() {
            this.form.addEventListener('submit', event => this.submit(event));
            this.buttons.forEach(button => button.addEventListener('click', () => this.select(button.dataset.feedbackRating)));
            this.range.addEventListener('input', () => {
                if (this.busy) return;
                this.cancelAnimation(); this.paint(this.range.value);
                const index = Math.round(this.value);
                this.rating.value = ratings[index]; this.range.setAttribute('aria-valuetext', labels[index]);
            });
            this.range.addEventListener('change', () => this.select(this.range.value));
            this.range.addEventListener('keydown', event => {
                const keys = {ArrowLeft: -1, ArrowDown: -1, ArrowRight: 1, ArrowUp: 1};
                if (Object.prototype.hasOwnProperty.call(keys, event.key)) { event.preventDefault(); this.select(ratings.indexOf(this.rating.value) + keys[event.key]); }
                if (event.key === 'Home' || event.key === 'End') { event.preventDefault(); this.select(event.key === 'Home' ? 0 : 2); }
            });
            this.modal.querySelector('[data-feedback-add-note]').addEventListener('click', () => this.setNote(true));
            this.modal.querySelector('[data-feedback-note-back]').addEventListener('click', () => this.setNote(false));
            const stage = this.modal.querySelector('[data-feedback-swipe]');
            stage.addEventListener('pointerdown', event => {
                if (this.busy || this.sent || this.modal.classList.contains('is-noting') || (event.button !== undefined && event.button !== 0)) return;
                this.cancelAnimation();
                this.drag = {id: event.pointerId, x: event.clientX, y: event.clientY, value: this.value, width: stage.getBoundingClientRect().width};
                stage.setPointerCapture(event.pointerId);
            });
            stage.addEventListener('pointermove', event => {
                const drag = this.drag;
                if (!drag || drag.id !== event.pointerId) return;
                if (Math.abs(event.clientY - drag.y) > Math.abs(event.clientX - drag.x) + 12) { this.drag = null; this.select(this.value); return; }
                this.paint(drag.value + (event.clientX - drag.x) * 2 / drag.width);
            });
            const end = event => {
                if (!this.drag || this.drag.id !== event.pointerId) return;
                this.drag = null; this.select(this.value);
            };
            stage.addEventListener('pointerup', end); stage.addEventListener('pointercancel', end);
            this.modal.addEventListener('shown.bs.modal', () => this.fitViewport());
            this.modal.addEventListener('hidden.bs.modal', () => this.reset());
            if (this.win.visualViewport) {
                this.win.visualViewport.addEventListener('resize', () => this.fitViewport());
                this.win.visualViewport.addEventListener('scroll', () => this.fitViewport());
            }
        }
    }
    return {Controller, expression};
}));
