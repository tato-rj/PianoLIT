(function (root) {
    'use strict';

    const clone = value => JSON.parse(JSON.stringify(value));
    const level = reading => reading[0] ? (reading[1] ? 'advanced' : 'intermediate') : (reading[1] ? 'beginner' : 'elementary');

    // Estimates, not backend candidate pools. Replace this object when counts become available.
    const Candidates = {
        after(total, previous, stage, answers, pieces) {
            let ratio;
            if (stage === 'listen') {
                const piece = pieces.find(piece => piece.id === answers.preferredPiece);
                ratio = 0.50 - Math.min(5, new Set(piece.traits).size) * 0.03;
            } else if (stage === 'reading') {
                ratio = {elementary: 0.12, beginner: 0.15, intermediate: 0.18, advanced: 0.14}[level(answers.reading)];
            } else {
                const winners = pieces.filter(piece => answers.winners.indexOf(piece.id) !== -1);
                const traits = winners.reduce((all, piece) => all.concat(piece.traits), []);
                const overlap = traits.length ? 1 - new Set(traits).size / traits.length : 0;
                ratio = 0.05 - overlap * 0.03;
            }
            // Reserve a distinct count for each remaining stage, even in small catalogs.
            const floor = stage === 'listen' ? 4 : (stage === 'reading' ? 3 : 2);
            return Math.max(floor, Math.min(previous - 1, Math.round(total * ratio)));
        }
    };

    class State {
        constructor(total) { this.total = total; this.reset(); }
        reset() {
            this.step = 0; this.count = this.total; this.history = [];
            this.answers = {preferredPiece: null, reading: [], estimatedLevel: null, winners: [], intent: null};
        }
        remember() { this.history.push(clone({step: this.step, count: this.count, answers: this.answers})); }
        back() {
            const previous = this.history.pop();
            if (previous) Object.assign(this, previous);
        }
        choose(value, pieces) {
            this.remember();
            let stage;
            if (this.step === 0) { this.answers.preferredPiece = value; stage = 'listen'; }
            else if (this.step <= 2) {
                this.answers.reading[this.step - 1] = value;
                if (this.step === 2) { this.answers.estimatedLevel = level(this.answers.reading); stage = 'reading'; }
            } else if (this.step <= 5) {
                this.answers.winners[this.step - 3] = value;
                if (this.step === 5) stage = 'preferences';
            } else { this.answers.intent = value; }
            if (stage) this.count = Candidates.after(this.total, this.count, stage, this.answers, pieces);
            this.step++;
            return this.count;
        }
    }

    class AnimatedCount {
        constructor(element, unit, frame, reduced) {
            this.element = element; this.unit = unit;
            this.frame = frame || root.requestAnimationFrame.bind(root);
            this.reduced = reduced; this.generation = 0;
        }
        set(value) {
            this.value = value;
            this.element.textContent = value.toLocaleString('en-US');
            this.unit.textContent = value === 1 ? 'piece' : 'pieces';
        }
        cancel() { this.generation++; }
        to(target, duration) {
            const generation = ++this.generation;
            const from = this.value;
            if (this.reduced || from === target) { this.set(target); return Promise.resolve(); }
            return new Promise(resolve => {
                let start;
                const tick = now => {
                    if (generation !== this.generation) return resolve();
                    if (start === undefined) start = now;
                    const progress = Math.min(1, (now - start) / duration);
                    const eased = 1 - Math.pow(1 - progress, 3);
                    this.set(Math.round(from + (target - from) * eased));
                    if (progress < 1) this.frame(tick); else resolve();
                };
                this.frame(tick);
            });
        }
    }

    class Previews {
        constructor(seconds, report) {
            this.audio = new root.Audio(); this.audio.preload = 'none';
            this.seconds = seconds; this.report = report; this.generation = 0;
            this.audio.addEventListener('timeupdate', () => { if (this.audio.currentTime >= this.seconds) this.stop(); });
            this.audio.addEventListener('ended', () => this.stop());
            this.audio.addEventListener('error', () => {
                if (this.button) { this.stop(); this.report('This preview could not be played. You can still choose the piece.'); }
            });
        }
        stop() {
            this.generation++;
            this.audio.pause();
            if (this.button) { this.button.innerHTML = '<i class="fas fa-play" aria-hidden="true"></i>'; this.button.setAttribute('aria-pressed', 'false'); }
            this.button = null;
        }
        async play(piece, button) {
            const same = this.button === button;
            this.stop();
            if (same) return;
            this.button = button;
            const generation = this.generation;
            this.audio.src = piece.audio;
            this.audio.currentTime = 0;
            button.innerHTML = '<i class="fas fa-pause" aria-hidden="true"></i>';
            button.setAttribute('aria-pressed', 'true');
            try { await this.audio.play(); }
            catch (error) {
                if (generation === this.generation) { this.stop(); this.report('This preview could not be played. You can still choose the piece.'); }
            }
        }
    }

    function excerptHeight(canvas) {
        const width = canvas.width, height = canvas.height;
        const pixels = canvas.getContext('2d').getImageData(0, 0, width, height).data;
        let blankStart = null, end = Math.round(height * 0.48);
        for (let y = Math.round(height * 0.24); y < height * 0.58; y++) {
            let ink = 0;
            for (let x = Math.round(width * 0.04); x < width * 0.96; x++) {
                const offset = (y * width + x) * 4;
                if (pixels[offset + 3] > 0 && pixels[offset] < 210 && pixels[offset + 1] < 210 && pixels[offset + 2] < 210) ink++;
            }
            if (ink <= 2) { if (blankStart === null) blankStart = y; }
            else {
                if (blankStart !== null && y - blankStart > height * 0.022) end = Math.round((y + blankStart) / 2);
                blankStart = null;
            }
        }
        return end;
    }

    const escape = value => String(value).replace(/[&<>"']/g, character => ({'&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;'}[character]));
    const pause = duration => new Promise(resolve => root.setTimeout(resolve, duration));

    class Controller {
        constructor(element, data, http, pdfjs) {
            this.element = element; this.data = data; this.http = http; this.pdfjs = pdfjs;
            this.state = new State(data.total); this.generation = 0; this.busy = false;
            this.stage = element.querySelector('[data-stage]');
            this.error = element.querySelector('[data-error]');
            this.reduced = root.matchMedia && root.matchMedia('(prefers-reduced-motion: reduce)').matches;
            this.counter = new AnimatedCount(element.querySelector('[data-count]'), element.querySelector('[data-count-unit]'), null, this.reduced);
            this.previews = new Previews(data.previewSeconds, message => this.report(message));
            this.counter.set(data.total);
            element.addEventListener('click', event => this.click(event));
            root.addEventListener('pagehide', () => this.dispose());
            root.document.addEventListener('visibilitychange', () => { if (root.document.hidden) this.stopMedia(); });
            this.render(false);
        }
        report(message) { this.error.textContent = message; this.error.hidden = !message; }
        stopMedia() {
            this.previews.stop();
            this.stage.querySelectorAll('audio,video').forEach(media => media.pause());
        }
        dispose() {
            this.generation++; this.counter.cancel(); this.stopMedia();
            if (this.resultPlayer) { this.resultPlayer.destroy(); this.resultPlayer = null; }
            if (this.pdfTask) { this.pdfTask.destroy(); this.pdfTask = null; }
        }
        navigation() {
            this.element.querySelector('[data-back]').disabled = !this.state.history.length;
            this.element.querySelector('[data-count-note]').textContent = this.state.step === 0 ? 'In the PianoLIT library' : (this.state.step === 7 ? 'Chosen for you' : 'Estimated pieces remaining');
        }
        async click(event) {
            const button = event.target.closest('button');
            if (!button || button.disabled) return;
            if (button.hasAttribute('data-back') || button.hasAttribute('data-restart')) {
                this.dispose(); this.busy = false;
                if (button.hasAttribute('data-back')) this.state.back(); else this.state.reset();
                this.counter.set(this.state.count); this.render(); return;
            }
            if (this.busy) return;
            if (button.hasAttribute('data-play')) {
                this.report('');
                this.previews.play(this.data.pieces.find(piece => piece.id === Number(button.dataset.play)), button); return;
            }
            if (button.hasAttribute('data-score-retry')) { this.render(); return; }
            if (!button.hasAttribute('data-choice')) return;
            let value = button.dataset.choice;
            if (this.state.step === 1 || this.state.step === 2) value = value === 'yes';
            else if (this.state.step < 6) value = Number(value);
            this.busy = true; this.report(''); this.stopMedia();
            (button.closest('.match-card') || button).classList.add('selected');
            button.setAttribute('aria-pressed', 'true');
            this.stage.querySelectorAll('button').forEach(control => { control.disabled = true; });
            const generation = ++this.generation;
            this.state.choose(value, this.data.pieces);
            this.navigation();
            if (this.state.step === 7) { await this.result(generation); return; }
            await pause(this.reduced ? 0 : 180);
            if (generation !== this.generation) return;
            await this.counter.to(this.state.count, 850);
            if (generation !== this.generation) return;
            this.element.querySelector('[data-count-announcement]').textContent = this.state.count.toLocaleString('en-US') + ' pieces remaining';
            await this.transition(generation);
        }
        async transition(generation) {
            this.stage.classList.add('leaving');
            await pause(this.reduced ? 0 : 140);
            if (generation !== this.generation) return;
            this.busy = false; this.render();
        }
        heading(title, subtitle) {
            return '<div class="text-center"><h3 tabindex="-1">' + title + '</h3>' + (subtitle ? '<p class="text-muted mb-0">' + subtitle + '</p>' : '') + '</div>';
        }
        cards(pieces) {
            return '<div class="match-choices">' + pieces.map(piece => '<div class="match-card">' +
                '<button type="button" class="match-select" data-choice="' + piece.id + '" aria-pressed="false">' +
                '<img class="rounded-circle" src="' + escape(piece.image) + '" alt=""><span class="match-piece-copy"><strong>' + escape(piece.title) +
                '</strong><small class="text-muted">' + escape(piece.composer) + '</small></span></button>' +
                '<button type="button" class="match-play btn btn-green rounded-circle" data-play="' + piece.id + '" aria-pressed="false" aria-label="Play preview: ' + escape(piece.title) + '"><i class="fas fa-play" aria-hidden="true"></i></button></div>').join('') + '</div>';
        }
        render(focus = true) {
            this.stage.classList.remove('leaving'); this.report(''); this.navigation();
            const step = this.state.step;
            if (step === 0) this.stage.innerHTML = this.heading('Which piece do you prefer?', 'Listen to a little of each, then choose one.') + this.cards(this.data.pieces.slice(0, 4));
            else if (step <= 2) this.reading();
            else if (step <= 5) {
                this.stage.innerHTML = this.heading('Which one would you rather play?', '<span class="match-rounds">' + (step - 2) + ' / 3</span>') + this.cards(this.data.pieces.slice(4 + (step - 3) * 2, 6 + (step - 3) * 2));
            } else if (step === 6) {
                this.stage.innerHTML = this.heading('What kind of piece are you looking for?') + '<div class="match-intents">' + Object.keys(this.data.intents).map(key =>
                    '<button type="button" class="btn btn-outline-secondary match-intent rounded" data-choice="' + key + '">' + escape(this.data.intents[key]) + '</button>').join('') + '</div>';
            }
            if (focus) {
                if (this.element.getBoundingClientRect().top < 0) this.element.scrollIntoView({block: 'start'});
                this.focus();
            }
        }
        focus() { const heading = this.stage.querySelector('h3'); if (heading) heading.focus({preventScroll: true}); }
        async reading() {
            const generation = ++this.generation;
            const score = this.data.scores[this.state.step === 1 ? 'middle' : (this.state.answers.reading[0] ? 'hard' : 'easy')];
            this.stage.innerHTML = this.heading(this.state.step === 1 ? 'Could you comfortably sight-read this?' : 'How about this one?') +
                '<div class="match-score" aria-busy="true"><p class="text-muted text-center my-4" data-score-status>Opening the score excerpt…</p></div>' +
                '<p class="text-muted text-center small">' + escape(score.title) + ' · ' + escape(score.composer) + '</p>' +
                '<div class="match-reading-actions"><button class="btn btn-outline-secondary rounded-pill match-reading-answer" data-choice="yes" disabled>Yes</button>' +
                '<button class="btn btn-outline-secondary rounded-pill match-reading-answer" data-choice="no" disabled>Not comfortably</button></div>';
            const container = this.stage.querySelector('.match-score');
            let task;
            try {
                if (!this.pdfjs) throw new Error('PDF renderer unavailable');
                if (this.pdfTask) this.pdfTask.destroy();
                task = this.pdfTask = this.pdfjs.getDocument({url: score.url});
                const pdf = await task.promise;
                const page = await pdf.getPage(1);
                if (generation !== this.generation) return;
                const viewport = page.getViewport({scale: 1.8});
                const canvas = root.document.createElement('canvas');
                canvas.width = viewport.width;
                // Only the opening systems: a reading excerpt, not a full-score viewer.
                canvas.height = Math.round(viewport.height);
                canvas.setAttribute('role', 'img');
                canvas.setAttribute('aria-label', 'Opening score excerpt from ' + score.title + ' by ' + score.composer);
                await page.render({canvasContext: canvas.getContext('2d'), viewport: viewport}).promise;
                if (generation !== this.generation) return;
                // End in whitespace between systems instead of slicing through notation.
                const cropped = root.document.createElement('canvas');
                cropped.width = canvas.width; cropped.height = excerptHeight(canvas);
                cropped.getContext('2d').drawImage(canvas, 0, 0);
                cropped.setAttribute('role', 'img');
                cropped.setAttribute('aria-label', canvas.getAttribute('aria-label'));
                container.innerHTML = ''; container.appendChild(cropped); container.setAttribute('aria-busy', 'false');
                this.stage.querySelectorAll('[data-choice]').forEach(button => { button.disabled = false; });
            } catch (error) {
                if (generation !== this.generation) return;
                container.setAttribute('aria-busy', 'false');
                container.innerHTML = '<p class="text-center my-3">The score excerpt could not be opened.</p><div class="text-center"><button class="btn btn-outline-secondary rounded-pill" data-score-retry>Try again</button></div>';
            } finally {
                if (task) { task.destroy(); if (this.pdfTask === task) this.pdfTask = null; }
            }
        }
        async result(generation) {
            const previous = this.state.count;
            const request = this.http.post(this.element.dataset.url, clone(this.state.answers), {timeout: 20000});
            try {
                const results = await Promise.all([request, this.counter.to(1, 1350)]);
                if (generation !== this.generation) return;
                this.state.count = 1;
                this.stopMedia();
                this.stage.classList.add('leaving');
                await pause(this.reduced ? 0 : 140);
                if (generation !== this.generation) return;
                this.stage.innerHTML = results[0].data;
                this.stage.classList.remove('leaving'); this.navigation(); this.focus(); this.busy = false;
                this.element.querySelector('[data-count-announcement]').textContent = '1 piece. Your match is ready.';
                const media = this.stage.querySelector('[data-result-media]');
                if (media) {
                    ['timeupdate', 'seeking'].forEach(event => media.addEventListener(event, () => {
                        if (media.currentTime >= this.data.previewSeconds) { media.pause(); media.currentTime = 0; }
                    }));
                    if (root.Plyr) this.resultPlayer = new root.Plyr(media);
                }
            } catch (error) {
                if (generation !== this.generation) return;
                this.counter.cancel(); this.state.back(); this.counter.set(previous);
                this.busy = false; this.render();
                this.report(error.response && error.response.status === 422
                    ? 'The library has changed since you started. Please start over to refresh the listening choices.'
                    : 'We couldn’t find your match just now. Choose your intent again to retry; your earlier answers are saved.');
            }
        }
    }

    root.MatchTour = {Candidates, State, AnimatedCount, Previews, Controller, level};
})(typeof window !== 'undefined' ? window : globalThis);
