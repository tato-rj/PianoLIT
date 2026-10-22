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
            'name' => 'required|string|max:60',
            'clips' => 'required|array|size:4',
            'clips.*' => 'required|string|max:128060',
        ]);
        if (array_keys($data['clips']) !== [0, 1, 2, 3]) {
            throw \Illuminate\Validation\ValidationException::withMessages(['clips' => 'Record all four numbers.']);
        }

        return response()->json($recording->save(auth('web')->user(), $data['name'], $data['clips']))
            ->header('Cache-Control', 'private, no-store');
    }

    public function recording(string $voice, VoiceRecording $recording)
    {
        return response()->json($recording->find(auth('web')->user(), $voice))->header('Cache-Control', 'private, no-store');
    }

    public function rename(Request $request, string $voice, VoiceRecording $recording)
    {
        $data = $request->validate(['revision' => 'required|uuid', 'name' => 'required|string|max:60']);

        return response()->json($recording->rename(auth('web')->user(), $voice, $data['revision'], $data['name']))
            ->header('Cache-Control', 'private, no-store');
    }

    public function destroy(Request $request, string $voice, VoiceRecording $recording)
    {
        $data = $request->validate(['revision' => 'required|uuid']);
        $recording->delete(auth('web')->user(), $voice, $data['revision']);

        return response()->json(['deleted' => $voice])->header('Cache-Control', 'private, no-store');
    }
}
