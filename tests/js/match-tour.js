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
    // The new first question anchors playing ability independently of musical taste.
    const ranges = ['elementary', 'beginner', 'intermediate', 'advanced'];
    const levelPieces = ranges.map((name, i) => ({id: 101 + i, level: name}));
    for (const [anchorIndex, anchor] of ranges.entries()) {
        for (const [readingIndex, reading] of [[false,false], [false,true], [true,false], [true,true]].entries()) {
            const state = new State(961, levelPieces);
            state.choose(levelPieces[anchorIndex].id, pieces);
            assert.strictEqual(state.step, 1); assert.strictEqual(state.answers.playingLevel, anchor);
            assert(state.count < 961 && state.count > 1);
            const afterLevel = state.count;
            state.choose(1, pieces); assert(state.count < afterLevel);
            state.choose(reading[0], pieces); state.choose(reading[1], pieces);
            assert.strictEqual(state.answers.estimatedLevel, ranges[Math.round((2 * anchorIndex + readingIndex) / 3)]);
            state.choose(5, pieces); state.choose(7, pieces); state.choose(9, pieces); state.choose('calm', pieces);
            assert.strictEqual(state.step, 8);
            for (let i = 0; i < 8; i++) state.back();
            assert.strictEqual(state.step, 0); assert.strictEqual(state.count, 961);
            assert.strictEqual(state.answers.levelPiece, null); assert.strictEqual(state.answers.playingLevel, null);
        }
        assert.strictEqual(window.MatchTour.level([null,null], 'beginner', anchor), anchor);
    }
    const newSkipped = new State(961, levelPieces);
    [104, 1, null, null, null, null, null, 'open'].forEach(value => newSkipped.choose(value, pieces));
    assert.strictEqual(newSkipped.step, 8); assert.strictEqual(newSkipped.answers.estimatedLevel, 'advanced');

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
    window.document = {createElement: () => ({
        attributes: {},
        setAttribute(name, value) { this.attributes[name] = value; },
        removeAttribute(name) { delete this.attributes[name]; },
        getAttribute(name) { return this.attributes[name] || null; }
    })};
    window.Audio = class {
        constructor() { this.events = {}; this.nodes = []; }
        appendChild(node) { this.nodes.push(node); }
        removeAttribute(name) { delete this[name]; }
        load() { this.currentTime = 0; this.loads = (this.loads || 0) + 1; }
        addEventListener(name, callback) { this.events[name] = callback; }
        pause() { this.paused = true; }
        play() { this.paused = false; return this.failure ? Promise.reject(new Error()) : Promise.resolve(); }
    };
    const previews = new Previews(60, () => warnings++);
    const button = () => ({innerHTML: '', setAttribute() {}});
    const a = button(), b = button();
    for (const [url, type] of [['/legacy.MPGA?version=1', 'audio/mpeg'], ['/recording.mp3#fragment', 'audio/mpeg'], ['/recording.mp4', 'video/mp4'], ['/recording.m4a', 'audio/mp4'], ['/recording.wav', null]]) {
        await previews.play({audio: url}, a);
        assert.strictEqual(previews.audio.src, undefined, 'Direct src must not override the typed source');
        const source = previews.audio.nodes[0];
        assert(source, 'Tour previews need a typed source for legacy Safari recordings');
        assert.strictEqual(source.getAttribute('src'), url);
        assert.strictEqual(source.getAttribute('type'), type);
        assert.strictEqual(previews.audio.nodes.length, 1, 'Switching formats reuses one source');
        previews.stop();
    }
    assert.strictEqual(previews.audio.loads, 5, 'Source changes explicitly restart media selection');
    await previews.play(pieces[0], a);
    previews.audio.currentTime = 12;
    const loads = previews.audio.loads;
    await previews.play(pieces[0], a);
    assert(previews.audio.paused, 'The active button pauses its recording');
    await previews.play(pieces[0], a);
    assert(!previews.audio.paused);
    assert.strictEqual(previews.audio.currentTime, 12, 'Resume keeps the listening position');
    assert.strictEqual(previews.audio.loads, loads, 'Resume does not reload the typed source');
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

    // Every shared audio preview starts around its midpoint after metadata arrives.
    const randomWindow = Object.assign({}, window);
    const middleMath = Object.assign(Object.create(Math), {random: () => 0.5});
    vm.runInNewContext(fs.readFileSync(path.join(__dirname, '../../resources/js/views/match-tour.js'), 'utf8'), {window: randomWindow, Math: middleMath});
    const middle = new randomWindow.MatchTour.Previews(60, () => { throw Error('Unexpected preview error'); });
    const c = button(), d = button();
    await middle.play(pieces[0], c);
    assert.strictEqual(middle.audio.currentTime, 0, 'Seeking waits for usable metadata');
    middle.audio.duration = 300; middle.audio.readyState = 1; middle.audio.events.loadedmetadata();
    assert.strictEqual(middle.audio.currentTime, 150);
    middle.audio.currentTime = 165; middle.audio.events.durationchange();
    assert.strictEqual(middle.audio.currentTime, 165, 'Duration updates cannot select a new start during playback');
    await middle.play(pieces[0], c); await middle.play(pieces[0], c);
    assert.strictEqual(middle.audio.currentTime, 165, 'Pause/resume preserves the random excerpt and position');
    middle.audio.currentTime = 209.9; middle.audio.events.timeupdate(); assert(!middle.audio.paused);
    middle.audio.currentTime = 210; middle.audio.events.seeking(); assert(middle.audio.paused, 'The limit is sixty seconds after the random start');

    const inlineAudio = new randomWindow.Audio(); inlineAudio.tagName = 'AUDIO'; inlineAudio.readyState = 1; inlineAudio.duration = 300;
    await middle.play(pieces[0], c, inlineAudio);
    assert.strictEqual(inlineAudio.currentTime, 150, 'Audio-only result cards use the same random start');
    inlineAudio.currentTime = 210; inlineAudio.events.timeupdate(); assert(inlineAudio.paused);
    const fullVideo = new randomWindow.Audio(); fullVideo.tagName = 'VIDEO'; fullVideo.readyState = 1; fullVideo.duration = 300;
    await middle.play(pieces[0], d, fullVideo);
    fullVideo.events.loadedmetadata(); assert.strictEqual(fullVideo.currentTime, 0, 'Video still starts at the beginning');
    fullVideo.currentTime = 270; fullVideo.events.timeupdate(); assert(!fullVideo.paused, 'Video still plays to completion');

    middle.audio.readyState = 0; middle.audio.duration = NaN;
    await middle.play(pieces[0], c); middle.stop();
    middle.audio.readyState = 1; middle.audio.duration = 300; middle.audio.currentTime = 8; middle.audio.events.loadedmetadata();
    assert.strictEqual(middle.audio.currentTime, 8, 'Late metadata after Back/close cannot seek abandoned media');
    middle.audio.readyState = 0; middle.audio.duration = NaN;
    await middle.play(pieces[1], d);
    middle.audio.readyState = 1; middle.audio.duration = 120; middle.audio.events.loadedmetadata();
    assert.strictEqual(middle.audio.currentTime, 54, 'A shorter recording keeps a full minute where a middle start allows it');
    middle.stop(); middle.audio.readyState = 0; middle.audio.duration = Infinity;
    await middle.play(pieces[0], c); middle.audio.events.durationchange(); assert.strictEqual(middle.audio.currentTime, 0);
    middle.audio.readyState = 1; middle.audio.duration = 30; middle.audio.events.durationchange();
    assert.strictEqual(middle.audio.currentTime, 15, 'Short recordings start around their middle and can end naturally');
    middle.stop();
    for (const random of [0, 0.9999]) {
        middleMath.random = () => random; middle.audio.readyState = 0; middle.audio.duration = NaN;
        await middle.play(pieces[0], c);
        middle.audio.readyState = 1; middle.audio.duration = 300; middle.audio.events.loadedmetadata();
        assert(Math.abs(middle.audio.currentTime - (120 + random * 60)) < 0.000001, 'Fresh playback rerolls the excerpt across the middle range');
        middle.stop();
    }
    middle.audio.readyState = 0; middle.audio.duration = NaN;
    let rejectLoading;
    middle.audio.play = function () { this.paused = false; return new Promise((resolve, reject) => { rejectLoading = reject; }); };
    const loadingPreview = middle.play(pieces[0], c);
    await middle.play(pieces[0], c);
    rejectLoading(Error('Playback was paused before metadata')); await loadingPreview;
    assert(middle.audio.paused); assert.strictEqual(middle.button, c, 'Pausing during loading preserves selection without reporting a false playback error');
    middle.stop();

    window.setTimeout = callback => callback();
    // A result delivered after restart/back must never replace the current screen.
    let resolve;
    const stale = {data: {draw: 'current-draw'}, finding() { this.waiting = true; }, state: {count: 47, answers: {}}, http: {post: (url, answers) => { assert.strictEqual(answers.draw, 'current-draw'); return new Promise(done => { resolve = done; }); }}, element: {dataset: {url: '/result'}}, counter: {to: () => Promise.resolve()}, generation: 1};
    const response = Controller.prototype.result.call(stale, 1);
    assert(stale.waiting, 'Completion feedback appears before the response arrives');
    stale.generation = 2;
    resolve({data: '<article>obsolete</article>'}); await response;
    assert.strictEqual(stale.state.count, 47);

    // Successful results reuse the stage and do not require a second modal/player.
    window.bootstrap = {Modal: class {constructor() { throw Error('No second modal'); }}};
    let mounted = false, mediaStopped = false;
    const result = {
        data: {draw: 'current-draw'},
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

    // Fast responses wait for the countdown; Back during that wait discards the reveal.
    let finishCountdown, resolveMatch;
    const countTargets = [];
    const waitingResult = Object.assign({}, result, {
        reduced: false, generation: 1, state: {count: 15, answers: {}},
        stage: {innerHTML: 'waiting'},
        http: {post: () => new Promise(done => { resolveMatch = done; })},
        counter: {to(target, duration, linear) {
            countTargets.push([target, duration, linear]);
            return new Promise(done => { finishCountdown = done; });
        }}
    });
    const pendingReveal = Controller.prototype.result.call(waitingResult, 1);
    assert.deepStrictEqual(countTargets, [[2, 2200, true]], 'Countdown begins before the result arrives');
    resolveMatch({data: 'premature match'});
    await Promise.resolve(); await Promise.resolve();
    assert.strictEqual(waitingResult.stage.innerHTML, 'waiting', 'Fast responses cannot bypass the countdown');
    waitingResult.generation++;
    finishCountdown(); await pendingReveal;
    assert.strictEqual(waitingResult.stage.innerHTML, 'waiting', 'Back during the reveal wait keeps the current screen');
    assert.strictEqual(waitingResult.state.count, 15);

    let failureReported;
    const failed = {data: {draw: 'current-draw'}, finding() {}, finishFinding() { this.finishedWaiting = true; }, state: {count: 2, answers: {}, back() { this.step = 6; }}, http: {post: () => Promise.reject(new Error('offline'))}, element: {dataset: {url: '/result'}},
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
    const reader = {state: {step: 1}, generation: 1, readingScore: () => ({title: 'Single score'}),
        pdfTasks: new Set(), stage: {}, heading: () => '', pdfjs: null};
    const reading = pendingWindow.MatchTour.Controller.prototype.reading.call(reader);
    reader.generation++;
    pendingWindow.pdfjsLib = pdf; scripts[3].onload();
    await reading;
    assert.strictEqual(reader.pdfTasks.size, 0);

    // A title-heavy first page must yield its middle music, not the header.
    const savedCreateElement = window.document.createElement;
    let pageLoads = 0;
    for (const [width, height] of [[700,1000], [1000,300]]) {
        const canvases = [];
        window.document.createElement = () => {
            const canvas = {draws: [], setAttribute() {}, getContext() {
                return {drawImage: (...args) => this.draws.push(args)};
            }};
            canvases.push(canvas); return canvas;
        };
        const slot = {setAttribute() {}, appendChild(canvas) { this.canvas = canvas; }};
        const scoreAnswers = [{disabled: true}, {disabled: true}];
        const scorer = {generation: 1, pdfTasks: new Set(), scoreExcerpts: new Map(), pdfjs: {
            getDocument() { pageLoads++; return {destroy() {}, promise: Promise.resolve({getPage: () => Promise.resolve({
                getViewport: () => ({width, height}), render: () => ({promise: Promise.resolve()})
            })})}; }
        }, stage: {querySelector: () => slot, querySelectorAll: selector => selector === '[data-choice]' ? scoreAnswers : []}, scoreFailure() { throw Error('Unexpected PDF failure'); }};
        await Controller.prototype.score.call(scorer, {url: 'fixture.pdf', title: 'Title above notes', composer: 'Composer'}, 0, 1);
        const [source, x, top, cropWidth, cropHeight] = canvases[1].draws[0];
        assert.strictEqual(source, canvases[0]); assert.strictEqual(x, 0); assert.strictEqual(cropWidth, width);
        assert(top >= 0 && top + cropHeight <= height, 'Portrait and landscape crops stay within the page');
        assert(Math.abs(top + cropHeight / 2 - height / 2) <= 1, 'The excerpt includes the middle of the page');
        if (height > width) assert(top > height * 0.25, 'Title/header area is excluded');
        assert.strictEqual(slot.canvas.width, cropWidth); assert.strictEqual(slot.canvas.height, cropHeight);
        assert(scoreAnswers.every(button => !button.disabled), 'One rendered excerpt enables both difficulty answers');
        const loaded = pageLoads;
        await Controller.prototype.score.call(scorer, {url: 'fixture.pdf', title: 'Title above notes', composer: 'Composer'}, 0, 1);
        assert.strictEqual(pageLoads, loaded, 'Revisiting the excerpt reuses its cached crop');
    }
    window.document.createElement = savedCreateElement;

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
    const readerState = {data: {scores}, state: {step: 1, answers: {reading: []}}};
    assert.strictEqual(Controller.prototype.readingScore.call(readerState), 'middle');
    readerState.state.step = 2; readerState.state.answers.reading = [false];
    assert.strictEqual(Controller.prototype.readingScore.call(readerState), 'beginner');
    readerState.state.answers.reading = [true];
    assert.strictEqual(Controller.prototype.readingScore.call(readerState), 'hard');
    readerState.state.answers.reading = [false]; readerState.data.scores.beginner = null;
    assert.strictEqual(Controller.prototype.readingScore.call(readerState), 'easy', 'Missing beginner excerpt still gives a genuinely easier follow-up');

    // Reused card renderer safely escapes names and uses inline media only for reward.
    const card = {id:1,title:'<danger>',composer:'A & B',image:'/portrait',artwork:'/art',audio:'/audio',video:'/video',url:'/pieces/1'};
    const renderer = {pendingPiece: 1, waveform: Controller.prototype.waveform};
    const listening = Controller.prototype.cards.call(renderer, [card], 'listening');
    assert(listening.includes('&lt;danger&gt;')); assert(listening.includes('data-select-piece="1"'));
    assert(!listening.includes('<video'));
    const reward = Controller.prototype.cards.call(renderer, [card], 'reward');
    assert(reward.includes('<video data-result-media controls playsinline preload="none"'));
    assert(reward.includes('match-artwork')); assert(reward.includes('data-play="1"'));
    const audioReward = Controller.prototype.cards.call(renderer, [Object.assign({}, card, {video: null, audio: '/legacy.MPGA?name=a&version=1'})], 'reward');
    assert(audioReward.includes('<audio data-result-media preload="none"><source src="/legacy.MPGA?name=a&amp;version=1" type="audio/mpeg"></audio>'), 'Audio-only rewards also type legacy recordings and escape URLs');
    const recommendations = Controller.prototype.cards.call(renderer, [card], 'recommendation');
    assert(recommendations.includes('href="/pieces/1"')); assert(!recommendations.includes('<video'));

    renderer.pendingLevel = 1;
    renderer.data = {levels: {intermediate: {label: 'Intermediate', hint: 'Independent hands & pedal'}}};
    const levelCard = Controller.prototype.cards.call(renderer, [Object.assign({}, card, {level: 'early intermediate'})], 'level');
    assert(levelCard.includes('match-card--level match-card--listening selected'), 'Level cards reuse the existing listening card');
    assert(levelCard.includes('data-select-level="1"'));
    assert(levelCard.includes('aria-label="Choose Intermediate level: &lt;danger&gt; by A &amp; B"'), 'The level is part of the accessible selection name');
    assert(levelCard.includes('Intermediate')); assert(levelCard.includes('Independent hands &amp; pedal'));
    assert(levelCard.indexOf('</button>') < levelCard.indexOf('data-play="1"'), 'Playback remains outside selection');
    assert.strictEqual((levelCard.match(/class="filled"/g) || []).length, 3);

    let freshDraws = 0;
    await Controller.prototype.click.call({onRestart: () => { freshDraws++; }}, {
        target: {closest: () => ({disabled: false, hasAttribute: attribute => attribute === 'data-restart'})}
    });
    assert.strictEqual(freshDraws, 1, 'Start over requests a fresh draw instead of resetting the old choices');

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
