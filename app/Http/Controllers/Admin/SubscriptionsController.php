<?php

namespace App\Http\Controllers\Admin;

use App\{Subscription, EmailList, User};
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Exports\Subscriptions\SubscriptionExport;

class SubscriptionsController extends Controller
{
    public function store(Request $request)
    {
        $request->validate([
            'emails' => 'required',
            'origin_url' => 'required',
        ]);

    	$emails = $this->clean($request->emails);

        if ($failedEmail = $this->cannotProccess($emails, $request))
            return back()->withErrors(['emails' => $failedEmail . ' is not a valid email.']);

    	return back()->with('status', 'We subscribed or re-activated a total of ' . count($emails) . ' ' . str_plural('email', count($emails)) . '.');
    }

    public function cannotProccess($emails, $request)
    {
        foreach ($emails as $email) {
            if (! valid_email($email))
                return $email;
        }

        foreach ($emails as $email) {
            Subscription::createOrActivate((object) ['email' => $email, 'origin_url' => $request->origin_url], $notifyUser = false);
        }
    }

    public function export(Request $request)
    {
        $emails = (new SubscriptionExport)->for('members')->get();

        return view('admin.pages.subscriptions.exports.txt', compact('emails'));
    }
    
    public function clean($emails)
    {
    	return explode(',', preg_replace('/\s+/', '', $emails));
    }

    public function findUser(Request $request)
    {
        $subscription = Subscription::findOrFail($request->id);
        $user = User::byEmail($subscription->email)->first();

        return view('admin.pages.subscriptions.user', compact('user'));
    }

    public function destroyMany(Request $request)
    {
        foreach($request->ids as $id) {
            Subscription::find($id)->delete();
        }

        return redirect()->back()->with('status', count($request->ids) . ' emails has been removed.');
    }

    public function destroy(Subscription $subscription)
    {
        $subscription->delete();

        return redirect()->back()->with('status', 'The email has been removed.');
    }
}
