<?php
namespace App\Http\Controllers\Games;

use App\Games\Duels\GameRegistry;
use App\Games\Player;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class LeaderboardsController extends Controller
{
    private function gameNames(): array
    {
        return collect(GameRegistry::GAMES)->map(fn ($class) => (new $class)->gameName())->values()->all();
    }

    private function result(Request $request, bool $posting = false): array
    {
        $rules = [
            'score' => 'required|integer|min:0|max:10000',
            'accuracy' => 'required|numeric|min:0|max:100',
            'rounds' => 'required|integer|min:2|max:12',
            'duration' => $posting ? ['required', 'string', 'regex:/^\d{2,3}:[0-5]\d$/D'] : 'required|integer|min:0|max:59999',
        ];
        if ($posting) {
            $rules += ['game' => ['required', Rule::in($this->gameNames())], 'username' => 'required|string|min:1|max:40',
                'avatar_url' => ['required', Rule::in(array_map(fn ($i) => asset('images/games/avatars/avatar-'.$i.'.svg'), range(1, 78)))]];
        }
        $data = $request->validate($rules);
        if ($posting) {
            [$minutes, $seconds] = array_map('intval', explode(':', $data['duration']));
            $data['duration'] = $minutes * 60 + $seconds;
        }
        return $data;
    }

    public function store(Request $request)
    {
        $data = $this->result($request, true);
        $slug = collect(GameRegistry::GAMES)->search(fn ($class) => (new $class)->gameName() === $data['game']);
        $return = $request->validate(['return_options' => 'sometimes|string|max:8000']);
        $options = [];
        if (isset($return['return_options'])) {
            $input = json_decode($return['return_options'], true);
            if (!is_array($input)) throw ValidationException::withMessages(['return_options' => 'Invalid game settings.']);
            $options = Validator::make($input, GameRegistry::settings($slug)->duelRules())->validate();
        }
        $player = new Player($data);
        $player->finalScore = $player->calculateFinalScore((object) $data);
        $player->save();
        // Always return to this game's own host. Never trust Referer for a score POST.
        return redirect()->route('games.'.$slug.'.play', $options)->with('newPlayer', $player);
    }

    public function show(Request $request)
    {
        $data = $request->validate(['game' => ['required', Rule::in($this->gameNames())], 'range' => ['nullable', Rule::in(['week', 'all'])]]);
        $leaderboard = Player::byGame($data['game'])->range($data['range'] ?? null)
            ->orderByDesc('finalScore')->orderBy('id')->limit(20)->get();
        return response()->view('games.challenges.components.leaderboard.list', compact('leaderboard'))->header('Cache-Control', 'no-store');
    }

    public function finalPoints(Request $request)
    {
        return response()->json((new Player)->calculateFinalScore((object) $this->result($request)));
    }
}
