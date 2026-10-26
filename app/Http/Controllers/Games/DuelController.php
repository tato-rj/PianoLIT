<?php

namespace App\Http\Controllers\Games;

use App\Http\Controllers\Controller;
use App\Games\Duels\Duel;
use App\Games\Duels\DuelService;
use App\Games\Duels\GameRegistry;
use Illuminate\Http\Request;

class DuelController extends Controller
{
    public function __construct(private DuelService $duels) {}

    public function store(Request $request)
    {
        $data = $request->validate(['game' => 'required|string|max:40', 'settings' => 'required|array|max:40']);
        $settings = GameRegistry::snapshot($data['game'], $data['settings']);

        return $this->response($request, $this->duels->create($request, $data['game'], $settings));
    }

    public function join(Request $request)
    {
        $data = $request->validate(['code' => ['required', 'string', 'regex:/^\d{4}$/D']]);

        return $this->response($request, $this->duels->join($request, $data['code']));
    }

    public function show(Request $request, string $duel)
    {
        return $this->response($request, Duel::findOrFail($duel));
    }

    public function update(Request $request, string $duel, string $action)
    {
        $data = match ($action) {
            'connect', 'depart' => $request->validate(['connection_id' => ['required', 'string', 'regex:/^[0-9a-f]{32}$/D']]),
            'heartbeat' => $request->validate(['connection_id' => ['sometimes', 'string', 'regex:/^[0-9a-f]{32}$/D']]),
            'progress' => $request->validate(['sequence' => 'required|integer|min:1|max:100', 'progress' => 'required|integer|min:1|max:12', 'score' => 'required|integer|min:0|max:10000', 'checkpoint' => 'sometimes|array|max:16']),
            'finish' => $request->validate(['score' => 'required|integer|min:0|max:10000', 'accuracy' => 'required|integer|min:0|max:100']),
            'rematch' => $request->validate(['seed' => 'required|string|size:32']),
            default => [],
        };
        if (in_array($action, ['ready', 'progress', 'finish'])) {
            $data += $request->validate(['seed' => 'sometimes|string|size:32']);
        }

        return $this->response($request, $this->duels->mutate($request, $duel, $action, $data));
    }

    public function answer(Request $request, string $duel)
    {
        $data = $request->validate(['correct' => 'required|boolean']);
        $this->duels->answer($request, $duel, (bool) $data['correct']);

        return response()->json(['accepted' => true]);
    }

    private function response(Request $request, Duel $duel)
    {
        return response()->json($this->duels->state($request, $duel))->header('Cache-Control', 'no-store');
    }
}
