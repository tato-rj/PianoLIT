<?php

namespace App\Http\Controllers\Admin;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\CrashCourse\{CrashCourse, CrashCourseTopic};

class CrashCoursesController extends Controller
{
    public function index()
    {
        if (request()->ajax())
            return CrashCourse::datatable();

        return view('admin.pages.crashcourses.index');
    }

    public function create()
    {
        $topics = CrashCourseTopic::all();

        return view('admin.pages.crashcourses.create', compact(['topics']));
    }

    public function store(Request $request)
    {
        $request->validate([
            'title' => 'required|min:4|max:120',
            'description' => 'required|max:238',
            'cover_image' => 'sometimes|required|mimes:jpeg,jpg'
        ]);

        $crashcourse = CrashCourse::create([
            'creator_id' => auth()->guard('admin')->user()->id,
            'slug' => str_slug($request->title),
            'title' => $request->title,
            'description' => $request->description
        ]);

        $crashcourse->topics()->attach($request->topics);

        $crashcourse->uploadCoverImage($request);

        return redirect(route('admin.crashcourses.edit', $crashcourse))->with('status', 'The crashcourse has been successfuly created!');
    }

    public function edit(CrashCourse $crashcourse)
    {
        $topics = CrashCourseTopic::all();

        return view('admin.pages.crashcourses.edit.index', compact(['crashcourse', 'topics']));
    }

    public function update(Request $request, CrashCourse $crashcourse)
    {
        $request->validate([
            'title' => 'required|min:4|max:120',
            'description' => 'required|max:238',
            'cover_image' => 'sometimes|required|mimes:jpeg,jpg'
        ]);

        $crashcourse->update([
            'slug' => str_slug($request->title),
            'title' => $request->title,
            'description' => $request->description,
        ]);

        $crashcourse->topics()->sync($request->topics);

        $crashcourse->uploadCoverImage($request);

        return redirect()->back()->with('status', 'The course has been successfuly updated!');
    }

    public function updateStatus(Request $request, CrashCourse $crashcourse)
    {
        $crashcourse->updateStatus();

        return response()->json(['status' => 'The Crash Course has been ' . $crashcourse->status . '.']);
    }

    public function destroy(CrashCourse $crashcourse)
    {
        $crashcourse->delete();

        return redirect()->back()->with('status', 'The course has been successfuly deleted!');
    }
}
