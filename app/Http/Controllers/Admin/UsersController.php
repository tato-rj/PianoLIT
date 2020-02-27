<?php

namespace App\Http\Controllers\Admin;

use App\{Admin, User, Piece, Api};
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Log\Loggers\DailyLog;

class UsersController extends Controller
{
    public function index()
    {
        $users = User::latest()->get();

        return view('admin.pages.users.index', compact('users'));
    }

    public function show(User $user)
    {
        if (request('format') == 'json')
            return $user->membership;

        return view('admin.pages.users.show.index', ['user' => $user->load('favorites')]);
    }

    public function destroy(User $user)
    {
        $user->delete();

        return redirect(route('admin.users.index'))->with('status', 'The user has been successfully deleted.');
    }

    public function loadLogs(User $user, Request $request)
    {
        $type = $request->type;

        $logs = array_slice($user->log()->$type, $request->start_at);

        return view('admin.pages.users.show.logs.' . $type . '.rows', ['logs' => $logs, 'limit' => 5])->render();
    }

    public function loadFavorites(User $user, Request $request)
    {
        $pieces = $user->favorites->slice($request->start_at);

        return view('admin.pages.users.show.favorites.rows', ['pieces' => $pieces, 'limit' => 5])->render();
    }

    public function destroyMany(Request $request)
    {        
        foreach (json_decode($request->ids) as $id) {
            User::findOrFail($id)->delete();
        }

        return redirect(route('admin.users.index'))->with('status', 'The users have been successfully deleted.');
    }

    public function purge(User $user)
    {
        \Redis::del($user->redisKey('app'));
        \Redis::del($user->redisKey('web'));
        \Artisan::call('redis:refresh-daily-logs');

        $user->delete();

        return redirect(route('admin.users.index'))->with('status', 'The user has been successfully deleted and its logs removed.');
    }
}
