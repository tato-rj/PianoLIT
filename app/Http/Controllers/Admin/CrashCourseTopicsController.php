<?php

namespace App\Http\Controllers\Admin;

use App\CrashCourse\CrashCourseTopic;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class CrashCourseTopicsController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $topics = CrashCourseTopic::all();

        return view('admin.pages.crashcourses.topics.index', compact('topics'));
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|unique:crash_course_topics|max:255',
        ]);

        CrashCourseTopic::create([
            'slug' => str_slug($request->name),
            'name' => $request->name,
            'creator_id' => auth()->guard('admin')->user()->id
        ]);

        return redirect()->back()->with('status', "The topic has been successfully created!");
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\CrashCourse\CrashCourseTopic  $topic
     * @return \Illuminate\Http\Response
     */
    public function show(CrashCourseTopic $topic)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\CrashCourse\CrashCourseTopic  $topic
     * @return \Illuminate\Http\Response
     */
    public function edit(CrashCourseTopic $topic)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\CrashCourse\CrashCourseTopic  $topic
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, CrashCourseTopic $topic)
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

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\CrashCourse\CrashCourseTopic  $topic
     * @return \Illuminate\Http\Response
     */
    public function destroy(CrashCourseTopic $topic)
    {
        $topic->delete();

        return redirect()->back()->with('status', "The topic has been successfully deleted!");
    }
}
