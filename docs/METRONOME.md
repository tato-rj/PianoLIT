# Standalone PianoLIT Metronome

Added October 22, 2026 at `metronome.pianolit.com` (local: `metronome.pianolit.test`), named route `metronome.home`. This is Arthur's requested copy of `metronome.arthurvillar.com`, not a replacement for the score toolbar metronome in `my.*`.

## Source and behavior

The original source was copied from `/Users/arthur/Desktop/Websites/ArthurVillar/resources/js/music/metronome.js` and its `metronome/` modules. The gesture and conductor modules retain their original behavior; the audio clock also supports private counting recordings. The hand SVG comes from that project's `resources/artwork/metronome/conductor-hand.svg`. The original JavaScript regression tests were copied with the source and adapted for PianoLIT's selectors/default blue.

- 20–300 BPM; tap the center or press Space/Enter to start/stop.
- Tap/hold either edge for one-BPM changes; vertical swipes retain the original speed-sensitive adjustment. Arrow keys adjust tempo; Escape stops playback.
- One to four beats; Classic click, Woodblock, Beep and Cowbell sounds. Signed-in accounts select named recordings in the same Sound dropdown, under Recorded voices. The generic My voice entry appears only without saved recordings and opens the recorder.
- Classic pulse and Conductor modes, with Dot/Hand indicators and an optional path.
- The same audio-clock scheduling, preparation beats, conductor paths, gesture cancellation, reduced-motion handling, recovery from stalled scheduling and keyboard isolation while settings are open.
- Browser-local settings and a five-second settled-tempo save. Instruments work without an account. Private named voice recordings require a web session; using a recording and its selected identity are remembered separately per account on this browser. Storage failure leaves controls usable for the current visit.

Default/reset color is `#0055fe`; the canvas CSS references `--color-primary`. The tempo numeral retains the original thin system face. The settings use PianoLIT typography, segmented controls, `@select`, `@toggle`, the shared native color field and standard buttons/shadows. The full-screen layout intentionally has no public header/footer. Settings use the same Bootstrap bottom offcanvas as the web-app Save to panel: shared close handle, heading, backdrop, focus trap and viewport height cap. Metronome content is centered at a maximum of 560px, shrinking to the available phone width. Bootstrap handles transitions, Escape/backdrop/Done dismissal and reduced motion. Closing immediately cancels capture and previews; keyboard/tempo controls stay isolated until the panel is hidden, then focus returns to Settings. There is no separate dialog animation.

The shared `components.project-watermark` places a transparent white PianoLIT icon with a white rounded-square border at the top left, linking to the main homepage. Its opacity increases from 30% to 65% on hover or keyboard focus. Its 52px target stays above the tempo edge controls and respects phone safe areas; settings and their backdrop remain above it. Enter on the focused link keeps normal navigation instead of toggling playback.

## Editing map

- Route: `routes/metronome.php`, registered before host-independent routes.
- Markup: `resources/views/metronome/{index,settings,conductor-hand}.blade.php`.
- Behavior: `resources/js/metronome.js` and `resources/js/metronome/`.
- Voice UI/audio: `components.voice-recorder`, `components.metronome-sound`, `components.metronome-sound-voice`, `sound-menu.js`, `voice-recorder.js` and `voice-audio.js`; the components share a Design System specimen. Sound uses Bootstrap's dropdown, shared field/menu/button finishes and a hidden native select for its existing value contract.
- Private recording endpoints: `MetronomeVoiceController` and `App\Services\Metronome\VoiceRecording`.
- Canvas layout and recording-state visibility: `resources/sass/metronome.scss`. Settings reuse `components.bottom-sheet` / `bottom-sheet-header` and `components/_bottom-sheet.scss`, shared with the web-app favorites panel.
- Homepage card: `HomeController`, `components.project-card`, and `resources/images/home/editorial/metronome.webp`. The editorial artwork shows a close-up walnut-and-brass metronome on a real piano surface, with stronger natural contrast and no abstract scenery, matching the other project cards; the finite gallery lists all seven projects once.
- The public hub menu includes the same named route. Public navigation from this host returns to the main site; campaign arrivals retain anonymous, host-local rules and account exclusions.
- `config/newsletter.php` registers the dated tool release for the existing monthly digest. No email or queue command is needed to use the metronome.

## Build and verification

Mix produces/version-hashes `public/js/metronome.js`, `public/css/metronome.css` and the homepage WebP. Shared recorder styles also rebuild app/admin/shared CSS. Commit matching source, outputs and manifest entries. Deploy `2026_10_22_190000_create_metronome_voices_table.php` and `2026_10_22_210000_create_metronome_voice_recordings_table.php` before the voice library. No new dependency is required. Production needs metronome DNS/vhost/TLS pointed at this Laravel deployment.

Run `node --test tests/js/metronome.test.cjs tests/js/metronome-voice.test.cjs` for the original behavior and voice regressions. The regular JavaScript runner includes these tests too. `MetronomeVoiceTest` checks private ownership, PCM validation, stale revisions, CSRF, account deletion and the sign-in return. `PublicProjectPagesTest` covers host routing and guest markup; `MetronomePreferencesTest` checks the separate web-app preference contract. Use `phpunit-review.xml`, never live storage or accounts, for these PHP checks. Browser QA uses synthetic speech in an isolated fixture, not a real microphone. Physical iOS/Android microphone, permission and background behavior remains a release check.

## Personal counting voice

Settings → Record my voice prompts for one, two, three and four in turn. Each
number stops automatically after speech and a short silence. The review offers
individual playback, Listen to all and a selected number's Redo button. Name the
recording and choose Save and use to save all four numbers together, select the
sound and preview the chosen beat count. New recording adds another voice without
replacing existing ones. Sound lists every named recording in a Recorded voices
group beneath the four instruments; there is no second recording selector.
Each voice row has play/stop, edit-name and delete icon buttons. Previewing,
renaming or deleting another row preserves the selected sound. Deletion asks for confirmation with the browser’s native confirm dialog, naming
the target recording. Cancel sends no delete request. A full-width `.btn-sm` New recording button stays visible
directly below the Sound control, outside the menu. The generic My voice option
appears only when the library is empty and starts recording. Names use shared `@input` fields, allow up to 60
characters, and each account can keep up to 20 recordings. Cancel preserves all
saved recordings. There is no inline deletion message or custom delete modal. Closing settings or
hiding/leaving the page stops the microphone and preview; a late permission grant
is immediately released.

Each number starts with a yellow Wait cue and a three-step, 1.5-second countdown.
It turns green and pulses with Speak now only when capture starts. A successful
number gets a green Recorded check; a missed number stays red with Try again
until retried. Retry goes through the countdown again. Text and icons accompany
the colors, and reduced-motion preferences disable the ring/pop/shake motion.
The shared `components.voice-recorder-cue` also supplies the four Design System
state previews. Cancel/exit clears the cue as well as the microphone.

The trigger looks like the standard PianoLIT select: `form-control` plus the
shared `form-control-select-trigger` arrow mixin. The popup reuses `dropdown-menu`,
`dropdown-item` and standard buttons, with a bounded scrolling option area.
The recording action sits below the closed control, outside the popup. Bootstrap
handles placement/outside clicks; Escape closes the menu before the settings sheet. Closing the menu cancels pending or
playing row previews. There is no custom field color, border or shadow finish.

The audio clock retains its internal `my-voice` sound. The hidden select uses
`voice:{UUID}` values, mapped to the clock only after the selected clips load.
Changing other settings keeps the displayed recording name, and late loads do
not override a more recent instrument/recording choice. Saving, renaming and
deleting update the same menu; deleting the last recording restores My voice.

During the yellow countdown, the recorder measures background noise and
adapts speech detection to the microphone. It requests automatic input gain when
supported, detects quiet speech, and shows it clearly on the level meter. The
browser negotiates a supported MediaRecorder format, trims surrounding silence
without treating steady noise as a number, normalizes even quiet recordings to
a consistent playback level and exports mono 24 kHz / 16-bit PCM WAV. The server
checks the exact WAV structure and bounds each clip to 0.06–2 seconds. Four
base64 clips are stored together in each private `metronome_voice_recordings` row
(under 513 KB per recording), with an account owner, UUID, name, revision conflict
checks and account-delete cascade. GET `/voice` lists metadata without audio;
GET `/voice/{voice}` loads only the chosen recording. PUT `/voice` creates a new
recording; PATCH and DELETE `/voice/{voice}` rename/delete an owned recording with
its current revision. All require the authenticated web session; writes also
require CSRF and are rate limited. Creation serializes on the owner to enforce
the library cap. Reads are `private, no-store`. There are no public audio URLs,
user-supplied ownership IDs, mobile model fields, transcription services or
external recording uploads.

The library migration copies each original `metronome_voices` recording as
“My voice,” preserving its audio and revision. Pause metronome voice writes for
the migration/code activation window: old code still writes the singleton table,
while new code uses only the library. Deploy the new server code and versioned
bundle together. The old table remains for rollback; deleting its migrated
recording also removes that legacy copy. Rolling back the library migration drops
new recordings, so export/preserve those before any rollback after writes resume.

The existing audio clock schedules samples by beat index and wraps at the
selected 1–4 count. At fast tempos it speeds up a syllable to fit the beat and
prevents overlapping counts after tempo changes. Ordinary instrument settings,
web-app score metronome preferences and mobile contracts are independent.

Microphone capture needs a secure browser context. Production should serve
HTTPS. Locally, run `valet secure metronome.pianolit` in Terminal (Mac
administrator authentication is required), then use
`https://metronome.pianolit.test`.
Reference: [browser microphone requirements](https://developer.mozilla.org/en-US/docs/Web/API/MediaDevices/getUserMedia#privacy_and_security)
and [recording format negotiation](https://developer.mozilla.org/en-US/docs/Web/API/MediaRecorder/isTypeSupported_static).
