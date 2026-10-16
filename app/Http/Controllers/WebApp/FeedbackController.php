<?php

namespace App\Http\Controllers\WebApp;

use App\{Admin, FeedbackSubmission};
use App\Http\Controllers\Controller;
use App\Notifications\FeedbackSubmittedNotification;
use App\Services\FeedbackContext;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class FeedbackController extends Controller
{
    public function store(Request $request)
    {
        $data = $request->validate([
            'rating' => ['required', 'string', Rule::in(array_keys(FeedbackSubmission::RATINGS))],
            'message' => ['nullable', 'string', 'max:2000'],
            'page_context' => ['nullable', 'string', 'max:4096'],
            'device_type' => ['nullable', 'string', Rule::in(['desktop', 'tablet', 'mobile'])],
        ]);

        DB::transaction(function () use ($request, $data) {
            $feedback = FeedbackSubmission::create([
                'user_id' => optional($request->user('web'))->id,
                'rating' => $data['rating'],
                'message' => $data['message'] ?? null,
                'context' => (new FeedbackContext)->fromSubmission($request, $data),
            ]);

            Admin::notifyAll(new FeedbackSubmittedNotification($feedback));
        });

        return response()->json(['saved' => true], 201);
    }
}
