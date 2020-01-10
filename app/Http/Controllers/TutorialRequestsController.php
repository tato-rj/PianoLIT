<?php

namespace App\Http\Controllers;

use App\{TutorialRequest, User};
use App\Events\Tutorials\{NewRequest, RequestPublished};
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
        if (request()->ajax())
            return TutorialRequest::datatable();

        return view('admin.pages.requests.index');
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

        $user = User::find($request->user_id);

        if ($user->pendingTutorialRequests()->exists())
            return response()->json('You have a pending request, please wait until we publish it before making a new one!', 403);

        if ($user->publishedTutorialRequests()->where('piece_id', $request->piece_id)->exists())
            return response()->json('Looks like you have already made a request for this piece, please send us an email if you were looking for something else.', 403);

        $tutorial = TutorialRequest::create([
            'user_id' => $request->user_id,
            'piece_id' => $request->piece_id
        ]);

        event(new NewRequest($tutorial));
        
        return response()->json('Your request has been received, we will send you an email when the tutorial is ready.');
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function simulate(Request $request)
    {
        if (production())
            abort(403);

        $request->validate([
            'user_id' => 'required|exists:users,id',
            'piece_id' => 'required|exists:pieces,id'
        ]);

        $user = User::find($request->user_id);

        if ($user->pendingTutorialRequests()->exists())
            return back()->with('error', 'You have a pending request, please wait until we publish it before making a new one!');

        if ($user->publishedTutorialRequests()->where('piece_id', $request->piece_id)->exists())
            return back()->with('error', 'Looks like you have already made a request for this piece, please send us an email if you were looking for something else.');

        $tutorial = TutorialRequest::create([
            'user_id' => $request->user_id,
            'piece_id' => $request->piece_id
        ]);

        event(new NewRequest($tutorial));
        
        return back()->with('status', 'Your request has been received, we will send you an email when the tutorial is ready.');
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
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\TutorialRequest  $tutorialRequest
     * @return \Illuminate\Http\Response
     */
    public function publish(TutorialRequest $tutorialRequest)
    {
        $tutorialRequest->update(['published_at' => now()]);

        event(new RequestPublished($tutorialRequest));

        return back()->with('status', 'The request has been published!');
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
