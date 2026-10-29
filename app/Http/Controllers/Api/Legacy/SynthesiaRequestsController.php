<?php

namespace App\Http\Controllers\Api\Legacy;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\{User, SynthesiaRequest};
use App\Events\SynthesiaRequests\NewRequest;

class SynthesiaRequestsController extends Controller
{
    public function __construct()
    {
        $this->middleware('log.app')->only('show');
    }

    public function show(Request $request)
    {
        $user = User::find($request->user_id);

        if (! $user)
            return null;

        $requests = $user->tutorialRequests;

        $requests->each(function($request, $index) use ($requests) {
            $requests[$index]->piece->request_published_at = $request->published_at ? $request->published_at->toFormattedDateString() : null;
            $requests[$index]->piece->request_created_at = $request->created_at->toFormattedDateString();
        });

        return $requests->pluck('piece');
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

        $synthesiaRequest = SynthesiaRequest::create([
            'user_id' => $request->user_id,
            'piece_id' => $request->piece_id,
            'types' => $request->video_types ? serialize($request->video_types) : null,
        ]);

        event(new NewRequest($synthesiaRequest));
        
        return response()->json('Your request has been received, we\'ll start working on it right away and it will be published within a few days. You\'ll receive an email when it is ready.');
    }
}
