const assert = require('assert');
const initialize = require('../../resources/js/views/explore-moods');

module.exports = function () {
    function fixture(count) {
        const choices = Array.from({length: count}, () => {
            const summary = {focused: false, focus() { this.focused = true; }};
            return {hidden: false, summary, querySelector() { return summary; }};
        });
        const more = {hidden: true, events: {}, addEventListener(event, handler) { this.events[event] = handler; }};
        const list = {querySelectorAll() { return choices; }};
        initialize({querySelector(selector) { return selector === '[data-explore-mood-choices]' ? list : more; }});
        return {choices, more, visible: () => choices.filter(choice => !choice.hidden).length};
    }

    [0, 1, 5].forEach(count => {
        const page = fixture(count);
        assert.strictEqual(page.visible(), count);
        assert.strictEqual(page.more.hidden, true, 'No button when all matching moods fit');
    });
    [6, 10, 13].forEach(count => {
        const page = fixture(count);
        assert.strictEqual(page.visible(), 5, 'Initially show five moods');
        assert.strictEqual(page.more.hidden, false);
        for (let shown = 5; shown < count; shown += 5) {
            page.more.events.click();
            assert.strictEqual(page.visible(), Math.min(shown + 5, count), 'Each click adds up to five moods without removing earlier ones');
            assert.strictEqual(page.choices[shown].summary.focused, true, 'Focus moves to the newly revealed batch');
        }
        assert.strictEqual(page.more.hidden, true, 'The button disappears after the final batch');
    });
    initialize({querySelector() { return null; }});
    console.log('Passed: Explore mood batches, final partial batch, focus, short lists and unrelated guides.');
};
