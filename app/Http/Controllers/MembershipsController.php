<?php

namespace App\Http\Controllers;

use App\{User, Membership};
use Illuminate\Http\Request;

class MembershipsController extends Controller
{
    /**
     * Updates the user's trial period
     * @param  User $user
     * @return redirect
     */
    public function updateTrial(Request $request, User $user)
    {
        $newDate = $user->trial_ends_at->gte(now()) ? $user->trial_ends_at->addWeek() : now()->addWeek();

        $user->update(['trial_ends_at' => $newDate]);

        // \Mail::to($user->email)->send(new \App\Mail\PianoLit\TrialExtendedEmail($user));

        return redirect()->back()->with('success', "The trial has been update. It now expires on {$newDate->toFormattedDateString()}.");
    }

    public function validateAll()
    {
        $this->authorize('validate', Membership::class);

        $users = User::expired();

        if ($users->isEmpty())
            return redirect()->back()->with('error', "We found no expired subscriptions.");

        foreach ($users as $user) {
            $request = $user->callApple($user->membership->latest_receipt, $user->membership->password);

            $user->membership->validate($request);   
        }
    
        return redirect()->back()->with('success', "All users have been successfully re-validated.");
    }
}
