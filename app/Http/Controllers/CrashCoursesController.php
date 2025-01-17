<?php

namespace App\Http\Controllers;

use App\CrashCourse\{CrashCourse, CrashCourseTopic, CrashCourseSubscription};
use App\Http\Requests\CrashCourseForm;
use App\Subscription;
use Illuminate\Http\Request;
use App\Filters\CrashCourseFilters;

class CrashCoursesController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(CrashCourseFilters $filters)
    {
        $topics = CrashCourseTopic::all();
        $crashcourses = CrashCourse::published()->latest()->filter($filters)->paginate(12);

        return view('crashcourses.index', compact(['crashcourses', 'topics']));
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
        $video = config('services.googlecloud.crashcourses') . $request->path;

        return view('crashcourses.video', compact('video'));
    }

    public function signup(Request $request, CrashCourse $crashcourse, CrashCourseForm $form)
    {
        if ($crashcourse->hasActive($request->email))
            return redirect()->back()->with('error', 'You are already signed up for this course!');

        $subscription = Subscription::createOrActivate($request, $notifyUser = false, $joinAll = false);

        $crashcourse->signup($subscription, $request->first_name);

        return redirect()->back()->with('status', 'Thanks for signing up! Please check your email for the first lesson.');
    }

    public function cancel(Request $request)
    {
        $subscription = CrashCourseSubscription::byEmail($request->email)->active();

        if (! $subscription->exists())
            return 'You are no longer receiving emails from this course';

        $subscription->first()->cancel();

        return 'Sorry to see you go! You will no longer receive these emails';
    }
}
