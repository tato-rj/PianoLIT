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
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        //
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
     * Display the specified resource.
     *
     * @param  \App\Subscription  $subscription
     * @return \Illuminate\Http\Response
     */
    public function show(Subscription $subscription)
    {
        //
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

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Subscription  $subscription
     * @return \Illuminate\Http\Response
     */
    public function destroy(Subscription $subscription)
    {
        $subscription->delete();

        return redirect()->back()->with('status', 'The email has been removed.');
    }
}
