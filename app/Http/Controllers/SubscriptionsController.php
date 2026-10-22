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

    public function modal(Request $request)
    {
        $model = $request->productClass;

        $product = $model ? $model::findOrFail($request->productId) : null;

        if ($product && auth()->user() && auth()->user()->purchasesOf($product)->exists())
            return null;

        $view = $request->view;
        if ($view === 'blog-subscription' || ($view === 'subscription' && $request->getHost() === 'blog.'.config('app.short_url'))) {
            $view = 'newsletter-subscription';
        }

        return view('components.popups.popup', compact('view', 'product'))->render();
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request, SubscriptionForm $form)
    {
        // Already-open blog forms retain their host/CSRF endpoint and join PianoLIT news.
        if ($request->getHost() === 'blog.'.config('app.short_url') && !$request->gift_url) {
            $request->validate(['email' => 'required|string|email|max:254']);
            app(\App\Services\Newsletter\Subscribers::class)->subscribe($form->email);
            return back()->with('status', 'You’re on the list! Look out for monthly PianoLIT news, whenever there’s something new to share.');
        }
        $list = EmailList::where('name', EmailList::FREE_PICK)->first();
        if ($list && $list->has($form->email) && ! $request->gift_url)
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
