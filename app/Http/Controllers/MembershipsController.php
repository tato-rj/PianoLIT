<?php

namespace App\Http\Controllers;

use App\Billing\Membership;
use App\Events\Memberships\NewTrial;
use App\User;
use App\Http\Requests\AppleMembershipForm;
use Illuminate\Http\Request;
use App\Services\Apple\AppleValidator;
use App\Billing\Sources\{Apple, Stripe};
use App\Jobs\ValidateAppleMemberships;
use App\Events\Memberships\AppleMembershipsValidated;

class MembershipsController extends Controller
{
    /**
     * Checks the status of a subscription with Apple
     * @param  Request $request
     * @param  AppleMembershipForm $form
     * @return json        
     */
    public function store(Request $request, AppleMembershipForm $form)
    {
        \App\MembershipLog::create(['data' => json_encode($request->all())]);

        Apple::subscribe($form->user, $request);

        event(new NewTrial($form->user));

        if (app()->environment() == 'local')
            return redirect()->back()->with('status', "A susbcription was requested to {$form->user->first_name}'s profile.");

        return response()->json(true);
    }

    public function validateAll()
    {
        $this->authorize('validate', Membership::class);

        $users = User::exclude([284, 260, 249, 196])->noSuperUsers()->expired();

        if ($users->isEmpty())
            return redirect()->back()->with('error', "We found no expired subscriptions.");

        $this->dispatch(new ValidateAppleMemberships($users));
    
        return redirect()->back()->with('status', "Apple memberships are being validated, please allow a few moments to complete.");
    }

    public function validateUser(Request $request)
    {
        $user = User::find($request->user_id);

        if (! $user)
            return redirect()->back()->with('error', "Sorry, we couldn't find the user");

        $request = (new AppleValidator)->verify($user->membership->source->latest_receipt, $user->membership->source->password);

        $user->membership->source->validate($request);
    
        return redirect()->back()->with('success', "The has been successfully re-validated.");
    }

    /**
     * Retrieves entire membership history from a given user
     * @param  Request $request
     * @return json          
     */
    public function history(Request $request)
    {
        $validator = new AppleValidator;

        $user = User::findOrFail($request->user_id);

        $receipt = $validator->verify($user->membership->source->latest_receipt, $user->membership->source->password);

        $history = $validator->clean($receipt);

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

    public function logs()
    {
        return \App\MembershipLog::latest()->get();
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
