<?php

namespace App\Http\Controllers;

use App\Quiz\{Quiz, Topic, Level};
use App\Filters\QuizFilters;
use App\Services\QuizPageContent;
use App\Services\QuizResultComparison;
use Illuminate\Http\Request;
use App\Services\QuizResultShare;

class QuizzesController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request, QuizFilters $filters)
    {
        $quizzes = Quiz::published()->with(['level', 'topics'])->latest()->orderByDesc('id')
            ->filter($filters)->paginate(12)->appends($request->only('topics', 'level'));
        $levels = Level::whereHas('quizzes', function ($query) { $query->published(); })->orderBy('id')->get();
        $topics = Topic::whereHas('quizzes', function ($query) { $query->published(); })->orderBy('name')->get();

        $page = $quizzes->currentPage();
        $filtered = $request->query->has('topics') || $request->query->has('level');
        $indexable = ! $filtered && $quizzes->isNotEmpty();
        $canonical = $indexable ? route('quizzes.index', $page > 1 ? ['page' => $page] : []) : null;
        $response = response()->view('games.quizzes.index', compact('quizzes', 'levels', 'topics', 'canonical'));

        return $indexable ? $response : $response->header('X-Robots-Tag', 'noindex');
    }

    public function sitemap()
    {
        $urls = Quiz::published()->select(['id', 'slug', 'questions'])->orderBy('id')->get()
            ->filter(fn (Quiz $quiz) => count($quiz->questions) > 0)
            ->map(fn (Quiz $quiz) => route('quizzes.show', $quiz));

        if ($urls->isNotEmpty()) $urls->prepend(route('quizzes.index'));

        return response()->view('components.seo.sitemap', compact('urls'), 200, ['Content-Type' => 'application/xml; charset=UTF-8']);
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Quiz  $quiz
     * @return \Illuminate\Http\Response
     */
    public function show(Quiz $quiz, QuizPageContent $content)
    {
        $preview = ! $quiz->published_at;
        abort_if($preview && ! auth()->guard('admin')->check(), 404);
        $quiz->loadMissing(['topics', 'level']);
        $suggestions = $this->related($quiz);
        $statistics = app(QuizResultComparison::class)->beforeCurrentPlay($quiz, 0, count($quiz->questions));
        $copy = $content->for($quiz);
        $canonical = ! $preview && count($quiz->questions) ? route('quizzes.show', $quiz) : null;
        $response = response()->view('games.quizzes.show', compact('quiz', 'suggestions', 'statistics', 'preview', 'copy', 'canonical'));

        return $canonical ? $response : $response->header('X-Robots-Tag', 'noindex');
    }

    private function related(Quiz $quiz)
    {
        $related = Quiz::published()->with('level')->whereKeyNot($quiz->getKey());
        $topicIds = $quiz->topics->modelKeys();
        if ($topicIds) {
            $related->withCount(['topics as shared_topics_count' => fn ($query) => $query->whereIn('quiz_topics.id', $topicIds)])
                ->orderByDesc('shared_topics_count');
        }

        return $related->orderByDesc('published_at')->orderByDesc('id')->limit(4)->get();
    }

    public function feedback(Request $request, Quiz $quiz)
    {
        if (! is_array($request->answers))
            return response()->view('components.feedback.error')->header('X-Robots-Tag', 'noindex');

        $feedback = $quiz->evaluate($request->answers);
        $comparison = app(QuizResultComparison::class)->beforeCurrentPlay($quiz, $feedback['score'], $feedback['questions_count']);

        if (traffic()->isRealVisitor()) {
            $quiz->results()->create([
                'score' => $feedback['score'],
                'user_id' => auth()->guard('web')->check() ? auth()->user()->id : null
            ]);
        }

        $share = $quiz->published_at && $feedback['questions_count'] > 0 && $feedback['questions_count'] <= 999
            ? app(QuizResultShare::class)->data($quiz, $feedback['score'], $feedback['questions_count']) : null;
        return response()->view('games.quizzes.feedback', compact('feedback', 'share', 'comparison'))->header('X-Robots-Tag', 'noindex');
    }

    public function share(Quiz $quiz, int $score, int $total, QuizResultShare $sharing)
    {
        $share = $sharing->data($quiz, $score, $total);
        return response()->view('games.quizzes.share', compact('quiz', 'share'))->header('X-Robots-Tag', 'noindex');
    }

    public function shareImage(Request $request, Quiz $quiz, int $score, int $total, QuizResultShare $sharing)
    {
        $share = $sharing->data($quiz, $score, $total);
        return response($sharing->image($share), 200, [
            'Content-Type' => 'image/png',
            'Content-Disposition' => ($request->boolean('download') ? 'attachment' : 'inline').'; filename="pianolit-quiz-score.png"',
            'Cache-Control' => 'public, max-age=86400',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }
}
