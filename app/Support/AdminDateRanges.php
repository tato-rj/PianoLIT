<?php

namespace App\Support;

class AdminDateRanges
{
    const LABELS = [
        'today' => 'Today', 'yesterday' => 'Yesterday', '7' => 'Last 7 days', '30' => 'Last 30 days',
        'this_month' => 'This month', 'last_month' => 'Last month', 'custom' => 'Custom range',
    ];

    public static function bounds($range, $today = null)
    {
        $today = ($today ?: now())->copy()->startOfDay();
        switch ((string) $range) {
            case 'today': return [$today->copy(), $today];
            case 'yesterday': return [$today->copy()->subDay(), $today->copy()->subDay()];
            case '7': return [$today->copy()->subDays(6), $today];
            case '30': return [$today->copy()->subDays(29), $today];
            case 'this_month': return [$today->copy()->startOfMonth(), $today];
            case 'last_month': return [$today->copy()->startOfMonth()->subMonth(), $today->copy()->startOfMonth()->subDay()];
        }
        throw new \InvalidArgumentException('Unknown date preset.');
    }

    public static function presets()
    {
        $presets = [];
        foreach (self::LABELS as $key => $label) {
            if ($key === 'custom') continue;
            [$from, $to] = self::bounds($key);
            $presets[$key] = ['label' => $label, 'from' => $from->toDateString(), 'to' => $to->toDateString()];
        }
        return $presets;
    }
}
