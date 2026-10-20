<?php

namespace App\Services;

use App\Activity\ExcludedAccounts;
use App\EmailLog;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class MailgunEvents
{
    public static function campaignTag($campaign)
    {
        return 'pianolit-campaign-'.hash('sha256', $campaign);
    }

    public function normalize(array $payload)
    {
        $event = $payload['event'] ?? null;
        if (! in_array($event, ['delivered', 'failed', 'opened', 'clicked'], true)
            || ($event === 'failed' && ($payload['severity'] ?? null) === 'temporary')) {
            return null;
        }
        $data = Validator::make([
            'id' => $payload['id'] ?? null,
            'message_id' => data_get($payload, 'message.headers.message-id'),
            'recipient' => $payload['recipient'] ?? null,
        ], [
            'id' => 'required|string|max:255|not_regex:/[[:cntrl:]]/',
            'message_id' => 'required|string|max:255|not_regex:/[[:cntrl:]]/',
            'recipient' => 'required|string|email|max:254',
        ])->validate();
        $at = $this->time($payload['timestamp'] ?? $payload['@timestamp'] ?? null);
        $messageId = trim(trim($data['message_id']), '<>');
        if ($messageId === '') throw new \InvalidArgumentException('Mailgun message ID is empty.');

        return [
            // Mailgun event IDs are unique within a UTC day.
            'event_key' => hash('sha256', $at->format('Y-m-d').':'.$data['id']),
            'event' => $event, 'message_id' => $messageId,
            'recipient' => mb_strtolower(trim($data['recipient'])),
            'occurred_at' => $at,
            'delivered_at' => isset($payload['delivered-at']) ? $this->time($payload['delivered-at']) : null,
        ];
    }

    public function time($value)
    {
        if (is_int($value) || is_float($value) || (is_string($value) && is_numeric($value))) {
            if (! is_finite((float) $value) || (float) $value <= 0) {
                throw new \InvalidArgumentException('Invalid Mailgun event time.');
            }
            return Carbon::createFromTimestamp((int) $value, 'UTC');
        }
        if (! is_string($value) || strlen($value) > 100 || ! preg_match('/\d{4}/', $value)) {
            throw new \InvalidArgumentException('Mailgun event time is missing or invalid.');
        }
        return Carbon::parse($value, 'UTC')->utc();
    }

    public function apply(array $event, $dryRun = false, $recovery = false, $token = null, array $campaigns = [])
    {
        if (ExcludedAccounts::email($event['recipient'])) return ['status' => 'excluded'];

        $operation = function () use ($event, $dryRun, $recovery, $token, $campaigns) {
            $receipt = DB::table('mailgun_event_receipts')->where('event_key', $event['event_key'])->first();
            if ($receipt) {
                $campaign = EmailLog::whereKey($receipt->email_log_id)->value('list_id');
                return ['status' => 'duplicate', 'campaign' => ! $campaigns || in_array($campaign, $campaigns, true) ? $campaign : null];
            }
            $query = EmailLog::whereIn('message_id', [$event['message_id'], '<'.$event['message_id'].'>'])
                ->whereRaw('LOWER(TRIM(recipient)) = ?', [$event['recipient']]);
            if ($campaigns) $query->whereIn('list_id', $campaigns);
            $matches = ($dryRun ? $query : $query->lockForUpdate())->limit(2)->get();
            if ($matches->count() !== 1) {
                return ['status' => $matches->isEmpty() ? 'unmatched' : 'ambiguous'];
            }
            $email = $matches->first();
            $flags = [$event['event']];
            if ($event['delivered_at'] && in_array($event['event'], ['opened', 'clicked'], true)) $flags[] = 'delivered';
            $newFlags = array_values(array_filter(array_unique($flags), function ($flag) use ($email) {
                return ! $email->{'unique_'.$flag};
            }));
            if (! $dryRun) {
                $tokenHash = $token === null ? null : hash('sha256', $token);
                $inserted = DB::table('mailgun_event_receipts')->insertOrIgnore([
                    'event_key' => $event['event_key'], 'token_hash' => $tokenHash,
                    'email_log_id' => $email->id, 'event' => $event['event'],
                    'occurred_at' => $event['occurred_at'], 'created_at' => now(),
                ]);
                if (! $inserted) {
                    if ($tokenHash && DB::table('mailgun_event_receipts')->where('token_hash', $tokenHash)
                        ->where('event_key', '!=', $event['event_key'])->exists()) {
                        throw new \InvalidArgumentException('Mailgun signature token was reused for another event.');
                    }
                    return ['status' => 'duplicate'];
                }
                $updates = ['unique_'.$event['event'] => 1];
                if (in_array($event['event'], ['delivered', 'failed'], true)) {
                    $column = $event['event'].'_at';
                    $occurredAt = $event['occurred_at']->copy()->timezone(config('app.timezone'));
                    $updates[$column] = $email->{$column} && $email->{$column}->lt($event['occurred_at'])
                        ? $email->{$column} : $occurredAt;
                } else {
                    // A recovery window may overlap already-counted historical events.
                    $updates[$event['event']] = $recovery
                        ? max((int) $email->{$event['event']}, DB::table('mailgun_event_receipts')
                            ->where('email_log_id', $email->id)->where('event', $event['event'])->count())
                        : (int) $email->{$event['event']} + 1;
                    if ($event['delivered_at']) {
                        $updates['unique_delivered'] = 1;
                        $updates['delivered_at'] = $email->delivered_at ?: $event['delivered_at']->copy()->timezone(config('app.timezone'));
                    }
                }
                $email->update($updates);
            }
            return ['status' => $dryRun ? 'matched' : 'applied', 'campaign' => $email->list_id,
                'email_log_id' => $email->id, 'new_flags' => $newFlags];
        };

        return $dryRun ? $operation() : DB::transaction($operation, 3);
    }
}
