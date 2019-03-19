<?php

namespace App\Http\Controllers;

use App\User;
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
}
