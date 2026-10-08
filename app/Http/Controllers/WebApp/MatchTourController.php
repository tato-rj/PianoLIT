<?php

namespace App\Http\Controllers\WebApp;

use App\Http\Controllers\Controller;
use App\Services\WebApp\MatchQuiz;
use App\Services\WebApp\MatchTour;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class MatchTourController extends Controller
{
    public function result(Request $request, MatchTour $tour, MatchQuiz $quiz)
    {
        $request->validate(['draw' => 'required|string|max:4096']);
        $draw = $tour->drawChoices($request->input('draw'));
        $ids = $draw['ids'] ?? null;
        abort_unless($ids, 422, 'The listening choices have expired or changed. Start over.');
        $rounds = intdiv(count($ids) - 4, 2);
        $rules = [
            'levelPiece' => [$draw['levels'] ? 'required' : 'nullable', 'integer', Rule::in(array_values($draw['levels']))],
            'preferredPiece' => ['required', 'integer', Rule::in(array_slice($ids, 0, 4))],
            'reading' => 'required|array|size:2',
            'reading.0' => 'present|nullable|boolean',
            'reading.1' => 'present|nullable|boolean',
            'winners' => 'required|array|size:'.$rounds,
            'mood' => ['nullable', Rule::in(array_keys(MatchTour::MOODS))],
            'intent' => ['required_without:mood', 'nullable', Rule::in(array_keys(MatchTour::INTENTS))],
        ];
        for ($i = 0; $i < $rounds; $i++) {
            $rules['winners.'.$i] = ['present', 'nullable', 'integer', Rule::in(array_slice($ids, 4 + $i * 2, 2))];
        }
        $answers = $request->validate($rules);
        $answers['playingLevel'] = isset($answers['levelPiece']) ? array_search((int) $answers['levelPiece'], $draw['levels'], true) : null;
        // Derive the level server-side; never trust a submitted level or user identity.
        $piece = $quiz->getKeywords($tour->keywords($answers))->exclude($tour->exclusions($answers))->search(true, true);
        abort_unless($piece, 503, 'No match is available right now.');
        $piece->loadMissing(['composer', 'tags', 'tutorials']);
        $explanation = $tour->explanation($answers, $quiz->matchContext($piece));
        // Reuse shared-mood recommendations at the match's exact difficulty; period can vary.
        $recommendations = $tour->recommendations($piece);
        if ($recommendations->count() < 2) $recommendations = collect();
        return view('webapp.tour.result', compact('piece', 'recommendations', 'explanation'));
    }
}
