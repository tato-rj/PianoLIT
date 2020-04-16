<?php

namespace App\Http\Controllers;

use App\{TutorialRequest, User};
use App\Events\Tutorials\NewRequest;
use Illuminate\Http\Request;

class TutorialRequestsController extends Controller
{
    public function simulate(Request $request)
    {
        if (production())
            abort(403);

        return $this->store($request);
    }

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
        
        return response()->json('Your request has been received, we\'ll start working on it right away and it will be published within a few days. You\'ll receive an email when it is ready.');
    }
}
