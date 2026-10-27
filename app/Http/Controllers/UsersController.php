<?php

namespace App\Http\Controllers;

use App\{User, EmailList};
use Illuminate\Http\Request;

class UsersController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth', ['except' => ['gift']]);
    }

    public function gift()
    {
        $requested = request('gift');
        $file = is_string($requested) && strpos($requested, "\0") === false
            ? realpath(public_path($requested)) : false;
        $allowed = false;

        foreach ([public_path('images/gifts'), storage_path('app/public/gifts')] as $directory) {
            $directory = realpath($directory);
            if ($file && $directory && strpos($file, $directory.DIRECTORY_SEPARATOR) === 0 && is_file($file)) {
                $allowed = true;
                break;
            }
        }

        if (! $allowed)
            $file = public_path('images/gifts/circle-of-fifths.jpg');

        abort_unless(is_file($file), 404);

        return response()->file($file);
    }

    public function profile()
    {
        return redirect(\App\Support\ProfileUrl::get(), 301);
    }

    public function purchases()
    {
        $purchases = auth()->user()->purchases()->with('item')->orderByDesc('id')->paginate(8)->withQueryString();

        return view('users.purchases.index', compact('purchases'));
    }

    public function invite()
    {
        return view('users.invite');
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

        $accounts = app(\App\Auth\Google\Accounts::class);
        if ($request->filled('password') && !$accounts->hasPassword($user)) return redirect('/auth/google/password');
        $sensitive = $request->filled('password') || ($request->has('email') && $request->email !== $user->email);
        if ($sensitive && !$accounts->recent($request, $user)) {
            if (!app(\App\Auth\Google\Configuration::class)->ready()) return redirect(\App\Support\ProfileUrl::get('security'));
            return app(\App\Http\Controllers\Auth\GoogleController::class)->confirm($request, 'profile');
        }
        $request->validate(['first_name' => 'required|string|max:200', 'last_name' => 'nullable|string|max:200',
            'email' => ['required', 'string', 'email', 'max:255', \Illuminate\Validation\Rule::unique('users')->ignore($user->id)],
            'password' => 'nullable|string|min:8|max:1024|confirmed']);
        $profile = app(ProfileController::class);
        if ($request->filled('password')) $profile->password($request);
        $result = $profile->details($request);
        // Legacy forms share the new proof-of-address flow too.
        if (mb_strtolower($request->email) !== mb_strtolower($user->email)) {
            return $profile->email($request, app(\App\Services\Profile\EmailChange::class));
        }
        return $result;
    }

    public function updateSubscription(Request $request, EmailList $list)
    {
        if ($request->has('enabled')) $request->validate(['enabled' => 'required|boolean']);
        $subscriber = $request->user('web')->subscription;
        $enabled = $request->has('enabled') ? $request->boolean('enabled') : !($subscriber && $subscriber->in($list));
        app(\App\Services\Profile\EmailPreferences::class)->save($request->user('web')->id, $list, $enabled);

        return view('components.feedback.alert', [
            'color' => 'green',
            'message' => 'Your subscription has been updated',
            'temporary' => true,
            'dismissible' => true,
            'floating' => 'top'
        ])->render();
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\User  $user
     * @return \Illuminate\Http\Response
     */
    public function destroy(Request $request, User $user)
    {
        $this->authorize('update', $user);

        return app(ProfileController::class)->destroy($request, app(\App\Services\Profile\CloseAccount::class));
    }
}
