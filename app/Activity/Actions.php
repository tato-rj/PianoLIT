<?php

namespace App\Activity;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class Actions
{
    public static function record(Request $request, $type, array $metadata = [], $resourceType = null, $resourceId = null, $eventId = null, $actor = null)
    {
        $recorder = new Recorder;
        if (!$recorder->eligible($request)) return;
        try {
            $page = $request->attributes->get('activity.page') ?: $recorder->context($request);
            $event = ['type' => $type, 'source' => 'server', 'occurred_at' => now()->format('Y-m-d H:i:s.v'), 'occurred_at_utc' => now('UTC')->format('Y-m-d H:i:s.v'), 'resource_type' => $resourceType, 'resource_id' => $resourceId, 'metadata' => Metadata::select($metadata, ['result', 'folder_id', 'annotation_count', 'created_count', 'edited_count', 'removed_count', 'revision', 'status', 'plan'])];
            if ($eventId) $event['event_id'] = $eventId;
            // Auth listeners use the verified guard event, never submitted input.
            if ($actor !== null) $event['user_id'] = $actor;
            $recorder->insert($request, $page, $event);
        } catch (\Throwable $exception) { report($exception); }
    }

    public static function linkAnonymous(Request $request, $userId, $reason)
    {
        if (!config('activity.enabled') || !$request->hasSession() || ExcludedAccounts::id($userId)) return;
        try {
            $visit = DB::table('activity_visits')->where('id', $request->session()->get('activity.visit', ''))
                ->where('visitor_id', $request->session()->get('activity.visitor', ''))->whereNull('user_id')
                ->where(function ($query) {
                    $query->where('last_activity_at_utc', '>=', now('UTC')->subMinutes(config('activity.visit_idle_minutes')))
                        ->orWhere(function ($legacy) { $legacy->whereNull('last_activity_at_utc')->where('last_activity_at', '>=', now()->subMinutes(config('activity.visit_idle_minutes'))); });
                })->first();
            if ($visit) DB::table('activity_visit_links')->insertOrIgnore(['visit_id' => $visit->id, 'user_id' => $userId, 'linked_at_utc' => now('UTC')->format('Y-m-d H:i:s.v'), 'reason' => $reason]);
        } catch (\Throwable $exception) { report($exception); }
    }

    public static function paidInvoice(array $payload)
    {
        if (!config('activity.enabled')) return;
        $invoice = $payload['data']['object'] ?? [];
        $subscription = $invoice['subscription'] ?? ($invoice['parent']['subscription_details']['subscription'] ?? null);
        if (is_array($subscription)) $subscription = $subscription['id'] ?? null;
        if (($invoice['paid'] ?? false) !== true || !is_int($invoice['amount_paid'] ?? null) || $invoice['amount_paid'] <= 0
            || !is_string($invoice['id'] ?? null) || !preg_match('/^in_[A-Za-z0-9]+$/D', $invoice['id'])
            || !is_string($subscription) || !preg_match('/^sub_[A-Za-z0-9]+$/D', $subscription)
            || !is_string($invoice['customer'] ?? null) || !is_int($payload['created'] ?? null)) return;
        try {
            $user = DB::table('memberships as m')->join('stripe_memberships as s', 's.id', '=', 'm.source_id')
                ->where('m.source_type', \App\Billing\Sources\Stripe::class)->where('s.stripe_id', $invoice['customer'])->value('m.user_id');
            if (!$user || ExcludedAccounts::id($user)) return;
            $at = \Carbon\Carbon::createFromTimestamp($invoice['status_transitions']['paid_at'] ?? $payload['created'], config('app.timezone'));
            $id = (string) \Ramsey\Uuid\Uuid::uuid5(\Ramsey\Uuid\Uuid::NAMESPACE_URL, 'pianolit:paid-invoice:'.$invoice['id']);
            DB::table('activity_events')->insertOrIgnore([
                'event_id' => $id, 'user_id' => $user, 'platform' => 'webapp', 'type' => 'subscription_paid', 'source' => 'server',
                'occurred_at' => $at->format('Y-m-d H:i:s.v'), 'occurred_at_utc' => $at->copy()->utc()->format('Y-m-d H:i:s.v'),
                'received_at' => now()->format('Y-m-d H:i:s.v'), 'received_at_utc' => now('UTC')->format('Y-m-d H:i:s.v'),
                'metadata' => json_encode(['status' => 'paid']),
            ]);
        } catch (\Throwable $exception) { report($exception); }
    }
}
