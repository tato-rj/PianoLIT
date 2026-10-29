<?php

namespace App\Http\Controllers\WebApp;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\{Piece, SynthesiaRequest};
use App\Events\SynthesiaRequests\NewRequest;

class SynthesiaRequestsController extends Controller
{
    public function store(Request $request, Piece $piece)
    {
        if (auth()->user()->pendingSynthesiaRequests()->exists()) {
            $this->outcome($request, 'synthesia_request_rejected', 'pending_request');
            return back()->with('error', 'You have a pending request, please wait until we publish it before making a new one!');
        }

        if (auth()->user()->publishedSynthesiaRequests()->where('piece_id', $piece->id)->exists()) {
            $this->outcome($request, 'synthesia_request_rejected', 'already_requested');
            return back()->with('error', 'Looks like you have already made a request for this piece, please send us an email if you were looking for something else.');
        }

        if (! auth()->user()->isAuthorized()) {
            $this->outcome($request, 'synthesia_request_rejected', 'membership_required');
            return redirect(route('webapp.membership.pricing'));
        }

        $synthesiaRequest = SynthesiaRequest::create([
            'user_id' => auth()->user()->id,
            'piece_id' => $piece->id,
            // The browser cannot select other video types, including on old open pages.
            'types' => serialize(['Synthesia']),
        ]);

        $this->outcome($request, 'synthesia_request_created', null, $synthesiaRequest->id);
        event(new NewRequest($synthesiaRequest));

        return back()->with('status', 'Your Synthesia request has been received and will be published within a few days. You\'ll receive an email when it is ready.');
    }

    private function outcome(Request $request, $type, $reason = null, $id = null)
    {
        $attempt = $request->input('_activity_attempt');
        $request->attributes->set('activity.action', ['type' => $type, 'metadata' => [
            'control' => 'synthesia-request-form',
            'reason' => $reason, 'request_id' => $id,
            // Correlation only; ownership still comes from the web session and page token.
            'attempt_id' => is_string($attempt) && \Illuminate\Support\Str::isUuid($attempt) ? $attempt : null,
        ]]);
    }
}
