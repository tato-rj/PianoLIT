const assert = require('assert');
const fs = require('fs');
const vm = require('vm');
const path = require('path');

module.exports = async function () {
    const window = {};
    vm.runInNewContext(fs.readFileSync(path.join(__dirname, '../../resources/js/views/match-tour.js'), 'utf8'), {window});
    const {State, Candidates, AnimatedCount, Previews, Controller} = window.MatchTour;
    const pieces = Array.from({length: 10}, (_, i) => ({id: i + 1, audio: i + '.mp3', level: 'beginner', traits: [i % 2 ? 'calm' : 'flashy', 'romantic']}));
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
            state.choose('calm', pieces);
            state.back(); assert.strictEqual(state.step, 6); assert.strictEqual(state.answers.mood, null);
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
    const previews = new Previews(60, () => warnings++);
    const button = () => ({innerHTML: '', setAttribute() {}});
    const a = button(), b = button();
    await previews.play(pieces[0], a); await previews.play(pieces[1], b);
    assert(a.innerHTML.includes('icon-play')); assert(b.innerHTML.includes('icon-pause'));
    previews.audio.currentTime = 60; previews.audio.events.timeupdate(); assert(previews.audio.paused);
    previews.audio.failure = true; await previews.play(pieces[0], a); assert.strictEqual(warnings, 1); assert.strictEqual(previews.button, null);

    previews.audio.failure = false;
    await previews.play(pieces[0], a);
    previews.audio.currentTime = 59.9; previews.audio.events.seeking();
    assert(!previews.audio.paused, 'Audio plays until the full 60-second boundary');
    previews.audio.currentTime = 60; previews.audio.events.seeking();
    assert(previews.audio.paused); assert.strictEqual(previews.audio.currentTime, 0);
    const video = new window.Audio(); video.tagName = 'VIDEO';
    await previews.play(pieces[0], b, video);
    video.currentTime = 125; video.events.timeupdate(); video.events.seeking();
    assert(!video.paused, 'Result video plays beyond the audio cutoff');
    video.events.ended(); assert(video.paused); assert.strictEqual(previews.media, null);
    video.events.play(); assert(video.paused, 'Disposed native media cannot resume');

    // A result delivered after restart/back must never replace the current screen.
    let resolve;
    const stale = {finding() { this.waiting = true; }, state: {count: 47, answers: {}}, http: {post: () => new Promise(done => { resolve = done; })}, element: {dataset: {url: '/result'}}, counter: {to: () => Promise.resolve()}, generation: 1};
    const response = Controller.prototype.result.call(stale, 1);
    assert(stale.waiting, 'Completion feedback appears before the response arrives');
    stale.generation = 2;
    resolve({data: '<article>obsolete</article>'}); await response;
    assert.strictEqual(stale.state.count, 47);

    // Successful results reuse the stage and do not require a second modal/player.
    window.setTimeout = callback => callback();
    window.bootstrap = {Modal: class {constructor() { throw Error('No second modal'); }}};
    let mounted = false, mediaStopped = false;
    const result = {
        finding() {}, finishFinding() { this.finishedWaiting = true; },
        state: {count: 2, answers: {}}, http: {post: () => Promise.resolve({data: 'chosen piece'})},
        element: {dataset: {url: '/result'}, querySelector: () => ({})},
        stage: {querySelector: () => ({}), classList: {add() {}, remove() {}}},
        counter: {to: () => Promise.resolve()}, generation: 1, reduced: true,
        stopMedia() { mediaStopped = true; }, mountResult() { mounted = true; }, navigation() {}, focus() {}
    };
    await Controller.prototype.result.call(result, 1);
    assert.strictEqual(result.state.count, 1);
    assert.strictEqual(result.stage.innerHTML, 'chosen piece'); assert(mounted && mediaStopped && result.finishedWaiting);

    let failureReported;
    const failed = {finding() {}, finishFinding() { this.finishedWaiting = true; }, state: {count: 2, answers: {}, back() { this.step = 6; }}, http: {post: () => Promise.reject(new Error('offline'))}, element: {dataset: {url: '/result'}},
        counter: {to: () => Promise.resolve(), cancel() {}, set(value) { this.value = value; }}, generation: 1,
        render() { this.rendered = true; }, report(message) { failureReported = message; }};
    await Controller.prototype.result.call(failed, 1);
    assert.strictEqual(failed.state.step, 6); assert.strictEqual(failed.counter.value, 2);
    assert(failed.rendered && failed.finishedWaiting); assert(!failed.busy); assert(failureReported.includes('retry'));

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
    const reader = {state: {step: 1}, generation: 1, readingPair: () => [{title: 'A'}, {title: 'B'}],
        pdfTasks: new Set(), stage: {}, heading: () => '', pdfjs: null};
    const reading = pendingWindow.MatchTour.Controller.prototype.reading.call(reader);
    reader.generation++;
    pendingWindow.pdfjsLib = pdf; scripts[3].onload();
    await reading;
    assert.strictEqual(reader.pdfTasks.size, 0);

    // Every difficulty outcome and optional skip retains its intended level.
    for (const [answers, expected] of [[[false,false],'elementary'], [[false,true],'beginner'], [[true,false],'intermediate'], [[true,true],'advanced'], [[null,null],'beginner'], [[false,null],'beginner'], [[true,null],'intermediate']]) {
        assert.strictEqual(window.MatchTour.level(answers, 'beginner'), expected);
    }
    const skipped = new State(961);
    [1, null, null, null, null, null, 'open'].forEach(value => skipped.choose(value, pieces));
    assert.strictEqual(skipped.step, 7); assert.strictEqual(skipped.answers.estimatedLevel, 'beginner');
    assert.deepStrictEqual(Array.from(skipped.answers.winners), [null,null,null]);
    for (const difficulty of ['early beginner', 'late beginner', 'early intermediate', 'late intermediate']) {
        const catalog = pieces.map(piece => Object.assign({}, piece, {level: difficulty}));
        const skip = new State(961);
        [1, null, null].forEach(value => skip.choose(value, catalog));
        assert(Number.isFinite(skip.count), 'Existing split difficulty names keep valid countdown estimates');
        assert.strictEqual(skip.answers.estimatedLevel, difficulty);
    }
    const scores = {easy: 'easy', beginner: 'beginner', middle: 'middle', hard: 'hard'};
    const pairs = {data: {scores}, state: {step: 1, answers: {reading: []}}};
    assert.deepStrictEqual(Array.from(Controller.prototype.readingPair.call(pairs)), ['easy','middle']);
    pairs.state.step = 2; pairs.state.answers.reading = [false];
    assert.deepStrictEqual(Array.from(Controller.prototype.readingPair.call(pairs)), ['easy','beginner']);
    pairs.state.answers.reading = [true];
    assert.deepStrictEqual(Array.from(Controller.prototype.readingPair.call(pairs)), ['middle','hard']);

    // Reused card renderer safely escapes names and uses inline media only for reward.
    const card = {id:1,title:'<danger>',composer:'A & B',image:'/portrait',artwork:'/art',audio:'/audio',video:'/video',url:'/pieces/1'};
    const renderer = {pendingPiece: 1, waveform: Controller.prototype.waveform};
    const listening = Controller.prototype.cards.call(renderer, [card], 'listening');
    assert(listening.includes('&lt;danger&gt;')); assert(listening.includes('data-select-piece="1"'));
    assert(!listening.includes('<video'));
    const reward = Controller.prototype.cards.call(renderer, [card], 'reward');
    assert(reward.includes('<video data-result-media controls playsinline preload="none"'));
    assert(reward.includes('match-artwork')); assert(reward.includes('data-play="1"'));
    const recommendations = Controller.prototype.cards.call(renderer, [card], 'recommendation');
    assert(recommendations.includes('href="/pieces/1"')); assert(!recommendations.includes('<video'));

    // Closing the shell discards a pending load and restores the launcher's focus.
    const events = {}, documentEvents = {};
    const content = {innerHTML: ''};
    const shell = {dataset: {tourUrl: '/tour'}, querySelector: selector => selector === '[data-tour-content]' ? content : null, querySelectorAll: () => [], addEventListener(name, callback) { events[name] = callback; }};
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
