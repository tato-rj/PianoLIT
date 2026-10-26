<?php
use Illuminate\Support\Facades\Route;
use App\Games\Duels\GameRegistry;

Route::get('/', 'Games\GamesController@home')->name('games.home');
Route::get('_connectivity', function () {
    return response('', 204)->header('Cache-Control', 'no-store');
})->name('games.connectivity');
foreach (array_keys(GameRegistry::GAMES) as $game) {
    Route::get($game, 'Games\GamesController@play')->defaults('game', $game)->name('games.'.$game.'.play');
}
Route::prefix('leaderboard')->name('games.leaderboard.')->group(function () {
    Route::get('show', 'Games\LeaderboardsController@show')->name('show');
    Route::get('final-points', 'Games\LeaderboardsController@finalPoints')->name('final-points');
    Route::post('', 'Games\LeaderboardsController@store')->middleware(['auth:web', 'throttle:15,1'])->name('store');
});
// Hosts sign in to create rooms; all participants retain session-bound Duel credentials.
Route::prefix('duels')->name('games.duels.')->withoutMiddleware(\App\Http\Middleware\Logs\RecordWebLog::class)->group(function () {
    Route::post('', 'Games\DuelController@store')->middleware(['auth:web', 'throttle:10,1,game-duel-create:'])->block(10, 10)->name('store');
    Route::post('join', 'Games\DuelController@join')->middleware('throttle:15,1,game-duel-join:')->block(10, 10)->name('join');
    Route::get('{duel}', 'Games\DuelController@show')->whereUuid('duel')->middleware('throttle:300,1,game-duel-state:')->name('show');
    Route::post('{duel}/answer', 'Games\DuelController@answer')->whereUuid('duel')->middleware('throttle:120,1,game-duel-answer:')->name('answer');
    Route::post('{duel}/{action}', 'Games\DuelController@update')->whereUuid('duel')
        ->where('action', 'ready|progress|finish|rematch|cancel|leave|heartbeat|connect|depart')
        ->middleware('throttle:120,1,game-duel-update:')->name('update');
});
