# Public quizzes

Public quiz pages live on Learn at `/quizzes` and `/quizzes/{slug}`. The directory
and individual quizzes use the shared public shell, breadcrumbs, editorial layout,
resource cards and existing card promotions. The directory uses the hub-header
newsletter action; detail pages use the shared newsletter invitation.

## Editing

- `resources/views/games/quizzes/index.blade.php`: directory heading, topic navigation, level filter, result count and empty states
- `resources/views/games/quizzes/card.blade.php`: the resource card shared by the directory and recommendations
- `resources/views/games/quizzes/show.blade.php`: quiz introduction, metadata, start action, inline results and recommendations
- `resources/views/games/quizzes/questions.blade.php`: accessible questions, native audio/images and answer choices
- `resources/views/games/quizzes/feedback.blade.php`: compact result fragment returned by the existing feedback endpoint
- `resources/js/views/quizzes.js`: start, progress, answer locking, playback cleanup, result requests, retry, review and restart
- `resources/sass/pages/_quizzes.scss`: page layout and question/result states

The shared `resource-card__art--cover` variant crops existing quiz covers into the
standard 2:1 preview. Missing covers use Learn's existing editable quiz SVG. The
same variant has a Design System specimen. No new artwork or media path is needed.
Topic choices exclude draft-only topics; the level selector retains the chosen
topics, topic links retain the chosen level, and pagination retains both. The
public page size remains 12. Directory/recommendation queries eager-load the
relations used by cards. Quizzes with no questions show a preparation state.

## Quiz behavior

Start reveals the existing ordered questions. Each answer locks that question,
marks the chosen answer and the correct answer with visible text, and advances
the answered-question count. Audio inside the quiz stops when answering/restarting.
After the final answer, the same GET feedback endpoint receives the same ordered
`answers[]` indexes. The existing model evaluates and records the result; only its
HTML presentation is replaced. The result appears inline, with review/restart and
More quizzes actions. Failed requests retain answers and expose a retry; requests
cannot overlap, and late responses from an earlier attempt cannot overwrite a
reset quiz. Review/reset moves keyboard focus, respecting reduced motion.

`quizzes.*` names, legacy feedback URLs, guest access, admin draft previews, the
serialized questions/answer order, scoring method and session-owned result user ID
remain intact. Native audio/image paths and quiz administration are unchanged.
The generic games feedback/modal views remain available for existing callers;
already-open legacy quiz pages can display the new score fragment in their modal.
No email type, signup flow, quiz data or database schema is introduced.

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
URLs. Its optional `QUIZ_PREVIEW_DIR` writes fictional rendered directory, detail
and feedback HTML for browser review. The JavaScript regression also covers
pending-request guards, failure/retry, reset and stale responses.

The local database had no published quizzes during this redesign. Browser review
used these isolated fixtures with existing sample artwork and intercepted feedback
responses: desktop and 390px phone layouts, no horizontal overflow, loaded images,
start/answer/progress, error/retry, review and restart. SQL scoring/persistence was
verified separately in the isolated PHP tests. No real quiz completions, accounts
or email deliveries were created as verification.
