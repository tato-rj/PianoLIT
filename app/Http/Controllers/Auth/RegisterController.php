<?php

namespace App\Http\Controllers\Auth;

use App\{User, Admin};
use App\Notifications\NewUser;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Foundation\Auth\RegistersUsers;
use Illuminate\Auth\Events\Registered;

class RegisterController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | Register Controller
    |--------------------------------------------------------------------------
    |
    | This controller handles the registration of new users as well as their
    | validation and creation. By default this controller uses a trait to
    | provide this functionality without requiring any additional code.
    |
    */

    use RegistersUsers;

    /**
     * Where to redirect users after registration.
     *
     * @var string
     */
    protected $redirectTo = '/';

    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('guest');
    }

    /**
     * Get a validator for an incoming registration request.
     *
     * @param  array  $data
     * @return \Illuminate\Contracts\Validation\Validator
     */
    protected function validator(array $data)
    {
        return Validator::make($data, [
            'first_name' => 'required|max:255',
            'last_name' => 'required|max:255',
            'email' => 'required|email|unique:users|max:255',
            'password' => 'required|string|min:8|confirmed',
        ]);
    }

    public function register(Request $request)
    {
        $validator = $this->validator($request->all());

        if ($validator->fails()) {
            return response()->json($validator->messages(), 403);
        }
        
        event(new Registered($user = $this->create($request->all())));

        Admin::notifyAll(new NewUser($quiz));

        $this->guard()->login($user);

        return $this->registered($request, $user);
    }

    /**
     * Create a new user instance after a valid registration.
     *
     * @param  array  $data
     * @return \App\User
     */
    protected function create(array $data)
    {
        return User::create([
            'first_name' => ucfirst($data['first_name']),
            'last_name' => ucfirst($data['last_name']),
            'email' => $data['email'],
            'password' => \Hash::make($data['password']),
            'locale' => 'unknown',
            'age_range' => array_key_exists('age_range', $data) ? strtolower($data['age_range']) : null,
            'experience' => array_key_exists('experience', $data) ? strtolower($data['experience']) : null,
            'preferred_piece_id' => array_key_exists('preferred_piece_id', $data) ? $data['preferred_piece_id'] : null,
            'occupation' => array_key_exists('occupation', $data) ? strtolower($data['occupation']) : null,
            'origin' => 'ios',
        ]);
    }

    protected function registered(Request $request, $user)
    {
        if ($user->origin == 'web')
            return back()->with('status', 'Your account successfully created! Please check your inbox to confirm your email.');
        
        return $user;
    }
}
