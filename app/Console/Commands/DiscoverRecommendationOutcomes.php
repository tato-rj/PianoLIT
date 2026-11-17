<?php

namespace App\Console\Commands;

use App\Activity\ExcludedAccounts;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class DiscoverRecommendationOutcomes extends Command
{
    protected $signature = 'discover:recommendation-outcomes {--days=28 : Number of complete exposure days, ending seven days ago}';
    protected $description = 'Show seven-day save outcomes for visible For you cards on the website';

    public function handle()
    {
        $days = filter_var($this->option('days'), FILTER_VALIDATE_INT, ['options' => ['min_range' => 1, 'max_range' => 365]]);
        if (!$days) { $this->error('Choose between 1 and 365 days.'); return 1; }
        $end = now('UTC')->startOfDay()->subDays(7);
        $start = $end->copy()->subDays($days);
        $counts = [];
        $this->line('Web exposures from '.$start->toDateString().' through '.$end->copy()->subDay()->toDateString().' (UTC).');
        $events = DB::table('activity_events')->where('platform', 'webapp')->where('resource_type', 'piece');
        (clone $events)->where('type', 'recommendation_seen')->where('source', 'browser')->whereNotNull('user_id')
            ->whereNotIn('user_id', ExcludedAccounts::ids())
            ->whereBetween('occurred_at', [$start->copy()->timezone(config('app.timezone'))->subDay(), $end->copy()->timezone(config('app.timezone'))->addDay()])
            ->where('metadata->section', 'for_you')
            ->where('occurred_at_utc', '>=', $start)->where('occurred_at_utc', '<', $end)
            ->chunkById(500, function ($seen) use ($events, &$counts) {
                $related = (clone $events)->whereIn('user_id', $seen->pluck('user_id')->unique())
                    ->whereIn('resource_id', $seen->pluck('resource_id')->unique())
                    ->whereBetween('occurred_at_utc', [$seen->min('occurred_at_utc'), Carbon::parse($seen->max('occurred_at_utc'), 'UTC')->addDays(7)->format('Y-m-d H:i:s.v')]);
                $clicks = (clone $related)->where('type', 'click')->where('source', 'browser')->where('metadata->section', 'for_you')
                    ->whereIn('page_id', $seen->pluck('page_id')->unique())->get(['user_id','resource_id','page_id','occurred_at_utc']);
                $saves = (clone $related)->where('type', 'favorite_changed')->where('source', 'server')->where('metadata->result', true)
                    ->get(['user_id', 'resource_id', 'occurred_at_utc']);
                $pieceKey = fn ($event) => $event->user_id.':'.$event->resource_id;
                $byPage = $clicks->groupBy(fn ($event) => $pieceKey($event).':'.$event->page_id);
                $byPiece = $saves->groupBy($pieceKey);
                foreach ($seen as $event) {
                    $meta = json_decode($event->metadata, true);
                    $group = ($meta['ranking_version'] ?? 'unknown').' / '.($meta['cohort'] ?? 'unknown');
                    $user = $event->user_id;
                    $counts[$group]['seen'][$user] = true;
                    $until = Carbon::parse($event->occurred_at_utc, 'UTC')->addDays(7)->format('Y-m-d H:i:s.v');
                    $key = $pieceKey($event);
                    $click = $byPage->get($key.':'.$event->page_id, collect())->whereBetween('occurred_at_utc', [$event->occurred_at_utc, $until])->min('occurred_at_utc');
                    $observedSaves = $byPiece->get($key, collect())->whereBetween('occurred_at_utc', [$event->occurred_at_utc, $until]);
                    if ($observedSaves->isNotEmpty()) $counts[$group]['saved'][$user] = true;
                    if (!$click) continue;
                    $counts[$group]['clicked'][$user] = true;
                    if ($observedSaves->where('occurred_at_utc', '>=', $click)->isNotEmpty()) {
                        $counts[$group]['clicked_and_saved'][$user] = true;
                    }
                }
            });
        if (!$counts) { $this->info('No measured recommendations in this period.'); return 0; }
        ksort($counts);
        $rows = [];
        foreach ($counts as $group => $bucket) {
            $seen = count($bucket['seen']);
            $saved = count($bucket['saved'] ?? []);
            $rows[] = [$group, $seen, count($bucket['clicked'] ?? []), $saved, count($bucket['clicked_and_saved'] ?? []), number_format(100 * $saved / $seen, 1).'%'];
        }
        $this->table(['Ranking / account history', 'Accounts shown', 'Clicked', 'Saved within 7 days', 'Saved after click', 'Save rate'], $rows);
        return 0;
    }
}
