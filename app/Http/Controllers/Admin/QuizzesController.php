<?php

namespace App\Http\Controllers\Admin;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Http\Requests\QuizForm;
use App\Quiz\{Quiz, Level, Topic};
use App\Files\Uploaders\ImageUpload;

class QuizzesController extends Controller
{
    public function create(Request $request)
    {
        $topics = Topic::all();
        $levels = Level::all();
        $returnFilters = \App\Support\AdminTableFilters::query(app(\App\Services\Admin\QuizzesTable::class)->filters($request));

        return view('admin.pages.quizzes.create', compact(['topics', 'levels', 'returnFilters']));
    }

    public function store(Request $request, QuizForm $form)
    {
        $filters = app(\App\Services\Admin\QuizzesTable::class)->filters(Request::create('/', 'GET', $request->query()));
        $quiz = \DB::transaction(function () use ($request, $form) {
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
            return $quiz;
        });

        return app(\App\Services\Admin\QuizzesTable::class)->redirect($filters)->with('status', 'Quiz created.');
    }

    public function topicStore(Request $request)
    {
        return app(\App\Services\Admin\QuizTopics::class)->save($request);
    }

    public function edit(Request $request, Quiz $quiz)
    {
        $topics = Topic::all();
        $levels = Level::all();
        $returnFilters = \App\Support\AdminTableFilters::query(app(\App\Services\Admin\QuizzesTable::class)->filters($request));

        return view('admin.pages.quizzes.edit', compact(['quiz', 'topics', 'levels', 'returnFilters']));
    }

    public function update(Request $request, Quiz $quiz, QuizForm $form)
    {
        $returnFilters = \App\Support\AdminTableFilters::query(app(\App\Services\Admin\QuizzesTable::class)->filters(Request::create('/', 'GET', $request->query())));
        \DB::transaction(function () use ($request, $quiz, $form) {
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
        });

        return redirect(route('admin.quizzes.edit', ['quiz' => $quiz] + $returnFilters))->with('status', 'Quiz saved.');
    }

    public function topicUpdate(Request $request, Topic $topic)
    {
        return app(\App\Services\Admin\QuizTopics::class)->save($request, $topic);
    }

    public function updateStatus(Request $request, Quiz $quiz, \App\Services\Admin\QuizzesTable $table)
    {
        if ($request->input('return_to') === 'directory') {
            $filters = $table->filters(Request::create('/', 'GET', $request->query()));
            $data = $request->validate(['published' => 'required|boolean']);
            \DB::transaction(function () use ($quiz, $data) {
                $record = Quiz::whereKey($quiz->id)->lockForUpdate()->firstOrFail();
                $record->update(['published_at' => $data['published'] ? ($record->published_at ?? now()) : null]);
            });
            return $table->redirect($filters)->with('status', 'Quiz publication saved.');
        }
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

    public function destroy(Request $request, Quiz $quiz, \App\Services\Admin\QuizzesTable $table)
    {
        $filters = $table->filters(Request::create('/', 'GET', $request->query()));
        $quiz->delete();

        return $table->redirect($filters)->with('status', 'Quiz deleted.');
    }

    public function topicDestroy(Request $request, Topic $topic)
    {
        return app(\App\Services\Admin\QuizTopics::class)->delete($request, $topic);
    }
}
