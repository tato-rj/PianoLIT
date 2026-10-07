<?php

namespace App\Http\Controllers\WebApp;

use App\Http\Controllers\Controller;
use App\Resources\FindYourMatch\Quiz;
use App\Services\WebApp\MatchTour;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class MatchTourController extends Controller
{
    public function result(Request $request, MatchTour $tour, Quiz $quiz)
    {
        $data = $tour->data();
        abort_unless($data['ready'], 503, 'The tour is temporarily unavailable.');
        $ids = array_column($data['pieces'], 'id');
        $answers = $request->validate([
            'preferredPiece' => ['required', 'integer', Rule::in(array_slice($ids, 0, 4))],
            'reading' => 'required|array|size:2',
            'reading.0' => 'present|nullable|boolean',
            'reading.1' => 'present|nullable|boolean',
            'winners' => 'required|array|size:3',
            'winners.0' => ['present', 'nullable', 'integer', Rule::in(array_slice($ids, 4, 2))],
            'winners.1' => ['present', 'nullable', 'integer', Rule::in(array_slice($ids, 6, 2))],
            'winners.2' => ['present', 'nullable', 'integer', Rule::in(array_slice($ids, 8, 2))],
            'mood' => ['nullable', Rule::in(array_keys(MatchTour::MOODS))],
            'intent' => ['required_without:mood', 'nullable', Rule::in(array_keys(MatchTour::INTENTS))],
        ]);
        // Derive the level server-side; never trust a submitted level or user identity.
        $piece = $quiz->getKeywords($tour->keywords($answers))->exclude($tour->exclusions($answers))->search(true);
        abort_unless($piece, 503, 'No match is available right now.');
        $piece->loadMissing(['composer', 'tags', 'tutorials']);
        // Reuse the existing More like this recommendations, with a short web presentation.
        $recommendations = ($piece->level && $piece->period ? $piece->similar(true, true, true) : collect())->reject(function ($other) use ($piece) { return $other->id === $piece->id; })->take(4)->values();
        return view('webapp.tour.result', compact('piece', 'recommendations'));
    }
}
