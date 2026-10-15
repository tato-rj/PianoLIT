<?php

namespace App\Http\Controllers\Webhooks;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Billing\Stripe\WebhookProcessor;

class StripeWebhookController extends Controller
{
    public function __invoke(Request $request)
    {
        $payload = $this->verify($request);

        abort_unless(isset($payload['type']) && is_string($payload['type']), 400);

        app(WebhookProcessor::class)->receive($payload, $request->attributes->get('stripe.billing_account'));

        return response('Webhook received', 200);
    }

    public function verify(Request $request)
    {
        $secrets = array_filter([
            'us' => config('services.stripe.webhook.us_secret') ?: config('services.stripe.webhook.secret'),
            'swiss' => config('services.stripe.webhook.swiss_secret'),
        ], function ($secret) { return is_string($secret) && $secret !== ''; });
        abort_unless(count($secrets), 503, 'Webhook signing is not configured.');
        foreach ($secrets as $account => $secret) {
            try {
                $payload = \Stripe\Webhook::constructEvent($request->getContent(), $request->header('Stripe-Signature', ''), $secret,
                    (int) config('services.stripe.webhook.tolerance', 300))->toArray();
                $request->attributes->set('stripe.billing_account', $account);
                return $payload;
            } catch (\UnexpectedValueException | \Stripe\Exception\SignatureVerificationException $exception) {
                // Try only the other explicitly configured account's secret.
            }
        }
        abort(400, 'Invalid webhook signature or payload.');
    }

    public function eventToMethod($event)
    {
    	return 'when' . studly_case(str_replace('.', '_', $event));
    }
}
