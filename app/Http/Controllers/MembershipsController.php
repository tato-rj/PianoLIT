<?php

namespace App\Http\Controllers;

use App\{User, Membership};
use App\Http\Requests\VerifySubscriptionForm;
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
            return redirect()->back()->with('status', "A susbcription was requested to {$form->user->first_name}'s profile.");

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

        return redirect()->back()->with('status', "The trial has been update. It now expires on {$newDate->toFormattedDateString()}.");
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
    
        return redirect()->back()->with('status', "All users have been successfully re-validated.");
    }

    /**
     * Retrieves entire membership history from a given user
     * @param  Request $request
     * @return json          
     */
    public function history(Request $request)
    {
        $user = User::findOrFail($request->user_id);
return $user;
        $receipt = $user->callApple($user->membership->latest_receipt, $user->membership->password);

        $history = $user->cleanReceipt($receipt);

        return view('admin.pages.users.show.membership.history', compact('history'))->render();
    }

    /**
     * Checks the status of a user (app only)
     * @return string
     */
    public function status(Request $request)
    {
        $user = User::find($request->user_id);

        if (! $user)
            return response()->json(false);

        $status = $user->getStatus($callApple = true);

        return response()->json($status);
    }

    public function superStatus(Request $request, User $user)
    {
        $user->update(['super_user' => ! $user->super_user]);

        return response()->json(['status' => $user->fullName . '\'s super status has been updated.']);
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

        return redirect()->back()->with('status', "The membership has been successfully removed.");
    }
}
