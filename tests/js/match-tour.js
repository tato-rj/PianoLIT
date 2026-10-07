const assert = require('assert');
const fs = require('fs');
const vm = require('vm');
const path = require('path');

module.exports = async function () {
    const window = {};
    vm.runInNewContext(fs.readFileSync(path.join(__dirname, '../../resources/js/views/match-tour.js'), 'utf8'), {window});
    const {State, Candidates, AnimatedCount, Previews, Controller} = window.MatchTour;
    const pieces = Array.from({length: 10}, (_, i) => ({id: i + 1, audio: i + '.mp3', traits: [i % 2 ? 'calm' : 'flashy', 'romantic']}));
    for (const total of [10, 50, 381, 2847, 10000]) {
        for (const reading of [[true, true], [true, false], [false, true], [false, false]]) {
            const state = new State(total);
            state.choose(1, pieces);
            const first = state.count;
            assert(first < total && first > 1);
            state.choose(reading[0], pieces);
            assert.strictEqual(state.count, first);
            state.choose(reading[1], pieces);
            const second = state.count;
            assert(second < first && second > 1);
            state.choose(5, pieces); state.choose(7, pieces);
            assert.strictEqual(state.count, second);
            state.choose(9, pieces);
            assert(state.count < second && state.count > 1);
            assert.strictEqual(state.count, Candidates.after(total, second, 'preferences', state.answers, pieces));
            state.choose('quick', pieces);
            state.back(); assert.strictEqual(state.step, 6); assert.strictEqual(state.answers.intent, null);
            for (let i = 0; i < 6; i++) state.back();
            assert.strictEqual(state.count, total); assert.strictEqual(state.step, 0);
            assert.strictEqual(state.answers.reading.length, 0); assert.strictEqual(state.answers.winners.length, 0);
        }
    }
    const frames = [];
    const node = {}; const unit = {};
    const count = new AnimatedCount(node, unit, callback => frames.push(callback), false);
    count.set(2847); assert.strictEqual(node.textContent, '2,847');
    const animation = count.to(1126, 850);
    const seen = [];
    for (let time = 0; frames.length; time += 50) { frames.shift()(time); seen.push(count.value); }
    await animation;
    assert(seen.length > 10); assert.strictEqual(seen[seen.length - 1], 1126);
    assert(seen.every((value, i) => i === 0 || value <= seen[i - 1]));
    const final = count.to(1, 1350);
    for (let time = 0; frames.length; time += 50) frames.shift()(time);
    await final; assert.strictEqual(unit.textContent, 'piece');
    const cancelled = count.to(10, 850); count.cancel(); count.set(381); frames.shift()(0); await cancelled;
    assert.strictEqual(count.value, 381);
    const reduced = new AnimatedCount(node, unit, () => { throw Error('No animation for reduced motion'); }, true);
    reduced.set(500); await reduced.to(2, 850); assert.strictEqual(node.textContent, '2');

    let warnings = 0;
    window.Audio = class {
        constructor() { this.events = {}; }
        addEventListener(name, callback) { this.events[name] = callback; }
        pause() { this.paused = true; }
        play() { this.paused = false; return this.failure ? Promise.reject(new Error()) : Promise.resolve(); }
    };
    const previews = new Previews(10, () => warnings++);
    const button = () => ({innerHTML: '', setAttribute() {}});
    const a = button(), b = button();
    await previews.play(pieces[0], a); await previews.play(pieces[1], b);
    assert(a.innerHTML.includes('icon-play')); assert(b.innerHTML.includes('icon-pause'));
    previews.audio.currentTime = 10.5; previews.audio.events.timeupdate(); assert(previews.audio.paused);
    previews.audio.failure = true; await previews.play(pieces[0], a); assert.strictEqual(warnings, 1); assert.strictEqual(previews.button, null);

    // A result delivered after restart/back must never replace the current screen.
    let resolve;
    const stale = {state: {count: 47, answers: {}}, http: {post: () => new Promise(done => { resolve = done; })}, element: {dataset: {url: '/result'}}, counter: {to: () => Promise.resolve()}, generation: 1};
    const response = Controller.prototype.result.call(stale, 1);
    stale.generation = 2;
    resolve({data: '<article>obsolete</article>'}); await response;
    assert.strictEqual(stale.state.count, 47);

    // The result stays in the fullscreen tour's stage with no second modal.
    window.setTimeout = callback => callback();
    const media = {events: {}, currentTime: 0, pause() { this.pauses = (this.pauses || 0) + 1; }, addEventListener(event, callback) { this.events[event] = callback; }};
    window.bootstrap = {Modal: class {constructor() { throw Error('Result must not open a second modal'); }}};
    let destroyed = false;
    window.Plyr = class {destroy() { destroyed = true; }};
    const result = {
        state: {count: 2, answers: {}}, http: {post: () => Promise.resolve({data: 'chosen piece'})},
        element: {dataset: {url: '/result'}, querySelector: () => ({})},
        stage: {classList: {add() {}, remove() {}}, querySelector: () => media, querySelectorAll: () => [media]},
        counter: {to: () => Promise.resolve(), cancel() {}}, generation: 1, reduced: true, data: {previewSeconds: 10}, previews: {stop() {}},
        stopMedia: Controller.prototype.stopMedia, navigation() {}, focus() {}
    };
    await Controller.prototype.result.call(result, 1);
    assert.strictEqual(result.state.count, 1);
    assert.strictEqual(result.stage.innerHTML, 'chosen piece');
    media.currentTime = 10; media.events.seeking();
    assert.strictEqual(media.currentTime, 0);
    Controller.prototype.dispose.call(result);
    assert(destroyed); assert(media.pauses > 0);
    assert.strictEqual(result.resultPlayer, null);

    let failureReported;
    const failed = {state: {count: 2, answers: {}, back() { this.step = 6; }}, http: {post: () => Promise.reject(new Error('offline'))}, element: {dataset: {url: '/result'}},
        counter: {to: () => Promise.resolve(), cancel() {}, set(value) { this.value = value; }}, generation: 1,
        render() { this.rendered = true; }, report(message) { failureReported = message; }};
    await Controller.prototype.result.call(failed, 1);
    assert.strictEqual(failed.state.step, 6); assert.strictEqual(failed.counter.value, 2);
    assert(failed.rendered); assert(!failed.busy); assert(failureReported.includes('retry'));

    // Media tools are requested only on demand; failures/timeouts permit retry.
    const scripts = [], timers = new Map();
    let timerId = 0;
    window.setTimeout = callback => { timers.set(++timerId, callback); return timerId; };
    window.clearTimeout = id => timers.delete(id);
    window.document = {
        createElement: tag => ({tag, remove() { this.removed = true; }}),
        head: {appendChild(script) { scripts.push(script); }}
    };
    const pdfFirst = window.MatchTour.loadPdf();
    const pdfSecond = window.MatchTour.loadPdf();
    assert.strictEqual(scripts.length, 1, 'Concurrent reading loads share one script');
    scripts[0].onerror();
    await assert.rejects(pdfFirst); await assert.rejects(pdfSecond);
    assert(scripts[0].removed); assert.strictEqual(timers.size, 0);
    const timedOut = window.MatchTour.loadPdf();
    timers.values().next().value();
    await assert.rejects(timedOut); assert(scripts[1].removed);
    const retried = window.MatchTour.loadPdf();
    const pdf = {GlobalWorkerOptions: {}, getDocument() { throw Error('Obsolete reading must not open a PDF'); }};
    window.pdfjsLib = pdf; scripts[2].onload();
    assert.strictEqual(await retried, pdf);
    assert(pdf.GlobalWorkerOptions.workerSrc.endsWith('/pdf.worker.min.js'));
    assert.strictEqual(await window.MatchTour.loadPdf(), pdf);
    assert.strictEqual(scripts.length, 3, 'Successful library loads are reused');

    // Back/restart during library loading must not fetch or replace an old score.
    delete window.pdfjsLib;
    // Use a fresh script context to exercise a pending first reading load.
    const pendingWindow = Object.assign({}, window);
    vm.runInNewContext(fs.readFileSync(path.join(__dirname, '../../resources/js/views/match-tour.js'), 'utf8'), {window: pendingWindow});
    const container = {};
    const reader = {state: {step: 1}, generation: 1, data: {scores: {middle: {title: 'Score', composer: 'Composer', url: '/score.pdf'}}},
        stage: {querySelector: () => container}, heading: () => '', pdfjs: null};
    const reading = pendingWindow.MatchTour.Controller.prototype.reading.call(reader);
    reader.generation++;
    pendingWindow.pdfjsLib = pdf; scripts[3].onload();
    await reading;
    assert.strictEqual(reader.pdfTask, undefined);

    delete window.Plyr;
    const player = window.MatchTour.loadPlayer();
    assert.strictEqual(scripts[4].tag, 'link');
    assert.strictEqual(scripts[5].tag, 'script');
    window.Plyr = class {};
    scripts[5].onload();
    assert.strictEqual(await player, window.Plyr);
    await window.MatchTour.loadPlayer();
    assert.strictEqual(scripts.length, 6);

    // Closing the shell discards a pending load and restores the launcher's focus.
    const events = {}, documentEvents = {};
    const content = {innerHTML: ''};
    const shell = {dataset: {tourUrl: '/tour'}, querySelector: () => content, addEventListener(name, callback) { events[name] = callback; }};
    window.document.addEventListener = (name, callback) => { documentEvents[name] = callback; };
    window.bootstrap.Modal = class {show() { events['show.bs.modal'](); }};
    let loaded, requests = 0;
    const launcher = new window.MatchTour.Launcher(shell, {get() { requests++; return new Promise(resolve => loaded = resolve); }});
    const launchButton = {isConnected: true, focus() { this.focused = true; }};
    launcher.open(launchButton);
    assert.strictEqual(requests, 1);
    await launcher.load(); assert.strictEqual(requests, 1, 'Do not duplicate an active load');
    events['hide.bs.modal'](); events['hidden.bs.modal']();
    loaded({data: {html: 'obsolete question', tour: {ready: false}}});
    await Promise.resolve();
    assert.strictEqual(content.innerHTML, '');assert(launchButton.focused);
    let stopped = false;
    launcher.controller = {destroy() { stopped = true; }};
    events['hide.bs.modal'](); assert(stopped); assert.strictEqual(launcher.controller, null);
    launcher.http.get = () => Promise.reject(Error('offline'));
    await launcher.load(); assert(content.innerHTML.includes('data-tour-retry'));assert(!launcher.loading);
    launcher.http.get = () => Promise.resolve({data: {html: 'unavailable', tour: {ready: false}}});
    await launcher.load(); assert.strictEqual(content.innerHTML, 'unavailable');
    let prevented = false;
    documentEvents.click({target: {closest: () => launchButton}, button: 0, ctrlKey: true, preventDefault() { prevented = true; }});
    assert(!prevented, 'Modified clicks keep normal new-tab navigation');

};
