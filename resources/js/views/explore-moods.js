(function (root, factory) {
    if (typeof module === 'object' && module.exports) module.exports = factory;
    else factory(root.document);
}(typeof window !== 'undefined' ? window : this, function (doc) {
    'use strict';
    var list = doc.querySelector('[data-explore-mood-choices]');
    var more = doc.querySelector('[data-explore-moods-more]');
    if (!list || !more) return;
    var choices = Array.prototype.slice.call(list.querySelectorAll('.explore-mood'));
    var visible = 5;

    function update() {
        choices.forEach(function (choice, index) { choice.hidden = index >= visible; });
        more.hidden = visible >= choices.length;
    }

    more.addEventListener('click', function () {
        var firstNew = choices[visible];
        visible = Math.min(visible + 5, choices.length);
        update();
        // Keep keyboard/screen-reader focus at the beginning of the new batch,
        // including when the final click removes the Show more button.
        if (firstNew) firstNew.querySelector('summary').focus();
    });
    // Without JavaScript all matching moods remain available.
    update();
}));
