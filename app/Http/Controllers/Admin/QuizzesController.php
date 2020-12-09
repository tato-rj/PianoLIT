<?php

namespace App\Http\Controllers\Admin;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Http\Requests\QuizForm;
use App\Quiz\{Quiz, Level, Topic};
use App\Files\Uploaders\ImageUpload;

class QuizzesController extends Controller
{
    public function create()
    {
        $topics = Topic::all();
        $levels = Level::all();

        return view('admin.pages.quizzes.create', compact(['topics', 'levels']));
    }

    public function store(Request $request, QuizForm $form)
    {
        $quiz = Quiz::create([
            'creator_id' => auth()->guard('admin')->user()->id,
            'slug' => str_slug($form->title),
            'title' => $form->title,
            'cover_path' => (new ImageUpload($request))->take('cover_image')
                                                       ->for(Quiz::class)
                                                       ->name(str_slug($form->title))
                                                       ->withThumbnail()
                                                       ->cropped()
                                                       ->upload(),
            'level_id' => $form->level_id,
            'description' => $form->description,
            'questions' => serialize($form->questions())
        ]);

        $quiz->topics()->attach($request->topics);

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

    public function edit(Quiz $quiz)
    {
        $topics = Topic::all();
        $levels = Level::all();

        return view('admin.pages.quizzes.edit', compact(['quiz', 'topics', 'levels']));
    }

    public function update(Request $request, Quiz $quiz, QuizForm $form)
    {
        $quiz->update([
            'slug' => str_slug($form->title),
            'title' => $form->title,
            'level_id' => $form->level_id,
            'description' => $form->description,
            'questions' => serialize($form->questions()),
            'cover_path' => (new ImageUpload($request))->take('cover_image')
                                                       ->for($quiz)
                                                       ->name(str_slug($form->title))
                                                       ->withThumbnail()
                                                       ->cropped()
                                                       ->upload(),
        ]);

        $quiz->topics()->sync($request->topics);

        return redirect(route('admin.quizzes.edit', $quiz))->with('status', 'The quiz has been successfuly updated!');
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

    public function updateStatus(Quiz $quiz)
    {
        return $this->updateStatusFor($quiz);
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
