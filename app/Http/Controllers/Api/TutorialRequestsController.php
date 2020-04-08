<?php

namespace App\Http\Controllers\Api;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\User;

class TutorialRequestsController extends Controller
{
    public function __construct()
    {
        $this->middleware('log.app');
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
}
