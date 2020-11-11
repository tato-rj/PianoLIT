<?php

namespace App\Http\Controllers\Admin;

use App\{Subscription, EmailList};
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

class SubscriptionsController extends Controller
{
    public function store(Request $request)
    {
        $request->validate([
            'emails' => 'required',
            'origin_url' => 'required',
        ]);

        $count = 0;
    	$emails = $this->clean($request->emails);

    	foreach ($emails as $email) {
    		if (valid_email($email)) {
    			Subscription::createOrActivate((object) ['email' => $email, 'origin_url' => $request->origin_url], $notifyUser = false);
    			$count += 1;
    		}
    	}

    	return back()->with('status', 'We subscribed or re-activated a total of ' . $count . ' ' . str_plural('email', $count) . '.');
    }

    public function export()
    {
        if (! request()->has('type'))
            abort(422, 'Please specify which format you need.');

        $ids = json_decode(request('ids'));

        if ($ids) {
            $emails = Subscription::find($ids)->pluck('email')->toArray();
        } elseif (request()->has('list_id')) {
            $emails = EmailList::find(request('list_id'))->subscribers->pluck('email')->toArray();
        } else {
            $emails = Subscription::all()->pluck('email')->toArray();
        }

        return view('admin.pages.subscriptions.exports.txt', compact('emails'));
    }
    
    public function clean($emails)
    {
    	return explode(',', preg_replace('/\s+/', '', $emails));
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
