# Composing piano animation

Saved on 2026-10-06 at the user’s request for reuse later. Removed from Create eScore, which uses the subtle updating fade again.

Open `preview.html` for the animated standalone reference. `markup.html` contains the character and label, and `animation.scss` preserves the exact animation styles.

The lavender piano has rosy cheeks, a smile, blinking eyes, bouncing keys, floating notes and a sparkle. It displays “Composing your preview…” in a small badge.

For the original integration, place the markup inside `.escore-modal .escore-page`, make the page positioned, and set `aria-busy="true"` on the page while working. Setting it to `false` hides the badge. The badge ignores pointer events, is decorative for screen readers, and respects reduced-motion preferences; keep a separate accessible status. The original eScore request logic already manages that busy state. Adjust the container selectors and label for its future location.

This archive is not imported or served by the application.
