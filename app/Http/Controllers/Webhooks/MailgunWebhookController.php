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
            if ($data['event'] === 'opened') {
                $email->increment($data['event']);
                $email->update(['unique_opened' => 1]);
            }

            if ($data['event'] === 'clicked') {
                $email->increment($data['event']);
                $email->update(['unique_clicked' => 1]);
            }

            if ($data['event'] === 'delivered') {
                $email->update(["{$data['event']}_at" => now()]);
                $email->update(['unique_delivered' => 1]);
            }

            if ($data['event'] === 'failed') {
                $email->update(["{$data['event']}_at" => now()]);
                $email->update(['unique_failed' => 1]);
            }
        }

        return response(200);
    }
}
