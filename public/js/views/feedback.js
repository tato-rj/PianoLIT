(function (root, factory) {
    if (typeof module === 'object' && module.exports) module.exports = factory();
    else {
        const Feedback = factory();
        const initialize = () => {
            const modal = root.document.getElementById('feedback-modal');
            if (modal) new Feedback.Controller(modal, root.axios, root);
            const utility = root.document.querySelector('[data-feedback-utility]');
            if (utility) new Feedback.Floating(utility, root);
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
            this.handleMouth = modal.querySelector('[data-feedback-handle-mouth]');
            this.note = modal.querySelector('[data-feedback-note]');
            this.error = modal.querySelector('[data-feedback-error]');
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
            const mouthBaseline = 13 - this.value;
            this.handleMouth.setAttribute('d', 'M 8 ' + mouthBaseline + ' Q 12 ' + (7 + this.value * 5) + ' 16 ' + mouthBaseline);
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
                    _token: this.form.querySelector('[name="_token"]').value,
                    page_context: this.form.querySelector('[name="page_context"]').value || null,
                    device_type: this.win.innerWidth < 700 ? 'mobile' : this.win.innerWidth < 1100 ? 'tablet' : 'desktop'
                }, {timeout: 15000});
                if (request !== this.request) return;
                if (!response.data || response.data.saved !== true) throw new Error('Feedback was not confirmed');
                this.sent = true; this.setBusy(false); this.message.blur();
                this.win.bootstrap.Modal.getOrCreateInstance(this.modal).hide();
            } catch (error) {
                if (request !== this.request) return;
                this.setBusy(false);
                const response = error.response || {}, errors = response.data && response.data.errors;
                this.error.textContent = response.status === 419 ? 'Your session has expired. Refresh the page to send your feedback.' :
                    response.status === 429 ? 'Please wait a minute before sending more feedback.' :
                    errors && (errors.message || errors.rating || errors.page_context) ? (errors.message || errors.rating || errors.page_context)[0] :
                    'Your feedback could not be sent. Please try again.';
                this.error.hidden = false;
            }
        }
        reset() {
            // A response from a dismissed form must never change a reopened one.
            ++this.request; this.cancelAnimation(); this.drag = null;
            this.sent = false; this.setBusy(false); this.form.reset();
            this.form.hidden = false; this.note.hidden = true; this.error.hidden = true;
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
            this.modal.addEventListener('shown.bs.modal', () => {
                this.fitViewport();
                if (this.sent) this.win.bootstrap.Modal.getOrCreateInstance(this.modal).hide();
            });
            this.modal.addEventListener('hidden.bs.modal', () => {
                const submitted = this.sent;
                this.reset();
                if (submitted) this.modal.dispatchEvent(new this.win.CustomEvent('feedback:submitted', {bubbles: true}));
            });
            if (this.win.visualViewport) {
                this.win.visualViewport.addEventListener('resize', () => this.fitViewport());
                this.win.visualViewport.addEventListener('scroll', () => this.fitViewport());
            }
        }
    }
    class Floating {
        constructor(element, win) {
            this.element = element; this.win = win; this.doc = win.document;
            this.button = element.querySelector('[data-feedback-launcher]');
            this.confirmation = element.querySelector('[data-feedback-confirmation]');
            this.mobile = win.matchMedia('(max-width: 1099px)');
            this.overlays = new Set(); this.observed = new Set();
            this.lastY = this.scrollY(); this.distance = 0; this.direction = 0; this.away = false;
            this.frame = null; this.toastTimer = null; this.mediaFullscreen = false; this.keyboardFocus = false;
            this.bind(); this.update();
        }
        scrollY() {
            return Math.max(0, Math.min(this.win.scrollY || 0, Math.max(0, this.doc.documentElement.scrollHeight - this.win.innerHeight)));
        }
        scroll() {
            const y = this.scrollY(), delta = y - this.lastY;
            this.lastY = y;
            if (!this.mobile.matches || this.blocked()) return;
            if (y <= 12) { this.away = false; this.distance = 0; }
            else if (delta) {
                const direction = delta > 0 ? 1 : -1;
                if (direction !== this.direction) this.distance = 0;
                this.direction = direction; this.distance += Math.abs(delta);
                if (this.distance >= 24) { this.away = delta > 0; this.distance = 0; }
            }
            this.schedule();
        }
        visible(element) {
            const rect = element.getBoundingClientRect(), style = this.win.getComputedStyle(element);
            return !element.hidden && rect.width > 0 && rect.height > 0 && rect.top < this.win.innerHeight && rect.bottom > 0 &&
                style.display !== 'none' && style.visibility !== 'hidden';
        }
        blocked() {
            if (this.overlays.size || this.mediaFullscreen || this.doc.fullscreenElement || this.doc.webkitFullscreenElement ||
                this.doc.body.classList.contains('score-editor-fullscreen-open')) return true;
            const field = this.doc.activeElement;
            if (this.mobile.matches && field && field.matches('textarea, select, input:not([type="range"]):not([type="checkbox"]):not([type="radio"]):not([type="button"]):not([type="submit"]), [contenteditable]:not([contenteditable="false"])')) return true;
            const viewport = this.win.visualViewport;
            if (viewport && viewport.scale === 1 && this.win.innerHeight - viewport.height > 120) return true;
            return Array.from(this.doc.querySelectorAll('.modal.show, .modal.showing, .modal-backdrop, .offcanvas.show, .offcanvas.showing, .offcanvas-backdrop, .plyr--fullscreen-fallback, .plyr--fullscreen-active, .screen-lock-overlay, #subscribe-overlay, #bottom-popup, [data-feedback-blocking-overlay], .score-footer, [data-playlist-player]'))
                .some(element => this.visible(element));
        }
        schedule() {
            if (this.frame !== null) return;
            this.frame = this.win.requestAnimationFrame(() => { this.frame = null; this.update(); });
        }
        update() {
            let blocked = this.blocked(), bottom = 0;
            if (!blocked) {
                Array.from(this.doc.querySelectorAll('#menu')).forEach(element => {
                    if (this.resize && !this.observed.has(element)) { this.observed.add(element); this.resize.observe(element); }
                    if (!this.visible(element)) return;
                    const position = this.win.getComputedStyle(element).position;
                    if (position === 'fixed' || position === 'sticky') bottom = Math.max(bottom, this.win.innerHeight - element.getBoundingClientRect().top);
                });
                if (bottom > this.win.innerHeight * .6) blocked = true;
            }
            if (this.bottom !== bottom) { this.bottom = bottom; this.element.style.setProperty('--feedback-bottom', Math.ceil(bottom) + 'px'); }
            this.button.hidden = blocked;
            this.button.classList.toggle('is-scrolled-away', !blocked && this.mobile.matches && this.away && !(this.keyboardFocus && this.doc.activeElement === this.button));
            if (blocked) this.confirmation.hidden = true;
        }
        submitted() {
            this.away = false; this.distance = 0; this.lastY = this.scrollY();
            this.update();
            this.confirmation.textContent = 'Thank you for your feedback!';
            this.confirmation.hidden = false;
            if (this.toastTimer !== null) this.win.clearTimeout(this.toastTimer);
            this.toastTimer = this.win.setTimeout(() => { this.confirmation.hidden = true; this.toastTimer = null; }, 5000);
        }
        bind() {
            this.win.addEventListener('scroll', () => this.scroll(), {passive: true});
            this.win.addEventListener('resize', () => this.schedule(), {passive: true});
            this.win.addEventListener('pageshow', () => { this.away = false; this.distance = 0; this.lastY = this.scrollY(); this.schedule(); });
            ['focusin', 'focusout', 'fullscreenchange', 'webkitfullscreenchange', 'shown.bs.tab'].forEach(name => this.doc.addEventListener(name, () => this.schedule()));
            this.doc.addEventListener('keydown', event => { if (event.key === 'Tab') { this.keyboardFocus = true; this.schedule(); } });
            ['pointerdown', 'touchstart'].forEach(name => this.doc.addEventListener(name, () => { this.keyboardFocus = false; }, {passive: true, capture: true}));
            ['show.bs.modal', 'show.bs.offcanvas'].forEach(name => this.doc.addEventListener(name, event => {
                this.overlays.add(event.target); this.update();
                // A canceled Bootstrap show has no matching hidden event.
                this.win.setTimeout(() => { if (event.defaultPrevented) { this.overlays.delete(event.target); this.schedule(); } }, 0);
            }));
            ['hidden.bs.modal', 'hidden.bs.offcanvas'].forEach(name => this.doc.addEventListener(name, event => { this.overlays.delete(event.target); this.update(); }, true));
            this.doc.addEventListener('webkitbeginfullscreen', () => { this.mediaFullscreen = true; this.update(); }, true);
            this.doc.addEventListener('webkitendfullscreen', () => { this.mediaFullscreen = false; this.schedule(); }, true);
            this.doc.addEventListener('feedback:submitted', () => this.submitted());
            if (this.win.visualViewport) {
                this.win.visualViewport.addEventListener('resize', () => this.schedule());
                this.win.visualViewport.addEventListener('scroll', () => this.schedule());
            }
            if (this.win.ResizeObserver) this.resize = new this.win.ResizeObserver(() => this.schedule());
            if (this.win.MutationObserver) {
                this.observer = new this.win.MutationObserver(records => {
                    const relevant = records.some(record => record.type === 'childList' || record.target.matches('body, #menu, [data-playlist-player], .score-footer, .modal, .offcanvas, .modal-backdrop, .offcanvas-backdrop, .tab-pane, .plyr, .screen-lock-overlay, #subscribe-overlay, #bottom-popup, [data-feedback-blocking-overlay]'));
                    if (relevant) this.schedule();
                });
                this.observer.observe(this.doc.body, {subtree: true, childList: true, attributes: true, attributeFilter: ['class', 'style', 'hidden']});
            }
        }
    }
    return {Controller, Floating, expression};
}));
