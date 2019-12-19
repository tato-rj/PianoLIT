<?php

namespace App\Http\Controllers;

use App\{TutorialRequest, User};
use Illuminate\Http\Request;

class TutorialRequestsController extends Controller
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
        $request->validate([
            'user_id' => 'required|exists:users,id',
            'piece_id' => 'required|exists:pieces,id'
        ]);

        auth()->login(User::find($request->user_id));

        $this->authorize('create', TutorialRequest::class);

        TutorialRequest::create([
            'user_id' => $request->user_id,
            'piece_id' => $request->piece_id
        ]);
        
        return response()->json(['message' => 'Your request has been received, we will send you an email when the tutorial is ready.']);
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\TutorialRequest  $tutorialRequest
     * @return \Illuminate\Http\Response
     */
    public function show(TutorialRequest $tutorialRequest)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\TutorialRequest  $tutorialRequest
     * @return \Illuminate\Http\Response
     */
    public function edit(TutorialRequest $tutorialRequest)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\TutorialRequest  $tutorialRequest
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, TutorialRequest $tutorialRequest)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\TutorialRequest  $tutorialRequest
     * @return \Illuminate\Http\Response
     */
    public function destroy(TutorialRequest $tutorialRequest)
    {
        //
    }
}
