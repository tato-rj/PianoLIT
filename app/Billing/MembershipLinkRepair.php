<?php

namespace App\Billing;

use App\Billing\Sources\{Apple, Stripe};
use App\Billing\Stripe\{Accounts, MembershipBilling, SubscriptionState};
use App\Services\Apple\AdminReceiptVerifier;
use Illuminate\Support\Facades\{DB, Schema};

class MembershipLinkRepair
{
    private const TYPES = ['apple' => Apple::class, 'stripe' => Stripe::class];

    public function run(array $plan, bool $apply = false): array
    {
        $this->validate($plan);
        // The correction identities are unchanged by a read-only skip policy.
        // Preserve hashes written before that policy was added to the plan.
        $hash = hash('sha256', json_encode(['version' => $plan['version'], 'cases' => $plan['cases']], JSON_THROW_ON_ERROR));
        if ($apply && !Schema::hasTable('membership_link_archives')) {
            throw new \DomainException('Deploy the membership link archive migration before applying this repair.');
        }
        $started = now();
        $snapshots = [];
        $rows = [];
        $skipped = [];
        foreach ($plan['cases'] as $case) {
            if (!DB::table('users')->where('id', $case['user'])->exists()) {
                if (in_array($case['user'], $plan['preserve_missing_users'] ?? [], true)) {
                    $skipped[] = $case['user'];
                    $rows[] = [$case['user'], '-', '-', 'Skipped; account missing, records preserved'];
                    continue;
                }
                throw new \DomainException('Reviewed user '.$case['user'].' does not exist.');
            }
            $links = $this->links($case['user']);
            if ($this->alreadyApplied($case, $links, $hash)) {
                $rows[] = [$case['user'], $case['keep'], implode(', ', $this->discardIds($case)), 'Already applied'];
                continue;
            }
            $this->assertLinks($case, $links);
            $sources = $proof = [];
            foreach ($case['links'] as $id => $spec) {
                $source = DB::table($this->table($spec))->where('id', $spec['source'])->first();
                if (!$source) throw new \DomainException('Missing source for membership '.$id.'.');
                $sources[$id] = (array) $source;
                $this->assertExclusiveOwner($case['user'], $spec);
                $proof[$id] = $this->verify($id, $spec, $sources[$id]);
                if ($id !== $case['keep'] && !$this->ended($spec, $proof[$id])) {
                    throw new \DomainException('Membership '.$id.' is not verified ended with renewal off. Nothing can be archived.');
                }
            }
            $snapshots[] = compact('case', 'links', 'sources', 'proof');
            $rows[] = [$case['user'], $case['keep'], implode(', ', $this->discardIds($case)), 'Verified; '.($apply ? 'ready to apply' : 'preview only')];
        }
        if ($apply && $snapshots) {
            DB::transaction(function () use ($snapshots, $plan, $hash, $started) {
                // Same parent lock as web billing. Recheck the membership set and
                // every source row after provider reads; never hold locks over HTTP.
                $users = array_column(array_column($snapshots, 'case'), 'user');
                $locked = DB::table('users')->whereIn('id', $users)->orderBy('id')->lockForUpdate()->pluck('id');
                if ($locked->count() !== count($users)) throw new \DomainException('An account changed during verification. Run the preview again.');
                if ($started->diffInSeconds(now()) > 300) throw new \DomainException('Verification took too long. Run the preview again.');
                foreach ($snapshots as $snapshot) {
                    $case = $snapshot['case'];
                    $links = $this->links($case['user'], true);
                    $this->assertLinks($case, $links);
                    if ($links !== $snapshot['links']) throw new \DomainException('Membership links changed during verification. Run the preview again.');
                    foreach ($case['links'] as $id => $spec) {
                        $current = DB::table($this->table($spec))->where('id', $spec['source'])->lockForUpdate()->first();
                        if (!$current || (array) $current !== $snapshot['sources'][$id]) {
                            throw new \DomainException('A billing source changed during verification. Run the preview again.');
                        }
                        $this->assertExclusiveOwner($case['user'], $spec, true);
                    }
                }
                foreach ($snapshots as $snapshot) {
                    $case = $snapshot['case'];
                    foreach ($case['links'] as $id => $spec) {
                        if ($spec['type'] === 'stripe') {
                            // Only hidden provider identity fields change. Dates,
                            // cards, Apple receipts and provider state stay intact.
                            DB::table('stripe_memberships')->where('id', $spec['source'])->update([
                                'subscription_id' => $spec['subscription'], 'billing_account' => $spec['account'],
                            ]);
                        }
                        if ($id === $case['keep']) continue;
                        $link = collect($snapshot['links'])->firstWhere('id', $id);
                        DB::table('membership_link_archives')->insert([
                            'membership_id' => $id, 'user_id' => $case['user'], 'kept_membership_id' => $case['keep'],
                            'source_type' => $link['source_type'], 'source_id' => $link['source_id'],
                            'original_created_at' => $link['created_at'], 'original_updated_at' => $link['updated_at'],
                            'repair_version' => $plan['version'], 'plan_hash' => $hash,
                            'evidence' => json_encode(['kept' => $snapshot['proof'][$case['keep']], 'archived' => $snapshot['proof'][$id]], JSON_THROW_ON_ERROR),
                            'archived_at' => now(),
                        ]);
                        $deleted = DB::table('memberships')->where('id', $id)->where('user_id', $case['user'])
                            ->where('source_type', $link['source_type'])->where('source_id', $link['source_id'])->delete();
                        if ($deleted !== 1) throw new \DomainException('A membership link changed while archiving. The repair was rolled back.');
                    }
                }
                foreach ($snapshots as $snapshot) {
                    $case = $snapshot['case'];
                    $expected = array_values(array_filter($snapshot['links'], function ($link) use ($case) { return (int) $link['id'] === $case['keep']; }));
                    if ($this->links($case['user'], true) !== $expected) {
                        throw new \DomainException('Membership links changed while applying the repair. The repair was rolled back.');
                    }
                }
            });
        }
        return ['rows' => $rows, 'changed' => $apply ? count($snapshots) : 0, 'skipped' => $skipped];
    }

    private function verify(int $id, array $spec, array $raw): array
    {
        $proof = ['membership_id' => $id, 'source_id' => $spec['source'], 'provider' => $spec['type'], 'verified_at' => now()->toIso8601String()];
        if ($spec['type'] === 'stripe') {
            $model = new Stripe;
            $model->setRawAttributes($raw, true);
            if ($raw['stripe_id'] !== $spec['customer'] || app(Accounts::class)->forSource($model) !== $spec['account']
                || (!empty($raw['subscription_id']) && $raw['subscription_id'] !== $spec['subscription'])) {
                throw new \DomainException('Stripe identity changed for membership '.$id.'.');
            }
            if (DB::table('stripe_memberships')->where('subscription_id', $spec['subscription'])->where('id', '!=', $spec['source'])->exists()) {
                throw new \DomainException('The reviewed Stripe subscription is already linked to a different source.');
            }
            try { $all = app(MembershipBilling::class)->subscriptions($spec['account'], $spec['customer']); }
            catch (\Throwable $e) { throw new \DomainException('Stripe verification failed for membership '.$id.' ('.get_class($e).').'); }
            if (count($all) !== 1 || ($all[0]['id'] ?? null) !== $spec['subscription']
                || SubscriptionState::id($all[0]['customer'] ?? null) !== $spec['customer'] || ($all[0]['status'] ?? null) !== $spec['status']) {
                throw new \DomainException('Stripe subscriptions changed for membership '.$id.'. Review them before changing the plan.');
            }
            return $proof + ['account' => $spec['account'], 'customer_id' => $spec['customer'], 'subscription_id' => $spec['subscription'],
                'status' => $all[0]['status'], 'ended_at' => $all[0]['ended_at'] ?? null,
                'period_end' => SubscriptionState::periodEnd($all[0]), 'cancel_at_period_end' => $all[0]['cancel_at_period_end'] ?? null];
        }
        $info = json_decode($raw['latest_receipt_info'] ?? 'null', true);
        if ((string) ($info['original_transaction_id'] ?? '') !== $spec['chain']) {
            throw new \DomainException('Apple subscription chain changed for membership '.$id.'.');
        }
        $model = new Apple;
        $model->setRawAttributes($raw, true);
        try { $result = app(AdminReceiptVerifier::class)->verify($model); }
        catch (\Throwable $e) { throw new \DomainException('Apple verification failed for membership '.$id.' ('.get_class($e).').'); }
        if (isset($result['error_code']) || ($result['environment'] ?? null) !== 'Production' || ($result['status'] ?? null) !== $spec['status']) {
            throw new \DomainException('Apple could not confirm the reviewed production status for membership '.$id.'.');
        }
        return $proof + ['chain_hash' => hash('sha256', $spec['chain']), 'status' => $result['status'], 'environment' => 'Production',
            'expires_at' => isset($result['expires_at']) ? $result['expires_at']->toIso8601String() : null,
            'grace_expires_at' => isset($result['grace_expires_at']) ? $result['grace_expires_at']->toIso8601String() : null,
            'auto_renews' => $result['auto_renews'] ?? null];
    }

    private function ended(array $spec, array $proof): bool
    {
        if ($spec['type'] === 'stripe') return in_array($proof['status'], ['canceled', 'incomplete_expired'], true);
        return in_array($proof['status'], ['expired', 'revoked'], true) && $proof['auto_renews'] === false
            && $proof['expires_at'] && carbon($proof['expires_at'])->lte(now())
            && (!$proof['grace_expires_at'] || carbon($proof['grace_expires_at'])->lte(now()));
    }

    private function links(int $user, bool $lock = false): array
    {
        $query = DB::table('memberships')->where('user_id', $user)->orderBy('id');
        if ($lock) $query->lockForUpdate();
        return $query->get(['id', 'user_id', 'source_type', 'source_id', 'created_at', 'updated_at'])
            ->map(function ($row) { return (array) $row; })->all();
    }

    private function assertLinks(array $case, array $links): void
    {
        $actual = [];
        foreach ($links as $link) $actual[(int) $link['id']] = [$link['source_type'], (int) $link['source_id']];
        $expected = [];
        foreach ($case['links'] as $id => $spec) $expected[$id] = [self::TYPES[$spec['type']], $spec['source']];
        ksort($actual); ksort($expected);
        if ($actual !== $expected) throw new \DomainException('Membership set changed for user '.$case['user'].'. No automatic choice will be made.');
    }

    private function alreadyApplied(array $case, array $links, string $hash): bool
    {
        if (count($links) !== 1 || (int) $links[0]['id'] !== $case['keep'] || !Schema::hasTable('membership_link_archives')) return false;
        $kept = $case['links'][$case['keep']];
        if ($links[0]['source_type'] !== self::TYPES[$kept['type']] || (int) $links[0]['source_id'] !== $kept['source']) return false;
        foreach ($this->discardIds($case) as $id) {
            $spec = $case['links'][$id];
            if (!DB::table('membership_link_archives')->where('membership_id', $id)->where('user_id', $case['user'])
                ->where('kept_membership_id', $case['keep'])->where('plan_hash', $hash)
                ->where('source_type', self::TYPES[$spec['type']])->where('source_id', $spec['source'])->exists()) return false;
        }
        return true;
    }

    private function assertExclusiveOwner(int $user, array $spec, bool $lock = false): void
    {
        $query = DB::table('memberships')->where('source_type', self::TYPES[$spec['type']])->where('source_id', $spec['source'])->where('user_id', '!=', $user);
        if ($lock) $query->lockForUpdate();
        if ($query->first(['id'])) throw new \DomainException('A source is also linked to another account. Manual review is required.');
    }

    private function table(array $spec): string { return $spec['type'] === 'apple' ? 'apple_memberships' : 'stripe_memberships'; }
    private function discardIds(array $case): array { return array_values(array_diff(array_keys($case['links']), [$case['keep']])); }

    private function validate(array $plan): void
    {
        if (!is_string($plan['version'] ?? null) || !preg_match('/^[a-zA-Z0-9-]{1,80}$/D', $plan['version'])
            || !is_array($plan['cases'] ?? null) || !$plan['cases'] || count($plan['cases']) > 20) throw new \DomainException('Invalid reviewed repair plan.');
        $users = $ids = $sources = [];
        foreach ($plan['cases'] as $case) {
            if (!is_int($case['user'] ?? null) || $case['user'] < 1 || isset($users[$case['user']])
                || !is_int($case['keep'] ?? null) || !is_array($case['links'] ?? null) || count($case['links']) < 2
                || !isset($case['links'][$case['keep']])) throw new \DomainException('Invalid reviewed membership selection.');
            $users[$case['user']] = true;
            foreach ($case['links'] as $id => $spec) {
                if (!is_int($id) || $id < 1 || isset($ids[$id]) || !is_int($spec['source'] ?? null) || $spec['source'] < 1
                    || !isset(self::TYPES[$spec['type'] ?? '']) || !is_string($spec['status'] ?? null)) throw new \DomainException('Invalid reviewed source identity.');
                $key = $spec['type'].':'.$spec['source'];
                if (isset($sources[$key])) throw new \DomainException('The repair plan repeats a provider source.');
                $ids[$id] = $sources[$key] = true;
                if ($spec['type'] === 'stripe') {
                    if (!in_array($spec['account'] ?? null, ['us', 'swiss'], true)
                        || !is_string($spec['customer'] ?? null) || !preg_match('/^cus_[A-Za-z0-9]+$/D', $spec['customer'])
                        || !is_string($spec['subscription'] ?? null) || !preg_match('/^sub_[A-Za-z0-9]+$/D', $spec['subscription'])
                        || !in_array($spec['status'], ['active', 'trialing', 'canceled', 'incomplete_expired'], true)) throw new \DomainException('Invalid reviewed Stripe identity.');
                } elseif (!is_string($spec['chain'] ?? null) || !preg_match('/^[0-9]+$/D', $spec['chain'])
                    || !in_array($spec['status'], ['expired', 'revoked'], true)) throw new \DomainException('Invalid reviewed Apple identity.');
            }
        }
        if (!is_array($plan['preserve_missing_users'] ?? [])) throw new \DomainException('Invalid missing-account preservation policy.');
        $preserved = [];
        foreach ($plan['preserve_missing_users'] ?? [] as $id) {
            if (!is_int($id) || !isset($users[$id]) || isset($preserved[$id])) {
                throw new \DomainException('Invalid missing-account preservation policy.');
            }
            $preserved[$id] = true;
        }
    }
}
