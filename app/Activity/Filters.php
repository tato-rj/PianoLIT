<?php

namespace App\Activity;

use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use App\Support\AdminDateRanges;

class Filters
{
    public $start;
    public $end;
    public $values;

    public function __construct(Request $request)
    {
        $this->values = $request->validate([
            'range' => 'nullable|string|in:'.implode(',', array_merge(array_keys(AdminDateRanges::LABELS), ['30'])),
            'from' => 'nullable|date_format:Y-m-d', 'to' => 'nullable|date_format:Y-m-d|after_or_equal:from',
            'platform' => 'nullable|in:all,web,webapp,app',
            'audience' => 'nullable|in:all,registered,anonymous',
        ]);
        $this->values = array_merge(['range' => '7', 'platform' => 'all', 'audience' => 'registered'], array_filter($this->values, function ($value) { return $value !== null; }));
        $today = now()->startOfDay();
        // Preserve saved Last 30 days links as a custom range without offering
        // the retired preset in either date selector.
        if ($this->values['range'] === '30') {
            [$from, $to] = AdminDateRanges::bounds('30', $today);
            $this->values = array_merge($this->values, ['range' => 'custom', 'from' => $from->toDateString(), 'to' => $to->toDateString()]);
        }
        switch ($this->values['range']) {
            case 'custom':
                if (empty($this->values['from']) || empty($this->values['to'])) throw ValidationException::withMessages(['from' => 'Choose both dates for a custom period.']);
                $this->start = Carbon::parse($this->values['from'])->startOfDay();
                $this->end = Carbon::parse($this->values['to'])->addDay()->startOfDay();
                break;
            default:
                [$this->start, $last] = AdminDateRanges::bounds($this->values['range'], $today);
                $this->end = $last->copy()->addDay()->startOfDay();
                if ($this->values['range'] === 'all') unset($this->values['from'], $this->values['to']);
        }
    }

    public function apply($query, $column, $platform = 'platform')
    {
        if ($this->start) $query->where($column, '>=', $this->start);
        $query->where($column, '<', $this->end);
        if ($this->values['platform'] !== 'all') $query->where($platform, $this->values['platform']);
        return $query;
    }
}
