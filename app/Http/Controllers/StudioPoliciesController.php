<?php

namespace App\Http\Controllers;

use App\StudioPolicy;
use Illuminate\Http\Request;

class StudioPoliciesController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        return view('studio-policies.index');
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        return view('studio-policies.create.index');
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $data = [
            'name' => $request->name,
            'start_month' => $request->start_month,
            'end_month' => $request->end_month,
            'years' => [$request->start_year, $request->end_year],
            'vacation_weeks' => 2,
            'makeup_weeks' => 3,
            'group_classes' => 3,
            'student_agreement' => true,
            'absence_notice' => 48,
        ];
        
        $pdf = \PDF::loadView('pdf.agreement.index', compact('data'));

        return $pdf->download('my-policy.pdf');

        // $request->validate(['data' => 'required']);

        // StudioPolicy::create([
        //     'user_id' => auth()->user()->id,
        //     'data' => json_encode($request->except('_token')),
        //     'theme' => $request->theme ?? 'default'
        // ]);

        // return back()->with('status', 'Your studio policy has been successfully created!');
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\StudioPolicy  $studioPolicy
     * @return \Illuminate\Http\Response
     */
    public function show(StudioPolicy $studioPolicy)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\StudioPolicy  $studioPolicy
     * @return \Illuminate\Http\Response
     */
    public function edit(StudioPolicy $studioPolicy)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\StudioPolicy  $studioPolicy
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, StudioPolicy $studioPolicy)
    {
        $this->authorize('update', $studioPolicy);

        // $request->validate(['data' => 'required']);

        $studioPolicy->update([
            'data' => json_encode($request->except('_token')),
            'theme' => $request->theme ?? 'default'
        ]);

        return back()->with('status', 'Your studio policy has been updated!');
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\StudioPolicy  $studioPolicy
     * @return \Illuminate\Http\Response
     */
    public function destroy(StudioPolicy $studioPolicy)
    {
        $this->authorize('update', $studioPolicy);
        
        $studioPolicy->delete();

        return back()->with('status', 'Your studio policy has been removed!');
    }
}
