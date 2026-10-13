<?php

namespace App\Activity;

use Carbon\Carbon;

class Time
{
    public static function date($row, $column)
    {
        $utc = $column.'_utc';
        if (!empty($row->$utc)) return Carbon::parse($row->$utc, 'UTC')->timezone(config('app.timezone'));
        return !empty($row->$column) ? Carbon::parse($row->$column, config('app.timezone')) : null;
    }

    public static function event($row)
    {
        return self::date($row, 'occurred_at') ?: self::date($row, 'received_at');
    }

    public static function duration($milliseconds)
    {
        if ($milliseconds === null) return 'Not recorded';
        $seconds = max(0, (int) round($milliseconds / 1000));
        if ($seconds < 60) return $seconds.'s';
        if ($seconds < 3600) return intdiv($seconds, 60).'m '.($seconds % 60).'s';
        return intdiv($seconds, 3600).'h '.intdiv($seconds % 3600, 60).'m';
    }

    public static function visitDuration($visit)
    {
        $start = self::date($visit, 'started_at');
        $last = self::date($visit, 'last_activity_at');
        return $start && $last && $last->gt($start) ? self::duration($start->diffInMilliseconds($last)) : 'Duration unknown';
    }
}
