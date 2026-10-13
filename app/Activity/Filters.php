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
            'range' => 'nullable|in:'.implode(',', array_keys(AdminDateRanges::LABELS)),
            'from' => 'nullable|date_format:Y-m-d', 'to' => 'nullable|date_format:Y-m-d|after_or_equal:from',
            'platform' => 'nullable|in:all,web,webapp,app',
            'audience' => 'nullable|in:all,registered,anonymous',
        ]);
        $this->values = array_merge(['range' => '7', 'platform' => 'all', 'audience' => 'registered'], array_filter($this->values, function ($value) { return $value !== null; }));
        $today = now()->startOfDay();
        switch ($this->values['range']) {
            case 'custom':
                if (empty($this->values['from']) || empty($this->values['to'])) throw ValidationException::withMessages(['from' => 'Choose both dates for a custom period.']);
                $this->start = Carbon::parse($this->values['from'])->startOfDay();
                $this->end = Carbon::parse($this->values['to'])->addDay()->startOfDay();
                if ($this->start->diffInDays($this->end) > 366) throw ValidationException::withMessages(['to' => 'Choose a period of at most 366 days.']);
                break;
            default:
                [$this->start, $last] = AdminDateRanges::bounds($this->values['range'], $today);
                $this->end = $last->copy()->addDay()->startOfDay();
        }
    }

    public function apply($query, $column, $platform = 'platform')
    {
        $query->where($column, '>=', $this->start)->where($column, '<', $this->end);
        if ($this->values['platform'] !== 'all') $query->where($platform, $this->values['platform']);
        return $query;
    }
}
