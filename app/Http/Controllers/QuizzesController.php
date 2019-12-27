<?php

namespace App\Http\Controllers;

use App\Quiz\{Quiz, Topic, Level};
use App\Admin;
use App\Notifications\QuizCompleted;
use App\Http\Requests\QuizForm;
use App\Filters\QuizFilters;
use Illuminate\Http\Request;

class QuizzesController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request, QuizFilters $filters)
    {
        $quizzes = Quiz::published()->latest()->filter($filters)->paginate(12);
        $levels = Level::all();
        $topics = Topic::all();

        return view('quizzes.index', compact(['quizzes', 'levels', 'topics']));
    }

    public function topic(Topic $topic)
    {
        $topics = Topic::exclude([$topic->id])->get();
        $quizzes = Quiz::published()->latest()->byTopic($topic)->paginate(12);

        return view('quizzes.topic', compact(['quizzes', 'topics', 'topic']));
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        $topics = Topic::all();
        $levels = Level::all();

        return view('admin.pages.quizzes.create', compact(['topics', 'levels']));
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
            'level_id' => $form->level_id,
            'description' => $form->description,
            'questions' => serialize($form->questions())
        ]);

        $quiz->topics()->attach($request->topics);

        $quiz->uploadCoverImage($request);

        return redirect(route('admin.quizzes.index'))->with('status', 'The quiz has been successfuly created!');
    }

    public function topicStore(Request $request)
    {
        $request->validate([
            'name' => 'required|unique:quiz_topics|max:255',
        ]);

        Topic::create([
            'slug' => str_slug($request->name),
            'name' => $request->name,
            'creator_id' => auth()->guard('admin')->user()->id
        ]);

        return redirect()->back()->with('status', "The topic has been successfully added!");
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Quiz  $quiz
     * @return \Illuminate\Http\Response
     */
    public function show(Quiz $quiz)
    {
        $suggestions = Quiz::exclude([$quiz->id])->suggestions(8)->get();

        if (! $quiz->published_at) {
            if (auth()->guard('admin')->check())
                return view('quizzes.show', compact(['quiz', 'suggestions']))->with('preview', true);

            abort(404);
        }

        if (traffic()->isRealVisitor())
            $quiz->increment('views');

        return view('quizzes.show', compact(['quiz', 'suggestions']));
    }

    public function feedback(Request $request, Quiz $quiz)
    {
        $feedback = $quiz->evaluate($request->answers);

        if (traffic()->isRealVisitor()) {
            $quiz->results()->create(['score' => $feedback['score']]);

            Admin::notifyAll(new QuizCompleted($quiz));
        }

        return view('components.games.feedback', compact('feedback'))->render();
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Quiz  $quiz
     * @return \Illuminate\Http\Response
     */
    public function edit(Quiz $quiz)
    {
        $topics = Topic::all();
        $levels = Level::all();

        return view('admin.pages.quizzes.edit', compact(['quiz', 'topics', 'levels']));
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
            'level_id' => $form->level_id,
            'description' => $form->description,
            'questions' => serialize($form->questions())
        ]);

        $quiz->topics()->sync($request->topics);

        $quiz->uploadCoverImage($request);

        return redirect()->back()->with('status', 'The quiz has been successfuly updated!');
    }

    public function topicUpdate(Request $request, Topic $topic)
    {
        $request->validate([
            'name' => 'required|max:255',
        ]);
        
        $topic->update([
            'slug' => str_slug($request->name),
            'name' => $request->name
        ]);

        return redirect()->back()->with('status', "The topic has been successfully updated!");
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

    public function topicDestroy(Topic $topic)
    {
        $topic->delete();

        return redirect()->back()->with('status', "The topic has been successfully deleted!");
    }
}
