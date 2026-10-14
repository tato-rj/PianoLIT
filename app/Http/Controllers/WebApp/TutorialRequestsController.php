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
        if (auth()->user()->pendingTutorialRequests()->exists()) {
            $this->outcome($request, 'tutorial_request_rejected', 'pending_request');
            return back()->with('error', 'You have a pending request, please wait until we publish it before making a new one!');
        }

        if (auth()->user()->publishedTutorialRequests()->where('piece_id', $piece->id)->exists()) {
            $this->outcome($request, 'tutorial_request_rejected', 'already_requested');
            return back()->with('error', 'Looks like you have already made a request for this piece, please send us an email if you were looking for something else.');
        }

        if (! auth()->user()->isAuthorized()) {
            $this->outcome($request, 'tutorial_request_rejected', 'membership_required');
            return redirect(route('webapp.membership.pricing'));
        }

        $tutorial = TutorialRequest::create([
            'user_id' => auth()->user()->id,
            'piece_id' => $piece->id,
            'types' => $request->video_types ? serialize($request->video_types) : null,
        ]);

        $this->outcome($request, 'tutorial_request_created', null, $tutorial->id);
        event(new NewRequest($tutorial));

        return back()->with('status', 'Your request has been received an it will be published within a few days. You\'ll receive an email when it is ready.');
    }

    private function outcome(Request $request, $type, $reason = null, $id = null)
    {
        $synthesia = $request->input('video_types') === ['Synthesia'];
        $attempt = $request->input('_activity_attempt');
        $request->attributes->set('activity.action', ['type' => $type, 'metadata' => [
            'control' => $synthesia ? 'synthesia-request-form' : 'tutorial-request-form',
            'reason' => $reason, 'request_id' => $id,
            // Correlation only; ownership still comes from the web session and page token.
            'attempt_id' => is_string($attempt) && \Illuminate\Support\Str::isUuid($attempt) ? $attempt : null,
        ]]);
    }
}
