<?php

namespace App\Http\Controllers\WebApp;

use App\Http\Controllers\Controller;
use App\Services\WebApp\MetronomePreferences;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class MetronomePreferencesController extends Controller
{
    public function show(MetronomePreferences $preferences)
    {
        return response()->json(['preferences' => $preferences->forUser(auth('web')->user())])->header('Cache-Control', 'private, no-store');
    }

    public function update(Request $request, MetronomePreferences $preferences)
    {
        $data = $request->validate([
            'preferences' => 'required|array:tempo,beats,sound|min:1',
            'preferences.tempo' => 'sometimes|required|integer|between:20,200',
            'preferences.beats' => 'sometimes|required|integer|between:1,4',
            'preferences.sound' => ['sometimes', 'required', 'string', Rule::in(MetronomePreferences::SOUNDS)],
        ]);

        return response()->json(['preferences' => $preferences->save(auth('web')->user(), $data['preferences'])])->header('Cache-Control', 'private, no-store');
    }
}
