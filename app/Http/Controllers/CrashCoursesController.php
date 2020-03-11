<?php

namespace App\Http\Controllers;

use App\CrashCourse\CrashCourse;
use App\Subscription;
use Illuminate\Http\Request;

class CrashCoursesController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        //
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
    public function show(CrashCourse $crashCourse)
    {
        //
    }

    public function signup(Request $request, CrashCourse $crashcourse)
    {
        $request->validate([
            'first_name' => 'required|max:255',
            'email' => 'required|email|max:255'
        ]);

        $subscription = Subscription::createOrActivate($request, $notifyUser = false, $joinAll = false);

        $crashcourse->signup($subscription, $request->first_name);
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
