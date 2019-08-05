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

        $emails = Subscription::active()->get()->pluck('email')->toArray();

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
        if (Subscription::active()->byEmail($form->email)->exists() && ! $request->gift)
            return redirect()->back()->with('error', 'We already have this email in our subscription list.');

        Subscription::createOrActivate($form->email);

        if ($request->gift) {
            \Mail::to($form->email)->send(new Gift($request->gift));
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
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Subscription  $subscription
     * @return \Illuminate\Http\Response
     */
    public function updateStatus(Request $request, Subscription $subscription)
    {
        $subscription->updateStatus();

        return response()->json(['status' => 'The subscription is now ' . $subscription->status . '.']);
    }

    public function unsubscribe(Request $request)
    {
        Subscription::byEmail($request->email)->firstOrFail()->deactivate();

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
