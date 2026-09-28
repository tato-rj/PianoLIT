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
    assert(a.innerHTML.includes('fa-play')); assert(b.innerHTML.includes('fa-pause'));
    previews.audio.currentTime = 10.5; previews.audio.events.timeupdate(); assert(previews.audio.paused);
    previews.audio.failure = true; await previews.play(pieces[0], a); assert.strictEqual(warnings, 1); assert.strictEqual(previews.button, null);

    // A result delivered after restart/back must never replace the current screen.
    let resolve;
    const stale = {state: {count: 47, answers: {}}, http: {post: () => new Promise(done => { resolve = done; })}, element: {dataset: {url: '/result'}}, counter: {to: () => Promise.resolve()}, generation: 1};
    const response = Controller.prototype.result.call(stale, 1);
    stale.generation = 2;
    resolve({data: '<article>obsolete</article>'}); await response;
    assert.strictEqual(stale.state.count, 47);
};
