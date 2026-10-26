// Promise-based animate.css helper
// Usage:
//   await $el.animateCSS('headShake');
//   await $el.animateCSS('bounce', { speed: 'fast' });
//   $el.animateCSS('tada').then(() => { ... });

(function ($) {
  $.fn.animateCSS = function (animation, options) {
    // Back-compat: animateCSS(animation, speedString)
    if (typeof options === "string") options = { speed: options };

    const opts = $.extend(
      {
        speed: null,          // 'slow'|'slower'|'fast'|'faster'|null
        keepClasses: false,   // keep animate classes after finishing
        namespace: ".animateCSS"
      },
      options || {}
    );

    const animClass = "animate__" + animation;
    const baseClass = "animate__animated";
    const speedClass = opts.speed ? "animate__" + opts.speed : null;

    const endEvents =
      "animationend" + opts.namespace +
      " webkitAnimationEnd" + opts.namespace +
      " oAnimationEnd" + opts.namespace +
      " MSAnimationEnd" + opts.namespace;

    const $els = this;

    return new Promise((resolve) => {
      if (!$els.length) return resolve($els);

      let remaining = $els.length;

      function doneOne() {
        remaining -= 1;
        if (remaining <= 0) resolve($els);
      }

      $els.each(function () {
        const el = this;
        const $el = $(el);

        // Prevent stacking handlers if called repeatedly
        $el.off(endEvents);

        // Remove classes so we can restart the same animation reliably
        $el.removeClass(baseClass + " " + animClass + (speedClass ? " " + speedClass : ""));

        // Force reflow to restart animation
        // eslint-disable-next-line no-unused-expressions
        el.offsetWidth;

        // Add classes to trigger animation
        $el.addClass(baseClass);
        if (speedClass) $el.addClass(speedClass);
        $el.addClass(animClass);

        // Resolve on animation end (and cleanup)
        $el.one(endEvents, function () {
          if (!opts.keepClasses) {
            $el.removeClass(baseClass + " " + animClass + (speedClass ? " " + speedClass : ""));
          }
          doneOne();
        });

        // Safety: if animationend never fires (rare), resolve anyway
        // Uses computed duration if available, otherwise fallback.
        const cs = window.getComputedStyle(el);
        const dur = parseFloat(cs.animationDuration) || 0;
        const delay = parseFloat(cs.animationDelay) || 0;
        const totalMs = Math.max(50, (dur + delay) * 1000 + 50);

        setTimeout(() => {
          // If still has animClass, we likely missed animationend
          if ($el.hasClass(animClass)) {
            if (!opts.keepClasses) {
              $el.removeClass(baseClass + " " + animClass + (speedClass ? " " + speedClass : ""));
            }
            // remove handler to avoid double-callback
            $el.off(endEvents);
            doneOne();
          }
        }, totalMs);
      });
    });
  };
})(jQuery);
