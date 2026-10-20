<?php

namespace App\Services;

use App\{EmailCampaignReport, EmailLog};
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class EmailCampaignReports
{
    public function rebuild($campaign, $dryRun = false)
    {
        $operation = function () use ($campaign, $dryRun) {
            $query = EmailCampaignReport::where('list_id', $campaign);
            $existing = ($dryRun ? $query : $query->lockForUpdate())->first();
            $source = EmailLog::byList($campaign)->toBase()->selectRaw('COUNT(*) as emails_count,
                COALESCE(SUM(unique_delivered), 0) as delivered_count,
                COALESCE(SUM(unique_failed), 0) as failed_count,
                COALESCE(SUM(unique_opened), 0) as opens_count,
                COALESCE(SUM(unique_clicked), 0) as clicks_count, MIN(created_at) as first_logged_at')->first();
            // Preserve historical snapshots when their underlying rows no longer exist.
            if (! $source->emails_count && ! EmailLog::where('list_id', $campaign)->exists()) return null;
            $counts = (array) $source;
            unset($counts['first_logged_at']);
            if (! $dryRun) {
                if ($existing) {
                    $existing->update($counts);
                } elseif ($source->emails_count) {
                    $parts = explode('.', $campaign);
                    $timestamp = end($parts);
                    EmailCampaignReport::create($counts + [
                        'list_id' => $campaign, 'name' => slug_str($parts[0]),
                        'sent_at' => count($parts) > 1 && ctype_digit($timestamp)
                            ? Carbon::createFromTimestamp((int) $timestamp, config('app.timezone')) : $source->first_logged_at,
                    ]);
                }
            }
            return $counts;
        };
        return $dryRun ? $operation() : DB::transaction($operation, 3);
    }
}
