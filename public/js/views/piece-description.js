(function (doc, win) {
    'use strict';
    var section = doc.querySelector('[data-piece-description]');
    if (!section) return;
    var content = section.querySelector('.piece-description__content');
    var button = section.querySelector('.piece-description__toggle');
    var expanded = false;
    function update() {
        if (!content.getClientRects().length) return;
        var overflows = content.scrollHeight > 250;
        if (!overflows) expanded = false;
        section.classList.toggle('is-collapsed', overflows && !expanded);
        button.hidden = !overflows;
        button.setAttribute('aria-expanded', expanded ? 'true' : 'false');
        button.textContent = expanded ? 'Read less' : 'Read more';
    }
    button.addEventListener('click', function () {
        expanded = !expanded;
        update();
    });
    win.addEventListener('resize', update);
    if (win.ResizeObserver) new win.ResizeObserver(update).observe(content);
    if (doc.fonts && doc.fonts.ready) doc.fonts.ready.then(update);
    update();
}(document, window));
