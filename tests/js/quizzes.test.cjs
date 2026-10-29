const assert = require('assert');
const fs = require('fs');
const path = require('path');
const vm = require('vm');

module.exports = async function () {
    let focused;
    const animationCalls = [], scrollCalls = [];
    function node() {
        const classes = new Set();
        return {
            hidden: false, disabled: false, textContent: '', innerHTML: '', style: {}, dataset: {}, attributes: {}, events: {}, one: {}, many: {},
            classList: {add(...items) { items.forEach(x => classes.add(x)); }, remove(...items) { items.forEach(x => classes.delete(x)); }, contains(x) { return classes.has(x); }},
            addEventListener(type, fn) { this.events[type] = fn; },
            querySelector(selector) { return this.one[selector] || null; },
            querySelectorAll(selector) { return this.many[selector] || []; },
            setAttribute(key, value) { this.attributes[key] = value; },
            getAttribute(key) { return this.attributes[key]; }, removeAttribute(key) { delete this.attributes[key]; },
            hasAttribute(key) { return Object.hasOwn(this.attributes, key); },
            appendChild(child) { child.parent = this; }, remove() { this.removed = true; },
            animate(frames, options) {
                let finish;
                const animation = {finished: new Promise(resolve => { finish = resolve; }), cancel() { this.cancelled = true; finish(); }};
                animationCalls.push({element: this, frames, options, animation});
                return animation;
            },
            focus(options) { focused = this; this.focusOptions = options; }, scrollIntoView(options) { this.scroll = options; scrollCalls.push(this); },
            click(event = {detail: 1}) { if (!this.disabled) return this.events.click(event); }
        };
    }
    const root = node(), requests = [];
    root.dataset.url = 'https://learn.example.test/quizzes/piano/feedback';
    const selectors = ['introduction', 'content', 'start', 'results', 'feedback', 'result-status', 'result-actions', 'retry', 'review', 'reset', 'next', 'streak', 'progress-label', 'modal', 'reveal', 'show-result'];
    const elements = {};
    selectors.forEach(key => { elements[key] = root.one['[data-quiz-' + key + ']'] = node(); });
    const progress = root.one['[role="progressbar"]'] = node();
    const bar = progress.one['.progress-bar'] = node();
    elements.content.hidden = elements.results.hidden = elements.reveal.hidden = elements['show-result'].hidden = true;
    let pauses = 0, shows = 0, opening = false;
    const audio = {pause() { pauses++; }, currentTime: 12};
    root.many.audio = [audio];
    const questions = [0, 1].map(correct => {
        const question = node();
        question.one.h2 = node();
        question.one['[data-quiz-question-feedback]'] = node();
        question.many['[data-quiz-answer]'] = [0, 1, 2].map(index => {
            const button = node();
            button.dataset.quizAnswer = String(index);
            button.one['[data-quiz-answer-outcome]'] = node();
            button.one['.quiz-answer-copy'] = node();
            button.one['.quiz-answer-copy'].textContent = 'Choice ' + index;
            if (correct === index) button.attributes.correct = '';
            return button;
        });
        return question;
    });
    root.many['[data-quiz-question]'] = questions;
    const documentEvents = {}, windowEvents = {};
    const motion = {matches: true, addEventListener(type, fn) { this.change = fn; }};
    const doc = {hidden: false, querySelector() { return root; }, createElement() { return node(); }, addEventListener(type, fn) { documentEvents[type] = fn; }};
    const dialog = {
        show() { shows++; elements.modal.classList.add('show'); },
        hide() {
            if (opening || !elements.modal.classList.contains('show')) return;
            elements.modal.events['hide.bs.modal']();
            elements.modal.classList.remove('show');
            elements.modal.events['hidden.bs.modal']();
        },
        handleUpdate() {}
    };
    let now = 0, timerId = 0;
    const timers = new Map();
    function setTimeout(fn, delay) { const id = ++timerId; timers.set(id, {fn, at: now + delay}); return id; }
    function clearTimeout(id) { timers.delete(id); }
    const flush = () => new Promise(resolve => setImmediate(resolve));
    async function tick(ms) {
        const end = now + ms;
        while (true) {
            const due = [...timers].filter(([,timer]) => timer.at <= end).sort((a,b) => a[1].at - b[1].at)[0];
            if (!due) break;
            now = due[1].at; timers.delete(due[0]); due[1].fn(); await flush();
        }
        now = end; await flush();
    }
    const source = fs.readFileSync(path.join(__dirname, '../../resources/js/views/quizzes.js'), 'utf8');
    const navigator = {};
    let shareInitializations = 0;
    vm.runInNewContext(source, {document: doc, window: {
        addEventListener(type, fn) { windowEvents[type] = fn; },
        matchMedia() { return motion; }, setTimeout, clearTimeout, navigator,
        PianoLITLinkShare: {init() { shareInitializations++; }},
        bootstrap: {Modal: {getOrCreateInstance() { return dialog; }}},
        axios: {get(url, options) { return new Promise((resolve, reject) => requests.push({url, options, resolve, reject})); }}
    }});
    const choices = question => question.many['[data-quiz-answer]'];
    const result = '<div data-quiz-score>1 out of 2</div>';
    function answerRound() { choices(questions[0])[0].click(); elements.next.click(); choices(questions[1])[1].click(); }

    elements.start.click();
    assert(elements.introduction.hidden && !elements.content.hidden);
    assert.strictEqual(focused, questions[0].one.h2);
    assert.strictEqual(focused.focusOptions.preventScroll, true);
    choices(questions[0])[1].click();
    assert(choices(questions[0]).every(button => button.disabled));
    assert(choices(questions[0])[1].classList.contains('is-incorrect'));
    assert(choices(questions[0])[0].classList.contains('is-correct'));
    assert(questions[0].one['[data-quiz-question-feedback]'].attributes['aria-label'].includes('Correct answer: Choice 0'));
    assert.notStrictEqual(focused, elements.next, 'Pointer answers do not transfer focus.');
    assert.strictEqual(bar.style.width, '50%');
    choices(questions[0])[0].events.click();
    assert.strictEqual(requests.length, 0, 'Locked answers cannot submit.');
    elements.next.click();
    assert(questions[0].hidden && !questions[1].hidden);
    choices(questions[1])[1].click({detail: 0});
    assert(elements.next.hidden, 'The final answer has no extra results button.');
    await tick(349);
    assert.strictEqual(requests.length, 0, 'Let the final reaction land first.');
    await tick(1);
    assert.strictEqual(requests.length, 1, 'The result request starts automatically.');
    assert.strictEqual(shows, 1);
    assert(!elements.reveal.hidden && !questions[1].hidden, 'The overlay opens over the unchanged question.');
    assert.strictEqual(elements.results.attributes['aria-busy'], 'true');
    assert.strictEqual(scrollCalls.length, 0, 'Play and automatic reveal do not scroll the page.');
    assert.deepStrictEqual(Array.from(requests[0].options.params.answers), [1,1]);
    assert.strictEqual(requests[0].options.timeout, 15000, 'Slow requests have a bounded failure path.');
    assert.strictEqual(audio.currentTime, 0); assert(pauses > 0);
    elements.retry.click();
    assert.strictEqual(requests.length, 1, 'Pending requests cannot be duplicated.');
    requests[0].reject(new Error('Offline')); await flush();
    assert(elements.reveal.hidden && !elements.retry.hidden);
    assert.strictEqual(elements['result-status'].attributes.role, 'alert');
    elements.retry.click();
    const gif = elements.feedback.one['[data-quiz-gif]'] = node();
    const sharePanel = elements.feedback.one['[data-quiz-share-panel]'] = node();
    const shareOpen = elements.feedback.one['[data-quiz-share-open]'] = node();
    const score = elements.feedback.one['[data-quiz-score]'] = node();
    const comparisonBars = elements.feedback.many['[data-quiz-compare-fill]'] = [node(), node()];
    const medal = elements.feedback.one['[data-quiz-award]'] = node();
    const shareBack = sharePanel.one['[data-quiz-share-back]'] = node();
    const shareNative = sharePanel.one['[data-quiz-share-native]'] = node();
    const shareError = sharePanel.one['[data-share-error]'] = node();
    const shareErrorMessage = sharePanel.one['[data-share-error-message]'] = node();
    sharePanel.one.h2 = node(); sharePanel.one.img = node();
    sharePanel.dataset = {shareTitle: 'I scored 1/2!', shareUrl: 'https://learn.example.test/result?signed=score'};
    sharePanel.hidden = shareNative.hidden = true;
    gif.attributes.src = 'https://media.giphy.com/media/test/giphy_s.gif';
    gif.dataset.animatedSrc = 'https://media.giphy.com/media/test/giphy.gif';
    requests[1].resolve({data: result}); await flush();
    assert.strictEqual(elements.feedback.innerHTML, result, 'Retry has no artificial suspense delay.');
    assert.strictEqual(gif.src, gif.attributes.src, 'Reduced motion uses a still GIF.');
    assert.strictEqual(animationCalls.length, 0);
    assert.strictEqual(shareInitializations, 1);
    shareOpen.click();
    assert(!sharePanel.hidden && score.hidden && elements['result-actions'].hidden);
    assert.strictEqual(focused, sharePanel.one.h2);
    assert(shareNative.hidden, 'Unsupported browsers retain social links, download and copy.');
    shareBack.click();
    assert(sharePanel.hidden && !score.hidden && !elements['result-actions'].hidden);
    assert.strictEqual(focused, shareOpen);
    assert.strictEqual(requests.length, 2, 'Opening and closing sharing never re-evaluates the quiz.');
    elements.review.click();
    assert(!elements.modal.classList.contains('show'));
    assert(questions.every(question => !question.hidden));
    assert.strictEqual(focused.scroll.behavior, 'auto', 'Explicit review respects reduced motion.');
    assert.strictEqual(gif.src, gif.attributes.src);
    elements['show-result'].click();
    assert.strictEqual(requests.length, 2, 'Reopening an existing result never submits another score.');
    opening = true;
    elements.reset.click();
    assert(!elements.results.hidden, 'Replay waits for an opening modal to finish.');
    opening = false; elements.modal.events['shown.bs.modal']();
    assert(elements.results.hidden && elements['show-result'].hidden);
    assert.strictEqual(progress.attributes['aria-valuenow'], '0');
    assert(questions.every(question => choices(question).every(button => !button.disabled)));

    motion.matches = false;
    let nativePayload;
    navigator.share = async payload => { nativePayload = payload; };
    choices(questions[0])[0].click({detail: 0});
    assert.strictEqual(focused, elements.next);
    assert.strictEqual(focused.focusOptions.preventScroll, true);
    elements.next.click(); choices(questions[1])[1].click();
    assert(!elements.streak.hidden && elements.streak.textContent.includes('2 in a row'));
    await tick(649); assert.strictEqual(requests.length, 2);
    await tick(1); assert.strictEqual(requests.length, 3);
    requests[2].resolve({data: result}); await flush();
    assert.strictEqual(elements.feedback.innerHTML, '', 'A fast response does not skip the spinning reveal.');
    await tick(1099); assert.strictEqual(elements.feedback.innerHTML, '');
    await tick(1);
    assert.strictEqual(elements.feedback.innerHTML, result);
    assert(elements.reveal.hidden && !elements['result-actions'].hidden);
    assert.strictEqual(gif.src, gif.dataset.animatedSrc);
    assert(animationCalls.length >= 32, 'The finale keeps its animated burst.');
    comparisonBars.forEach(bar => {
        const fill = animationCalls.find(call => call.element === bar);
        assert(fill && fill.frames[0].transform === 'scaleX(0)' && fill.frames[1].transform === 'scaleX(1)', 'Both comparison bars animate to their server-rendered widths.');
        assert.strictEqual(fill.options.duration, 850);
    });
    assert(animationCalls.some(call => call.element === medal), 'The result badge gets a short award animation.');
    shareOpen.click();
    assert(animationCalls.filter(call => comparisonBars.includes(call.element) || call.element === medal).every(call => call.animation.cancelled), 'Sharing cancels comparison effects.');
    await shareNative.click();
    assert.strictEqual(nativePayload.url, sharePanel.dataset.shareUrl);
    assert.strictEqual(nativePayload.title, sharePanel.dataset.shareTitle);
    navigator.share = async () => { throw {name: 'AbortError'}; };
    await shareNative.click(); assert(shareError.hidden, 'Cancelling native sharing stays quiet.');
    navigator.share = async () => { throw {name: 'NotAllowedError'}; };
    await shareNative.click();
    assert(!shareError.hidden && shareErrorMessage.textContent.includes('copy your link'));
    assert(!shareNative.disabled);
    shareBack.click();
    dialog.hide();
    assert.strictEqual(gif.src, gif.attributes.src);
    assert.strictEqual(focused, elements['show-result']);

    elements.reset.click(); answerRound(); await tick(650);
    const hiddenShows = shows;
    dialog.hide();
    requests[3].resolve({data: result}); await flush();
    assert.strictEqual(shows, hiddenShows, 'A late result cannot reopen a dismissed overlay.');
    assert.strictEqual(elements.feedback.innerHTML, result, 'The result remains available to reopen.');
    assert.strictEqual(gif.src, gif.attributes.src, 'Dismissed results do not start a hidden GIF.');

    elements.reset.click(); answerRound(); elements.reset.click(); await tick(2000);
    assert.strictEqual(requests.length, 4, 'Replay cancels the scheduled automatic reveal.');
    answerRound(); await tick(650); elements.reset.click();
    requests[4].resolve({data: '<div data-quiz-score>Stale</div>'}); await flush();
    assert(elements.results.hidden && elements.feedback.innerHTML === '', 'An earlier request cannot overwrite a new attempt.');

    answerRound(); doc.hidden = true; documentEvents.visibilitychange(); await tick(2000);
    assert.strictEqual(requests.length, 5, 'No automatic overlay opens in a hidden tab.');
    doc.hidden = false; documentEvents.visibilitychange(); await tick(650);
    assert.strictEqual(requests.length, 6, 'Returning resumes the one pending reveal.');
    requests[5].resolve({data: result}); await flush();
    motion.matches = true; motion.change(); await flush();
    assert.strictEqual(elements.feedback.innerHTML, result, 'Reduced motion skips remaining artificial suspense.');
    assert(animationCalls.every(call => call.animation.cancelled));
    gif.events.error(); assert(gif.hidden);
    windowEvents.pagehide();
    assert(!elements.modal.classList.contains('show'));
    assert.strictEqual(timers.size, 0, 'Leaving the page clears reveal timers.');
    vm.runInNewContext(source, {document: {querySelector() { return null; }}});
    console.log('Passed: automatic delayed overlay, suspense timing, reduced motion, stationary play, keyboard focus, score/retry guards, dismissal/reopen, reset/stale/hidden-page cleanup and GIF fallback.');
};

if (require.main === module) module.exports().catch(error => { console.error(error); process.exitCode = 1; });
