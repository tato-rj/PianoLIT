# Public quizzes

Public quiz pages live on Learn at `/quizzes` and `/quizzes/{slug}`. The directory
and individual quizzes use the shared public shell, breadcrumbs, editorial layout,
resource cards and existing card promotions. The directory uses the shared hub header; detail pages retain the contextual
newsletter invitation.

## Editing

- `resources/views/games/quizzes/index.blade.php`: directory heading, topic navigation, level filter, result count and empty states
- `resources/views/games/quizzes/card.blade.php`: the resource card shared by the directory and recommendations
- `resources/views/games/quizzes/show.blade.php`: quiz introduction, metadata, start action, shared result overlay and recommendations
- `resources/views/games/quizzes/questions.blade.php`: accessible questions, native audio/images and answer choices
- `resources/views/games/quizzes/level.blade.php`: difficulty label with the shared ascending-bars icon
- `resources/views/games/quizzes/feedback.blade.php`: compact result fragment returned by the existing feedback endpoint
- `resources/views/games/quizzes/comparison.blade.php` and `app/Services/QuizResultComparison.php`: visual score/average comparison, award and earlier-play count
- `resources/js/views/quizzes.js`: start, progress, answer locking, playback cleanup, result requests, sharing, retry, review and restart
- `resources/views/games/quizzes/{share-panel,share}.blade.php`: social options inside the result overlay and the public challenge landing page
- `app/Services/QuizResultShare.php`: signed result links, share text and the 1200 × 630 PNG score card
- `resources/sass/pages/_quizzes.scss`: page layout and question/result states

The shared `resource-card__art--cover` variant crops existing quiz covers into the
standard 2:1 preview. Missing covers use Learn's existing editable quiz SVG. The
same variant has a Design System specimen. No new artwork or media path is needed.
Topic choices exclude draft-only topics; the level selector retains the chosen
topics, topic links retain the chosen level, and pagination retains both. The
public page size remains 12. Directory/recommendation queries eager-load the
relations used by cards. Quizzes with no questions show a preparation state.

## Quiz behavior

As of October 29, Arthur explicitly requested colorful, funny quizzes with the
original Giphy endings, very little copy and more animated play. Start reveals one
question at a time, retaining the original question and answer indexes. An answer
locks that question, reveals the correct answer, and animates the choice: a pop for
a correct answer, a wobble for a mistake. Short reaction lines avoid immediate
repeats; consecutive correct answers build a visible streak. Scoped musical bursts,
colored states and a gradient progress bar add motion without changing shared
button styling elsewhere. Next advances only after answering. The final answer
needs no extra click: after 650ms of reaction time, the shared result modal opens
with a colorful rotating musical ring and a question mark. The request starts then;
a fast response waits for 1.1 seconds of suspense, while a slow response keeps the
spinner visible until completion or the 15-second request timeout. Score, GIF and
replay actions replace the spinner inside the same overlay, without moving the
underlying question or scrolling the page. Reduced motion uses a 350ms reaction
pause, a static ring and no artificial suspense delay. Audio stops between steps.

Arthur’s follow-up keeps the viewport steady during play: Start, answering, Next
and result reveal do not call scrollIntoView. Pointer answers leave focus alone;
keyboard answers can focus Next with preventScroll. Quiz pages disable browser
scroll anchoring and reserve space for reactions/Next so feedback does not pull
the viewport. Explicit Review answers still scrolls to the review. The centered
start panel uses a large shared primary play button, brief instructions, colorful
musical accents and a short, reduced-motion-aware entrance for its three symbols.
Empty quizzes retain their preparation state without a start button.

The same GET feedback endpoint receives the same ordered `answers[]` indexes. The
existing model still evaluates and records the result. The finale shows a large
score, one randomized short joke and a randomly chosen GIF from the original
score-specific collection. The 404 entry `GCvktC0KFy9l6` was removed; the other 24
original animated URLs responded successfully during verification. A colored
entrance and larger burst accompany the reveal. Share my score is the primary action;
Review answers displays every locked question, and More quizzes retains its route.

GIFs start as still images and animate only when motion is allowed. Arthur removed
the Play/Pause control and requested a discreet 10px GIF via GIPHY credit.
Reduced-motion changes, sharing, review, replay, hidden pages and page exit stop
animation/audio. Missing GIFs leave a native emoji, readable score and joke; no
Giphy API request or key is introduced. Image requests omit the page referrer.
Animations are bounded and cancelled between questions and on reset. Results have
an accessible live region; answer corrections have text as well as color.

Failed requests retain answers and expose Retry inside the overlay; retries skip
the suspense delay. Closing the overlay stops effects/GIF playback and keeps the
result available through View result without another score request. Late responses
cannot reopen a dismissed overlay or overwrite a reset quiz. Reset/page exit clear
reveal timers; hidden tabs defer a scheduled reveal until they return. Bootstrap
owns focus trapping, Escape/backdrop/close dismissal and scroll locking. Review and
replay wait for dismissal before updating the page and moving keyboard focus.

`quizzes.*` names, legacy feedback URLs, guest access, admin draft previews, the
serialized questions/answer order, scoring method and session-owned result user ID
remain intact. Native audio/image paths and quiz administration are unchanged.
The generic games feedback/modal views remain available for existing callers;
already-open legacy quiz pages can display the new score fragment in their modal.
No email type, signup flow, quiz data or database schema is introduced.

## Score showdown

The GIF finale now pairs animated You/Average bars with a yellow award medal and
a dark crowd-count badge. Above-average, tied, below-average and first-play
results receive short distinct badges; the existing joke, GIF, burst and sharing
remain. Both bars use the same current quiz scale, with accessible labels and
numeric values. The award spins/pops once and bars fill in sequence; reduced
motion shows final values immediately. Close, sharing, replay and hidden-page
cleanup cancel these effects through the existing animation manager. Desktop
places the comparison beside the GIF; phones stack them with compact spacing.
Panels remain white.

`QuizResultComparison` reads one aggregate (COUNT/AVG/MIN/MAX) scoped to this quiz
before the existing completion write. The current round never changes its own
benchmark. The average is rounded to one decimal for the displayed value and bar;
standing badges compare to that same displayed average. The count says **earlier
plays**, not unique people: anonymous and repeated completions are attempts.
No account identities or historical result collection are loaded, no percentile
or leaderboard rank is invented, and request-supplied averages/counts are ignored.

No earlier results shows First on stage and an unavailable average (—), while a
real zero average remains 0. Legacy rows contain no question-count/version
snapshot. If any score is outside today's valid range, retain the real play count
and omit the incompatible average instead of clamping or discarding history.
Changes within the same range cannot be detected from the existing data. Scoring,
result ownership and persistence, model/mobile output and shared-card URLs are
unchanged; this adds no migration, analytics event or email.

## Result sharing

Arthur's latest color direction replaces pastel panel washes with white start,
question and result surfaces. Strong blue, yellow, green and pink accents remain
in the start symbols, answer states, spinning reveal and score card.

Share my score switches the existing modal to a card preview plus Facebook,
WhatsApp, X, Reddit, Telegram and Email links. It reuses the shared
`components.media.link-share-options`, copy field, tooltip and error alert;
piece sharing keeps its original text and six destinations. More apps uses
native URL sharing when supported; cancellation is silent, other failures retain
the social/copy alternatives. Save score card downloads the PNG for manual use in
Instagram or Stories. No social post or email is sent by PianoLIT.

The existing evaluation produces the score and total. Published nonempty quizzes
up to 999 questions receive permanent signed Learn URLs at
`/quizzes/{quiz}/results/{score}/{total}` and `/image.png`.
They contain no player name, account ID or answers. Opening either is read-only,
does not record a completion, requires a valid signature and rejects unpublished
quizzes or invalid score bounds. Existing links retain their historical score/total
if questions change; the current quiz title remains editable. Draft previews keep
their score but do not offer public sharing.

`ValidateQuizShareSignature` delegates to Laravel signature validation while
ignoring only enumerated social attribution keys (fbclid, igsh/igshid and five UTM
keys). Quiz, score, total and download mode remain signed. Canonical/OpenGraph URLs
retain the valid signature and exclude arriving tracking parameters; other signed
routes are unaffected. The shared metadata view opts into a large Twitter card and
1200 × 630 dimensions for these pages, keeping existing defaults elsewhere.

The PNG renderer uses PHP GD/FreeType and existing bundled OpenSans fonts, with
bounded title wrapping and a fixed canvas. It makes no remote request and stores
no generated files. The same score-specific PNG powers the preview, download,
OpenGraph and Twitter image; the landing page offers Take the quiz. Deploy the
controller, service, middleware, routes, views, fonts and built assets together.
Real social crawler/card rendering requires a reachable production deployment;
it has not been tested by posting or sharing through real accounts.

## Verification

```sh
php -d error_reporting=8191 vendor/bin/phpunit -c phpunit-review.xml --filter 'QuizPagesTest|LearnSubdomainTest|InfographicPagesTest|AdminDesignSystemTest'
node tests/js/quizzes.test.cjs
npm run production
npm run test:build
git diff --check
```

`QuizPagesTest` uses in-memory SQLite with fake services. It checks combined
filters/pagination, draft-only topic exclusion, shared components, question/media
markup, accessible and unique targets, empty states, recommendations, versioned
assets, and identical scoring/account ownership on canonical and legacy feedback
URLs. Its optional `QUIZ_PREVIEW_DIR` writes fictional rendered directory, detail,
playable quiz and feedback HTML for browser review. The JavaScript regression also covers
question pacing, streaks, motion cancellation, GIF automatic playback/still/failure, automatic
reveal timing, fast/slow response handling, dismissal/reopen, hidden-tab deferral,
pending-request guards, failure/retry, reset and stale responses.

The local database had no published quizzes during this redesign. Browser review
used these isolated fixtures with existing sample artwork and intercepted feedback
responses: desktop and 390px phone layouts, no horizontal overflow, loaded images,
start/answer/progress, error/retry, review and restart. SQL scoring/persistence was
verified separately in the isolated PHP tests. No real quiz completions, accounts
or email deliveries were created as verification.

October 29 follow-up verification used the isolated rendered fixtures and a local
preview server, with no writes to the normal database. Desktop (1440px) and phone
(390px) playthroughs verified correct/incorrect states, loaded animated/still GIFs,
perfect/poor endings, pause, review and replay with no horizontal overflow. The
focused PHP suite, JavaScript regression, isolated Node 24 production/CSS build
and final workspace asset checks pass. Production has not been deployed.

October 29 sharing follow-up: isolated QuizPagesTest passes 8 tests / 194 assertions,
and LinkShareTest passes 4 tests / 54 assertions. Checks cover signed/tampered
URLs, social tracking parameters, public metadata, PNG dimensions/downloads,
draft privacy, original score ownership and read-only preview requests. JavaScript
checks cover share-panel navigation, native sharing/cancellation/failure, copy and
detached fragment cleanup alongside the existing quiz lifecycle. Isolated Node 24
production/CSS and final workspace asset checks pass. Desktop and 390px phone
fixture playthroughs checked the share screen, copy tooltip, perfect/poor cards,
public challenge link and replay. Final white-panel proof:
`quiz/share-final.png` in this chat's visualization directory. No real-account
results, posts, email sends or production changes were made.

October 29 score-showdown verification: QuizPagesTest passes 10 tests / 236
assertions; the quiz JS regression covers staggered comparison fills, the award
animation and cancellation/reduced-motion behavior. Desktop and 390px phone
fictional fixtures show correct zero/half/full bars, trophy/comeback states,
loaded GIFs and working share/back navigation. The compact mobile dialog measures
815px within an 844px viewport with no horizontal overflow. Production/CSS and
final asset checks pass; no normal database writes or deployment.
