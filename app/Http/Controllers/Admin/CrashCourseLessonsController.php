<?php

namespace App\Http\Controllers\Admin;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\CrashCourse\{CrashCourse, CrashCourseLesson};
use App\Mail\CrashCourseEmail;

class CrashCourseLessonsController extends Controller
{
    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create(CrashCourse $crashcourse)
    {
        return view('admin.pages.crashcourses.lessons.create', compact('crashcourse'));
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request, CrashCourse $crashcourse)
    {
        $request->validate([
            'subject' => 'required|max:120',
            'body' => 'required'
        ]);

        $crashcourse->lessons()->create([
            'subject' => $request->subject,
            'body' => $request->body,
            'reading_time' => calculateReadingTime($request->body),
            'order' => $crashcourse->lessons_count
        ]);

        return redirect(route('admin.crashcourses.edit', $crashcourse))->with('status', "The lesson has been successfully created!");
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function duplicate(Request $request, CrashCourse $crashcourse, CrashCourseLesson $lesson)
    {
        $crashcourse->lessons()->create([
            'subject' => $lesson->subject . ' - copy',
            'body' => $lesson->body,
            'reading_time' => $lesson->reading_time,
            'order' => $crashcourse->lessons_count
        ]);

        return redirect(route('admin.crashcourses.edit', $crashcourse))->with('status', "The lesson has been successfully duplicated!");
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\CrashCourse\CrashCourseLesson  $lesson
     * @return \Illuminate\Http\Response
     */
    public function preview(CrashCourse $crashcourse, CrashCourseLesson $lesson)
    {
        return new CrashCourseEmail($lesson);
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\CrashCourse\CrashCourseLesson  $lesson
     * @return \Illuminate\Http\Response
     */
    public function sendTo(Request $request, CrashCourse $crashcourse, CrashCourseLesson $lesson)
    {
        \Mail::to($request->email)->send(new CrashCourseEmail($lesson, $request->email));

        return back()->with('status', 'A preview was sent to ' . $request->email);
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\CrashCourse\CrashCourseLesson  $lesson
     * @return \Illuminate\Http\Response
     */
    public function edit(CrashCourse $crashcourse, CrashCourseLesson $lesson)
    {
        return view('admin.pages.crashcourses.lessons.edit', compact(['crashcourse', 'lesson']));
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\CrashCourse\CrashCourseLesson  $lesson
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, CrashCourse $crashcourse,  CrashCourseLesson $lesson)
    {
        $request->validate([
            'subject' => 'required|max:120',
            'body' => 'required'
        ]);

        $lesson->update([
            'subject' => $request->subject,
            'body' => $request->body,
            'reading_time' => calculateReadingTime($request->body)
        ]);

        return redirect()->back()->with('status', "The lesson has been successfully updated!");
    }

    public function reorder(Request $request, CrashCourse $crashcourse)
    {
        foreach ($request->ids as $order => $id) {
            $crashcourse->lessons()->find($id)->update(['order' => $order]);
        }

        return response(200);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\CrashCourse\CrashCourseLesson  $lesson
     * @return \Illuminate\Http\Response
     */
    public function destroy(CrashCourse $crashcourse, CrashCourseLesson $lesson)
    {
        $lesson->delete();

        $crashcourse->lessons->each(function($lesson, $index) {
            $lesson->update(['order' => $index]);
        });

        return redirect(route('admin.crashcourses.edit', $crashcourse))->with('status', "The lesson has been successfully deleted!");
    }
}
