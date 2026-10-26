const assert = require('assert');
const fs = require('fs');
const path = require('path');
const vm = require('vm');

module.exports = async function () {
    let focused;
    function node() {
        const classes = new Set();
        return {
            hidden: false, disabled: false, textContent: '', innerHTML: '', style: {}, dataset: {}, attributes: {}, events: {}, one: {}, many: {},
            classList: {add(...items) { items.forEach(x => classes.add(x)); }, remove(...items) { items.forEach(x => classes.delete(x)); }, contains(x) { return classes.has(x); }},
            addEventListener(type, fn) { this.events[type] = fn; },
            querySelector(selector) { return this.one[selector] || null; },
            querySelectorAll(selector) { return this.many[selector] || []; },
            setAttribute(key, value) { this.attributes[key] = value; },
            hasAttribute(key) { return Object.hasOwn(this.attributes, key); },
            focus() { focused = this; }, scrollIntoView(options) { this.scroll = options; },
            click() { if (!this.disabled) return this.events.click(); }
        };
    }
    const root = node(), requests = [];
    root.dataset.url = 'https://learn.example.test/quizzes/piano/feedback';
    const selectors = ['introduction', 'content', 'start', 'results', 'feedback', 'result-status', 'result-actions', 'retry', 'review', 'reset', 'progress-label'];
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
    const source = fs.readFileSync(path.join(__dirname, '../../resources/js/views/quizzes.js'), 'utf8');
    vm.runInNewContext(source, {document: {querySelector() { return root; }}, window: {
        matchMedia() { return {matches: true}; },
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
    assert.strictEqual(questions[0].one['[data-quiz-question-feedback]'].textContent, 'The correct answer is Choice 0.');
    assert.strictEqual(progress.attributes['aria-valuenow'], '1');
    assert.strictEqual(bar.style.width, '50%');
    choices(questions[0])[0].events.click();
    assert.strictEqual(requests.length, 0, 'A locked answer cannot submit or change the choice.');
    choices(questions[1])[1].click();
    assert.strictEqual(pauses, 2);
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
    requests[1].resolve({data: '<div data-quiz-score>1 out of 2</div>'});
    await flush();
    assert.strictEqual(elements.feedback.innerHTML, '<div data-quiz-score>1 out of 2</div>');
    assert(!elements['result-actions'].hidden);
    elements.review.click();
    assert.strictEqual(focused, questions[0].one.h2);
    elements.reset.click();
    assert(elements.results.hidden);
    assert(questions.every(question => choices(question).every(button => !button.disabled && button.attributes['aria-pressed'] === 'false')));
    assert.strictEqual(progress.attributes['aria-valuenow'], '0');
    assert.strictEqual(elements['progress-label'].textContent, '0 of 2 answered');
    assert.strictEqual(elements.feedback.innerHTML, '');

    choices(questions[0])[0].click();
    choices(questions[1])[1].click();
    elements.reset.click();
    requests[2].resolve({data: '<div data-quiz-score>Stale result</div>'});
    await flush();
    assert(elements.results.hidden && elements.feedback.innerHTML === '', 'An old attempt cannot overwrite a reset quiz.');
    vm.runInNewContext(source, {document: {querySelector() { return null; }}});
    console.log('Passed: quiz progress, locked answers, audio cleanup, pending guard, result retry, review, reset and stale responses.');
};

if (require.main === module) module.exports().catch(error => { console.error(error); process.exitCode = 1; });
