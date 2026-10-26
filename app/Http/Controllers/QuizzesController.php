<?php

namespace App\Http\Controllers;

use App\Quiz\{Quiz, Topic, Level};
use App\Filters\QuizFilters;
use Illuminate\Http\Request;

class QuizzesController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(QuizFilters $filters)
    {
        $quizzes = Quiz::published()->with(['level', 'topics'])->latest()->orderByDesc('id')
            ->filter($filters)->paginate(12)->withQueryString();
        $levels = Level::whereHas('quizzes', function ($query) { $query->published(); })->orderBy('id')->get();
        $topics = Topic::whereHas('quizzes', function ($query) { $query->published(); })->orderBy('name')->get();

        return view('games.quizzes.index', compact(['quizzes', 'levels', 'topics']));
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Quiz  $quiz
     * @return \Illuminate\Http\Response
     */
    public function show(Quiz $quiz)
    {
        $quiz->loadMissing(['topics', 'level']);
        $suggestions = $quiz->suggestions(4)->with('level')->get();

        if (! $quiz->published_at) {
            if (auth()->guard('admin')->check())
                return view('games.quizzes.show', compact(['quiz', 'suggestions']))->with('preview', true);

            abort(404);
        }

        return view('games.quizzes.show', compact(['quiz', 'suggestions']));
    }

    public function feedback(Request $request, Quiz $quiz)
    {
        if (! is_array($request->answers))
            return view('components.feedback.error')->render();

        $feedback = $quiz->evaluate($request->answers);        

        if (traffic()->isRealVisitor()) {
            $quiz->results()->create([
                'score' => $feedback['score'],
                'user_id' => auth()->guard('web')->check() ? auth()->user()->id : null
            ]);
        }

        return view('games.quizzes.feedback', compact('feedback'))->render();
    }
}
