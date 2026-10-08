(function (root) {
    'use strict';

    const clone = value => JSON.parse(JSON.stringify(value));
    const readingLevel = (reading, fallback = 'intermediate') => reading[0] === null ? fallback : reading[1] === null ? (reading[0] ? 'intermediate' : 'beginner') : reading[0] ? (reading[1] ? 'advanced' : 'intermediate') : (reading[1] ? 'beginner' : 'elementary');
    const levels = ['elementary', 'beginner', 'intermediate', 'advanced'];
    const baseLevel = name => String(name).replace(/^(early|late) /, '');
    const level = (reading, fallback = 'intermediate', playingLevel = null) => {
        if (!playingLevel) return readingLevel(reading, fallback);
        if (reading[0] === null) return playingLevel;
        return levels[Math.round((2 * levels.indexOf(playingLevel) + levels.indexOf(readingLevel(reading))) / 3)];
    };

    // Estimates, not backend candidate pools. Replace this object when counts become available.
    const Candidates = {
        after(total, previous, stage, answers, pieces) {
            let ratio;
            if (stage === 'level') {
                ratio = 0.75;
            } else if (stage === 'listen') {
                const piece = pieces.find(piece => piece.id === answers.preferredPiece);
                ratio = 0.50 - Math.min(5, new Set(piece.traits || []).size) * 0.03;
            } else if (stage === 'reading') {
                const preferred = pieces.find(piece => piece.id === answers.preferredPiece);
                const difficulty = String(level(answers.reading, preferred.level, answers.playingLevel)).replace(/^(early|late) /, '');
                ratio = {elementary: 0.12, beginner: 0.15, intermediate: 0.18, advanced: 0.14}[difficulty] || 0.18;
            } else {
                const winners = pieces.filter(piece => answers.winners.indexOf(piece.id) !== -1);
                const traits = winners.reduce((all, piece) => all.concat(piece.traits), []);
                const overlap = traits.length ? 1 - new Set(traits).size / traits.length : 0;
                ratio = 0.018 - overlap * 0.012;
            }
            // Reserve a distinct count for each remaining stage, even in small catalogs.
            const floor = stage === 'level' ? 5 : stage === 'listen' ? 4 : (stage === 'reading' ? 3 : 2);
            return Math.max(floor, Math.min(previous - 1, Math.round(total * ratio)));
        }
    };

    class State {
        constructor(total, levelPieces = []) { this.total = total; this.levelPieces = levelPieces; this.offset = levelPieces.length ? 1 : 0; this.reset(); }
        reset() {
            this.step = 0; this.count = this.total; this.history = [];
            this.answers = {levelPiece: null, playingLevel: null, preferredPiece: null, reading: [], estimatedLevel: null, winners: [], intent: null, mood: null};
        }
        remember() { this.history.push(clone({step: this.step, count: this.count, answers: this.answers})); }
        back() {
            const previous = this.history.pop();
            if (previous) Object.assign(this, previous);
        }
        choose(value, pieces) {
            this.remember();
            let stage;
            const step = this.step - this.offset;
            if (step === -1) {
                this.answers.levelPiece = value;
                this.answers.playingLevel = baseLevel(this.levelPieces.find(piece => piece.id === value).level);
                this.answers.estimatedLevel = this.answers.playingLevel; stage = 'level';
            } else if (step === 0) { this.answers.preferredPiece = value; stage = 'listen'; }
            else if (step <= 2) {
                this.answers.reading[step - 1] = value;
                if (step === 2) { this.answers.estimatedLevel = level(this.answers.reading, pieces.find(piece => piece.id === this.answers.preferredPiece).level, this.answers.playingLevel); stage = 'reading'; }
            } else if (step <= 5) {
                this.answers.winners[step - 3] = value;
                if (step === 5) stage = 'preferences';
            } else { this.answers.mood = value; }
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
        to(target, duration, linear = false) {
            const generation = ++this.generation;
            const from = this.value;
            if (this.reduced || from === target) { this.set(target); return Promise.resolve(); }
            return new Promise(resolve => {
                let start;
                const tick = now => {
                    if (generation !== this.generation) return resolve();
                    if (start === undefined) start = now;
                    const progress = Math.min(1, (now - start) / duration);
                    const eased = linear ? progress : 1 - Math.pow(1 - progress, 3);
                    this.set(Math.round(from + (target - from) * eased));
                    if (progress < 1) this.frame(tick); else resolve();
                };
                this.frame(tick);
            });
        }
    }

    const icon = name => '<i class="app-icon icon-' + name + '" aria-hidden="true"></i>';
    const arrow = icon('arrow-right');

    // Match the piece player: Safari needs a typed source for legacy .mpga
    // recordings served as application/octet-stream.
    const audioType = url => ({mpga: 'audio/mpeg', mp3: 'audio/mpeg', mp4: 'video/mp4', m4a: 'audio/mp4'})[url.split(/[?#]/)[0].split('.').pop().toLowerCase()];

    class Previews {
        constructor(seconds, report) {
            this.audio = new root.Audio(); this.audio.preload = 'none';
            this.seconds = seconds; this.report = report; this.generation = 0;
            this.bound = new WeakSet(); this.attach(this.audio);
        }
        attach(media) {
            if (this.bound.has(media)) return;
            this.bound.add(media);
            ['loadedmetadata', 'durationchange', 'canplay'].forEach(event => media.addEventListener(event, () => this.seekStart(media)));
            ['timeupdate', 'seeking'].forEach(event => media.addEventListener(event, () => {
                if (this.media === media && media.tagName !== 'VIDEO' && media.currentTime >= this.startTime + this.seconds) { this.stop(); media.currentTime = 0; }
            }));
            media.addEventListener('ended', () => { if (this.media === media) this.stop(); });
            media.addEventListener('pause', () => { if (this.media === media && this.button) this.paint(false); });
            media.addEventListener('play', () => {
                if (this.media === media && this.button) this.paint(true);
                else media.pause(); // A hidden/stale native video may never resume.
            });
            media.addEventListener('error', () => {
                if (this.media === media && this.button) { this.stop(); this.report('This preview could not be played. You can still choose or view the piece.'); }
            });
        }
        seekStart(media) {
            if (this.media !== media || !this.awaitingStart || media.readyState < 1
                || !Number.isFinite(media.duration) || media.duration <= 0) return;
            const earliest = media.duration * 0.4;
            // Keep a full minute when a middle excerpt has enough recording left.
            const latest = media.duration - this.seconds > earliest
                ? Math.min(media.duration * 0.6, media.duration - this.seconds) : media.duration * 0.6;
            const start = earliest + Math.random() * (latest - earliest);
            try {
                this.startTime = start; this.awaitingStart = false;
                media.currentTime = start;
            } catch (error) {
                // Some browsers cannot seek until a later metadata/duration event.
                this.startTime = 0; this.awaitingStart = true;
            }
        }
        paint(playing) {
            const button = this.button;
            if (!button) return;
            const play = button.querySelector && button.querySelector('[data-play-icon]');
            const pause = button.querySelector && button.querySelector('[data-pause-icon]');
            const label = button.querySelector && button.querySelector('[data-play-label]');
            if (play && pause) { play.hidden = playing; pause.hidden = !playing; }
            else button.innerHTML = icon(playing ? 'pause' : 'play');
            if (label) label.textContent = playing ? 'Pause' : 'Play';
            button.setAttribute('aria-pressed', playing ? 'true' : 'false');
            if (button.dataset && button.dataset.pieceTitle) button.setAttribute('aria-label', (playing ? 'Pause preview: ' : 'Play preview: ') + button.dataset.pieceTitle);
            const card = button.closest && button.closest('[data-piece-card]');
            if (card) card.classList.toggle('is-playing', playing);
        }
        stop() {
            this.generation++; this.awaitingStart = false; this.startTime = 0;
            if (this.media) this.media.pause();
            this.paint(false); this.button = null; this.media = null;
        }
        setSource(url) {
            if (!this.source) {
                this.source = root.document.createElement('source');
                this.audio.appendChild(this.source);
            }
            this.audio.removeAttribute('src');
            const type = audioType(url);
            if (type) this.source.setAttribute('type', type);
            else this.source.removeAttribute('type');
            this.source.setAttribute('src', url);
            this.audio.load();
        }
        async play(piece, button, media) {
            const same = this.button === button;
            if (same && this.media && !this.media.paused) { this.generation++; this.media.pause(); return; }
            if (!same) {
                this.stop(); this.button = button; this.media = media || this.audio;
                this.attach(this.media);
                if (!media) this.setSource(piece.audio);
                this.media.currentTime = 0;
                this.awaitingStart = this.media.tagName !== 'VIDEO';
                this.seekStart(this.media);
            }
            const generation = ++this.generation;
            this.paint(true);
            try { await this.media.play(); }
            catch (error) {
                if (generation === this.generation) { this.stop(); this.report('This preview could not be played. You can still choose or view the piece.'); }
            }
        }
    }

    function excerptBounds(canvas) {
        const height = Math.max(1, Math.min(canvas.height, Math.round(canvas.width * 0.4)));
        return {top: Math.floor((canvas.height - height) / 2), height};
    }

    const escape = value => String(value).replace(/[&<>"']/g, character => ({'&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;'}[character]));
    const pause = duration => new Promise(resolve => root.setTimeout(resolve, duration));

    const libraries = {};
    function loadLibrary(name, url) {
        if (root[name]) return Promise.resolve(root[name]);
        if (libraries[name]) return libraries[name];
        libraries[name] = new Promise((resolve, reject) => {
            const script = root.document.createElement('script');
            let settled = false;
            const fail = () => {
                if (settled) return;
                settled = true;
                script.onload = script.onerror = null;
                root.clearTimeout(timer); script.remove(); delete libraries[name];
                reject(new Error('Media tool unavailable'));
            };
            const timer = root.setTimeout(fail, 15000);
            script.src = url;
            script.async = true;
            script.onerror = fail;
            script.onload = () => {
                if (!root[name]) { fail(); return; }
                settled = true;
                script.onload = script.onerror = null;
                root.clearTimeout(timer);
                resolve(root[name]);
            };
            root.document.head.appendChild(script);
        });
        return libraries[name];
    }
    function loadPdf() {
        return loadLibrary('pdfjsLib', 'https://cdn.jsdelivr.net/npm/pdfjs-dist@2.3.200/build/pdf.min.js').then(pdfjs => {
            pdfjs.GlobalWorkerOptions.workerSrc = 'https://cdn.jsdelivr.net/npm/pdfjs-dist@2.3.200/build/pdf.worker.min.js';
            return pdfjs;
        });
    }
    const quizStep = controller => controller.state.step - (controller.data && controller.data.levelPieces && controller.data.levelPieces.length ? 1 : 0);
    function progress(shell, section, suggestions, finding) {
        if (!shell) return;
        const names = ['Your level', 'Listening', 'Sight-reading', 'Your taste', 'Mood', 'We found your perfect match!'];
        const themes = ['level', 'listening', 'reading', 'duel', 'mood', 'reward'];
        if (shell.style) shell.style.setProperty('--match-progress', Math.min(100, (section + 1) * 20) + '%');
        shell.dataset.theme = suggestions ? 'recommendations' : themes[section];
        const number = shell.querySelector('[data-step-number]');
        if (number) number.textContent = section < 5 ? (section + 1) + ' / 5' : '';
        const name = shell.querySelector('[data-step-name]');
        if (name) name.textContent = finding ? 'Finding your match' : suggestions ? 'Your discoveries' : names[section];
        shell.querySelectorAll('[data-progress-step]').forEach((dot, index) => {
            dot.classList.toggle('is-current', index === section); dot.classList.toggle('is-complete', index < section);
            if (index === section) dot.setAttribute('aria-current', 'step'); else dot.removeAttribute('aria-current');
        });
    }
    class Controller {
        constructor(element, data, http, pdfjs, onRestart) {
            this.onRestart = onRestart;
            this.element = element; this.data = data; this.http = http; this.pdfjs = pdfjs;
            this.state = new State(data.total, data.levelPieces || []); this.generation = 0; this.busy = false;
            this.stage = element.querySelector('[data-stage]'); this.error = element.querySelector('[data-error]');
            this.shell = element.closest('.match-tour-modal');
            this.reduced = root.matchMedia && root.matchMedia('(prefers-reduced-motion: reduce)').matches;
            this.counter = new AnimatedCount(element.querySelector('[data-count]'), element.querySelector('[data-count-unit]'), null, this.reduced);
            this.previews = new Previews(data.previewSeconds, message => this.report(message));
            this.counter.set(data.total); this.pdfTasks = new Set(); this.scoreExcerpts = new Map();
            this.onClick = event => this.click(event);
            element.addEventListener('click', this.onClick);
            this.onPageHide = () => this.dispose();
            this.onVisibility = () => { if (root.document.hidden) this.stopMedia(); };
            root.addEventListener('pagehide', this.onPageHide);
            root.document.addEventListener('visibilitychange', this.onVisibility);
            this.render(false);
        }
        report(message) { this.error.textContent = message; this.error.hidden = !message; }
        stopMedia() {
            this.previews.stop();
            this.stage.querySelectorAll('audio,video').forEach(media => media.pause());
        }
        dispose() {
            this.generation++; this.counter.cancel(); this.stopMedia();
            this.finishFinding();
            this.pdfTasks.forEach(task => task.destroy()); this.pdfTasks.clear();
        }
        destroy() {
            this.dispose();
            this.element.removeEventListener('click', this.onClick);
            root.removeEventListener('pagehide', this.onPageHide);
            root.document.removeEventListener('visibilitychange', this.onVisibility);
        }
        navigation() {
            const step = quizStep(this);
            const section = step < 0 ? 0 : step === 0 ? 1 : step <= 2 ? 2 : step <= 5 ? 3 : step === 6 ? 4 : 5;
            progress(this.shell, section, this.suggestions, this.waiting);
            const back = this.element.querySelector('[data-back]');
            back.disabled = !this.state.history.length && !this.suggestions; back.hidden = this.state.step === 0;
            this.element.querySelector('.match-navigation').hidden = this.state.step === 0;
            this.element.querySelector('.match-navigation [data-skip]').hidden = step <= 0 || step === 7;
            this.element.querySelector('[data-skip-label]').textContent = step < 6 ? 'Not sure? Skip' : 'Skip';
            this.element.querySelector('[data-restart]').hidden = step < 7;
            this.element.querySelector('.match-count').hidden = !!this.suggestions || (step === 7 && !this.waiting);
            if (this.shell) this.shell.classList.toggle('has-match-result', step === 7 && !this.waiting && !this.suggestions);
            const unit = this.element.querySelector('[data-count-unit]');
            unit.hidden = step === 7 && !this.waiting;
            unit.textContent = unit.hidden ? '' : 'pieces';
            this.element.querySelector('[data-count-note]').textContent = step <= 0 ? 'In the PianoLIT library' : this.waiting ? 'Finding your match' : step === 7 ? 'Chosen for you' : 'Estimated pieces remaining';
        }
        async click(event) {
            const button = event.target.closest('button');
            if (!button || button.disabled) return;
            if (button.hasAttribute('data-restart') && this.onRestart) { await this.onRestart(); return; }
            if (button.hasAttribute('data-back') || button.hasAttribute('data-restart')) {
                if (this.suggestions && button.hasAttribute('data-back')) { this.showSuggestions(false); return; }
                this.dispose(); this.busy = false; this.suggestions = false; this.resultData = null;
                if (button.hasAttribute('data-back')) this.state.back(); else this.state.reset();
                this.pendingLevel = quizStep(this) === -1 ? this.state.answers.levelPiece : null;
                this.pendingPiece = quizStep(this) === 0 ? this.state.answers.preferredPiece : null;
                this.counter.set(this.state.count); this.render(); return;
            }
            if (this.busy) return;
            if (button.hasAttribute('data-recommendations')) { this.showSuggestions(true); return; }
            if (button.hasAttribute('data-play')) {
                this.report('');
                const id = Number(button.dataset.play);
                const pieces = (this.data.levelPieces || []).concat(this.data.pieces).concat(this.resultData ? [this.resultData.piece].concat(this.resultData.recommendations) : []);
                const piece = pieces.find(piece => piece.id === id);
                // Result data takes precedence if the chosen match is also an opening card.
                const actual = this.resultData && this.resultData.piece.id === id ? this.resultData.piece : piece;
                const card = button.closest('[data-piece-card]');
                const media = card.querySelector('[data-result-media]');
                if (media && media.tagName === 'VIDEO') card.classList.add('has-played');
                await this.previews.play(actual, button, media);
                if (media && media.tagName === 'VIDEO' && !media.paused && media.isConnected) media.focus();
                return;
            }
            if (button.hasAttribute('data-score-retry')) { this.reading(); return; }
            if (button.hasAttribute('data-select-piece') || button.hasAttribute('data-select-level')) {
                const isLevel = button.hasAttribute('data-select-level');
                const attribute = isLevel ? 'data-select-level' : 'data-select-piece';
                const id = Number(button.getAttribute(attribute));
                if (isLevel) this.pendingLevel = id; else this.pendingPiece = id;
                this.stage.querySelectorAll('[' + attribute + ']').forEach(control => {
                    const selected = Number(control.getAttribute(attribute)) === id;
                    control.setAttribute('aria-pressed', selected ? 'true' : 'false');
                    const label = control.querySelector('[data-select-label]');
                    if (label) label.textContent = selected ? 'Selected' : 'Select';
                    control.closest('[data-piece-card]').classList.toggle('selected', selected);
                });
                this.stage.querySelector(isLevel ? '[data-level-confirm]' : '[data-listen-confirm]').hidden = false;
                return;
            }
            if (button.hasAttribute('data-skip')) {
                if (quizStep(this) === 1) { if (await this.choose(null) && quizStep(this) === 2) await this.choose(null); }
                else await this.choose(quizStep(this) === 6 ? 'open' : null);
                return;
            }
            if (button.hasAttribute('data-level-confirm')) { await this.choose(this.pendingLevel); return; }
            if (button.hasAttribute('data-listen-confirm')) { await this.choose(this.pendingPiece); return; }
            if (!button.hasAttribute('data-choice')) return;
            let value = button.dataset.choice;
            if (quizStep(this) <= 2) value = value === 'yes';
            else if (quizStep(this) < 6) value = Number(value);
            (button.closest('[data-piece-card]') || button).classList.add('selected');
            await this.choose(value);
        }
        async choose(value) {
            this.busy = true; this.report(''); this.stopMedia();
            this.stage.querySelectorAll('button').forEach(button => { button.disabled = true; });
            const generation = ++this.generation;
            this.state.choose(value, this.data.pieces);
            if (quizStep(this) === 7) { await this.result(generation); return; }
            await this.counter.to(this.state.count, this.reduced ? 0 : 650);
            if (generation !== this.generation) return;
            this.element.querySelector('[data-count-announcement]').textContent = this.state.count.toLocaleString('en-US') + ' estimated pieces remaining';
            this.stage.classList.add('leaving');
            await pause(this.reduced ? 0 : 130);
            if (generation !== this.generation) return;
            this.busy = false; this.render(); return true;
        }
        heading(title, subtitle, detail) {
            return '<div class="match-heading"><h3 tabindex="-1">' + title + '</h3><p>' + subtitle + '</p>' +
                (detail ? '<span class="match-rounds">' + detail + '</span>' : '') + '</div>';
        }
        waveform() {
            return '<span class="match-waveform" aria-hidden="true">' + Array.from({length: 32}, (_, i) =>
                '<span style="--bar-height:' + (12 + ((i * 17 + 9) % 31)) + '%;--bar-delay:' + (-i * 0.08) + 's"></span>').join('') + '</span>';
        }
        // All music cards share one renderer and the same preview controller.
        cards(pieces, variant) {
            return '<div class="match-cards match-cards--' + variant + (variant === 'level' ? ' match-cards--listening' : '') + '">' + pieces.map(piece => {
                const isLevel = variant === 'level', listening = variant === 'listening' || isLevel, duel = variant === 'duel', reward = variant === 'reward', recommendation = variant === 'recommendation';
                const play = '<button type="button" class="btn match-play" data-play="' + piece.id + '" data-piece-title="' + escape(piece.title) + '" aria-pressed="false" aria-label="Play preview: ' + escape(piece.title) + '"' +
                    (!piece.audio && !piece.video ? ' disabled' : '') + '><span data-play-icon>' + icon('play') + '</span><span data-pause-icon hidden>' + icon('pause') + '</span>' + (duel ? '<span data-play-label>Play</span>' : '') + '</button>';
                const selected = (isLevel ? this.pendingLevel : this.pendingPiece) === piece.id;
                const action = isLevel ? 'data-select-level="' + piece.id + '" aria-pressed="' + selected + '"' : listening ? 'data-select-piece="' + piece.id + '" aria-pressed="' + (this.pendingPiece === piece.id) + '"' : 'data-choice="' + piece.id + '"';
                const tag = recommendation ? 'a' : 'button';
                const attrs = recommendation ? 'href="' + escape(piece.url) + '"' : 'type="button" ' + action + (duel || isLevel ? ' aria-label="Choose ' + escape((isLevel ? this.data.levels[baseLevel(piece.level)].label + ' level: ' : '') + piece.title + ' by ' + piece.composer) + '"' : '');
                const image = '<img src="' + escape(listening ? piece.image : piece.artwork) + '" alt=""' + (recommendation ? ' loading="lazy"' : '') + '>';
                const difficulty = baseLevel(piece.level);
                const definition = isLevel ? this.data.levels[difficulty] : null;
                const badge = isLevel ? '<span class="match-level-badge">' + escape(definition.label) + '</span>' : '';
                const skills = isLevel ? '<span class="match-level-skills"><span class="match-level-scale">' + badge + '<span class="match-level-dots" aria-hidden="true">' + levels.map((name, index) => '<span' + (index <= levels.indexOf(difficulty) ? ' class="filled"' : '') + '></span>').join('') + '</span></span><span class="match-level-hint">' + escape(definition.hint) + '</span></span>' : '';
                const select = listening ? '<span class="match-select-label" data-select-label aria-hidden="true">' + (selected ? 'Selected' : 'Select') + '</span>' : '';
                const copy = '<span class="match-piece-copy"><strong>' + escape(piece.title) + '</strong><small>' + escape(piece.composer) + '</small></span>';
                const media = reward ? (piece.video ? '<video data-result-media controls playsinline preload="none" poster="' + escape(piece.artwork) + '" src="' + escape(piece.video) + '"></video>' : piece.audio ? '<audio data-result-media preload="none"><source src="' + escape(piece.audio) + '"' + (audioType(piece.audio) ? ' type="' + audioType(piece.audio) + '"' : '') + '></audio>' : '') : '';
                const content = reward ? '<div class="match-artwork' + (piece.video ? ' has-video' : '') + '">' + image + media + play + '</div>' + copy :
                    '<' + tag + ' class="match-select" ' + attrs + '>' + image + (duel ? '' : copy) + select + '</' + tag + '>' + (duel ? copy : '') + '<div class="match-card-player">' + play + (isLevel ? skills : listening ? this.waveform() : '') + '</div>';
                return '<article class="match-card match-card--' + variant + (isLevel ? ' match-card--listening' : '') + (listening && selected ? ' selected' : '') + '"' + (isLevel ? ' data-level="' + difficulty + '"' : '') + ' data-piece-card>' + content + '</article>';
            }).join('') + (variant === 'duel' ? '<span class="match-duel-or" aria-hidden="true">OR</span>' : '') + '</div>';
        }
        render(focus = true) {
            this.stage.classList.remove('leaving'); this.report(''); this.navigation();
            const step = quizStep(this);
            if (step === -1) this.stage.innerHTML = this.heading('What feels closest to your level?', 'Pick the closest fit, not necessarily your favorite') + this.cards(this.data.levelPieces, 'level') +
                '<div class="match-main-action"><button type="button" class="btn btn-primary match-primary" data-level-confirm' + (!this.pendingLevel ? ' hidden' : '') + '>This feels right ' + arrow + '</button></div>';
            else if (step === 0) this.stage.innerHTML = this.heading('Which piece do you like more?', 'Listen and pick your favorite.') + this.cards(this.data.pieces.slice(0, 4), 'listening') +
                '<div class="match-main-action"><button type="button" class="btn btn-primary match-primary" data-listen-confirm' + (!this.pendingPiece ? ' hidden' : '') + '>I like this one ' + arrow + '</button></div>';
            else if (step <= 2) this.reading();
            else if (step <= 5) this.stage.innerHTML = this.heading('Which feels more like you?', 'Pick the piece that speaks to you.', (step - 2) + ' of 3') + this.cards(this.data.pieces.slice(4 + (step - 3) * 2, 6 + (step - 3) * 2), 'duel');
            else if (step === 6) this.stage.innerHTML = this.heading('What mood are you in?', "Choose the vibe you're looking for.") + '<div class="match-moods">' + Object.keys(this.data.moods).map(key => {
                const mood = this.data.moods[key];
                return '<button type="button" class="match-mood" data-choice="' + key + '"><span class="match-mood-icon" aria-hidden="true">' + icon(mood.icon) + '</span><span>' + escape(mood.label) + '</span></button>';
            }).join('') + '</div>';
            if (focus) this.focus();
        }
        focus() {
            const body = this.element.closest('.match-tour-body'); if (body) body.scrollTop = 0;
            const heading = this.suggestions ? this.stage.querySelector('[data-recommendations-view] h3') : this.stage.querySelector('h3');
            if (heading) heading.focus({preventScroll: true});
        }
        readingScore() {
            const scores = this.data.scores;
            return quizStep(this) === 1 ? scores.middle : this.state.answers.reading[0] ? scores.hard : (scores.beginner || scores.easy);
        }
        async reading() {
            const generation = ++this.generation, score = this.readingScore();
            this.pdfTasks.forEach(task => task.destroy()); this.pdfTasks.clear();
            this.stage.innerHTML = this.heading('Which feels easier to sight-read?', 'Imagine playing it for the first time at a slow pace.', quizStep(this) + ' of 2') +
                '<article class="match-score-card match-score-single"><div class="match-score" data-score-slot="0" aria-busy="true"><p role="status">Opening score…</p></div></article>' +
                '<div class="match-score-answers mt-3" role="group" aria-label="Sight-reading difficulty"><button type="button" class="btn btn-secondary match-score-choice" data-choice="yes" disabled>Easy for me</button><button type="button" class="btn btn-secondary match-score-choice" data-choice="no" disabled>Difficult for me</button></div>';
            try {
                if (!this.pdfjs) this.pdfjs = await loadPdf();
                if (generation !== this.generation) return;
                await this.score(score, 0, generation);
            } catch (error) {
                if (generation === this.generation) this.scoreFailure();
            }
        }
        scoreFailure() {
            this.stage.querySelectorAll('.match-score').forEach(slot => { slot.setAttribute('aria-busy', 'false'); slot.innerHTML = '<p>The score could not be opened.</p>'; });
            this.report('The score preview could not be opened. Try again or skip this step.');
            if (!this.stage.querySelector('[data-score-retry]')) this.stage.insertAdjacentHTML('beforeend', '<div class="text-center"><button type="button" class="btn btn-secondary" data-score-retry>Retry score</button></div>');
        }
        async score(score, index, generation) {
            const slot = this.stage.querySelector('[data-score-slot="' + index + '"]');
            let task, timer;
            try {
                let cropped = this.scoreExcerpts.get(score.url);
                if (!cropped) {
                    task = this.pdfjs.getDocument({url: score.url}); this.pdfTasks.add(task);
                    const pdf = await Promise.race([task.promise, new Promise((resolve, reject) => { timer = root.setTimeout(() => reject(new Error('Score timed out')), 15000); })]);
                    root.clearTimeout(timer);
                    if (generation !== this.generation) return;
                    const page = await pdf.getPage(1);
                    if (generation !== this.generation) return;
                    const viewport = page.getViewport({scale: 1.5}), canvas = root.document.createElement('canvas');
                    canvas.width = viewport.width; canvas.height = viewport.height;
                    await page.render({canvasContext: canvas.getContext('2d'), viewport}).promise;
                    if (generation !== this.generation) return;
                    const excerpt = excerptBounds(canvas);
                    cropped = root.document.createElement('canvas'); cropped.width = canvas.width; cropped.height = excerpt.height;
                    cropped.getContext('2d').drawImage(canvas, 0, excerpt.top, canvas.width, excerpt.height, 0, 0, cropped.width, cropped.height);
                    this.scoreExcerpts.set(score.url, cropped);
                }
                if (generation !== this.generation) return;
                const copy = root.document.createElement('canvas'); copy.width = cropped.width; copy.height = cropped.height;
                copy.getContext('2d').drawImage(cropped, 0, 0); copy.setAttribute('role', 'img');
                copy.setAttribute('aria-label', 'Middle of score page: ' + score.title + ' by ' + score.composer);
                slot.innerHTML = ''; slot.appendChild(copy); slot.setAttribute('aria-busy', 'false');
                // Answer only after the actual excerpt is ready.
                this.stage.querySelectorAll('[data-choice]').forEach(button => { button.disabled = false; });
            } catch (error) { if (generation === this.generation) this.scoreFailure(); }
            finally { root.clearTimeout(timer); if (task) { task.destroy(); this.pdfTasks.delete(task); } }
        }
        showSuggestions(show) {
            this.stopMedia(); this.suggestions = show;
            this.stage.querySelector('[data-result-view]').hidden = show;
            this.stage.querySelector('[data-recommendations-view]').hidden = !show;
            this.navigation(); this.focus();
        }
        mountResult() {
            this.resultData = JSON.parse(this.stage.querySelector('[data-result-data]').textContent);
            this.stage.querySelector('[data-result-card]').innerHTML = this.cards([this.resultData.piece], 'reward');
            this.stage.querySelector('[data-recommendation-cards]').innerHTML = this.resultData.recommendations.length ? this.cards(this.resultData.recommendations, 'recommendation') : '<p class="text-center">Explore the library for more pieces to discover.</p>';
        }
        finding() {
            this.waiting = true; this.element.classList.add('is-finding');
            this.stage.classList.remove('leaving'); this.stage.setAttribute('aria-busy', 'true');
            this.element.querySelector('.match-count-number').insertAdjacentHTML('beforeend', '<span class="match-finding-orbit" aria-hidden="true"></span>');
            this.stage.innerHTML = '<div class="match-finding is-playing">' +
                this.heading('Your answers are in!', 'Finding the piece you’ll love…') + this.waveform() + '</div>';
            this.navigation(); this.focus();
            this.element.querySelector('[data-count-announcement]').textContent = 'Your answers are complete. Finding your match.';
        }
        finishFinding() {
            this.waiting = false; this.element.classList.remove('is-finding'); this.stage.removeAttribute('aria-busy');
            const orbit = this.element.querySelector('.match-finding-orbit');
            if (orbit) orbit.remove();
        }
        async result(generation) {
            const previous = this.state.count;
            this.finding();
            try {
                // Narrow the estimate while fetching; reserve the final piece for a ready result.
                const duration = this.reduced ? 0 : 2200;
                const countdown = Promise.all([this.counter.to(2, duration, true), pause(duration)]);
                // Keep the current estimated count if recommendation retrieval fails.
                const response = await this.http.post(this.element.dataset.url, Object.assign(clone(this.state.answers), {draw: this.data.draw}), {timeout: 20000});
                if (generation !== this.generation) return;
                await countdown;
                if (generation !== this.generation) return;
                this.stage.querySelector('h3').textContent = 'Your match is ready!';
                await this.counter.to(1, this.reduced ? 0 : 450, true);
                if (generation !== this.generation) return;
                this.state.count = 1; this.stopMedia();
                this.stage.classList.add('leaving'); await pause(this.reduced ? 0 : 130);
                if (generation !== this.generation) return;
                this.finishFinding(); this.stage.innerHTML = response.data; this.mountResult();
                this.stage.classList.remove('leaving'); this.navigation(); this.focus(); this.busy = false;
                this.element.querySelector('[data-count-announcement]').textContent = '1 perfect match. Your match is ready.';
            } catch (error) {
                if (generation !== this.generation) return;
                this.finishFinding();
                this.counter.cancel(); this.state.back(); this.counter.set(previous); this.suggestions = false;
                this.busy = false; this.render();
                const staleChoices = error.response && error.response.status === 422;
                if (staleChoices) this.element.querySelector('[data-restart]').hidden = false;
                this.report(staleChoices ? 'Your listening choices have expired or changed. Start over for a fresh tour.' : 'We couldn’t find your match just now. Choose your mood again to retry; your earlier answers are saved.');
            }
        }
    }

    class Launcher {
        constructor(element, http) {
            this.element = element; this.http = http; this.generation = 0;
            this.content = element.querySelector('[data-tour-content]');
            this.dialog = new root.bootstrap.Modal(element);
            element.addEventListener('show.bs.modal', () => this.load());
            element.addEventListener('shown.bs.modal', () => {
                this.shown = true;
                if (this.controller) this.controller.focus();
            });
            element.addEventListener('hide.bs.modal', () => {
                this.shown = false; this.generation++; this.loading = false;
                if (this.controller) { this.controller.destroy(); this.controller = null; }
            });
            element.addEventListener('hidden.bs.modal', () => {
                this.content.innerHTML = '';
                if (this.opener && this.opener.isConnected) this.opener.focus({preventScroll: true});
            });
            element.addEventListener('click', event => {
                if (event.target.closest('[data-tour-retry]')) this.load();
            });
            root.document.addEventListener('click', event => {
                const opener = event.target.closest('[data-match-tour-open]');
                if (!opener || event.defaultPrevented || event.button !== 0 || event.ctrlKey || event.metaKey || event.shiftKey || event.altKey) return;
                event.preventDefault(); this.open(opener);
            });
        }
        open(opener) { this.opener = opener; this.dialog.show(opener); }
        async load() {
            if (this.loading) return;
            if (this.controller) { this.controller.destroy(); this.controller = null; }
            progress(this.element, 0, false);
            this.loading = true;
            this.content.scrollTop = 0;
            const generation = ++this.generation;
            this.content.innerHTML = '<div class="match-tour-loading text-center" role="status"><i class="app-icon icon-loader-circle spin" aria-hidden="true"></i><p class="text-muted mt-3">Opening your choices…</p></div>';
            try {
                const response = await this.http.get(this.element.dataset.tourUrl, {headers: {Accept: 'application/json'}, timeout: 20000});
                if (generation !== this.generation) return;
                this.content.innerHTML = response.data.html;
                if (response.data.tour.ready) {
                    this.controller = new Controller(this.content.querySelector('#match-tour'), response.data.tour, this.http, undefined, () => this.load());
                    if (this.shown) this.controller.focus();
                }
            } catch (error) {
                if (generation !== this.generation) return;
                this.content.innerHTML = '<div class="match-tour-loading text-center"><p role="alert">The tour could not be opened.</p><button type="button" class="btn btn-secondary" data-tour-retry>Try again</button></div>';
            } finally {
                if (generation === this.generation) this.loading = false;
            }
        }
    }

    root.MatchTour = {Candidates, State, AnimatedCount, Previews, Controller, Launcher, level, loadPdf};
})(typeof window !== 'undefined' ? window : globalThis);
