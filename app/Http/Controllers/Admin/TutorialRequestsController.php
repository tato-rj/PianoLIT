<?php

namespace App\Http\Controllers\Admin;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Events\Tutorials\RequestPublished;
use App\TutorialRequest;

class TutorialRequestsController extends Controller
{
    public function index()
    {
        if (request()->ajax())
            return TutorialRequest::datatable();

        return view('admin.pages.requests.index');
    }

    public function publish(TutorialRequest $tutorialRequest)
    {
        $tutorialRequest->update(['published_at' => now()]);

        event(new RequestPublished($tutorialRequest));

        return back()->with('status', 'The request has been published!');
    }

    public function destroy(tutorialRequest $tutorialRequest)
    {
        $tutorialRequest->delete();

        return back()->with('status', 'The request has been deleted.');
    }
}
