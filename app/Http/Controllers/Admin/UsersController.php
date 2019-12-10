<?php

namespace App\Http\Controllers\Admin;

use App\{Admin, User, Piece, Api};
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

class UsersController extends Controller
{
    public function index()
    {
        $users = User::latest()->paginate(10);

        return view('admin.pages.users.index', compact('users'));
    }

    public function show(User $user)
    {
        if (request('format') == 'json')
            return $user->membership;

        $pieces = Piece::orderBy('name')->get();

        $pieces->each(function($piece) use ($user) {
            (new Api)->setCustomAttributes($piece, $user->id);
        });

        return view('admin.pages.users.show.index', compact(['user', 'pieces']));
    }

    public function destroy(User $user)
    {        
        $user->delete();

        return redirect(route('admin.users.index'))->with('status', 'The user has been successfully deleted');
    }
}
