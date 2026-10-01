(function (doc, win) {
    'use strict';
    var section = doc.querySelector('[data-piece-description]');
    if (!section) return;
    var content = section.querySelector('.piece-description__content');
    var button = section.querySelector('.piece-description__toggle');
    var video = doc.querySelector('#tab-about .piece-about-video');
    var expanded = false;
    function update() {
        if (!content.getClientRects().length) return;
        var overflows = content.scrollHeight > 250;
        if (!overflows) expanded = false;
        // Measure the row with the description collapsed, before restoring the
        // expanded text. This also refreshes the baseline after a width/font change.
        if (video && !video.querySelector('.plyr--fullscreen-active')) {
            video.style.removeProperty('--piece-about-video-height');
            if (expanded) {
                section.classList.toggle('is-collapsed', overflows);
                video.style.setProperty('--piece-about-video-height', video.getBoundingClientRect().height + 'px');
            }
        }
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
