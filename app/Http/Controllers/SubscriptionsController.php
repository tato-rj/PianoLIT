<?php

namespace App\Http\Controllers;

use App\Subscription;
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

        $emails = Subscription::activeList('newsletter_list')->get()->pluck('email')->toArray();

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
        if (Subscription::activeList('newsletter_list')->byEmail($form->email)->exists() && ! $request->gift)
            return redirect()->back()->with('error', 'We already have this email in our subscription list.');

        $subscriber = Subscription::createOrActivate($form);

        if ($request->gift) {
            \Mail::to($subscriber->email)->send(new Gift($request->gift, $subscriber));
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

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Subscription  $subscription
     * @return \Illuminate\Http\Response
     */
    public function toggleStatus(Request $request, Subscription $subscription)
    {
        $subscription->toggleStatusFor($request->list);

        return response()->json(['status' => 'The subscription is now ' . $subscription->getStatusFor($request->list) . '.']);
    }

    public function unsubscribe(Request $request)
    {
        Subscription::byEmail($request->email)->firstOrFail()->deactivate($request->list);

        return redirect()->back()->with('status', 'We\'re sorry to see you go!');
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
