<?php

namespace App\Http\Controllers\Webhooks;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Billing\Webhooks\StripeWebhooks;

class StripeWebhookController extends Controller
{
    public function __invoke(Request $request)
    {
        $payload = $this->verify($request);

        abort_unless(isset($payload['type']) && is_string($payload['type']), 400);

    	$method = $this->eventToMethod($payload['type']);

    	if (method_exists(StripeWebhooks::class, $method)) {
    		StripeWebhooks::$method($payload);
    	}

        return response('Webhook received', 200);
    }

    public function verify(Request $request)
    {
        $secret = config('services.stripe.webhook.secret');
        abort_unless(is_string($secret) && $secret !== '', 503, 'Webhook signing is not configured.');

		try {
		    return \Stripe\Webhook::constructEvent(
                $request->getContent(),
                $request->header('Stripe-Signature', ''),
                $secret,
                (int) config('services.stripe.webhook.tolerance', 300)
            )->toArray();
		} catch (\Exception $e) {
		    abort(400, 'Invalid webhook signature or payload.');
		}
    }

    public function eventToMethod($event)
    {
    	return 'when' . studly_case(str_replace('.', '_', $event));
    }
}
