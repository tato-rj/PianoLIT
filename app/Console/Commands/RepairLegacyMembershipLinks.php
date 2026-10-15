<?php

namespace App\Console\Commands;

use App\Billing\MembershipLinkRepair;
use Illuminate\Console\Command;

class RepairLegacyMembershipLinks extends Command
{
    protected $signature = 'memberships:repair-legacy-links {--apply : Archive the reviewed extra links and save verified Stripe identities}';
    protected $description = 'Preview the fixed legacy membership repair using read-only provider checks; change local data only with --apply';

    public function handle(MembershipLinkRepair $repair)
    {
        $apply = (bool) $this->option('apply');
        $this->line($apply ? 'Apply reviewed membership links; provider requests are read-only.' : 'PREVIEW ONLY — no database writes; checking Stripe and Apple.');
        try {
            $result = $repair->run(config('membership-link-repair', []), $apply);
        } catch (\DomainException $e) {
            $this->error($e->getMessage());
            return 1;
        } catch (\Throwable $e) {
            // Provider/DB exception messages may contain sensitive parameters.
            $this->error('Repair stopped ('.get_class($e).'). No repair changes were committed.');
            return 1;
        }
        $this->table(['User ID', 'Keep membership', 'Archive membership', 'Result'], $result['rows']);
        if ($result['skipped']) {
            $this->warn('Skipped missing account(s): '.implode(', ', $result['skipped']).'. Membership links and billing sources were left unchanged.');
        }
        $this->info($apply ? 'Applied '.$result['changed'].' account repair(s). Provider subscriptions and billing dates were not changed.' : 'Preview complete. Use --apply only after reviewing the listed selections.');
        return 0;
    }
}
