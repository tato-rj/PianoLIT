<?php

namespace App\Http\Controllers\Webhooks;

use Illuminate\Http\Request;
use App\Http\Middleware\Webhooks\MailgunWebhook;
use App\Http\Controllers\Controller;
use App\EmailLog;

class MailgunWebhookController extends Controller
{
    public function __construct()
    {
        $this->middleware(MailgunWebhook::class);
    }

    public function __invoke(Request $request)
    {
        $data = $request->get('event-data');

        $message_id = $data['message']['headers']['message-id'];

        if ($email = EmailLog::where('message_id', $message_id)->first()) {
            if ($data['event'] === 'opened' || $data['event'] === 'clicked') {
                $email->increment($data['event']);
            }

            if ($data['event'] === 'delivered' || $data['event'] === 'failed') {
                $email->update(["{$data['event']}_at" => now()]);
            }
        }

        return response(200);
    }
}
