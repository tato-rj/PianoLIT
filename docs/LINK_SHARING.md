# Sharing a piece link

The piece-page share icon sits beside the favorite heart for guests and signed-in
accounts. It opens `components.media.link-share`, also shown in the Design System.
The options offcanvas no longer contains the share action.

`App\Support\LinkShare` builds Email, Text, WhatsApp, Facebook, Reddit and Telegram
links from the named piece route. Email opens the visitor's mail application;
PianoLIT never collects a recipient or sends the message. The shared URL excludes
the current request's query string and fragment. Titles and query values are
escaped/encoded. External web destinations use `noopener noreferrer`.

Email uses the subject **Shared piece from PianoLIT**. Email, Text and WhatsApp
prefill a greeting, a short personal recommendation with the piece title and
full composer name, and the canonical link:

```text
Hi!

I found [piece title] by [composer] on PianoLIT and thought you might enjoy it.

[piece URL]
```

Telegram uses the same greeting/recommendation as its comment, alongside its
separate URL parameter, so the link is supplied only once. Reddit prefills the
post title **A piece I found on PianoLIT: [piece title] by [composer]**. Facebook
retains the existing URL-only share destination; no unsupported message
parameters are added. The shared presentation view supplies the full composer
name without changing Piece attributes or mobile serialization.

The email link uses a plain-text body with CRLF paragraph breaks, as required by
[RFC 6068](https://www.rfc-editor.org/rfc/rfc6068#section-2). It cannot reliably
create an HTML anchor around the title, so the URL stays on its own line rather
than exposing raw HTML in the draft. Messaging parameters follow the official
[WhatsApp click-to-chat](https://faq.whatsapp.com/5913398998672934) and
[Telegram share](https://core.telegram.org/widgets/share) formats. These links
only prefill drafts; visitors choose recipients and send in their own apps.

The readonly Page link field and its copy icon both copy. Enter or Space also
copies when the field is focused. Success shows a manual Bootstrap **Copied!**
tooltip for 1.6 seconds, with a screen-reader status announcement and no success
alert. Clipboard denial falls back to the selected field; if copying remains
unavailable, the standard error alert explains manual copying. Closing the modal
clears feedback and prevents late clipboard results from appearing.

The dialog ends with Page link; Arthur removed the native More sharing options
button. There is no separate Instagram destination; visitors can copy the link.
The SMS URI uses the iOS body separator on iPhone/iPadOS.

On iPhone/iPad, the Facebook button uses the system share sheet when supported;
choose Facebook there to pass the canonical URL to its share extension. This
works around a [reported Facebook iOS app handoff bug](https://github.com/heiseonline/shariff/issues/452)
where ordinary sharer links open the app without a composer. Only the URL is
passed, preserving Facebook's link preview. The browser cannot select a specific
app in the system sheet ([Web Share API](https://www.w3.org/TR/web-share/)).
This does not restore the separate More sharing options button. Other devices,
unsupported native sharing and modified clicks retain the existing web link;
cancellation is silent, failures offer copying through the shared error alert,
and closed/replaced dialogs cannot receive late errors. Final posting remains
inside Facebook. Public preview testing needs a reachable public URL; local
`.test` URLs remain local and are never rewritten to potentially unrelated live
piece IDs. Confirm the final Facebook app handoff on a physical device.

Edit `resources/js/components/link-share.js` for behavior and
`resources/sass/components/_link-share.scss` for layout. Standard shared modal,
inputs, buttons, icons, alerts and Bootstrap tooltip supply the controls.
The shared three-column grid uses compact 48px icon circles and 14px labels.
Messaging services use their brand colors on pale matching backgrounds, with
stronger tints on hover and keyboard focus; generic Email and Text use blue and
green respectively. The Design System renders this same component.

## Retirement and release

The old `webapp.pieces.share` POST route, controller method, PieceShared event,
listener, notification, SharePieceEmail and editable email body are removed.
The `piece-shared` admin catalog key cannot be previewed, enabled or sent.
Historical notifications/settings are left in place without an active sender.

Deploy the code and built assets together through Git, refresh route/view caches
and the Composer autoloader, and restart long-lived workers. Review and quarantine
queued/failed jobs referencing the removed event or mailable instead of replaying
them; do not purge unrelated jobs. No database migration is needed.

`LinkShareTest` covers canonical guest/account rendering, encoded destinations,
invalid URL rejection and closed server/admin delivery endpoints. The focused
`tests/js/link-share.test.cjs` covers copy, tooltip expiry/cleanup, fallbacks,
SMS variants, the iOS Facebook share-sheet fallback and stale feedback. Desktop/mobile browser checks use fictional
rendered Blade output; no messages or social posts are sent. Actual handoff to
installed messaging apps remains a physical-device check.

## Piece header on phones

`webapp/piece/header` keeps the back link, title, favorite heart, share icon and
options in one grid row beneath the level pill. `components/_pieces.scss` owns
its layout: icons align with the center of the title's first line, independently
of title wrapping; phone icon columns have no added gap and retain 44px height.
The title keeps the existing phone font size and wraps naturally. The shared
back component's optional `inline` argument places this header's back link in
the row; other callers retain their original positioning. The portrait and all
favorite/share/options destinations remain unchanged.
