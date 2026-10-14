<?php

namespace App\Services\Apple;

use App\Billing\Sources\Apple;
use Illuminate\Support\Facades\{Cache, DB};

class MembershipMonitor
{
    public function run($limit = 200)
    {
        $lock = Cache::lock('apple-membership-monitor', 7200);
        if (! $lock->get()) return null;

        try {
            $run = DB::table('apple_membership_sync_runs')->insertGetId(['started_at' => now()]);
            $totals = ['checked' => 0, 'verified' => 0, 'failed' => 0];
            $sources = Apple::query()->select('apple_memberships.*')
                ->leftJoin('apple_membership_checks as checks', 'checks.apple_membership_id', '=', 'apple_memberships.id')
                ->whereExists(function ($q) {
                    $q->selectRaw('1')->from('memberships as m')->join('users as u', 'u.id', '=', 'm.user_id')
                        ->whereColumn('m.source_id', 'apple_memberships.id')->where('m.source_type', Apple::class)
                        ->whereNotIn('u.id', config('apple-membership-monitor.excluded_user_ids'));
                    \App\Activity\ExcludedAccounts::users($q, 'u.email');
                })
                ->where(function ($q) {
                    $q->whereNull('checks.next_check_at')->orWhere('checks.next_check_at', '<=', now())
                        ->orWhereColumn('apple_memberships.updated_at', '>', 'checks.source_updated_at');
                })
                ->orderByRaw('CASE WHEN checks.next_check_at IS NULL THEN 0 ELSE 1 END')
                ->orderBy('checks.next_check_at')->orderBy('apple_memberships.id')->limit($limit)->get();

            foreach ($sources as $source) {
                try {
                    $result = app(AdminReceiptVerifier::class)->verify($source);
                } catch (\Throwable $e) {
                    $result = ['error_code' => 'invalid_response'];
                }
                // Recheck input under a row lock so an iOS receipt update cannot be overwritten
                // by monitoring evidence for a receipt fetched before that update.
                $saved = DB::transaction(function () use ($source, $result) {
                    $current = Apple::whereKey($source->id)->lockForUpdate()->first();
                    if (! $current || $this->inputHash($current) !== $this->inputHash($source)) return false;
                    $previous = DB::table('apple_membership_checks')->where('apple_membership_id', $source->id)->first();
                    $failed = isset($result['error_code']);
                    $failures = $failed ? min(10, ($previous->failures ?? 0) + 1) : 0;
                    $hours = $failed ? min(24, pow(2, $failures - 1)) : (in_array($result['status'], ['expired', 'revoked'], true) ? 24 : 6);
                    $values = [
                        'status' => $failed ? 'needs_review' : $result['status'],
                        'checked_at' => now(), 'source_updated_at' => $source->updated_at,
                        'input_hash' => $this->inputHash($source), 'error_code' => $result['error_code'] ?? null,
                        'failures' => $failures, 'next_check_at' => now()->addHours($hours),
                    ];
                    if (! $failed) {
                        $values = array_merge($values, $result, ['verified_at' => now()]);
                        // Recheck promptly at the next expiry boundary, including short trials.
                        $boundary = $result['grace_expires_at'] ?: $result['expires_at'];
                        if ($boundary && $boundary->gt(now()) && $boundary->lt($values['next_check_at'])) {
                            $values['next_check_at'] = $boundary;
                        }
                    }
                    DB::table('apple_membership_checks')->updateOrInsert(['apple_membership_id' => $source->id], $values);
                    return true;
                });
                if ($saved) {
                    $totals['checked']++;
                    $totals[isset($result['error_code']) ? 'failed' : 'verified']++;
                    DB::table('apple_membership_sync_runs')->where('id', $run)->update($totals);
                }
            }
            DB::table('apple_membership_sync_runs')->where('id', $run)->update($totals + ['finished_at' => now()]);
            // Retain a month of job diagnostics; the current per-membership evidence remains.
            DB::table('apple_membership_sync_runs')->where('started_at', '<', now()->subDays(30))->delete();
            return $totals;
        } finally {
            $lock->release();
        }
    }

    private function inputHash(Apple $source)
    {
        return hash('sha256', json_encode([$source->latest_receipt, $source->password, $source->plan, $source->getRawOriginal('latest_receipt_info')]));
    }
}
