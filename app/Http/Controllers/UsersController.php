<?php

namespace App\Http\Controllers;

use App\{User, Piece, Api};
use Illuminate\Http\Request;

class UsersController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $users = User::latest()->paginate(10);

        return view('admin.pages.users.index', compact('users'));
    }

    public function gift($gift)
    {
        if (! \Storage::disk('public')->exists('gifts/' . $gift))
            abort(404);

        return \Storage::disk('public')->download('gifts/' . $gift);
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $validator = \Validator::make($request->all(), [
            'first_name' => 'required',
            'last_name' => 'required',
            'email' => 'required|email|unique:users',
            'password' => 'required|string|min:6|confirmed',
        ]);

        if ($validator->fails()) {
            return response()->json($validator->messages(), 403);
        }

        $user = User::create([
            'first_name' => $request->first_name,
            'last_name' => $request->last_name,
            'email' => $request->email,
            'password' => \Hash::make($request->password),
            'locale' => $request->locale,
            'age_range' => strtolower($request->age_range),
            'experience' => strtolower($request->experience),
            'preferred_piece_id' => $request->preferred_piece_id,
            'occupation' => strtolower($request->occupation),
            'trial_ends_at' => now()->addWeek()
        ]);

        // \Mail::to($user->email)->send(new \App\Mail\PianoLit\WelcomeEmail($user));

        if ($request->has('from_backend'))
            return redirect()->back()->with('success', "The user has been successfully created!");

        return $user;
    }

    public function appLogin(Request $request)
    {
        $user = User::where('email', $request->email)->first();

        if ($user) {
            if (\Hash::check($request->password, $user->password))
                return response()->json($user);

            $error = ['Sorry, the password is not valid.'];

        } else {
            $error = ['This email is not registered.'];
        }

        $response = new \stdClass;
        $response->error = $error;

        return response()->json($response);
    }
    
    /**
     * Display the specified resource.
     *
     * @param  \App\User  $user
     * @return \Illuminate\Http\Response
     */
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

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\User  $user
     * @return \Illuminate\Http\Response
     */
    public function edit(User $user)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\User  $user
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, User $user)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\User  $user
     * @return \Illuminate\Http\Response
     */
    public function destroy(User $user)
    {
        //
    }
}
