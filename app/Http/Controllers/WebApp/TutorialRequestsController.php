<?php

namespace App\Http\Controllers\WebApp;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\{Piece, TutorialRequest};
use App\Events\Tutorials\NewRequest;

class TutorialRequestsController extends Controller
{
    public function store(Request $request, Piece $piece)
    {
        if (auth()->user()->pendingTutorialRequests()->exists())
            return back()->with('error', 'You have a pending request, please wait until we publish it before making a new one!');

        if (auth()->user()->publishedTutorialRequests()->where('piece_id', $piece->id)->exists())
            return back()->with('error', 'Looks like you have already made a request for this piece, please send us an email if you were looking for something else.');

        $tutorial = TutorialRequest::create([
            'user_id' => auth()->user()->id,
            'piece_id' => $piece->id,
            'types' => $request->video_types ? serialize($request->video_types) : null,
        ]);

        event(new NewRequest($tutorial));

        return back()->with('status', 'Your request has been received an it will be published within a few days. You\'ll receive an email when it is ready.');
    }
}
