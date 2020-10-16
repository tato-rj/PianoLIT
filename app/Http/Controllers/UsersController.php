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
        $file = public_path(request('gift'));

        if (! file_exists($file))
            $file = public_path('images/gifts/circle-of-fifths.jpg');

        return response()->file($file);
    }

    public function profile()
    {
        if (auth()->check() && auth()->user()->email == 'arthurvillar@gmail.com') {
            $array = auth()->user()->purchases->toArray();
            $purchases = $request->start_at ? collect(array_slice($array, $request->start_at, count($array), true))->take(5) : [];
            return $purchases;
        }

        return view('users.profile.index');
    }

    public function purchases()
    {
        return view('users.purchases.index');
    }

    public function loadPurchases(Request $request)
    {
        $array = auth()->user()->purchases->toArray();

        $purchases = $request->start_at ? collect(array_slice($array, $request->start_at, count($array), true))->take(5) : [];

        return view('users.purchases.rows', compact('purchases'))->render();
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
        
        return view('components.alerts.alert', [
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
