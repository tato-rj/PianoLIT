<?php

namespace App\Http\Controllers\Admin;

use App\Subscription;
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

    public function clean($emails)
    {
    	return explode(',', preg_replace('/\s+/', '', $emails));
    }
}
