<?php

namespace App\Http\Controllers;

use App\Http\Requests\NewsletterSubscriptionForm;
use App\Services\Newsletter\Subscribers;
use App\Services\Shop\{CheckoutEnvironment, FirstOrderDiscount};
use Illuminate\Support\Facades\DB;

class NewsletterSubscriptionsController extends Controller
{
    public function store(NewsletterSubscriptionForm $request, Subscribers $subscribers)
    {
        $shopOffer = $request->getHost() === 'shop.'.config('app.short_url')
            && $request->input('form_id') === 'shop-newsletter-subscription-form';
        DB::transaction(function () use ($request, $subscribers, $shopOffer) {
            $subscribers->subscribe($request->email);
            if ($shopOffer) app(FirstOrderDiscount::class)->grant($request->email);
        }, 3);
        $request->session()->put('newsletter_subscribed', true);

        if ($shopOffer) {
            $request->session()->put('shop_newsletter_email', mb_strtolower(trim($request->email)));
            if (!app(CheckoutEnvironment::class)->available() || !app(CheckoutEnvironment::class)->available('digital')) {
                return back()->with('status', 'You’re on the list! Your 10% first-order discount is saved for when Shop checkout is available.');
            }
            return back()->with('status', 'You’re on the list! Your 10% welcome discount applies automatically to your first Shop order when you check out with this email. Delivery and tax are excluded; other coupons cannot be combined.');
        }

        return back()->with('status', 'You’re on the list! Look out for monthly PianoLIT news, whenever there’s something new to share.');
    }
}
