<?php

namespace App\Http\Controllers;

use App\Quiz\Quiz;
use App\Http\Requests\QuizForm;
use Illuminate\Http\Request;

class QuizzesController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $quizzes = Quiz::published()->paginate(12);

        return view('quizzes.index', compact('quizzes'));
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        return view('admin.pages.quizzes.create');
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request, QuizForm $form)
    {
        $quiz = Quiz::create([
            'creator_id' => auth()->guard('admin')->user()->id,
            'slug' => str_slug($form->title),
            'title' => $form->title,
            'description' => $form->description,
            'questions' => serialize($form->questions),
            'feedback' => serialize($form->feedback)
        ]);

        $quiz->uploadCoverImage($request);

        return redirect(route('admin.quizzes.index'))->with('status', 'The quiz has been successfuly created!');
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Quiz  $quiz
     * @return \Illuminate\Http\Response
     */
    public function show(Quiz $quiz)
    {
        $suggestions = Quiz::exclude([$quiz->id])->suggestions(4)->get();

        if (! $quiz->published_at) {
            if (auth()->guard('admin')->check())
                return view('quizzes.show', compact(['quiz', 'suggestions']))->with('preview', true);

            abort(404);
        }

        if (traffic()->isRealVisitor())
            $quiz->increment('views');

        return view('quizzes.show', compact(['quiz', 'suggestions']));
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Quiz  $quiz
     * @return \Illuminate\Http\Response
     */
    public function edit(Quiz $quiz)
    {
        return view('admin.pages.quizzes.edit', compact('quiz'));
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Quiz  $quiz
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, Quiz $quiz, QuizForm $form)
    {
        $quiz->update([
            'slug' => str_slug($form->title),
            'title' => $form->title,
            'description' => $form->description,
            'questions' => serialize($form->questions),
            'feedback' => serialize($form->feedback)
        ]);

        $quiz->uploadCoverImage($request);

        return redirect()->back()->with('status', 'The quiz has been successfuly updated!');
    }

    public function updateStatus(Request $request, Quiz $quiz)
    {
        $quiz->updateStatus();

        return response()->json(['status' => 'The quiz has been ' . $quiz->status . '.']);
    }

    public function uploadImage(Request $request)
    {
        try {
            $path = \Storage::disk('public')->putFile(
                    "/quiz/content_images", 
                    $request->file('file'));
        } catch (\Exception $e) {
            return response()->json('Sorry, something went wrong...', 404);
        }

        return response()->json(['location' => asset('storage/' . $path)]);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Quiz  $quiz
     * @return \Illuminate\Http\Response
     */
    public function destroy(Quiz $quiz)
    {
        $quiz->delete();

        return redirect()->back()->with('status', 'The quiz has been successfuly deleted!');
    }
}
