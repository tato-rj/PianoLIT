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
        return view('users.profile.index');
    }

    public function purchases()
    {
        $purchases = auth()->user()->purchases()->paginate(8);

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
        
        return view('components.alert', [
            'color' => 'green',
            'message' => '<i class="fas fa-check-circle mr-2"></i>Your subscription has been updated',
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
    public function destroy(User $user)
    {
        $this->authorize('update', $user);

        $user->delete();

        session()->flush();

        Admin::notifyAll(new AccountDeleted($user));

        return back()->with('status', 'The user has been successfully deleted');
    }
}
