<?php

namespace App\Http\Controllers;

use App\{User, Piece, Api, Admin, EmailList};
use App\Notifications\User\AccountDeleted;
use Illuminate\Http\Request;
use Illuminate\Foundation\Auth\ResetsPasswords;

class UsersController extends Controller
{
    use ResetsPasswords;

    public function __construct()
    {
        $this->middleware('auth', ['except' => ['appLogin', 'gift']]);
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        // $users = User::latest()->paginate(10);

        // return view('admin.pages.users.index', compact('users'));
    }

    public function gift()
    {
        $file = public_path(request('gift'));

        if (! file_exists($file))
            $file = public_path('images/gifts/circle-of-fifths.jpg');

        return response()->file($file);
    }

    public function profile()
    {
        return view('users.profile.index');
    }


    public function invite()
    {
        return view('users.invite');
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
    // public function store(Request $request)
    // {
    //     $validator = \Validator::make($request->all(), [
    //         'first_name' => 'required',
    //         'last_name' => 'required',
    //         'email' => 'required|email|unique:users',
    //         'password' => 'required|string|min:6|confirmed',
    //     ]);

    //     if ($validator->fails()) {
    //         return response()->json($validator->messages(), 403);
    //     }

    //     $user = User::create([
    //         'first_name' => $request->first_name,
    //         'last_name' => $request->last_name,
    //         'email' => $request->email,
    //         'password' => \Hash::make($request->password),
    //         'locale' => 'unknown',
    //         'age_range' => strtolower($request->age_range),
    //         'experience' => strtolower($request->experience),
    //         'preferred_piece_id' => $request->preferred_piece_id,
    //         'occupation' => strtolower($request->occupation),
    //         // 'email_verified_at' => now()
    //     ]);

    //     // // \Mail::to($user->email)->send(new \App\Mail\PianoLit\WelcomeEmail($user));

    //     if ($request->has('from_backend'))
    //         return redirect()->back()->with('status', "The user has been successfully created!");

    //     return $user;
    // }

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
        // if (request('format') == 'json')
        //     return $user->membership;

        // $pieces = Piece::orderBy('name')->get();

        // $pieces->each(function($piece) use ($user) {
        //     (new Api)->setCustomAttributes($piece, $user->id);
        // });

        // return view('admin.pages.users.show.index', compact(['user', 'pieces']));
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
        $this->authorize('update', $user);

        $request->validate([
            'email' => 'email',
            'password' => 'confirmed|min:8|nullable'
        ]);

        $subscription = $user->subscription;

        $user->update([
            'first_name' => $request->first_name,
            'last_name' => $request->last_name,
            'email' => $request->email
        ]);

        if ($request->password)
            $this->resetPassword($user, $request->password);

        if ($subscription)
            $subscription->update(['email' => $request->email]);

        return back()->with(['status' => 'Update successful.']);
    }

    public function updateSubscription(EmailList $list)
    {
        $list->toggle(auth()->user()->subscription);
        
        return response()->json(['status' => 'This subscription has been updated.']);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\User  $user
     * @return \Illuminate\Http\Response
     */
    public function destroy(User $user)
    {
        $this->authorize('update', $user);

        $user->delete();

        session()->flush();

        Admin::notifyAll(new AccountDeleted($user));

        return back()->with('status', 'The user has been successfully deleted');
    }
}
