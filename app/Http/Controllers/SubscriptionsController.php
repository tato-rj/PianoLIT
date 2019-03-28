<?php

namespace App\Http\Controllers;

use App\Subscription;
use App\Http\Requests\SubscriptionForm;
use Illuminate\Http\Request;

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

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request, SubscriptionForm $form)
    {

        if (Subscription::active()->byEmail($form->email)->exists())
            return redirect()->back()->with('error', 'We already have this email in our subscription list.');

        Subscription::createOrActivate($form->email);

        return redirect()->back()->with('status', 'Thanks for subscribing!');
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
