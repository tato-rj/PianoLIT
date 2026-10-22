<?php

namespace App\Http\Controllers;

use App\Services\Metronome\VoiceRecording;
use Illuminate\Http\Request;

class MetronomeVoiceController extends Controller
{
    public function start()
    {
        return redirect()->route('metronome.home', ['record-voice' => 1]);
    }

    public function show(VoiceRecording $recording)
    {
        return response()->json($recording->forUser(auth('web')->user()))->header('Cache-Control', 'private, no-store');
    }

    public function store(Request $request, VoiceRecording $recording)
    {
        $data = $request->validate([
            'revision' => 'present|nullable|uuid',
            'clips' => 'required|array|size:4',
            'clips.*' => 'required|string|max:128060',
        ]);
        if (array_keys($data['clips']) !== [0, 1, 2, 3]) {
            throw \Illuminate\Validation\ValidationException::withMessages(['clips' => 'Record all four numbers.']);
        }

        return response()->json($recording->save(auth('web')->user(), $data['revision'], $data['clips']))
            ->header('Cache-Control', 'private, no-store');
    }

    public function destroy(Request $request, VoiceRecording $recording)
    {
        $data = $request->validate(['revision' => 'required|uuid']);
        $recording->delete(auth('web')->user(), $data['revision']);

        return response()->json(['revision' => null, 'clips' => []])->header('Cache-Control', 'private, no-store');
    }
}
