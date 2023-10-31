<?php

namespace App\Http\Controllers\Webhooks;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Billing\Webhooks\StripeWebhooks;

class StripeWebhookController extends Controller
{
    public function __invoke(Request $request)
    {
    	// $this->verify();

    	$payload = $request->all();

    	$method = $this->eventToMethod($payload['type']);

    	if (method_exists(StripeWebhooks::class, $method)) {
    		StripeWebhooks::$method($payload);
    		return response('Webhook received', 200);
    	}
    }

    public function verify()
    {
		try {
		    \Stripe\Webhook::constructEvent(
		    	@file_get_contents('php://input'), 
		    	$_SERVER['HTTP_STRIPE_SIGNATURE'], 
		    	config('services.stripe.webhook.secret'));
		} catch (\Exception $e) {
		    abort(404);
		}
    }

    public function eventToMethod($event)
    {
    	return 'when' . studly_case(str_replace('.', '_', $event));
    }
}
