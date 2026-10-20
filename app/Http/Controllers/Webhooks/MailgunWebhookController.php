<?php

namespace App\Http\Controllers\Webhooks;

use Illuminate\Http\Request;
use App\Http\Middleware\Webhooks\MailgunWebhook;
use App\Http\Controllers\Controller;
use App\Services\MailgunEvents;

class MailgunWebhookController extends Controller
{
    public function __construct()
    {
        $this->middleware(MailgunWebhook::class);
    }

    public function __invoke(Request $request, MailgunEvents $events)
    {
        $payload = $request->attributes->get('mailgun_payload');
        abort_unless(is_array($payload['event-data'] ?? null), 422, 'Invalid Mailgun event.');
        try {
            $event = $events->normalize($payload['event-data']);
            if ($event === null) return response('', 200);
            $result = $events->apply($event, false, false, $payload['signature']['token']);
        } catch (\InvalidArgumentException $exception) {
            abort(422, 'Invalid Mailgun event.');
        }
        // Ordinary transactional mail is not part of campaign tracking. Never
        // guess its campaign from its recipient or subject.
        abort_if($result['status'] === 'ambiguous', 503, 'Email tracking record is ambiguous.');
        return response('', 200);
    }
}
