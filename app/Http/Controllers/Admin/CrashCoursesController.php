<?php

namespace App\Http\Controllers\Admin;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\CrashCourse\{CrashCourse, CrashCourseTopic};
use App\Mail\CrashCourseFeedbackEmail;
use App\Files\Uploaders\ImageUpload;
use App\Services\Admin\CrashCoursesTable;
use Illuminate\Support\Facades\DB;

class CrashCoursesController extends Controller
{
    public function index(Request $request, CrashCoursesTable $table)
    {
        if (request()->ajax())
            return CrashCourse::datatable();

        return view('admin.pages.crashcourses.index', $table->get($request));
    }

    public function create(Request $request, CrashCoursesTable $table)
    {
        $topics = CrashCourseTopic::all();
        $returnFilters = $table->state($request);

        return view('admin.pages.crashcourses.editor', compact('topics', 'returnFilters'));
    }

    public function store(Request $request)
    {
        $returnFilters = app(CrashCoursesTable::class)->state($request);
        $request->validate([
            'title' => 'required|min:4|max:120',
            'description' => 'required|max:238',
            'cover_image' => 'sometimes|required|mimes:jpeg,jpg'
        ]);

        $crashcourse = CrashCourse::create([
            'creator_id' => auth()->guard('admin')->user()->id,
            'slug' => str_slug($request->title),
            'title' => $request->title,
            'cover_path' => (new ImageUpload($request))->take('cover_image')
                                                       ->for(CrashCourse::class)
                                                       ->name(str_slug($request->title))
                                                       ->withThumbnail()
                                                       ->cropped()
                                                       ->upload(),
            'description' => $request->description
        ]);

        $crashcourse->topics()->attach($request->topics);

        return redirect(route('admin.crashcourses.edit', ['crashcourse' => $crashcourse] + $returnFilters))->with('status', 'Course created.');
    }

    public function feedbackPreview(CrashCourse $crashcourse)
    {
        return new CrashCourseFeedbackEmail($crashcourse);
    }

    public function feedbackSendTo(Request $request, CrashCourse $crashcourse)
    {
        $data = $request->validate(['email' => 'required|email|max:254']);
        \Mail::to($data['email'])->send(new CrashCourseFeedbackEmail($crashcourse));

        return back()->with('status', 'A preview was sent to '.e($data['email']));
    }

    public function edit(Request $request, CrashCourse $crashcourse, CrashCoursesTable $table)
    {
        $topics = CrashCourseTopic::all();
        $returnFilters = $table->state($request);
        $crashcourse->load(['creator', 'topics', 'lessons']);
        $activeSubscribers = $crashcourse->subscriptions()->whereNull('completed_at')->whereNull('cancelled_at')->count();

        return view('admin.pages.crashcourses.editor', compact('crashcourse', 'topics', 'returnFilters', 'activeSubscribers'));
    }

    public function update(Request $request, CrashCourse $crashcourse)
    {
        $returnFilters = app(CrashCoursesTable::class)->state($request);
        $request->validate([
            'title' => 'required|min:4|max:120',
            'description' => 'required|max:238',
            'cover_image' => 'sometimes|required|mimes:jpeg,jpg'
        ]);

        $crashcourse->update([
            'slug' => str_slug($request->title),
            'title' => $request->title,
            'description' => $request->description,
            'cover_path' => (new ImageUpload($request))->take('cover_image')
                                                       ->for($crashcourse)
                                                       ->name(str_slug($request->title))
                                                       ->withThumbnail()
                                                       ->cropped()
                                                       ->upload(),
        ]);

        $crashcourse->topics()->sync($request->topics);

        return redirect(route('admin.crashcourses.edit', ['crashcourse' => $crashcourse] + $returnFilters))->with('status', 'Course saved.');
    }

    public function updateStatus(Request $request, CrashCourse $crashcourse, CrashCoursesTable $table)
    {
        if ($request->input('return_to') === 'directory') {
            $filters = $table->filters(Request::create('/', 'GET', $request->query()));
            $data = $request->validate(['published' => 'required|boolean']);
            DB::transaction(function () use ($crashcourse, $data) {
                $record = CrashCourse::whereKey($crashcourse->id)->lockForUpdate()->firstOrFail();
                $record->update(['published_at' => $data['published'] ? ($record->published_at ?? now()) : null]);
            });
            return $table->redirect($filters)->with('status', 'Publication saved.');
        }
        return $this->updateStatusFor($crashcourse);
    }

    public function destroy(Request $request, CrashCourse $crashcourse, CrashCoursesTable $table)
    {
        $filters = $table->filters(Request::create('/', 'GET', $request->query()));
        DB::transaction(fn () => $crashcourse->delete());

        return $table->redirect($filters)->with('status', 'Course deleted.');
    }
}
