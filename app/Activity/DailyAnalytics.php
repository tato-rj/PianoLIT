<?php

namespace App\Activity;

use Illuminate\Support\Facades\DB;

class DailyAnalytics
{
    public function latest(int $days = 6)
    {
        $days = min(365, max(0, $days));
        $counts = ExcludedAccounts::events(DB::table('activity_events'))->whereIn('platform', ['webapp', 'app'])->where('occurred_at', '>=', now()->subDays($days)->startOfDay())
            ->selectRaw('DATE(occurred_at) as date, platform, COUNT(*) as total')->groupBy('date', 'platform')->get()->groupBy('date');
        $result = [];
        for ($i = $days; $i >= 0; $i--) {
            $date = now()->subDays($i);
            $datasets = ['app' => 0, 'webapp' => 0];
            foreach ($counts->get($date->format('Y-m-d'), collect()) as $row) $datasets[$row->platform] = (int) $row->total;
            $result[] = ['label' => $date->format('D jS'), 'datasets' => $datasets];
        }
        return collect($result);
    }
}
