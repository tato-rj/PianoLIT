<?php

namespace App\Services\Admin;

use App\{EmailCampaignReport, EmailLog};
use App\Support\AdminTableFilters;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class EmailReportRecipientsTable
{
    const SORTS = [
        'date_desc' => 'Sent · newest', 'date_asc' => 'Sent · oldest',
        'recipient_asc' => 'Recipient · A–Z', 'recipient_desc' => 'Recipient · Z–A',
        'status_asc' => 'Status · A–Z', 'status_desc' => 'Status · Z–A',
        'delivered_desc' => 'Delivered · newest', 'delivered_asc' => 'Delivered · oldest',
        'failed_desc' => 'Failed · newest', 'failed_asc' => 'Failed · oldest',
        'opened_desc' => 'Opens · most', 'opened_asc' => 'Opens · fewest',
        'clicked_desc' => 'Clicks · most', 'clicked_asc' => 'Clicks · fewest',
    ];
    const STATUSES = ['all' => 'All statuses', 'delivered' => 'Delivered', 'failed' => 'Failed', 'unknown' => 'Unknown'];

    public function summary($list)
    {
        $campaign = EmailCampaignReport::where('list_id', $list)->first();
        // Keep account exclusions in both recipient rows and these totals. Use
        // a plain result object, without EmailLog's legacy list-ID date appends.
        $event = EmailLog::byList($list)->toBase()->selectRaw('COUNT(*) as emails_count,
            COALESCE(SUM(unique_delivered), 0) as delivered_count, COALESCE(SUM(unique_failed), 0) as failed_count,
            COALESCE(SUM(unique_opened), 0) as opens_count, COALESCE(SUM(unique_clicked), 0) as clicks_count,
            MIN(created_at) as first_logged_at')->first();
        abort_unless($campaign || $event->emails_count, 404);
        $event->list_id = $list;
        $event->name = $campaign ? $campaign->name : slug_str(explode('.', $list)[0]);
        $event->sent_at = $campaign ? $campaign->sent_at : null;
        if (!$event->sent_at && preg_match('/\.(\d{1,10})$/D', $list, $matches)) {
            $event->sent_at = \Carbon\Carbon::createFromTimestamp((int) $matches[1], config('app.timezone'));
        }
        if (!$event->sent_at && $event->first_logged_at) $event->sent_at = carbon($event->first_logged_at);

        return $event;
    }

    public function get(Request $request, $list)
    {
        $filters = AdminTableFilters::fromRequest($request, self::SORTS, 'date_desc', [
            'status' => ['nullable', 'string', Rule::in(array_keys(self::STATUSES))],
        ]);
        $filters['status'] = $filters['status'] ?? 'all';
        $query = EmailLog::byList($list)->select(['id', 'recipient', 'created_at', 'delivered_at', 'failed_at', 'opened', 'clicked']);
        if ($filters['search'] !== '') {
            $like = '%'.str_replace(['!', '%', '_'], ['!!', '!%', '!_'], mb_strtolower($filters['search'])).'%';
            $query->whereRaw("LOWER(recipient) LIKE ? ESCAPE '!'", [$like]);
        }
        if ($filters['status'] === 'delivered') $query->whereNotNull('delivered_at');
        if ($filters['status'] === 'failed') $query->whereNull('delivered_at')->whereNotNull('failed_at');
        if ($filters['status'] === 'unknown') $query->whereNull('delivered_at')->whereNull('failed_at');
        [$sort, $direction] = explode('_', $filters['sort']);
        if ($sort === 'status') {
            $query->orderByRaw('CASE WHEN delivered_at IS NOT NULL THEN 0 WHEN failed_at IS NOT NULL THEN 1 ELSE 2 END '.$direction);
        } else {
            $columns = ['date' => 'created_at', 'recipient' => 'recipient', 'delivered' => 'delivered_at',
                'failed' => 'failed_at', 'opened' => 'opened', 'clicked' => 'clicked'];
            $query->orderBy($columns[$sort], $direction);
        }
        $recipients = $query->orderByDesc('id')->paginate($filters['per_page'], ['*'], 'page', $filters['page'] ?? 1)
            ->appends(AdminTableFilters::query(array_diff_key($filters, ['page' => true])));

        return compact('recipients', 'filters');
    }
}
