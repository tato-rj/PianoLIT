const VanillaTilt = require('vanilla-tilt');

module.exports = function initializeLogoTilt(win) {
    function start() {
        const logos = Array.from(win.document.querySelectorAll('[data-logo-tilt]'));
        if (!logos.length || !win.matchMedia) return;

        const motion = win.matchMedia('(hover: hover) and (pointer: fine) and (prefers-reduced-motion: no-preference)');

        function sync() {
            logos.forEach(logo => {
                if (motion.matches) {
                    VanillaTilt.init(logo, {
                        max: 12,
                        perspective: 600,
                        scale: 1,
                        speed: 350,
                        glare: false,
                        gyroscope: false,
                        // Keep the link's hover area steady while its artwork tilts.
                        'mouse-event-element': logo.closest('a') || logo
                    });
                } else if (logo.vanillaTilt) {
                    logo.vanillaTilt.destroy();
                }
            });
        }

        if (motion.addEventListener) motion.addEventListener('change', sync);
        else motion.addListener(sync);
        sync();
    }

    if (win.document.readyState === 'loading') {
        win.document.addEventListener('DOMContentLoaded', start, {once: true});
    } else start();
};
