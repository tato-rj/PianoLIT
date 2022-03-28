<?php

namespace App\Http\Controllers;

use App\Quiz\{Quiz, Topic, Level};
use App\Admin;
use App\Notifications\QuizCompleted;
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
        $quizzes = Quiz::published()->latest()->filter($filters)->paginate(12);
        $levels = Level::all();
        $topics = Topic::all();

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
        $suggestions = $quiz->suggestions(8)->get();

        if (! $quiz->published_at) {
            if (auth()->guard('admin')->check())
                return view('games.quizzes.show', compact(['quiz', 'suggestions']))->with('preview', true);

            abort(404);
        }

        if (traffic()->isRealVisitor())
            $quiz->increment('views');

        return view('games.quizzes.show', compact(['quiz', 'suggestions']));
    }

    public function feedback(Request $request, Quiz $quiz)
    {
        if (! is_array($request->answers))
            return view('components.feedbacks.error')->render();

        $feedback = $quiz->evaluate($request->answers);        

        if (traffic()->isRealVisitor()) {
            $quiz->results()->create([
                'score' => $feedback['score'],
                'user_id' => auth()->guard('web')->check() ? auth()->user()->id : null
            ]);

            // Admin::notifyAll(new QuizCompleted($quiz));
        }

        return view('games.components.feedback', compact('feedback'))->render();
    }
}
