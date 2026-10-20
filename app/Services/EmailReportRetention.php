<?php

namespace App\Services;

use Carbon\CarbonInterface;
use Illuminate\Support\Facades\DB;

class EmailReportRetention
{
    public function run(CarbonInterface $cutoff, bool $execute = false, int $batch = 500): array
    {
        if (! $execute) {
            return [
                'reports' => $this->expiredReports($cutoff)->count(),
                'logs' => $this->expiredLogs($cutoff)->count(),
                'receipts' => DB::table('mailgun_event_receipts')->where(function ($query) use ($cutoff) {
                    $query->whereIn('email_log_id', $this->expiredLogs($cutoff)->select('email_logs.id'))
                        ->orWhereNotExists($this->receiptParent());
                })->count(),
            ];
        }

        $counts = ['reports' => 0, 'logs' => 0, 'receipts' => 0];

        // Keep each campaign's summary until its final recipient batch is removed.
        // A failed batch rolls back its receipts, recipient rows and summary together.
        while ($deleted = DB::transaction(function () use ($cutoff, $batch) {
            $report = $this->expiredReports($cutoff)->orderBy('id')->lockForUpdate()->first();
            if (! $report) {
                return null;
            }

            $ids = DB::table('email_logs')->where('list_id', $report->list_id)
                ->orderBy('id')->limit($batch)->lockForUpdate()->pluck('id')->all();
            $deleted = $this->deleteLogs($ids);
            $deleted['reports'] = DB::table('email_logs')->where('list_id', $report->list_id)->exists()
                ? 0 : DB::table('email_campaign_reports')->where('id', $report->id)->delete();

            return $deleted;
        }, 3)) {
            $this->addCounts($counts, $deleted);
        }

        // Legacy/unassociated logs have no campaign send date; use their creation date.
        // Advance by primary key so newer rows are not repeatedly scanned per batch.
        $lastId = 0;
        do {
            $deleted = DB::transaction(function () use ($cutoff, $batch, $lastId) {
                $ids = $this->unreportedLogs($cutoff)->where('id', '>', $lastId)
                    ->orderBy('id')->limit($batch)->lockForUpdate()->pluck('id')->all();
                $deleted = $this->deleteLogs($ids);
                return $deleted + ['last_id' => $ids ? end($ids) : $lastId];
            }, 3);
            $lastId = $deleted['last_id'];
            $this->addCounts($counts, $deleted);
        } while ($deleted['logs']);

        // Manual report deletion can leave receipts without a recipient row.
        $lastKey = '';
        do {
            $deleted = DB::transaction(function () use ($batch, $lastKey) {
                $keys = DB::table('mailgun_event_receipts')->whereNotExists($this->receiptParent())
                    ->where('event_key', '>', $lastKey)->orderBy('event_key')->limit($batch)
                    ->lockForUpdate()->pluck('event_key')->all();
                return [
                    'receipts' => DB::table('mailgun_event_receipts')->whereIn('event_key', $keys)->delete(),
                    'last_key' => $keys ? end($keys) : $lastKey,
                ];
            }, 3);
            $lastKey = $deleted['last_key'];
            $this->addCounts($counts, $deleted);
        } while ($deleted['receipts']);

        return $counts;
    }

    private function expiredReports(CarbonInterface $cutoff)
    {
        return DB::table('email_campaign_reports')->where('sent_at', '<', $cutoff);
    }

    private function unreportedLogs(CarbonInterface $cutoff)
    {
        return DB::table('email_logs')->where('created_at', '<', $cutoff)->whereNotExists(function ($query) {
            $query->selectRaw('1')->from('email_campaign_reports')
                ->whereColumn('email_campaign_reports.list_id', 'email_logs.list_id');
        });
    }

    private function expiredLogs(CarbonInterface $cutoff)
    {
        return DB::table('email_logs')->where(function ($query) use ($cutoff) {
            $query->whereIn('list_id', $this->expiredReports($cutoff)->select('list_id'))
                ->orWhereIn('id', $this->unreportedLogs($cutoff)->select('email_logs.id'));
        });
    }

    private function receiptParent(): \Closure
    {
        return function ($query) {
            $query->selectRaw('1')->from('email_logs')
                ->whereColumn('email_logs.id', 'mailgun_event_receipts.email_log_id');
        };
    }

    private function deleteLogs(array $ids): array
    {
        return [
            'receipts' => DB::table('mailgun_event_receipts')->whereIn('email_log_id', $ids)->delete(),
            'logs' => DB::table('email_logs')->whereIn('id', $ids)->delete(),
        ];
    }

    private function addCounts(array &$counts, array $deleted): void
    {
        foreach ($counts as $table => $count) {
            $counts[$table] += $deleted[$table] ?? 0;
        }
    }
}
