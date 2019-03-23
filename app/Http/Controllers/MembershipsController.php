<?php

namespace App\Http\Controllers;

use App\{User, Membership};
use App\Heep\Requests\VerifySubscriptionForm;
use Illuminate\Http\Request;

class MembershipsController extends Controller
{
    /**
     * Checks the status of a subscription with Apple
     * @param  Request       $request
     * @param  VerifySubscriptionForm $form
     * @return json        
     */
    public function store(Request $request, VerifySubscriptionForm $form)
    {
        $form->user->subscribe($request);

        if (app()->environment() == 'local')
            return redirect()->back()->with('success', "A susbcription was requested to {$form->user->first_name}'s profile.");

        return response()->json(true);
    }

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

    /**
     * Retrieves entire membership history from a given user
     * @param  Request $request
     * @return json          
     */
    public function history(Request $request)
    {
        $user = User::findOrFail($request->user_id);

        $receipt = $user->callApple($user->membership->latest_receipt, $user->membership->password);

        $history = $user->cleanReceipt($receipt);

        return view('admin.pages.users.show.membership.history', compact('history'))->render();
    }

    /**
     * Remove a user's membership record (local environment only)
     * @param  User   $user 
     * @return redirect
     */
    public function destroy(User $user)
    {
        if (app()->environment() !== 'local')
            return null;
        
        $user->membership()->delete();
        $user->update(['trial_ends_at' => now()->addWeek()]);

        return redirect()->back()->with('success', "The membership has been successfully removed.");
    }
}
