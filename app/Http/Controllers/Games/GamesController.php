<?php
namespace App\Http\Controllers\Games;

use App\Games\Duels\{Duel, DuelService, GameRegistry};
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class GamesController extends Controller
{
    public function home(Request $request)
    {
        $request->validate(['category' => 'nullable|string|max:40', 'range' => 'nullable|in:week,all']);
        $games = collect(GameRegistry::GAMES)->map(fn ($class) => new $class);
        return view('games.challenges.index', compact('games'));
    }

    public function play(Request $request, string $game)
    {
        abort_unless(isset(GameRegistry::GAMES[$game]), 404);
        $duelState = null;
        if ($request->filled('duel')) {
            $request->validate(['duel' => 'required|uuid']);
            $duel = Duel::findOrFail($request->query('duel'));
            $duelState = app(DuelService::class)->state($request, $duel);
            abort_unless($duel->game === $game, 409, 'Open the correct Duel game page.');
            $settings = GameRegistry::settings($game, $duel->settings);
        } else {
            $factory = GameRegistry::settings($game);
            $input = $request->query();
            foreach ($factory->replayOptions() as $name => $value) {
                // The reference replay URL uses name[]= to preserve an empty array.
                if (is_array($value) && isset($input[$name]) && in_array($input[$name], [[null], ['']], true)) {
                    $input[$name] = [];
                }
                if (is_bool($value) && isset($input[$name]) && is_string($input[$name])) {
                    $input[$name] = filter_var($input[$name], FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE) ?? $input[$name];
                }
            }
            $settings = GameRegistry::settings($game, Validator::make($input, $factory->duelRules())->validate());
        }
        return response()->view('games.challenges.'.$game.'.index', compact('settings', 'duelState'))
            ->header('Cache-Control', 'no-store, private');
    }
}
