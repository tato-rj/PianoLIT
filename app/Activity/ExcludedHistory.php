<?php

namespace App\Activity;

use Illuminate\Support\Facades\{DB, Schema};
use Illuminate\Support\Str;

class ExcludedHistory
{
    public function counts()
    {
        $ids = ExcludedAccounts::ids()->pluck('id')->all();
        $pages = DB::table('activity_pages')->where(function ($query) use ($ids) {
            $query->whereIn('user_id', $ids)->orWhereIn('visit_id', ExcludedAccounts::visitIds());
        });
        $counts = ['accounts' => count($ids), 'visits' => ExcludedAccounts::visitIds()->count(), 'pages' => (clone $pages)->count(),
            'events' => DB::table('activity_events')->where(function ($query) use ($ids, $pages) {
                $query->whereIn('user_id', $ids)->orWhereIn('visit_id', ExcludedAccounts::visitIds())->orWhereIn('page_id', $pages->select('id'));
            })->count(), 'links' => DB::table('activity_visit_links')->where(function ($query) use ($ids) {
                $query->whereIn('user_id', $ids)->orWhereIn('visit_id', ExcludedAccounts::visitIds());
            })->count()];
        foreach (['recently_viewed_pieces', 'piece_views', 'logs', 'locations', 'campaign_visits', 'quiz_results'] as $table) {
            $counts[$table] = Schema::hasTable($table) ? DB::table($table)->whereIn('user_id', $ids)->count() : 0;
        }
        $counts['campaign_subscriptions'] = Schema::hasTable('campaign_subscriptions') ? DB::table('campaign_subscriptions')
            ->whereIn('visit_id', DB::table('campaign_visits')->whereIn('user_id', $ids)->select('id'))->count() : 0;
        $counts['email_logs'] = Schema::hasTable('email_logs') ? DB::table('email_logs')->whereIn(DB::raw('LOWER(TRIM(recipient))'), ExcludedAccounts::emails())->count() : 0;
        $counts['membership_checks'] = Schema::hasTable('apple_membership_checks') ? DB::table('apple_membership_checks')->whereIn('apple_membership_id', $this->appleIds($ids))->count() : 0;
        return $counts;
    }

    public function purge()
    {
        // Bound the visit identities held in memory. Each visit and its related
        // rows disappear together; a failed batch rolls back and can be retried.
        while ($ids = ExcludedAccounts::visitIds()->limit(200)->pluck('id')->all()) $this->visits($ids);
        $users = ExcludedAccounts::ids()->pluck('id')->all();
        DB::transaction(function () use ($users) {
            DB::table('activity_events')->where(function ($query) use ($users) {
                $query->whereIn('user_id', $users)->orWhereIn('page_id', DB::table('activity_pages')->whereIn('user_id', $users)->select('id'));
            })->delete();
            DB::table('activity_pages')->whereIn('user_id', $users)->delete();
            DB::table('activity_visit_links')->whereIn('user_id', $users)->delete();
            if (Schema::hasTable('campaign_subscriptions')) DB::table('campaign_subscriptions')->whereIn('visit_id', DB::table('campaign_visits')->whereIn('user_id', $users)->select('id'))->delete();
            foreach (['recently_viewed_pieces', 'piece_views', 'logs', 'locations', 'campaign_visits', 'quiz_results'] as $table) {
                if (Schema::hasTable($table)) DB::table($table)->whereIn('user_id', $users)->delete();
            }
            if (Schema::hasTable('apple_membership_checks')) DB::table('apple_membership_checks')->whereIn('apple_membership_id', $this->appleIds($users))->delete();
            $this->emails();
        });
    }

    public function anonymous($visitor, $campaign = null)
    {
        $hasVisitor = is_string($visitor) && Str::isUuid($visitor);
        if ($hasVisitor) {
            $query = DB::table('activity_visits')->where('visitor_id', $visitor)->whereNull('user_id')
                ->whereNotIn('id', DB::table('activity_visit_links')->select('visit_id'));
            while ($ids = (clone $query)->limit(200)->pluck('id')->all()) $this->visits($ids);
        }
        DB::transaction(function () use ($visitor, $campaign, $hasVisitor) {
            if ($hasVisitor) DB::table('activity_events')->where('visitor_id', $visitor)->whereNull('user_id')->whereNull('visit_id')->delete();
            if (is_string($campaign) && Str::isUuid($campaign) && Schema::hasTable('campaign_visits')) {
                $ids = DB::table('campaign_visits')->where('id', $campaign)->whereNull('user_id')->pluck('id');
                DB::table('campaign_subscriptions')->whereIn('visit_id', $ids)->delete();
                DB::table('campaign_visits')->whereIn('id', $ids)->delete();
            }
        });
    }

    private function visits(array $ids)
    {
        DB::transaction(function () use ($ids) {
            DB::table('activity_visits')->whereIn('id', $ids)->lockForUpdate()->get(['id']);
            DB::table('activity_events')->where(function ($query) use ($ids) {
                $query->whereIn('visit_id', $ids)->orWhereIn('page_id', DB::table('activity_pages')->whereIn('visit_id', $ids)->select('id'));
            })->delete();
            DB::table('activity_pages')->whereIn('visit_id', $ids)->delete();
            DB::table('activity_visit_links')->whereIn('visit_id', $ids)->delete();
            DB::table('activity_visits')->whereIn('id', $ids)->delete();
        });
    }

    private function appleIds(array $users)
    {
        // Remove reporting evidence, preserving the actual billing source.
        return DB::table('memberships')->whereIn('user_id', $users)->where('source_type', \App\Billing\Sources\Apple::class)->select('source_id');
    }

    private function emails()
    {
        if (!Schema::hasTable('email_logs')) return;
        $logs = DB::table('email_logs')->whereIn(DB::raw('LOWER(TRIM(recipient))'), ExcludedAccounts::emails());
        $lists = (clone $logs)->whereNotNull('list_id')->distinct()->pluck('list_id');
        if (Schema::hasTable('email_campaign_reports')) {
            DB::table('email_campaign_reports')->whereIn('list_id', $lists)->lockForUpdate()->get(['id']);
        }
        $logs->delete();
        if (Schema::hasTable('email_campaign_reports')) foreach ($lists as $list) {
            // Rebuild only affected shared summaries from the remaining people's
            // rows; do not truncate reports or trust a possibly stale counter.
            $row = DB::table('email_logs')->where('list_id', $list)->selectRaw('COUNT(*) as emails_count, SUM(unique_delivered) as delivered_count, SUM(unique_failed) as failed_count, SUM(unique_opened) as opens_count, SUM(unique_clicked) as clicks_count')->first();
            $values = [];
            foreach (['emails_count', 'delivered_count', 'failed_count', 'opens_count', 'clicks_count'] as $column) $values[$column] = (int) $row->$column;
            DB::table('email_campaign_reports')->where('list_id', $list)->update($values);
        }
    }
}
