<?php

namespace App\Http\Controllers;

use App\Http\Requests\NewsletterSubscriptionForm;
use App\Services\Newsletter\Subscribers;

class NewsletterSubscriptionsController extends Controller
{
    public function store(NewsletterSubscriptionForm $request, Subscribers $subscribers)
    {
        $subscribers->subscribe($request->email);

        return back()->with('status', 'You’re on the list! Look out for monthly PianoLIT news, whenever there’s something new to share.');
    }
}
