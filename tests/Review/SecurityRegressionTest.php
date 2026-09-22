<?php

namespace Tests\Review;

use App\Http\Controllers\Admin\PiecesController;
use App\Http\Controllers\Webhooks\StripeWebhookController;
use App\Http\Middleware\ChatGPTRestricted;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\HttpException;

class SecurityRegressionTest extends ReviewTestCase
{
    private function webhookRequest($payload, $timestamp = null, $secret = 'whsec_review')
    {
        $timestamp = $timestamp ?? time();
        $signature = hash_hmac('sha256', $timestamp.'.'.$payload, $secret);

        return Request::create('/webhooks/stripe', 'POST', [], [], [], [
            'CONTENT_TYPE' => 'application/json',
            'HTTP_STRIPE_SIGNATURE' => 't='.$timestamp.',v1='.$signature,
        ], $payload);
    }

    public function test_signed_unhandled_stripe_events_are_acknowledged()
    {
        config(['services.stripe.webhook.secret' => 'whsec_review']);
        $response = (new StripeWebhookController)($this->webhookRequest('{"type":"review.unhandled"}'));
        $this->assertSame(200, $response->getStatusCode());
    }

    /** @dataProvider invalidWebhooks */
    public function test_unsigned_forged_and_expired_webhooks_are_rejected($kind)
    {
        config(['services.stripe.webhook.secret' => 'whsec_review']);
        $request = $this->webhookRequest('{"type":"charge.succeeded"}', $kind === 'expired' ? time() - 600 : time(), $kind === 'forged' ? 'wrong' : 'whsec_review');
        if ($kind === 'unsigned') $request->headers->remove('Stripe-Signature');
        if ($kind === 'tampered') $request->initialize([], [], [], [], [], $request->server->all(), '{"type":"customer.subscription.deleted"}');

        try {
            (new StripeWebhookController)($request);
            $this->fail('Invalid webhook was accepted.');
        } catch (HttpException $exception) {
            $this->assertSame(400, $exception->getStatusCode());
        }
    }

    public static function invalidWebhooks()
    {
        return [['unsigned'], ['forged'], ['expired'], ['tampered']];
    }

    public function test_stripe_fails_closed_when_signing_secret_is_missing()
    {
        config(['services.stripe.webhook.secret' => null]);
        try {
            (new StripeWebhookController)($this->webhookRequest('{"type":"review.unhandled"}'));
            $this->fail('Missing signing secret was accepted.');
        } catch (HttpException $exception) {
            $this->assertSame(503, $exception->getStatusCode());
        }
    }

    public function test_chatgpt_requires_a_configured_matching_token()
    {
        $middleware = new ChatGPTRestricted;
        $request = new Request;
        $next = function () { return response('OK'); };
        config(['services.chatgpt.token' => null]);
        $this->assertSame(401, $middleware->handle($request, $next)->getStatusCode());
        config(['services.chatgpt.token' => 'review-token']);
        $request->headers->set('Authorization', 'Bearer wrong');
        $this->assertSame(401, $middleware->handle($request, $next)->getStatusCode());
        $request->headers->set('Authorization', 'Bearer review-token');
        $this->assertSame(200, $middleware->handle($request, $next)->getStatusCode());
    }

    public function test_lookup_rejects_an_sql_expression_as_the_column()
    {
        $this->expectException(ValidationException::class);
        (new PiecesController)->singleLookup(new Request(['field' => '(select 1)', 'input' => 'x']));
    }
}
