<?php

namespace App\Http\Controllers\Admin;

use App\User;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

class UsersController extends Controller
{
    public function index(Request $request)
    {
        return view('admin.pages.users.index', app(\App\Services\Admin\UsersTable::class)->get($request));
    }

    public function show(User $user)
    {
        if (request('format') == 'json')
            return $user->membership()->exists() ? $user->membership->source : null;

        return view('admin.pages.users.show.index', app(\App\Services\Admin\UserProfile::class)->get($user, request()));
    }

    public function destroy(User $user, Request $request)
    {
        $request->validateWithBag('deleteAccount', ['confirmation_email' => 'required|string|max:255']);
        app(\App\Services\Admin\RemoveUser::class)->remove($user->id, $request->input('confirmation_email'));

        return redirect(route('admin.users.index'))->with('status', 'The user has been successfully deleted.');
    }

    public function loadFavorites(User $user, Request $request)
    {
        $pieces = $user->favorites->slice($request->start_at)->take(5);

        return view('admin.pages.users.show.favorites.rows', ['pieces' => $pieces])->render();
    }

    public function loadRequests(User $user, Request $request)
    {
        $requests = $user->tutorialRequests->slice($request->start_at)->take(5);

        return view('admin.pages.users.show.requests.rows', ['requests' => $requests])->render();
    }

    public function destroyMany(Request $request)
    {
        abort(410, 'Bulk account deletion is no longer available. Use the individual user page.');
    }

    public function purge(User $user, Request $request)
    {
        $request->validateWithBag('purgeAccount', ['confirmation_email' => 'required|string|max:255']);
        app(\App\Services\Admin\RemoveUser::class)->remove($user->id, $request->input('confirmation_email'), true);

        return redirect(route('admin.users.index'))->with('status', 'The user has been successfully deleted and its logs removed.');
    }
}
