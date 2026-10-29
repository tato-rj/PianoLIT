const assert = require('assert');
const fs = require('fs');
const path = require('path');
const vm = require('vm');

module.exports = async function () {
    let focused;
    const animationCalls = [];
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
            focus() { focused = this; }, scrollIntoView(options) { this.scroll = options; },
            click() { if (!this.disabled) return this.events.click(); }
        };
    }
    const root = node(), requests = [];
    root.dataset.url = 'https://learn.example.test/quizzes/piano/feedback';
    const selectors = ['introduction', 'content', 'start', 'results', 'feedback', 'result-status', 'result-actions', 'retry', 'review', 'reset', 'next', 'streak', 'progress-label'];
    const elements = {};
    selectors.forEach(key => { elements[key] = root.one['[data-quiz-' + key + ']'] = node(); });
    const progress = root.one['[role="progressbar"]'] = node();
    const bar = progress.one['.progress-bar'] = node();
    elements.content.hidden = elements.results.hidden = true;
    let pauses = 0;
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
    const source = fs.readFileSync(path.join(__dirname, '../../resources/js/views/quizzes.js'), 'utf8');
    vm.runInNewContext(source, {document: {querySelector() { return root; }, createElement() { return node(); }, addEventListener(type, fn) { documentEvents[type] = fn; }}, window: {
        addEventListener(type, fn) { windowEvents[type] = fn; },
        matchMedia() { return motion; },
        axios: {get(url, options) { return new Promise((resolve, reject) => requests.push({url, options, resolve, reject})); }}
    }});
    const flush = () => new Promise(resolve => setImmediate(resolve));
    const choices = question => question.many['[data-quiz-answer]'];

    elements.start.click();
    assert(elements.introduction.hidden && !elements.content.hidden);
    assert.strictEqual(focused, questions[0].one.h2);
    assert.strictEqual(focused.scroll.behavior, 'auto', 'Respect reduced motion.');
    choices(questions[0])[1].click();
    assert(choices(questions[0]).every(button => button.disabled));
    assert(choices(questions[0])[1].classList.contains('is-incorrect'));
    assert(choices(questions[0])[0].classList.contains('is-correct'));
    assert.strictEqual(choices(questions[0])[1].attributes['aria-pressed'], 'true');
    assert(questions[0].one['[data-quiz-question-feedback]'].attributes['aria-label'].includes('Correct answer: Choice 0'));
    assert(questions[1].hidden, 'Only the current question is shown.');
    assert.strictEqual(focused, elements.next, 'Keyboard users can continue after a locked answer.');
    assert.strictEqual(progress.attributes['aria-valuenow'], '1');
    assert.strictEqual(bar.style.width, '50%');
    choices(questions[0])[0].events.click();
    assert.strictEqual(requests.length, 0, 'A locked answer cannot submit or change the choice.');
    elements.next.click();
    assert(questions[0].hidden && !questions[1].hidden);
    choices(questions[1])[1].click();
    assert.strictEqual(requests.length, 0, 'The final answer reaction gets its moment before the reveal.');
    assert.strictEqual(elements.next.textContent, 'The big reveal →');
    elements.next.click();
    assert(pauses >= 2);
    assert.strictEqual(audio.currentTime, 0);
    assert.strictEqual(requests.length, 1);
    assert.deepStrictEqual(Array.from(requests[0].options.params.answers), [1, 1]);
    assert.strictEqual(requests[0].url, root.dataset.url);
    assert.strictEqual(progress.attributes['aria-valuenow'], '2');
    assert.strictEqual(elements.results.attributes['aria-busy'], 'true');
    elements.retry.click();
    assert.strictEqual(requests.length, 1, 'Pending requests cannot be duplicated.');
    requests[0].reject(new Error('Network unavailable'));
    await flush();
    assert(!elements.retry.hidden);
    assert.strictEqual(elements['result-status'].attributes.role, 'alert');
    assert(elements['result-status'].textContent.includes('Your answers are still here'));
    assert.strictEqual(elements.results.attributes['aria-busy'], 'false');
    elements.retry.click();
    assert.deepStrictEqual(Array.from(requests[1].options.params.answers), [1, 1]);
    const gif = elements.feedback.one['[data-quiz-gif]'] = node();
    const gifToggle = elements.feedback.one['[data-quiz-gif-toggle]'] = node();
    gif.attributes.src = 'https://media.giphy.com/media/test/giphy_s.gif';
    gif.dataset.animatedSrc = 'https://media.giphy.com/media/test/giphy.gif';
    requests[1].resolve({data: '<div data-quiz-score>1 out of 2</div>'});
    await flush();
    assert.strictEqual(elements.feedback.innerHTML, '<div data-quiz-score>1 out of 2</div>');
    assert(!elements['result-actions'].hidden);
    assert.strictEqual(gif.src, gif.attributes.src, 'Reduced motion uses a still image.');
    assert.strictEqual(animationCalls.length, 0, 'Reduced motion creates no bursts or entrance animations.');
    gifToggle.click();
    assert.strictEqual(gif.src, gif.dataset.animatedSrc, 'GIFs can be explicitly played.');
    assert.strictEqual(gifToggle.textContent, 'Pause GIF');
    elements.review.click();
    assert.strictEqual(focused, questions[0].one.h2);
    assert(questions.every(question => !question.hidden), 'Review shows every answer.');
    assert.strictEqual(gif.src, gif.attributes.src, 'Review pauses the GIF.');
    elements.reset.click();
    assert(elements.results.hidden);
    assert(questions.every(question => choices(question).every(button => !button.disabled && button.attributes['aria-pressed'] === 'false')));
    assert.strictEqual(progress.attributes['aria-valuenow'], '0');
    assert.strictEqual(elements['progress-label'].textContent, '0 / 2');
    assert(elements.streak.hidden);
    assert.strictEqual(questions[0].dataset.quizOutcome, undefined);
    assert.strictEqual(elements.feedback.innerHTML, '');

    choices(questions[0])[0].click();
    elements.next.click();
    choices(questions[1])[1].click();
    assert(!elements.streak.hidden && elements.streak.textContent.includes('2 in a row'));
    elements.next.click();
    elements.reset.click();
    requests[2].resolve({data: '<div data-quiz-score>Stale result</div>'});
    await flush();
    assert(elements.results.hidden && elements.feedback.innerHTML === '', 'An old attempt cannot overwrite a reset quiz.');
    motion.matches = false;
    elements.reset.click();
    const oldReaction = questions[0].one['[data-quiz-question-feedback]'].textContent;
    choices(questions[0])[1].click();
    assert(animationCalls.length >= 14, 'Normal motion animates the question, chosen answer, reaction and particles.');
    const reaction = questions[0].one['[data-quiz-question-feedback]'].textContent;
    assert.notStrictEqual(reaction, oldReaction);
    motion.matches = true;
    motion.change();
    assert(animationCalls.every(call => call.animation.cancelled), 'Changing reduced motion cancels active effects.');
    elements.reset.click();
    choices(questions[0])[1].click();
    assert.notStrictEqual(questions[0].one['[data-quiz-question-feedback]'].textContent, reaction, 'Reaction jokes do not immediately repeat.');
    elements.next.click();
    choices(questions[1])[0].click();
    assert(elements.streak.hidden, 'A mistake clears the streak.');
    motion.matches = false;
    elements.next.click();
    requests[3].resolve({data: '<div data-quiz-score>0 out of 2</div>'});
    await flush();
    assert.strictEqual(gif.src, gif.dataset.animatedSrc, 'Normal motion starts the reaction GIF.');
    assert(animationCalls.length >= 47, 'The finale has its own entrance and larger burst.');
    gifToggle.click();
    assert.strictEqual(gif.src, gif.attributes.src);
    gifToggle.click();
    windowEvents.pagehide();
    assert.strictEqual(gif.src, gif.attributes.src, 'Leaving the page stops animation and audio.');
    gif.events.error();
    assert(gif.hidden && gifToggle.hidden, 'A blocked or missing GIF leaves the score, joke and fallback visible.');
    vm.runInNewContext(source, {document: {querySelector() { return null; }}});
    console.log('Passed: question pacing, streaks, motion cleanup, GIF controls/fallback, quiz progress, locked answers, audio cleanup, pending guard, result retry, review, reset and stale responses.');
};

if (require.main === module) module.exports().catch(error => { console.error(error); process.exitCode = 1; });
