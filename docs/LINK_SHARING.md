# Sharing a piece link

The piece-page share icon sits beside the favorite heart for guests and signed-in
accounts. It opens `components.media.link-share`, also shown in the Design System.
The options offcanvas no longer contains the share action.

`App\Support\LinkShare` builds Email, Text, WhatsApp, Facebook, Reddit and Telegram
links from the named piece route. Email opens the visitor's mail application;
PianoLIT never collects a recipient or sends the message. The shared URL excludes
the current request's query string and fragment. Titles and query values are
escaped/encoded. External web destinations use `noopener noreferrer`.

The readonly Page link field and its copy icon both copy. Enter or Space also
copies when the field is focused. Success shows a manual Bootstrap **Copied!**
tooltip for 1.6 seconds, with a screen-reader status announcement and no success
alert. Clipboard denial falls back to the selected field; if copying remains
unavailable, the standard error alert explains manual copying. Closing the modal
clears feedback and prevents late clipboard results from appearing.

More sharing options appears only when the browser supports the native share
sheet for the page. Available apps depend on the device/browser. There is no
separate Instagram destination; visitors can copy the link or use an available
native target. The SMS URI uses the iOS body separator on iPhone/iPadOS.

Edit `resources/js/components/link-share.js` for behavior and
`resources/sass/components/_link-share.scss` for layout. Standard shared modal,
inputs, buttons, icons, alerts and Bootstrap tooltip supply the controls.

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
native sharing and stale feedback. Desktop/mobile browser checks use fictional
rendered Blade output; no messages or social posts are sent. Actual handoff to
installed messaging apps remains a physical-device check.
