<?php

namespace App\Http\Controllers\Admin;

use App\{Admin, User, Piece, Api};
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

class UsersController extends Controller
{
    public function index()
    {
        $users = User::exclude([260, 284])->latest()->get();
        $logs_total_count = ((new \App\Log\LogFactory)->total());

        return view('admin.pages.users.index', compact(['users', 'logs_total_count']));
    }

    public function show(User $user)
    {
        if (request('format') == 'json')
            return $user->membership;
        return $user->suggestions(10);
        return view('admin.pages.users.show.index', ['user' => $user->load('favorites')]);
    }

    public function destroy(User $user)
    {        
        $user->delete();

        return redirect(route('admin.users.index'))->with('status', 'The user has been successfully deleted');
    }

    public function destroyMany(Request $request)
    {        
        foreach (json_decode($request->ids) as $id) {
            User::findOrFail($id)->delete();
        }

        return redirect(route('admin.users.index'))->with('status', 'The users have been successfully deleted');
    }
}
