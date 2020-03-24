<?php

namespace App\Http\Controllers;

use App\CrashCourse\{CrashCourse, CrashCourseSubscription};
use App\Http\Requests\CrashCourseForm;
use App\Subscription;
use Illuminate\Http\Request;

class CrashCoursesController extends Controller
{
    protected $googleCloud = 'https://storage.googleapis.com/pianolit-app/crashcourses/';

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $crashcourses = CrashCourse::published()->latest()->paginate(12);

        return view('crashcourses.index', compact('crashcourses'));
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
        //
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\CrashCourse  $crashCourse
     * @return \Illuminate\Http\Response
     */
    public function show(CrashCourse $crashcourse)
    {
        return view('crashcourses.show', compact('crashcourse'));
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\CrashCourse  $crashCourse
     * @return \Illuminate\Http\Response
     */
    public function video(Request $request)
    {
        // revolutionary/lesson-1-01.mp4
        $video = $this->googleCloud . $request->path;

        return view('crashcourses.video', compact('video'));
    }

    public function signup(Request $request, CrashCourse $crashcourse, CrashCourseForm $form)
    {
        if ($crashcourse->hasActive($request->email))
            return redirect()->back()->with('error', 'You are already signed up for this course!');

        $subscription = Subscription::createOrActivate($request, $notifyUser = false, $joinAll = false);

        $crashcourse->signup($subscription, $request->first_name);

        return redirect()->back()->with('status', 'Thanks for signing up!');
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\CrashCourse  $crashCourse
     * @return \Illuminate\Http\Response
     */
    public function edit(CrashCourse $crashCourse)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\CrashCourse  $crashCourse
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, CrashCourse $crashCourse)
    {
        //
    }

    public function cancel(Request $request)
    {
        $subscription = CrashCourseSubscription::byEmail($request->email)->active();

        if (! $subscription->exists())
            return 'You are no longer receiving emails from this course';

        $subscription->first()->cancel();

        return 'Sorry to see you go! You will no longer receive these emails';
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\CrashCourse  $crashCourse
     * @return \Illuminate\Http\Response
     */
    public function destroy(CrashCourse $crashCourse)
    {
        //
    }
}
