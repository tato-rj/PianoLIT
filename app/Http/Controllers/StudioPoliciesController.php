<?php

namespace App\Http\Controllers;

use App\StudioPolicy;
use Illuminate\Http\Request;
use App\Events\StudioPolicyCreated;

class StudioPoliciesController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        return view('users.studio-policies.index');
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        return view('users.studio-policies.create.index');
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $policy = StudioPolicy::create([
            'user_id' => auth()->user()->id,
            'data' => json_encode($request->except('_token')),
            'theme' => $request->theme ?? 'default'
        ]);

        event(new StudioPolicyCreated($policy));

        return redirect(route('users.studio-policies.index'))->with('status', 'Your studio policy has been successfully created!');
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\StudioPolicy  $studioPolicy
     * @return \Illuminate\Http\Response
     */
    public function show(StudioPolicy $studioPolicy)
    {
        if (request()->has('preview'))
            return view('pdf.studio-policy.index', ['policy' => $studioPolicy]);

        try {
            return $studioPolicy->download();
        } catch (\Exception $e) {
            dd($e);
            return back()->with('error', 'We had problems generating your policy. If this issue persists, please let us know at contact@pianolit.com.');
        }
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\StudioPolicy  $studioPolicy
     * @return \Illuminate\Http\Response
     */
    public function edit(StudioPolicy $studioPolicy)
    {
        // return $studioPolicy;
        return view('users.studio-policies.edit', compact('studioPolicy'));
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
