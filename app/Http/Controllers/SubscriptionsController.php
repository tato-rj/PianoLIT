<?php

namespace App\Http\Controllers;

use App\{Subscription, EmailList};
use App\Http\Requests\SubscriptionForm;
use App\Mail\Gift;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class SubscriptionsController extends Controller
{
    /**
     * Instantiate a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('throttle:2')->only('store');
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request, SubscriptionForm $form)
    {
        if (EmailList::newsletter()->has($form->email) && ! $request->gift_url)
            return redirect()->back()->with('error', 'We already have this email in our subscription list.');

        $subscriber = Subscription::createOrActivate($form);

        if ($request->gift_url) {
            \Mail::to($subscriber->email)->send(new Gift($request->gift_url, $subscriber));
            $message = 'We just sent out the gift, check your mailbox!';
        } else {
            $message = 'Thanks for subscribing!';
        }

        return redirect()->back()->with('status', $message);
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Subscription  $subscription
     * @return \Illuminate\Http\Response
     */
    public function edit(Subscription $subscription)
    {
        return view('subscriptions.edit', compact('subscription'));
    }

    public function unsubscribe(Subscription $subscription, EmailList $list)
    {
        $list->remove($subscription);

        return view('subscriptions.unsubscribed');
    }
}
